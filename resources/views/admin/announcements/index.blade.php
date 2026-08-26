@extends('layouts.app')

@section('title', 'Admin Panel - Send Announcement')

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

    <div class="max-w-3xl bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Compose Announcement</h2>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm font-bold mb-6">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-bold mb-6">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-bold mb-6 space-y-1">
                @foreach($errors->all() as $error)
                    <p>⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.announcements.send') }}" method="POST" class="space-y-6">
            @csrf

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Enter announcement subject..." required
                    class="w-full p-4 rounded-xl border-2 border-slate-100 bg-white font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm shadow-sm" />
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Announcement Message Body</label>
                <textarea name="content" rows="10" placeholder="Type your announcement message here..." required
                    class="w-full p-4 rounded-xl border-2 border-slate-100 bg-white font-medium text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm leading-relaxed shadow-sm">{{ old('content') }}</textarea>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-6 py-4 rounded-xl transition-all shadow-md shadow-emerald-600/10 hover:shadow-emerald-600/20 hover:-translate-y-0.5">
                    Send Announcement to All Users 🚀
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
