@extends('layouts.app')
@section('title', 'Daily Quranic Dua - The Eternal Echo')

@push('styles')
    <style>
        .dua-container {
            position: relative;
            overflow: hidden;
        }

        .dua-container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0) 70%);
            border-radius: 50%;
        }

        .dua-container::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0) 70%);
            border-radius: 50%;
        }
    </style>
@endpush

@section('content')
    <div class="max-w-3xl mx-auto space-y-8 pb-10 animate-fadeIn">
        
        {{-- Header Section --}}
        <div class="text-center space-y-3">
            <div class="inline-flex items-center space-x-2 bg-emerald-100 text-emerald-800 rounded-full px-4 py-1.5 font-bold text-xs uppercase tracking-widest shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>Daily Supplication</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-slate-900 leading-tight">Quranic Dua</h1>
            <p class="text-slate-500 font-medium italic">"And your Lord says, 'Call upon Me; I will respond to you.'" <span class="text-xs text-slate-400 block mt-1">(Quran 40:60)</span></p>
        </div>

        {{-- Dua Card --}}
        <div class="bg-white rounded-[2rem] p-8 md:p-12 shadow-2xl border border-slate-100 space-y-8 dua-container">
            
            {{-- Verse Key Badge --}}
            <div class="flex justify-center">
                <span class="bg-slate-100 text-slate-600 px-4 py-1.5 rounded-full text-sm font-black font-mono shadow-inner border border-slate-200">
                    Surah / Ayah: {{ $dua['verse_key'] }}
                </span>
            </div>

            {{-- Arabic Text --}}
            <div class="text-center" dir="rtl">
                <p class="font-arabic text-4xl md:text-5xl leading-[1.8] md:leading-[2.2] text-slate-900 font-bold" style="word-spacing: 0.1em;">
                    {{ $dua['arabic'] }}
                </p>
            </div>

            <div class="w-24 h-1 bg-gradient-to-r from-emerald-400 to-blue-500 mx-auto rounded-full opacity-50"></div>

            {{-- English Translation --}}
            <div class="text-center px-4 md:px-8">
                <p class="text-slate-700 text-xl md:text-2xl leading-relaxed italic font-medium">
                    "{{ $dua['translation'] }}"
                </p>
                <p class="text-sm text-slate-400 mt-4 font-semibold uppercase tracking-widest">Sahih International</p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
            <button onclick="window.location.reload()" 
                class="w-full sm:w-auto bg-white text-slate-800 border-2 border-slate-200 py-4 px-8 rounded-2xl font-black text-lg shadow-sm hover:border-emerald-500 hover:text-emerald-700 transition-all active:scale-[0.98] flex items-center justify-center space-x-2 group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 group-hover:rotate-180 transition-all duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Read Another</span>
            </button>
            <a href="{{ route('home') }}" 
                class="w-full sm:w-auto bg-slate-900 text-white py-4 px-8 rounded-2xl font-black text-lg shadow-lg hover:bg-slate-800 transition-all active:scale-[0.98] text-center">
                Return to Dashboard
            </a>
        </div>
    </div>
@endsection
