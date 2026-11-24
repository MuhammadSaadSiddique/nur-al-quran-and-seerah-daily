@php
    $ua = (string) request()->userAgent();
    $showAndroidPrompt = preg_match('/Android/i', $ua) === 1 && preg_match('/;\s*wv\)/i', $ua) !== 1;
    $tone = $tone ?? 'light';
    $playStoreUrl = $playStoreUrl ?? 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho';
@endphp
@if($showAndroidPrompt)
    <div x-data="{ show: true }"
        x-init="try { show = sessionStorage.getItem('ee-android-app-prompt') !== '1' } catch (e) {}"
        x-show="show"
        class="{{ $tone === 'dark' ? 'border-b border-gold-600/40 bg-gold-500 text-emerald-950' : 'rounded-[2rem] border border-emerald-200 bg-emerald-700 text-white shadow-lg' }}">
        <div class="{{ $tone === 'dark' ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' : '' }} py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 {{ $tone === 'light' ? 'px-5 sm:px-6' : '' }}">
            <div class="min-w-0 flex-1">
                <p class="text-sm sm:text-base font-black">Download the app for the full experience</p>
                <p class="text-xs sm:text-sm mt-0.5 {{ $tone === 'dark' ? 'text-emerald-950/80' : 'text-emerald-50' }}">
                    Exact adhan reminders, nearby mosques and jamat times, and Quran root-word search are in the Android app.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-black {{ $tone === 'dark' ? 'bg-emerald-950 text-gold-300 hover:bg-emerald-900' : 'bg-white text-emerald-800 hover:bg-emerald-50' }}">
                    Get the app
                </a>
                <button type="button"
                    @click="show = false; try { sessionStorage.setItem('ee-android-app-prompt', '1') } catch (e) {}"
                    class="px-3 py-2.5 rounded-xl text-xs font-bold {{ $tone === 'dark' ? 'text-emerald-950/70 hover:text-emerald-950' : 'text-emerald-100 hover:text-white' }}">
                    Not now
                </button>
            </div>
        </div>
    </div>
@endif
