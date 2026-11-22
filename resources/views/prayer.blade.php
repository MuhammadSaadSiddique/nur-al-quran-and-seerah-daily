@extends('layouts.app')
@section('title', 'Prayer Times - The Eternal Echo')
@section('meta_description', 'Today’s salah times for your location. Opening this page completes your daily prayer task.')

@section('content')
    <div class="max-w-xl mx-auto space-y-8 pb-10 animate-fadeIn" x-data="prayerPage()">
        <div class="text-center space-y-3">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Salah</p>
            <h1 class="text-3xl font-black text-slate-900 md:text-4xl">Prayer times</h1>
            <p class="text-slate-600 font-medium">Fajr and Isha use the Karachi method. Asr uses the Hanafi shadow.</p>
        </div>

        @if($points > 0)
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-sm font-bold text-center">
                {{ \App\Services\ContributionPoints::note($points) }}
            </div>
        @endif

        @if($schedule)
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-100 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $localDate }}</p>
                @php
                    $rows = [
                        'fajr' => 'Fajr',
                        'sunrise' => 'Sunrise',
                        'dhuhr' => $isFriday ? "Jumu'ah" : 'Dhuhr',
                        'asr' => 'Asr',
                        'maghrib' => 'Maghrib',
                        'isha' => 'Isha',
                    ];
                @endphp
                @foreach($rows as $key => $label)
                    <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
                        <span class="font-bold text-slate-700">{{ $label }}</span>
                        <span class="font-black text-emerald-700 text-lg">{{ $schedule[$key] }}</span>
                    </div>
                @endforeach
            </div>
            <button type="button" @click="locate()"
                class="w-full bg-slate-900 text-white py-4 rounded-xl font-black hover:bg-emerald-800 transition-all">
                Use my location again
            </button>
        @else
            <div class="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-100 space-y-4 text-center">
                <p class="text-slate-600 font-medium">Share your location to see today’s salah times.</p>
                <button type="button" @click="locate()"
                    class="w-full bg-emerald-600 text-white py-4 rounded-xl font-black hover:bg-emerald-700 transition-all">
                    Show prayer times
                </button>
                <p x-show="denied" x-cloak class="text-sm font-bold text-rose-600">Location was blocked. Allow it in the browser and try again.</p>
            </div>
        @endif
    </div>

    <script>
        function prayerPage() {
            return {
                denied: false,
                locate() {
                    if (!navigator.geolocation) {
                        this.denied = true;
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((pos) => {
                        const url = new URL(window.location.href);
                        url.searchParams.set('latitude', pos.coords.latitude.toFixed(5));
                        url.searchParams.set('longitude', pos.coords.longitude.toFixed(5));
                        url.searchParams.set('tz', (-new Date().getTimezoneOffset() / 60).toString());
                        window.location.replace(url.toString());
                    }, () => {
                        this.denied = true;
                    }, { enableHighAccuracy: false, maximumAge: 300000, timeout: 8000 });
                }
            };
        }
    </script>
@endsection
