<?php

namespace App\Services;

use App\Models\Client;
use App\Models\FreightTable;
use App\Models\FreightRate;
use App\Models\FreightImportLog;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;
use League\Csv\Statement;

class FreightImportService
{
    private const BATCH_SIZE = 2000;

    /**
     * Cria registro da FreightTable
     */
    public function createFreightTable(Client $client, UploadedFile $file): FreightTable
    {
        $nextVersion = FreightTable::where('client_id', $client->id)->max('version') + 1;

        return FreightTable::create([
            'client_id' => $client->id,
            'file_name' => $file->getClientOriginalName(),
            'version' => $nextVersion,
            'status' => 'pending',
        ]);
    }

    /**
     * Processa arquivo CSV de forma otimizada
     */
    public function processImport(FreightTable $freightTable, string $filePath): void
    {
        try {
            $freightTable->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            // Processar em uma única passada
            $result = $this->processRecordsInBatches($csv, $freightTable);

            $freightTable->update([
                'status' => 'completed',
                'total_errors' => $result['errorCount'],
                'total_rows' => $result['totalRows'],
                'progress_percentage' => 100,
                'finished_at' => now(),
            ]);
        } catch (\Exception $e) {
            $freightTable->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);
            $this->logError($freightTable, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida e limpa registro
     */
    private function validateAndCleanRecord(array $record): array
    {
        $mappedRecord = $this->mapRecordColumns($record);

        $minWeight = $this->parseDecimal($mappedRecord['min_weight'] ?? '');
        $maxWeight = $this->parseDecimal($mappedRecord['max_weight'] ?? '');
        $price = $this->parseDecimal($mappedRecord['price'] ?? '');

        if ($minWeight < 0 || $maxWeight < 0 || $price < 0) {
            throw new \Exception('Valores negativos não são permitidos');
        }

        if ($maxWeight <= $minWeight) {
            throw new \Exception('Peso máximo deve ser maior que peso mínimo');
        }

        return [
            'min_weight' => $minWeight,
            'max_weight' => $maxWeight,
            'price' => $price,
        ];
    }

    /**
     * Mapeia colunas do registro baseado no formato detectado
     */
    private function mapRecordColumns(array $record): array
    {
        if (isset($record['min_weight']) && isset($record['max_weight']) && isset($record['price'])) {
            return $record;
        }

        if (isset($record['from_weight']) && isset($record['to_weight']) && isset($record['cost'])) {
            return [
                'min_weight' => $record['from_weight'],
                'max_weight' => $record['to_weight'],
                'price' => $record['cost']
            ];
        }

        return $record;
    }

    /**
     * Converte decimal brasileiro ou internacional
     */
    public function parseDecimal(string $value): float
    {
        if (trim($value) === '' || trim($value) === '0') {
            return 0.0;
        }

        $cleaned = trim($value, '"\'');

        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }

        $result = floatval($cleaned);

        if ($result < 0) {
            throw new \Exception("Valor inválido: {$value}");
        }

        return $result;
    }

    /**
     * Log de erro
     */
    private function logError(FreightTable $freightTable, ?int $row, string $message): void
    {
        FreightImportLog::create([
            'freight_table_id' => $freightTable->id,
            'row_number' => $row,
            'message' => $message,
        ]);
    }

    /**
     * Processa registros em batches otimizados
     */
    private function processRecordsInBatches($csv, FreightTable $freightTable): array
    {
        $records = Statement::create()->process($csv);
        $batch = [];
        $errorCount = 0;
        $totalRows = 0;
        $processedCount = 0;
        $timestamps = now();

        foreach ($records as $offset => $record) {
            $totalRows++;

            try {
                $cleanRecord = $this->validateAndCleanRecord($record);

                $batch[] = array_merge($cleanRecord, [
                    'freight_table_id' => $freightTable->id,
                    'created_at' => $timestamps,
                    'updated_at' => $timestamps,
                ]);

                if (count($batch) >= self::BATCH_SIZE) {
                    // Inserção em batch otimizada
                    FreightRate::insert($batch);
                    $batch = [];
                    $processedCount += self::BATCH_SIZE;

                    // Atualiza progresso apenas a cada 10k registros para reduzir I/O
                    if ($processedCount % 10000 === 0 || $processedCount >= $totalRows) {
                        $progress = $totalRows > 0 ? round(($processedCount / $totalRows) * 100, 2) : 0;
                        $freightTable->update([
                            'progress_percentage' => $progress,
                            'total_rows' => $totalRows
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                if ($errorCount <= 100) {
                    $this->logError($freightTable, $offset + 2, $e->getMessage());
                }
            }
        }

        // Inserção do último lote
        if (!empty($batch)) {
            FreightRate::insert($batch);
            $processedCount += count($batch);
        }

        // Atualização final
        $freightTable->update([
            'total_rows' => $totalRows,
            'progress_percentage' => 100
        ]);

        return [
            'totalRows' => $totalRows,
            'errorCount' => $errorCount,
            'processedCount' => $processedCount
        ];
    }
}
