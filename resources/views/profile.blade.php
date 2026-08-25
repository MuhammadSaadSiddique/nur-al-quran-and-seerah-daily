@extends('layouts.app')
@section('title', 'Profile - The Eternal Echo')
@section('meta_description', 'Manage your The Eternal Echo profile. Update your display name and view your spiritual learning journey.')

@section('content')
    <div class="max-w-2xl mx-auto space-y-8 pb-10 animate-fadeIn">
        {{-- Profile Card --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
            <div class="text-center space-y-4">
                <div
                    class="bg-emerald-100 w-24 h-24 rounded-3xl flex items-center justify-center mx-auto text-emerald-700 shadow-inner">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900">{{ $user->display_name ?: explode('@', $user->email)[0] }}
                </h2>
                <p class="text-slate-500 font-bold">{{ $user->email }}</p>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Display Name</label>
                    <input type="text" name="display_name" value="{{ $user->display_name }}"
                        placeholder="Enter a display name..."
                        class="w-full p-4 rounded-xl border-2 border-slate-100 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Password (Leave blank to keep current)</label>
                    <input type="password" name="password" placeholder="Enter new password..."
                        class="w-full p-4 rounded-xl border-2 border-slate-100 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all">
                </div>
                <button type="submit"
                    class="w-full bg-emerald-600 text-white py-4 rounded-xl font-black hover:bg-emerald-700 transition-all active:scale-[0.99]">
                    Update Profile
                </button>
            </form>
        </div>

        {{-- Growth Summary --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-4">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-wider">Growth Summary</h3>
            <div class="divide-y divide-slate-100">
                <div class="flex justify-between py-4">
                    <span class="font-bold text-slate-600">Accuracy</span>
                    <span class="font-black text-emerald-600">{{ $user->accuracy }}%</span>
                </div>
                <div class="flex justify-between py-4">
                    <span class="font-bold text-slate-600">Paras Completed</span>
                    <span class="font-black text-blue-600">{{ count($user->completed_paras ?? []) }}/30</span>
                </div>
                <div class="flex justify-between py-4">
                    <span class="font-bold text-slate-600">Seerah Insights</span>
                    <span class="font-black text-violet-600">{{ $user->seerah_read_count }}</span>
                </div>
                <div class="flex justify-between py-4">
                    <span class="font-bold text-slate-600">Quranic History</span>
                    <span class="font-black text-amber-600">{{ $user->quran_history_read_count }}</span>
                </div>
                <div class="flex justify-between py-4">
                    <span class="font-bold text-slate-600">Spiritual Level</span>
                    <span
                        class="font-black text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg text-sm">{{ $user->spiritual_level }}</span>
                </div>
            </div>
        </div>

        {{-- Share Your Experience --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-wider">Share Your Experience</h3>
            <p class="text-slate-500 text-sm font-medium">Your inspiring words could help others on their spiritual journey. Share how The Eternal Echo has impacted you.</p>
            
            <form action="{{ route('testimonials.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Your Name (as it will appear)</label>
                    <input type="text" name="name" 
                        value="{{ $user->display_name ?: explode('@', $user->email)[0] }}"
                        placeholder="E.g. Ahmad Hassan" required
                        class="w-full p-4 rounded-xl border-2 border-slate-100 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Your Feedback</label>
                    <textarea name="feedback" rows="4" required
                        placeholder="What do you love about the platform?"
                        class="w-full p-4 rounded-xl border-2 border-slate-100 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all resize-none"></textarea>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 text-white py-4 rounded-xl font-black hover:bg-emerald-900 transition-all active:scale-[0.99] flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <span>Submit Testimonial</span>
                </button>
            </form>
        </div>

        {{-- Sign Out --}}
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="w-full bg-rose-100 text-rose-700 py-5 rounded-2xl font-black text-lg hover:bg-rose-200 transition-all">
                Sign Out
            </button>
        </form>
    </div>
@endsection