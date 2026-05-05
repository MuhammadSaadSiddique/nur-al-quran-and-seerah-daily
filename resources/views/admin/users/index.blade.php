@extends('layouts.app')

@section('title', 'Admin Panel - User Statistics')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Admin Dashboard</h1>
            <p class="text-slate-500 mt-1">Platform management and insights.</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">User</th>
                        <th scope="col" class="px-6 py-4">Level</th>
                        <th scope="col" class="px-6 py-4">Questions Answered</th>
                        <th scope="col" class="px-6 py-4">Accuracy</th>
                        <th scope="col" class="px-6 py-4">Score</th>
                        <th scope="col" class="px-6 py-4">Joined</th>
                        <th scope="col" class="px-6 py-4 text-center">Admin Access</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-lg">
                                        {{ Str::limit(strtoupper($user->display_name ?? $user->name ?? 'U'), 1, '') }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $user->display_name ?? $user->name ?? 'Anonymous' }}</div>
                                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php $level = $user->spiritual_level; @endphp
                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg 
                                    {{ $level === 'Novice' ? 'bg-slate-100 text-slate-700' : '' }}
                                    {{ $level === 'Aspirant' ? 'bg-sky-100 text-sky-700' : '' }}
                                    {{ $level === 'Knowledge Seeker' ? 'bg-indigo-100 text-indigo-700' : '' }}">
                                    {{ $level }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-700">
                                {{ number_format($user->total_questions) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-700">
                                {{ $user->accuracy }}%
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-700 text-emerald-600">
                                {{ number_format($user->total_score) }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1 text-xs font-bold rounded-lg transition-all border {{ $user->is_admin ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200' }}">
                                            {{ $user->is_admin ? 'Revoke Admin' : 'Make Admin' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="px-3 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed">
                                        Current You
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <p class="font-medium">No users found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
