<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    public function index()
    {
        $gateways = Gateway::orderBy('priority', 'asc')->get();

        Log::info('Gateways list retrieved');

        return response()->json($gateways);
    }

    public function toggleStatus(Gateway $gateway)
    {
        $gateway->update([
            'is_active' => ! $gateway->is_active,
        ]);

        Log::info("[GATEWAY] '{$gateway->name}' status changed", [
            'id' => $gateway->id,
            'is_active' => $gateway->is_active,
        ]);

        return response()->json([
            'message' => "[GATEWAY] '{$gateway->name}' is now ".($gateway->is_active ? 'active' : 'inactive'),
            'gateway' => $gateway,
        ]);
    }

    public function updatePriority(Request $request, Gateway $gateway)
    {
        $validated = $request->validate([
            'priority' => 'required|integer|min:1',
        ]);

        $gateway->update([
            'priority' => $validated['priority'],
        ]);

        Log::info("[GATEWAY] '{$gateway->name}' priority updated", [
            'id' => $gateway->id,
            'priority' => $gateway->priority,
        ]);

        return response()->json([
            'message' => "[GATEWAY] '{$gateway->name}' priority updated",
            'gateway' => $gateway,
        ]);
    }
}
