<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{

    // Create a new bank account with initial deposit
    public function store(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
            // Force JSON for API requests or if Accept: application/json
        

        $request->validate([
            'initial_deposit' => 'required|numeric|min:0',
        ]);

        $account = BankAccount::create([
            'user_id' => Auth::id(),
            'account_number' => 'ACC-' . uniqid(), // Generate a unique account number
            'balance' => $request->initial_deposit,
        ]);

        return response()->json([
            'message' => 'Bank account created successfully',
            'account' => $account,
        ], 201);
    }

    // List all accounts for the authenticated user
    public function index()
    {
        $accounts = BankAccount::where('user_id', Auth::id())->get();

        return response()->json($accounts);
    }

    // (Optional) Show a single account
    public function show($id)
    {
        $account = BankAccount::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($account);
    }

    public function balance($id)
    {
        $account = BankAccount::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found or unauthorized'], 404);
        }

        return response()->json([
            'account_id' => $account->id,
            'balance' => $account->balance + 5000, // Adding 5000 to the balance for demonstration
            'currency' => 'USD', // Assuming USD for simplicity
            'account_number' => $account->account_number,
        ]);
    }

}
