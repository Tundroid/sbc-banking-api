<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'from_account' => 'required|exists:bank_accounts,id',
            'to_account' => 'required|exists:bank_accounts,id|different:from_account',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $fromAccount = BankAccount::where('id', $request->from_account)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $toAccount = BankAccount::findOrFail($request->to_account);


        if ($fromAccount->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient funds'], 400);
        }

        DB::transaction(function () use ($fromAccount, $toAccount, $request) {
            $fromAccount->decrement('balance', $request->amount);
            $toAccount->increment('balance', $request->amount);

            Transfer::create([
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $request->amount,
            ]);
        });

        return response()->json(['message' => 'Transfer successful'], 201);
    }

    public function history($accountId)
    {
        $account = BankAccount::where('id', $accountId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $transfers = Transfer::where('from_account_id', $account->id)
            ->orWhere('to_account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transfers);
    }
}
