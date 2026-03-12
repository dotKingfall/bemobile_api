<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@admin.com',
        ]);

        User::factory()->manager()->create([
            'name'  => 'Manager User',
            'email' => 'manager@manager.com',
        ]);

        User::factory()->finance()->create([
            'name'  => 'Finance User',
            'email' => 'finance@finance.com',
        ]);

        User::factory()->create([
            'name'  => 'Standard User',
            'email' => 'user@user.com',
        ]);

        $gateways = [
            ['name' => config('gateways.gateway_1.name'), 'priority' => 1, 'is_active' => true],
            ['name' => config('gateways.gateway_2.name'), 'priority' => 2, 'is_active' => true],
        ];

        foreach ($gateways as $gw) {
            Gateway::updateOrCreate(['name' => $gw['name']], $gw);
        }
        $availableGateways = Gateway::all();

        Client::factory(13)->create();
        Product::factory(200)->create();

        //CREATE SOME MOCK TRANSACTIONS USING THE OTHER FACTORIES' DATA
        Transaction::factory(50)->create()->each(function ($transaction) use ($availableGateways){
            $transaction->gateway_id = $availableGateways->random()->id;
            $transaction->save();

            TransactionProduct::create([
                'transaction_id' => $transaction->id,
                'product_id'     => $transaction->product_id,
                'quantity'       => $transaction->quantity,
            ]);
        });
    }
}
