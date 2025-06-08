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
    public function show(Request $request, $identifier)
    {
        $account = null;
        if ($request->identifier_type === 'id') {
            $account = BankAccount::where('user_id', Auth::id())->findOrFail($identifier);
        } else {
            $account = BankAccount::where('user_id', Auth::id())
                ->where('account_number', $identifier)
                ->firstOrFail();
        }
        return response()->json($account);
    }

    public function balance(Request $request, $identifier)
    {
        $account = null;
        if ($request->identifier_type === 'id') {
            $account = BankAccount::where('user_id', Auth::id())->findOrFail($identifier);
        } else {
            $account = BankAccount::where('user_id', Auth::id())
                ->where('account_number', $identifier)
                ->firstOrFail();
        }
        return response()->json([
            'balance' => $account->balance,
            'currency' => 'GBP',
        ]);
    }

}
