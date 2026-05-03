@extends('layouts.app')

@section('title', 'Admin Panel - Feedback')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Feedback</h1>
        <p class="text-slate-500 mt-1">User-reported issues and suggestions on questions.</p>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium">{{ session('success') }}</div>
    @endif

    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-4">User</th>
                        <th class="px-4 py-4">Type</th>
                        <th class="px-4 py-4">Question</th>
                        <th class="px-4 py-4">Message</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Date</th>
                        <th class="px-4 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($feedback as $fb)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-4 font-medium text-slate-700 text-xs">{{ $fb->user->display_name ?? $fb->user->email }}</td>
                            <td class="px-4 py-4">
                                @php
                                    $typeColors = ['error' => 'bg-rose-100 text-rose-700', 'suggestion' => 'bg-blue-100 text-blue-700', 'praise' => 'bg-emerald-100 text-emerald-700'];
                                @endphp
                                <span class="px-2 py-1 text-xs font-bold rounded-lg {{ $typeColors[$fb->type] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($fb->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-500 whitespace-normal max-w-[250px]">
                                {{ Str::limit($fb->question_text, 60) }}
                                @if($fb->question)
                                    <a href="{{ route('admin.questions.edit', $fb->question) }}" class="text-blue-500 hover:underline ml-1">[Edit]</a>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-700 whitespace-normal max-w-[300px]">{{ $fb->message }}</td>
                            <td class="px-4 py-4">
                                @php
                                    $statusColors = ['pending' => 'bg-amber-100 text-amber-700', 'reviewed' => 'bg-blue-100 text-blue-700', 'resolved' => 'bg-emerald-100 text-emerald-700'];
                                @endphp
                                <span class="px-2 py-1 text-xs font-bold rounded-lg {{ $statusColors[$fb->status] ?? '' }}">
                                    {{ ucfirst($fb->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400">{{ $fb->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-4 text-right">
                                <form method="POST" action="{{ route('admin.feedback.update-status', $fb) }}" class="inline-flex items-center space-x-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                        <option value="pending" {{ $fb->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="reviewed" {{ $fb->status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                        <option value="resolved" {{ $fb->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    </select>
                                    <button type="submit" class="px-2 py-1 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-slate-700">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <p class="font-medium">No feedback received yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($feedback->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
            {{ $feedback->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
