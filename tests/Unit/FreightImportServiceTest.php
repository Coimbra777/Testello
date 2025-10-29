<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\FreightTable;
use App\Services\FreightImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FreightImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FreightImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FreightImportService();
    }

    public function test_create_freight_table(): void
    {
        $client = Client::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            'min_weight,max_weight,price'
        );

        $freightTable = $this->service->createFreightTable($client, $file);

        $this->assertDatabaseHas('freight_tables', [
            'id' => $freightTable->id,
            'client_id' => $client->id,
            'file_name' => 'test.csv',
            'version' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_create_freight_table_increments_version(): void
    {
        $client = Client::factory()->create();

        FreightTable::factory()->create([
            'client_id' => $client->id,
            'version' => 1,
        ]);

        FreightTable::factory()->create([
            'client_id' => $client->id,
            'version' => 2,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            'min_weight,max_weight,price'
        );

        $freightTable = $this->service->createFreightTable($client, $file);

        $this->assertEquals(3, $freightTable->version);
    }

    public function test_parse_decimal(): void
    {
        $this->assertEquals(12.50, $this->service->parseDecimal('12,50'));
        $this->assertEquals(0.25, $this->service->parseDecimal('0,25'));

        $this->assertEquals(12.50, $this->service->parseDecimal('12.50'));

        $this->assertEquals(0.00, $this->service->parseDecimal('0'));
        $this->assertEquals(0.00, $this->service->parseDecimal('0,00'));

        $this->assertEquals(5.2, $this->service->parseDecimal('"5,2"'));
        $this->assertEquals(0.25, $this->service->parseDecimal('"0,25"'));
        $this->assertEquals(10.0, $this->service->parseDecimal("'10,0'"));
    }

    public function test_map_record_columns(): void
    {
        $standardRecord = [
            'min_weight' => '0',
            'max_weight' => '1.0',
            'price' => '10.50'
        ];

        $reflection = new \ReflectionClass($this->service);
        $mapMethod = $reflection->getMethod('mapRecordColumns');
        $mapMethod->setAccessible(true);

        $result = $mapMethod->invoke($this->service, $standardRecord);
        $this->assertEquals($standardRecord, $result);

        $alternateRecord = [
            'from_weight' => '0',
            'to_weight' => '1.0',
            'cost' => '10.50'
        ];

        $result = $mapMethod->invoke($this->service, $alternateRecord);
        $expected = [
            'min_weight' => '0',
            'max_weight' => '1.0',
            'price' => '10.50'
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_validate_and_clean_record(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $validateMethod = $reflection->getMethod('validateAndCleanRecord');
        $validateMethod->setAccessible(true);

        $record = [
            'min_weight' => '0',
            'max_weight' => '1,5',
            'price' => '10,50'
        ];

        $result = $validateMethod->invoke($this->service, $record);
        $expected = [
            'min_weight' => 0.0,
            'max_weight' => 1.5,
            'price' => 10.5
        ];
        $this->assertEquals($expected, $result);

        $alternateRecord = [
            'from_weight' => '0',
            'to_weight' => '"0,25"',
            'cost' => '"5,2"'
        ];

        $result = $validateMethod->invoke($this->service, $alternateRecord);
        $expected = [
            'min_weight' => 0.0,
            'max_weight' => 0.25,
            'price' => 5.2
        ];
        $this->assertEquals($expected, $result);
    }
}
