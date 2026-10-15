@extends('layouts.app')

@section('title', 'Admin Panel - Contributions & Donations')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Supporter Contributions & Donations</h1>
            <p class="text-slate-500 mt-1">Review incoming contributions, bank transfer notices, and donor messages.</p>
        </div>
        <div>
            <a href="{{ route('donate') }}" target="_blank" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl transition-all inline-flex items-center space-x-1.5">
                <span>View Public Donation Page</span>
                <span>↗</span>
            </a>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium">{{ session('success') }}</div>
    @endif

    {{-- Filter Bar --}}
    <div class="mb-6 p-4 bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl flex flex-wrap gap-4 items-center justify-between">
        <form method="GET" action="{{ route('admin.donations.index') }}" class="flex flex-wrap gap-3 items-center w-full sm:w-auto">
            <div>
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                </select>
            </div>
            <div>
                <select name="payment_method" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Methods</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Card (Stripe)</option>
                    <option value="paypal" {{ request('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                    <option value="easypaisa" {{ request('payment_method') === 'easypaisa' ? 'selected' : '' }}>EasyPaisa</option>
                    <option value="jazzcash" {{ request('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                    <option value="crypto" {{ request('payment_method') === 'crypto' ? 'selected' : '' }}>Crypto</option>
                </select>
            </div>
            <div>
                <select name="is_anonymous" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Visibility</option>
                    <option value="1" {{ request('is_anonymous') === '1' ? 'selected' : '' }}>🔒 Hidden (Anonymous)</option>
                    <option value="0" {{ request('is_anonymous') === '0' ? 'selected' : '' }}>👤 Public Names</option>
                </select>
            </div>
            @if(request()->anyFilled(['status', 'payment_method', 'is_anonymous']))
                <a href="{{ route('admin.donations.index') }}" class="text-xs text-rose-500 font-bold hover:underline">Reset Filters</a>
            @endif
        </form>
    </div>

    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-4">Donor</th>
                        <th class="px-4 py-4">Amount</th>
                        <th class="px-4 py-4">Method</th>
                        <th class="px-4 py-4">Reference</th>
                        <th class="px-4 py-4">Du'a / Message</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Date</th>
                        <th class="px-4 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($donations as $donation)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-4 text-xs">
                                <div class="flex items-center space-x-1.5 mb-0.5">
                                    <span class="font-bold text-slate-900">
                                        {{ $donation->donor_name ?: ($donation->user?->display_name ?: $donation->user?->name ?: 'Anonymous Supporter') }}
                                    </span>
                                    @if($donation->is_anonymous)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="Donor chose to remain anonymous publicly">
                                            🔒 Anonymous
                                        </span>
                                    @endif
                                </div>
                                @if($donation->user)
                                    <span class="text-[11px] text-slate-500 block">User: {{ $donation->user->email }}</span>
                                @elseif($donation->donor_email)
                                    <span class="text-[11px] text-slate-400 block">{{ $donation->donor_email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($donation->amount)
                                    <span class="font-extrabold text-emerald-600 text-sm">
                                        {{ $donation->currency }} {{ number_format($donation->amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Unspecified</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs font-semibold text-slate-600">
                                {{ ucwords(str_replace('_', ' ', $donation->payment_method)) }}
                            </td>
                            <td class="px-4 py-4 text-xs font-mono text-slate-700">
                                {{ $donation->transaction_reference ?: '—' }}
                            </td>
                            <td class="px-4 py-4 text-slate-700 text-xs whitespace-normal max-w-[280px]">
                                {{ $donation->message ?: '—' }}
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'verified' => 'bg-emerald-100 text-emerald-700',
                                        'acknowledged' => 'bg-blue-100 text-blue-700'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-bold rounded-lg {{ $statusColors[$donation->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($donation->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400 whitespace-nowrap">{{ $donation->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <form action="{{ route('admin.donations.update-status', $donation) }}" method="POST" class="inline-flex items-center space-x-1">
                                    @csrf
                                    @method('PATCH')
                                    @if($donation->status === 'pending')
                                        <input type="hidden" name="status" value="verified">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg font-bold">Verify</button>
                                    @elseif($donation->status === 'verified')
                                        <input type="hidden" name="status" value="acknowledged">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg font-bold">Acknowledge</button>
                                    @else
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-slate-50 text-slate-500 hover:bg-slate-100 rounded-lg font-bold">Reopen</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400 font-medium">No donation notices submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($donations->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $donations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
