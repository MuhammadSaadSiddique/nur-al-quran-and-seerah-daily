@php
    $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho';
@endphp
<a href="{{ $playStoreUrl }}"
   target="_blank"
   rel="noopener noreferrer"
   class="inline-flex items-center gap-3 rounded-xl border border-white/15 bg-black px-4 py-2 text-white shadow-lg transition hover:bg-slate-800"
   aria-label="Get The Eternal Echo on Google Play">
    <svg class="h-7 w-7 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#34A853" d="M3 20.5V3.5c0-.59.34-1.11.84-1.35L13.69 12 3.84 21.85A1.5 1.5 0 0 1 3 20.5z"/>
        <path fill="#EA4335" d="M16.81 15.12 6.05 21.34 14.54 12.85l2.27 2.27z"/>
        <path fill="#FBBC04" d="M20.16 10.81c.34.27.59.69.59 1.19s-.25.92-.57 1.18L17.89 14.5 15.39 12l2.5-2.5 2.27 1.31z"/>
        <path fill="#4285F4" d="M6.05 2.66 16.81 8.88 14.54 11.15 6.05 2.66z"/>
    </svg>
    <span class="text-left leading-none">
        <span class="block text-[10px] font-medium uppercase tracking-wide text-white/70">Get it on</span>
        <span class="mt-1 block text-sm font-bold tracking-tight">Google Play</span>
    </span>
</a>
