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
        //CREATE ALL ROLES FOR TESTING PURPOSES
        User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@admin.com']);
        User::factory()->manager()->create(['name' => 'Manager User', 'email' => 'manager@manager.com']);
        User::factory()->finance()->create(['name' => 'Finance User', 'email' => 'finance@finance.com']);
        User::factory()->create(['name' => 'Standard User', 'email' => 'user@user.com']);

        Gateway::factory()->gateway1()->create();
        Gateway::factory()->gateway2()->create();

        Client::factory(13)->create();
        Product::factory(200)->create();

        $gateways = Gateway::all();


        Transaction::factory(50)->make()->each(function ($transaction) use ($gateways) {
            $transaction->gateway_id = $gateways->random()->id;
            $transaction->save();

            // Create Pivot Record
            TransactionProduct::create([
                'transaction_id' => $transaction->id,
                'product_id'     => $transaction->product_id,
                'quantity'       => $transaction->quantity,
            ]);
        });
    }
}
