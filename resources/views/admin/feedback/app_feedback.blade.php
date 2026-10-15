@extends('layouts.app')

@section('title', 'Admin Panel - App Experience Feedback')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">App Experience Feedback</h1>
            <p class="text-slate-500 mt-1">Ratings and suggestions submitted by users across the Web Platform and Mobile Companion App.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.feedback.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition-all">
                Question Reports →
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
        <form method="GET" action="{{ route('admin.app-feedback.index') }}" class="flex flex-wrap gap-3 items-center w-full sm:w-auto">
            <div>
                <select name="platform" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Platforms</option>
                    <option value="web" {{ request('platform') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="android" {{ request('platform') === 'android' ? 'selected' : '' }}>Android</option>
                </select>
            </div>
            <div>
                <select name="rating" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Ratings</option>
                    <option value="5" {{ request('rating') === '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5 Stars)</option>
                    <option value="4" {{ request('rating') === '4' ? 'selected' : '' }}>⭐⭐⭐⭐ (4 Stars)</option>
                    <option value="3" {{ request('rating') === '3' ? 'selected' : '' }}>⭐⭐⭐ (3 Stars)</option>
                    <option value="2" {{ request('rating') === '2' ? 'selected' : '' }}>⭐⭐ (2 Stars)</option>
                    <option value="1" {{ request('rating') === '1' ? 'selected' : '' }}>⭐ (1 Star)</option>
                </select>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            @if(request()->anyFilled(['platform', 'rating', 'status']))
                <a href="{{ route('admin.app-feedback.index') }}" class="text-xs text-rose-500 font-bold hover:underline">Reset Filters</a>
            @endif
        </form>
    </div>

    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-4">Rating</th>
                        <th class="px-4 py-4">User</th>
                        <th class="px-4 py-4">Platform</th>
                        <th class="px-4 py-4">Category</th>
                        <th class="px-4 py-4">Comments / Message</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Date</th>
                        <th class="px-4 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($appFeedbacks as $fb)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center text-amber-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $fb->rating)
                                            <span>★</span>
                                        @else
                                            <span class="text-slate-300">★</span>
                                        @endif
                                    @endfor
                                    <span class="ml-1.5 text-xs font-black text-slate-700">({{ $fb->rating }}/5)</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                @if($fb->user)
                                    <span class="font-bold text-slate-800 block">{{ $fb->user->display_name ?? $fb->user->name }}</span>
                                    <span class="text-slate-400">{{ $fb->user->email }}</span>
                                @else
                                    <span class="font-bold text-slate-800 block">{{ $fb->user_name ?: 'Guest / Seeker' }}</span>
                                    @if($fb->user_email)
                                        <span class="text-slate-400">{{ $fb->user_email }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 text-[11px] font-black uppercase tracking-wider rounded-lg {{ $fb->platform === 'android' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $fb->platform }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs font-semibold text-slate-600">
                                {{ ucwords(str_replace('_', ' ', $fb->category)) }}
                            </td>
                            <td class="px-4 py-4 text-slate-700 whitespace-normal max-w-[320px]">
                                {{ $fb->message ?: '— No written message —' }}
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'reviewed' => 'bg-blue-100 text-blue-700',
                                        'resolved' => 'bg-emerald-100 text-emerald-700'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-bold rounded-lg {{ $statusColors[$fb->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($fb->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400 whitespace-nowrap">{{ $fb->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <form action="{{ route('admin.app-feedback.update-status', $fb) }}" method="POST" class="inline-flex items-center space-x-1">
                                    @csrf
                                    @method('PATCH')
                                    @if($fb->status === 'pending')
                                        <input type="hidden" name="status" value="reviewed">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg font-bold">Mark Reviewed</button>
                                    @elseif($fb->status === 'reviewed')
                                        <input type="hidden" name="status" value="resolved">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg font-bold">Resolve</button>
                                    @else
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-slate-50 text-slate-500 hover:bg-slate-100 rounded-lg font-bold">Reopen</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400 font-medium">No app feedback submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appFeedbacks->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $appFeedbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
