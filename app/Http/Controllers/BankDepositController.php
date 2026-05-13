<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Logger;
use App\Http\Requests\StoreBankDepositRequest;
use App\Traits\NormalizesData;

class BankDepositController extends Controller
{
    use NormalizesData;
    public function index(Request $request)
    {
        $query = BankDeposit::with('creator')->where('created_by', auth()->id());
        
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('enrollment_number', 'LIKE', "%{$search}%")
                  ->orWhere('nic', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('deposit_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('deposit_date', '<=', $request->to_date);
        }

        if ($request->wantsJson()) {
            return response()->json($query->latest()->paginate(20));
        }

        return Inertia::render('Registry/BankDeposits', [
            'deposits' => $query->latest()->paginate(20),
            'filters' => $request->only(['search', 'from_date', 'to_date'])
        ]);
    }

    public function store(StoreBankDepositRequest $request)
    {
        $validated = $request->validated();

        // Normalize data before saving
        $validated['nic'] = $this->normalizeNationalId($validated['nic']);
        $validated['mobile'] = $this->normalizePhoneNumber($validated['mobile']);

        $path = null;
        if ($request->hasFile('slip')) {
            $path = $request->file('slip')->store('slips', 'public');
            $validated['slip_path'] = $path;
        }

        $validated['created_by'] = auth()->id();

        try {
            $deposit = BankDeposit::create($validated);

            Logger::log('BANK_DEPOSIT_CREATE', "Recorded deposit for {$deposit->customer_name}", 'BANK_DEPOSIT', "ID: {$deposit->id}", [
                'enrollment_number' => $deposit->enrollment_number,
                'amount' => $deposit->amount
            ]);

            return response()->json([
                'message' => 'Bank deposit recorded successfully',
                'deposit' => $deposit
            ], 201);
        } catch (\Exception $e) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    public function all(Request $request)
    {
        // Only allow Decision Maker and Admin to see all records
        if (!in_array(auth()->user()->access_level, ['admin', 'decision maker'])) {
            return abort(403);
        }

        $query = BankDeposit::with('creator');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('enrollment_number', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('deposit_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('deposit_date', '<=', $request->to_date);
        }

        if ($request->wantsJson()) {
            return response()->json($query->latest()->paginate(15));
        }

        return Inertia::render('Registry/Dashboard', [
            'deposits' => $query->latest()->paginate(15)
        ]);
    }

    public function export(Request $request)
    {
        // Only allow Decision Maker and Admin
        if (!in_array(auth()->user()->access_level, ['admin', 'decision maker'])) {
            return abort(403);
        }

        $filters = $request->only(['search', 'from_date', 'to_date']);

        Logger::log('EXPORT_EXCEL', 'Bank deposits export generated', 'BANK_DEPOSIT', null, [
            'filters' => $filters
        ]);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BankDepositsExport($filters), 
            'Bank_Deposits_' . now()->format('Ymd_Hi') . '.xlsx'
        );
    }
}
