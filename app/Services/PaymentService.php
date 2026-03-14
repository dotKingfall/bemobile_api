<?php

namespace App\Services;

use App\Models\Gateway;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function processPurchase(Request $request, $gateways, $totalAmount)
    {
        foreach ($gateways as $gateway) {
            try {
                // *IF THIS WAS A REAL USE SCENARIO I'D PROBABLY BE USING DTO HERE OR SOMETHING TO MATCH THE DIFFERENT GATEWAY ARGUMENTS

                $client = Http::timeout(5)->connectTimeout(2);
                $response = null;

                if ($gateway->name === config('gateways.gateway_1.name')) {
                    Log::info('Attempting payment through Gateway 1');

                    $payload = [
                        'amount' => $totalAmount,
                        'name' => $request->name,
                        'email' => $request->email,
                        'cardNumber' => $request->cardNumber,
                        'cvv' => $request->cvv,
                    ];

                    $loginResponse = Http::post('http://localhost:3001/login', [
                        'email' => config('gateways.gateway_1.email'),
                        'token' => config('gateways.gateway_1.token'),
                    ]);

                    $response = $client->withToken($loginResponse->json('token'))
                        ->post(config('gateways.gateway_1.url'), $payload);

                } elseif ($gateway->name === config('gateways.gateway_2.name')) {
                    Log::info('Attempting payment through Gateway 2');

                    $payload = [
                        'valor' => $totalAmount,
                        'nome' => $request->name,
                        'email' => $request->email,
                        'numeroCartao' => $request->cardNumber,
                        'cvv' => $request->cvv,
                    ];

                    $response = $client->withHeaders([
                        'Gateway-Auth-Token' => config('gateways.gateway_2.token'),
                        'Gateway-Auth-Secret' => config('gateways.gateway_2.secret'),
                    ])->post(config('gateways.gateway_2.url'), $payload);
                }

                if ($response && $response->successful()) {
                    return [
                        'gateway' => $gateway,
                        'external_id' => $response->json('id'),
                    ];
                }

                Log::warning("[GATEWAY] {$gateway->name} returned status: ".($response ? $response->status() : 'Unknown'));
            } catch (ConnectionException $e) {
                Log::warning("TIMEOUT: [GATEWAY] '{$gateway->name}' timed out", [
                    'gateway' => $gateway->name,
                    'priority' => $gateway->priority,
                    'error' => $e->getMessage(),
                ]);

                continue;

            } catch (Exception $e) {
                Log::warning('Gateway Failed', [
                    'gateway' => $gateway->name,
                    'priority' => $gateway->priority,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return null; // ALL GATEWAYS FAILED
    }

    public function processRefund($transaction)
    {
        $gateway = $transaction->gateway;
        $client = Http::timeout(5)->connectTimeout(2);

        if ($gateway->name === config('gateways.gateway_1.name')) {
            $url = config('gateways.gateway_1.url')."/{$transaction->external_id}/charge_back";

            return $client->withToken(config('gateways.gateway_1.token'))->post($url);

        } elseif ($gateway->name === config('gateways.gateway_2.name')) {
            $url = config('gateways.gateway_2.url').'/reembolso';

            return $client->withHeaders([
                'Gateway-Auth-Token' => config('gateways.gateway_2.token'),
                'Gateway-Auth-Secret' => config('gateways.gateway_2.secret'),
            ])->post($url, ['id' => $transaction->external_id]);
        }
    }
}
