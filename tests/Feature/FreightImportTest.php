<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FreightTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FreightImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_import_freight_csv_successfully(): void
    {
        $csvContent = "min_weight,max_weight,price\n";
        $csvContent .= "0.1,1.0,10.50\n";
        $csvContent .= "1.1,5.0,25.75\n";

        $file = UploadedFile::fake()->createWithContent('freight_table.csv', $csvContent);

        $response = $this->postJson('/api/freight/import', [
            'csv_file' => $file,
            'client_name' => 'Cliente Teste',
            'client_document' => '12345678901234',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => 'Importação iniciada com sucesso',
            ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Teste',
            'document' => '12345678901234',
        ]);

        $this->assertDatabaseHas('freight_tables', [
            'file_name' => 'freight_table.csv',
            'status' => 'pending',
        ]);
    }

    public function test_import_with_invalid_file(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->postJson('/api/freight/import', [
            'csv_file' => $file,
            'client_name' => 'Cliente Teste',
            'client_document' => '12345678901234',
        ]);

        $response->assertStatus(422);
    }

    public function test_import_without_required_fields(): void
    {
        $file = UploadedFile::fake()->createWithContent('test.csv', 'min_weight,max_weight,price');

        $response = $this->postJson('/api/freight/import', [
            'csv_file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Dados de entrada inválidos',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'details' => [
                    'client_name',
                    'client_document'
                ]
            ]);
    }

    public function test_get_import_status(): void
    {
        $client = Client::factory()->create();
        $freightTable = FreightTable::factory()->create([
            'client_id' => $client->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/freight/import/{$freightTable->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $freightTable->id,
                    'status' => 'completed',
                ],
            ]);
    }

    public function test_get_status_of_nonexistent_import(): void
    {
        $response = $this->getJson('/api/freight/import/999/status');

        $response->assertStatus(404);
    }

    public function test_list_imports(): void
    {
        $client = Client::factory()->create();
        FreightTable::factory()->count(3)->create(['client_id' => $client->id]);

        $response = $this->getJson('/api/freight/imports');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_imports_filtered_by_client(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        FreightTable::factory()->count(2)->create(['client_id' => $client1->id]);
        FreightTable::factory()->count(1)->create(['client_id' => $client2->id]);

        $response = $this->getJson("/api/freight/imports?client_id={$client1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_health_check(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'environment',
            ]);
    }

    public function test_import_csv_with_quotes_and_commas(): void
    {
        $client = Client::factory()->create();

        $csvContent = <<<CSV
min_weight,max_weight,price
"0","1,5","5,25"
"1,5","3,0","8,50"
"3,0","5,0","12,75"
CSV;

        $file = UploadedFile::fake()->createWithContent('test_quotes.csv', $csvContent);

        $response = $this->postJson('/api/freight/import', [
            'csv_file' => $file,
            'client_name' => $client->name,
            'client_document' => $client->document,
        ]);

        $response->assertStatus(202);
        $data = $response->json();

        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Importação iniciada', $data['message']);

        $this->assertDatabaseHas('freight_tables', [
            'client_id' => $client->id,
            'file_name' => 'test_quotes.csv'
        ]);
    }

    public function test_import_csv_with_alternative_columns(): void
    {
        $client = Client::factory()->create();

        $csvContent = <<<CSV
from_weight,to_weight,cost
0,1.5,5.25
1.5,3.0,8.50
CSV;

        $file = UploadedFile::fake()->createWithContent('test_alternative.csv', $csvContent);

        $response = $this->postJson('/api/freight/import', [
            'csv_file' => $file,
            'client_name' => $client->name,
            'client_document' => $client->document,
        ]);

        $response->assertStatus(202);
        $data = $response->json();

        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Importação iniciada', $data['message']);
    }
}
