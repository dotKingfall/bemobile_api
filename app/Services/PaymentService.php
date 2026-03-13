<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Transaction;
use App\Models\Gateway;
use Exception;

class PaymentService
{
  public function processPurchase(Request $request, $gateways, $totalAmount)
  {
    foreach($gateways as $gateway){
      try {
        //*IF THIS WAS A REAL USE SCENARIO I'D PROBABLY BE USING DTO HERE OR SOMETHING TO MATCH THE DIFFERENT GATEWAY ARGUMENTS

        $client = Http::timeout(5)->connectTimeout(2);
        $response = null;

        if ($gateway->name === config('gateways.gateway_1.name')) {
          Log::info("Attempting payment through Gateway 1");

          $payload = [
            'amount' => $totalAmount,
            'name'  => $request->name,
            'email' => $request->email,
            'cardNumber' => $request->cardNumber,
            'cvv' => $request->cvv,
          ];

          $response = $client->withToken(config('gateways.gateway_1.token'))
            ->post(config('gateways.gateway_1.url'), $payload);

        } elseif ($gateway->name === config('gateways.gateway_2.name')) {
          Log::info("Attempting payment through Gateway 2");

          $payload = [
            'valor' => $totalAmount,
            'nome'  => $request->name,
            'email' => $request->email,
            'numeroCartao' => $request->cardNumber,
            'cvv' => $request->cvv,
          ];

          $response = $client->withHeaders([
            'Gateway-Auth-Token' => config('gateways.gateway_2.token'),
            'Gateway-Auth-Secret' => config('gateways.gateway_2.secret')
          ])->post(config('gateways.gateway_2.url'), $payload);
        }

        if ($response && $response->successful()) {
          return [
            'gateway'     => $gateway,
            'external_id' => $response->json('id')
          ];
        }

        Log::warning("[GATEWAY] {$gateway->name} returned status: " . ($response ? $response->status() : 'Unknown'));
      } catch (ConnectionException $e) {
        Log::warning("TIMEOUT: [GATEWAY] '{$gateway->name}' timed out", [
          'gateway' => $gateway->name,
          'priority' => $gateway->priority,
          'error' => $e->getMessage()
        ]);
        continue;

      } catch (Exception $e) {
        Log::warning("Gateway Failed", [
          'gateway' => $gateway->name,
          'priority' => $gateway->priority,
          'error' => $e->getMessage()
        ]);
        continue;
      }
    }

    return null; //ALL GATEWAYS FAILED
  }

  public function processRefund($transaction)
  {
    //TODO IMPORTANT: Implement refund processing logic
  }
}
