<?php

namespace App\Jobs;

use App\Models\FreightTable;
use App\Services\FreightImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFreightImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;
    public FreightTable $freightTable;
    public string $filePath;

    public function __construct(
        FreightTable $freightTable,
        string $filePath
    ) {
        $this->freightTable = $freightTable;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(FreightImportService $freightImportService): void
    {
        try {
            Log::info('Iniciando processamento de importação de frete', [
                'freight_table_id' => $this->freightTable->id,
                'file_path' => $this->filePath,
            ]);

            $freightImportService->processImport(
                $this->freightTable,
                $this->getStoragePath()
            );

            // Log::info('Processamento de importação concluído com sucesso', [
            //     'freight_table_id' => $this->freightTable->id,
            // ]);
        } catch (\Exception $e) {
            Log::error('Erro no processamento de importação de frete', [
                'freight_table_id' => $this->freightTable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->freightTable->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de importação de frete falhou definitivamente', [
            'freight_table_id' => $this->freightTable->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->freightTable->update([
            'status' => 'failed',
            'finished_at' => now(),
        ]);
    }

    /**
     * Obtem caminho completo do arquivo no storage
     */
    private function getStoragePath(): string
    {
        return Storage::disk('local')->path($this->filePath);
    }

    /**
     * Calcula atraso entre tentativas (backoff exponencial)
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
