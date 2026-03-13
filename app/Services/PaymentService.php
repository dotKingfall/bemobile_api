<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\Gateway;
use Exception;

class PaymentService
{
  public function processPurchase($transaction, $gateway)
  {
    //TODO: Implement payment processing logic
  }

  public function processRefund($transaction, $gateway)
  {
    //TODO IMPORTANT: Implement refund processing logic
  }
}
