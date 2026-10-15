<?php

namespace App\Http\Controllers;

use App\Models\DonationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    /**
     * Display the donation / support page.
     */
    public function index()
    {
        $this->ensureTableExists();

        $recentSupporters = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('donation_records')) {
                $recentSupporters = DonationRecord::with('user')
                    ->where('status', 'verified')
                    ->latest()
                    ->take(6)
                    ->get();
            }
        } catch (\Throwable $e) {
            $recentSupporters = collect();
        }

        return view('donate', compact('recentSupporters'));
    }

    /**
     * Ensure the donation_records table and columns exist in production databases.
     */
    protected function ensureTableExists(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('donation_records')) {
                \Illuminate\Support\Facades\Schema::create('donation_records', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                    $table->string('donor_name')->nullable();
                    $table->boolean('is_anonymous')->default(false);
                    $table->string('donor_email')->nullable();
                    $table->decimal('amount', 10, 2)->nullable();
                    $table->string('currency', 10)->default('USD');
                    $table->string('payment_method')->nullable();
                    $table->string('transaction_reference')->nullable();
                    $table->text('message')->nullable();
                    $table->enum('status', ['pending', 'verified', 'acknowledged'])->default('pending');
                    $table->timestamps();
                });
            } elseif (!\Illuminate\Support\Facades\Schema::hasColumn('donation_records', 'is_anonymous')) {
                \Illuminate\Support\Facades\Schema::table('donation_records', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('is_anonymous')->default(false)->after('donor_name');
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Donation table check warning: ' . $e->getMessage());
        }
    }

    /**
     * Store a donation confirmation / notification note submitted by a supporter.
     */
    public function storeNotification(Request $request)
    {
        $this->ensureTableExists();

        $validated = $request->validate([
            'donor_name' => 'nullable|string|max:100',
            'is_anonymous' => 'nullable|boolean',
            'donor_email' => 'nullable|email|max:150',
            'amount' => 'required|numeric|min:0.5',
            'currency' => 'required|string|max:10',
            'payment_method' => 'required|string|max:50',
            'transaction_reference' => 'nullable|string|max:150',
            'message' => 'nullable|string|max:2000',
        ]);

        $userId = Auth::id();
        $isAnonymous = $request->boolean('is_anonymous');
        $donorName = $validated['donor_name'] ?? null;
        $donorEmail = $validated['donor_email'] ?? null;

        if ($userId) {
            $user = Auth::user();
            $donorName = $donorName ?: ($user->display_name ?: $user->name);
            $donorEmail = $donorEmail ?: $user->email;
        }

        $record = DonationRecord::create([
            'user_id' => $userId,
            'donor_name' => $donorName,
            'is_anonymous' => $isAnonymous,
            'donor_email' => $donorEmail,
            'amount' => $validated['amount'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'USD'),
            'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'JazakAllahu Khairan! Your contribution notice has been received with sincere gratitude.',
                'data' => $record,
            ]);
        }

        return redirect()->route('donate')->with('success', 'JazakAllahu Khairan! Thank you for supporting the continuity of The Eternal Echo. May Allah reward you abundantly!');
    }

    /**
     * Admin view for donation notices.
     */
    public function adminIndex(Request $request)
    {
        $this->ensureTableExists();

        $query = DonationRecord::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('is_anonymous')) {
            $query->where('is_anonymous', $request->boolean('is_anonymous'));
        }

        $donations = $query->paginate(20)->withQueryString();

        return view('admin.donations.index', compact('donations'));
    }

    /**
     * Admin update status of donation record.
     */
    public function updateStatus(Request $request, DonationRecord $donation)
    {
        $request->validate(['status' => 'required|in:pending,verified,acknowledged']);
        $donation->update(['status' => $request->status]);

        return back()->with('success', 'Donation record status updated.');
    }
}
