<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FreightImportRequest;
use App\Jobs\ProcessFreightImportJob;
use App\Models\Client;
use App\Models\FreightTable;
use App\Services\FreightImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FreightImportController extends Controller
{
    protected $freightImportService;
    public function __construct(
        FreightImportService $freightImportService
    ) {
        $this->freightImportService = $freightImportService;
    }

    /**
     * Inicia importação de CSV de frete
     */
    public function import(FreightImportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $client = Client::firstOrCreate(
                ['document' => $validated['client_document']],
                ['name' => $validated['client_name']]
            );

            $freightTable = $this->freightImportService->createFreightTable($client, $request->file('csv_file'));

            $uploadedFile = $request->file('csv_file');
            $fileName = $freightTable->id . '_' . $uploadedFile->getClientOriginalName();
            $filePath = $uploadedFile->storeAs('imports', $fileName, 'local');

            ProcessFreightImportJob::dispatch($freightTable, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'Importação iniciada com sucesso',
                'data' => [
                    'freight_table_id' => $freightTable->id,
                    'client_id' => $client->id,
                    'status' => $freightTable->status,
                ]
            ], 202);
        } catch (\Exception $e) {
            Log::error('Erro na importação de frete', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }

    /**
     * Verifica status da importação
     */
    public function status(int $freightTableId): JsonResponse
    {
        try {
            $freightTable = FreightTable::with(['client', 'importLogs'])
                ->findOrFail($freightTableId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $freightTable->id,
                    'client' => [
                        'id' => $freightTable->client->id,
                        'name' => $freightTable->client->name,
                        'document' => $freightTable->client->document,
                    ],
                    'file_name' => $freightTable->file_name,
                    'version' => $freightTable->version,
                    'status' => $freightTable->status,
                    'total_rows' => $freightTable->total_rows,
                    'total_errors' => $freightTable->total_errors,
                    'started_at' => $freightTable->started_at,
                    'finished_at' => $freightTable->finished_at,
                    'progress_percentage' => $this->calculateProgress($freightTable),
                    'errors' => $freightTable->importLogs->map(fn($log) => [
                        'row' => $log->row_number,
                        'message' => $log->message,
                    ]),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de frete não encontrada'
            ], 404);
        }
    }

    /**
     * Lista importações do cliente
     */
    public function list(Request $request): JsonResponse
    {
        $query = FreightTable::with('client');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $freightTables = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $freightTables->items(),
            'pagination' => [
                'current_page' => $freightTables->currentPage(),
                'per_page' => $freightTables->perPage(),
                'total' => $freightTables->total(),
                'last_page' => $freightTables->lastPage(),
            ]
        ]);
    }

    /**
     * Calcula progresso da importação
     */
    private function calculateProgress(FreightTable $freightTable): float
    {
        if ($freightTable->status === 'completed' || $freightTable->status === 'failed') {
            return 100.0;
        }

        if ($freightTable->status === 'pending') {
            return 0.0;
        }

        $processedRows = $freightTable->freightRates()->count();

        if ($freightTable->total_rows > 0) {
            return min(100.0, ($processedRows / $freightTable->total_rows) * 100);
        }

        return 50.0;
    }
}
