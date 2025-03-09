<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IWithdrawalRepository;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    protected $withdrawalRepository;

    public function __construct(IWithdrawalRepository $withdrawalRepository)
    {
        $this->withdrawalRepository = $withdrawalRepository;
    }

    public function create(Request $request)
    {
        $userId = auth()->id();
        $validateWithdrawal = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|in:NGN,USD,EUR,GBP',
            'method' => 'required|string|in:bank,paypal,crypto', // nigga you ain't flying without this methods
        ]);
    
        if ($validateWithdrawal->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validateWithdrawal->errors(),
            ], 422);
        }
    
        $data = $validateWithdrawal->validated();
        
        // yu don't wanna our system yeah?
        $userBalance = Wallet::find($userId);
        if ($userBalance->balance < $data['amount']) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance',
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            $withdrawal = $this->withdrawalRepository->create($data);
            $userBalance->balance -= $data['amount'];
            $userBalance->save();

            $fundsRecord = FundsRecord::create([
                'user_id' => $userId,
                'pending' => $data['amount'],
                'currency' => $data['currency'],
                'type' => 'withdrawal',	
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Withdrawal created successfully',
                'data' => $withdrawal,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to process withdrawal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
