<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repository\PaystackRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaystackController extends Controller
{
    protected $paystack;

    public function __construct(PaystackRepository $paystack)
    {
        $this->paystack = $paystack;
    }

    public function banks(Request $request)
    {
        $country = $request->query('country', 'nigeria');

        // Normalize common inputs to Paystack-accepted country values
        $c = strtolower(trim($country));
        $map = [
            'ng' => 'nigeria', 'ngn' => 'nigeria', 'nigeria' => 'nigeria',
            'ghana' => 'ghana', 'gh' => 'ghana',
            'kenya' => 'kenya', 'ke' => 'kenya',
            'south africa' => 'south africa', 'south_africa' => 'south africa', 'south-africa' => 'south africa', 'za' => 'south africa', 'southafrica' => 'south africa',
        ];

        $normalized = $map[$c] ?? $c;

        $cacheKey = 'paystack_banks_' . str_replace(' ', '_', $normalized);

        $banks = Cache::remember($cacheKey, now()->addHours(24), function () use ($normalized) {
            return $this->paystack->listBanks($normalized);
        });

        if (!($banks['status'] ?? false)) {
            return response()->json(['status' => false, 'message' => $banks['message'] ?? 'Failed to fetch banks'], 500);
        }

        // create mapping code => name for frontend convenience
        $mapping = [];
        foreach ($banks['data'] as $b) {
            $mapping[$b['code']] = $b['name'];
        }

        return response()->json(['status' => true, 'data' => $banks['data'], 'mapping' => $mapping]);
    }

    /**
     * Resolve an account number and bank code to an account name via Paystack
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $accountNumber = $request->input('account_number');
        $bankCode = $request->input('bank_code');

        $resolved = $this->paystack->resolveAccount($accountNumber, $bankCode);

        if (!($resolved['status'] ?? false)) {
            return response()->json(['status' => false, 'message' => $resolved['message'] ?? 'Failed to resolve account', 'data' => $resolved['data'] ?? null], 400);
        }

        return response()->json(['status' => true, 'data' => $resolved['data'] ?? null]);
    }
}
