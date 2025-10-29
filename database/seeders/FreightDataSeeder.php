<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\FreightRate;
use App\Models\FreightTable;
use Illuminate\Database\Seeder;

class FreightDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            ['name' => 'Empresa ABC Transportes Ltda', 'document' => '12345678000195'],
            ['name' => 'XYZ Logística S.A.', 'document' => '98765432000176'],
            ['name' => 'FastDelivery Express', 'document' => '11122233000144'],
        ];

        foreach ($clients as $clientData) {
            $client = Client::factory()->create($clientData);

            $tableCount = rand(1, 2);

            for ($v = 1; $v <= $tableCount; $v++) {
                $freightTable = FreightTable::factory()->create([
                    'client_id' => $client->id,
                    'version' => $v,
                    'status' => 'completed',
                    'total_rows' => rand(50, 200),
                    'total_errors' => rand(0, 5),
                    'started_at' => now()->subDays(rand(1, 30)),
                    'finished_at' => now()->subDays(rand(0, 29)),
                ]);

                $this->createFreightRates($freightTable);
            }
        }

        $this->command->info('Dados de teste criados com sucesso!');
    }

    private function createFreightRates(FreightTable $freightTable): void
    {
        $rates = [];
        $rateCount = rand(10, 30);

        for ($i = 0; $i < $rateCount; $i++) {
            $minWeight = rand(1, 100) / 10;
            $maxWeight = $minWeight + rand(5, 50);
            $price = rand(500, 15000) / 100;

            $rates[] = [
                'freight_table_id' => $freightTable->id,
                'min_weight' => $minWeight,
                'max_weight' => $maxWeight,
                'price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        FreightRate::insert($rates);
    }
}
