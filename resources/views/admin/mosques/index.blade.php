@extends('layouts.app')

@section('title', 'Admin Panel - Mosque Directory')

@section('content')
@php
    $prayerLabels = ['fajr' => 'Fajr', 'dhuhr' => 'Dhuhr', 'asr' => 'Asr', 'maghrib' => 'Maghrib', 'isha' => 'Isha', 'jumuah' => "Jumu'ah"];
    $reasonLabels = [
        'closed' => 'Closed / not a mosque',
        'duplicate' => 'Duplicate',
        'wrong_location' => 'Wrong location',
        'wrong_times' => 'Wrong times',
        'wrong_sect' => 'Wrong school of thought',
        'inappropriate' => 'Inappropriate',
        'other' => 'Other',
    ];
    $facilityLabels = [
        'wheelchair_entrance' => '♿ Step-free entrance',
        'accessible_wudu' => 'Accessible wudu/toilet',
        'prayer_chairs' => 'Prayer chairs',
        'women_wudu' => "Women's wudu",
        'wudu_area' => 'Wudu area',
        'toilets' => 'Toilets',
        'car_parking' => 'Car parking',
        'bike_parking' => 'Bike parking',
        'air_conditioning' => 'A/C',
    ];
    $queues = [
        'all' => 'All',
        'sect_review' => 'Sect review',
        'reported' => 'Reported',
        'hidden' => 'Hidden',
        'unverified' => 'Unverified',
        'verified' => 'Verified',
    ];
@endphp
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Community Mosque Directory</h1>
        <p class="text-slate-500 mt-1">Mosques and jama'ah times added by worshippers in the app. New entries go live immediately; review reports and verify trusted mosques here.</p>
    </div>

    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium">{{ session('success') }}</div>
    @endif

    @php
        // Queue tabs keep the other filters; filters keep the queue.
        $carry = array_filter($filters, fn ($v) => $v !== '') + (request('sort') ? ['sort' => request('sort')] : []);
        $select = 'bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500 w-full';
    @endphp
    <div class="mb-6 p-4 bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl space-y-4">
        <div class="flex flex-wrap gap-2">
            @foreach($queues as $key => $label)
                <a href="{{ route('admin.mosques.index', $carry + ['filter' => $key]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold border {{ $filter === $key ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    {{ $label }} <span class="opacity-75">({{ $counts[$key] ?? 0 }})</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.mosques.index') }}" class="space-y-3">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-search">Search</label>
                    <input id="f-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Name, area, address or city"
                           class="{{ $select }} font-normal">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-city">City</label>
                    <select id="f-city" name="city" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">All cities</option>
                        <option value="{{ $none }}" @selected($filters['city'] === $none)>No city ({{ number_format($noCity) }})</option>
                        @foreach($cities as $city => $total)
                            <option value="{{ $city }}" @selected($filters['city'] === (string) $city)>{{ $city }} ({{ number_format($total) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-country">Country</label>
                    <select id="f-country" name="country" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">All countries</option>
                        <option value="{{ $none }}" @selected($filters['country'] === $none)>No country ({{ number_format($noCountry) }})</option>
                        @foreach($countries as $country => $total)
                            <option value="{{ $country }}" @selected($filters['country'] === (string) $country)>{{ $country }} ({{ number_format($total) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-sort">Sort by</label>
                    <select id="f-sort" name="sort" onchange="this.form.submit()" class="{{ $select }}">
                        @foreach($sorts as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-sect">School of thought</label>
                    <select id="f-sect" name="sect" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any</option>
                        <option value="unspecified" @selected($filters['sect'] === 'unspecified')>Not specified</option>
                        @foreach(\App\Models\Mosque::SECTS as $sect)
                            <option value="{{ $sect }}" @selected($filters['sect'] === $sect)>{{ \App\Models\Mosque::sectLabel($sect) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-place">Type</label>
                    <select id="f-place" name="place_type" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Masjid & musalla</option>
                        <option value="masjid" @selected($filters['place_type'] === 'masjid')>Masjid only</option>
                        <option value="musalla" @selected($filters['place_type'] === 'musalla')>Musalla only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-times">Jama'ah times</label>
                    <select id="f-times" name="times" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any</option>
                        @foreach($timeFilters as $key => $label)
                            <option value="{{ $key }}" @selected($filters['times'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-facility">Has</label>
                    <select id="f-facility" name="facility" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any facilities</option>
                        <option value="women_area" @selected($filters['facility'] === 'women_area')>Women's prayer area</option>
                        <option value="jumuah" @selected($filters['facility'] === 'jumuah')>Jumu'ah</option>
                        <option value="ramadan" @selected($filters['facility'] === 'ramadan')>Ramadan / Eid info</option>
                        @foreach($facilityLabels as $key => $label)
                            <option value="{{ $key }}" @selected($filters['facility'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-source">Source</label>
                    <select id="f-source" name="source" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any source</option>
                        <option value="osm" @selected($filters['source'] === 'osm')>Imported from OpenStreetMap</option>
                        <option value="community" @selected($filters['source'] === 'community')>Added in the app</option>
                        <option value="linked" @selected($filters['source'] === 'linked')>Added in the app, linked to OSM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-address">Address</label>
                    <select id="f-address" name="address" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any</option>
                        @foreach($addressFilters as $key => $label)
                            <option value="{{ $key }}" @selected($filters['address'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1" for="f-asr">Asr timing</label>
                    <select id="f-asr" name="asr" onchange="this.form.submit()" class="{{ $select }}">
                        <option value="">Any</option>
                        <option value="hanafi" @selected($filters['asr'] === 'hanafi')>Hanafi</option>
                        <option value="standard" @selected($filters['asr'] === 'standard')>Standard</option>
                        <option value="unknown" @selected($filters['asr'] === 'unknown')>Not known</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs text-slate-500">
                    {{ number_format($mosques->total()) }} {{ \Illuminate\Support\Str::plural('mosque', $mosques->total()) }}
                    @if($activeFilterCount > 0) · {{ $activeFilterCount }} {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }} on @endif
                </span>
                <div class="flex items-center gap-2">
                    @if($activeFilterCount > 0)
                        <a href="{{ route('admin.mosques.index', ['filter' => $filter]) }}" class="text-xs text-rose-500 font-bold hover:underline">Reset filters</a>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold">Apply</button>
                </div>
            </div>
        </form>
    </div>

    @error('bulk')
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl font-medium">{{ $message }}</div>
    @enderror

    {{-- Import from OpenStreetMap (the data behind falah.io's Mosque Finder) --}}
    @php
        $osmReport = session('osm_report');
        $importMode = old('mode', 'place');
    @endphp
    <details class="mb-6 bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl" @if($osmReport || $errors->has('osm_import') || $errors->hasAny(['place', 'latitude', 'longitude', 'radius_km'])) open @endif>
        <summary class="px-4 py-3 cursor-pointer select-none flex items-center justify-between">
            <span class="font-bold text-slate-800 text-sm">🗺️ Import mosques from OpenStreetMap</span>
            <span class="text-xs text-slate-500">Same data as falah.io's Mosque Finder</span>
        </summary>
        <div class="px-4 pb-4 space-y-3 text-sm">
            <p class="text-xs text-slate-500">
                Finds places tagged as mosques in OpenStreetMap and adds the ones not already listed. Re-running is safe: untouched imports are refreshed,
                mosques the community edited only get empty fields filled, and mosques you delete are not brought back. Preview first, then import.
                For a whole country use <code class="bg-slate-100 px-1 rounded">php artisan mosques:import-osm --country="Pakistan"</code>.
            </p>

            @error('osm_import')
                <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">{{ $message }}</div>
            @enderror
            @if($errors->hasAny(['place', 'latitude', 'longitude', 'radius_km', 'mode']))
                <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                    {{ $errors->first('place') ?: $errors->first('latitude') ?: $errors->first('longitude') ?: $errors->first('radius_km') ?: $errors->first('mode') }}
                </div>
            @endif

            @if($osmReport)
                <div class="p-3 rounded-xl border {{ $osmReport['dry_run'] ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200' }}">
                    <div class="font-bold {{ $osmReport['dry_run'] ? 'text-amber-900' : 'text-emerald-800' }}">
                        {{ $osmReport['dry_run'] ? 'Preview' : 'Imported' }}: {{ $osmReport['area'] }}
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 mt-2 text-xs text-slate-700">
                        <span><b>{{ $osmReport['created'] }}</b> {{ $osmReport['dry_run'] ? 'would be added' : 'added' }}</span>
                        <span><b>{{ $osmReport['refreshed'] }}</b> refreshed</span>
                        <span><b>{{ $osmReport['filled'] }}</b> gaps filled</span>
                        <span><b>{{ $osmReport['linked'] }}</b> linked to app entries</span>
                        <span><b>{{ $osmReport['unchanged'] }}</b> already up to date</span>
                        <span><b>{{ $osmReport['skipped_unnamed'] }}</b> skipped, no name</span>
                        <span><b>{{ $osmReport['skipped_deleted'] }}</b> skipped, deleted by admin</span>
                        <span><b>{{ $osmReport['skipped_duplicate'] }}</b> skipped, already listed</span>
                    </div>
                    @if(!empty($osmReport['samples']))
                        <div class="text-xs text-slate-500 mt-2">e.g. {{ implode(', ', $osmReport['samples']) }}</div>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.mosques.import-osm') }}" class="space-y-3">
                @csrf
                <div class="flex gap-4 text-xs font-semibold text-slate-700">
                    <label class="inline-flex items-center gap-1.5"><input type="radio" name="mode" value="place" @checked($importMode === 'place')> City or area</label>
                    <label class="inline-flex items-center gap-1.5"><input type="radio" name="mode" value="point" @checked($importMode === 'point')> Around a point</label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1" for="osm-place">City or area (for "City or area")</label>
                        <input id="osm-place" name="place" value="{{ old('place') }}" placeholder="e.g. Gulshan-e-Iqbal, Karachi" class="{{ $select }} font-normal">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1" for="osm-lat">Latitude</label>
                        <input id="osm-lat" name="latitude" value="{{ old('latitude') }}" inputmode="decimal" placeholder="24.9204" class="{{ $select }} font-mono font-normal">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1" for="osm-lng">Longitude</label>
                        <input id="osm-lng" name="longitude" value="{{ old('longitude') }}" inputmode="decimal" placeholder="67.0932" class="{{ $select }} font-mono font-normal">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1" for="osm-radius">Radius km (max 25)</label>
                        <input id="osm-radius" name="radius_km" type="number" min="0.5" max="25" step="0.5" value="{{ old('radius_km', 5) }}" class="{{ $select }} font-normal">
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 md:col-span-3 md:self-end pb-2">
                        <input type="hidden" name="include_unnamed" value="0">
                        <input type="checkbox" name="include_unnamed" value="1" @checked(old('include_unnamed') === '1') class="rounded text-emerald-600">
                        Also import mosques with no name in OSM (as "{{ \App\Services\OsmMosqueImporter::UNNAMED_NAME }}")
                    </label>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" name="apply" value="0" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold">Preview</button>
                    <button type="submit" name="apply" value="1" onclick="return confirm('Import these mosques from OpenStreetMap into the directory?');"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold">Import</button>
                    <span class="text-xs text-slate-400">Can take up to a minute for a large city.</span>
                </div>
            </form>
        </div>
    </details>

    {{-- Bulk actions. Row checkboxes live in the table and join this form via form="bulk-form". --}}
    <form id="bulk-form" method="POST" action="{{ route('admin.mosques.bulk') }}"
          data-total="{{ $mosques->total() }}" data-max="{{ $maxBulk }}">
        @csrf
        <input type="hidden" name="select_all_matching" value="0" id="bulk-all-matching">
        <input type="hidden" name="expected_count" value="{{ $mosques->total() }}">
        <input type="hidden" name="filter" value="{{ $filter }}">
        @foreach($filters as $key => $value)
            @if($value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
    </form>

    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 bg-slate-50/90 border-b border-slate-200 text-xs">
            <span id="bulk-count" class="font-bold text-slate-700">Tick mosques to act on several at once</span>
            <span id="bulk-offer" hidden class="text-slate-600">
                All {{ $mosques->count() }} on this page are selected.
                @if($mosques->total() <= $maxBulk)
                    <button type="button" id="bulk-select-all" class="text-emerald-700 font-bold hover:underline">Select all {{ number_format($mosques->total()) }} matching mosques</button>
                @else
                    <span class="text-slate-500">Narrow the filters to {{ $maxBulk }} or fewer to select them all.</span>
                @endif
            </span>
            <span id="bulk-all-note" hidden class="text-emerald-800 font-semibold">
                All {{ number_format($mosques->total()) }} mosques matching these filters are selected.
                <button type="button" id="bulk-clear" class="text-slate-600 font-bold hover:underline ml-1">Clear selection</button>
            </span>
            <div class="flex items-center gap-2 ml-auto">
                <label for="bulk-action" class="sr-only">Bulk action</label>
                <select id="bulk-action" name="action" form="bulk-form" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                    <option value="">Choose an action…</option>
                    @foreach($bulkActions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                {{-- Shown only for "Set city/country to…"; suggestions are names already in use. --}}
                <label for="bulk-value" class="sr-only">New value</label>
                <input id="bulk-value" name="bulk_value" form="bulk-form" hidden maxlength="120" autocomplete="off"
                       class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 w-44 focus:outline-none focus:border-emerald-500">
                <datalist id="bulk-city-names">
                    @foreach($cities as $city => $total)
                        <option value="{{ $city }}"></option>
                    @endforeach
                </datalist>
                <datalist id="bulk-country-names">
                    @foreach(collect($countries)->keys()->merge(['Pakistan'])->unique() as $country)
                        <option value="{{ $country }}"></option>
                    @endforeach
                </datalist>
                <button type="submit" form="bulk-form" id="bulk-apply" disabled
                        class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed">Apply</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="pl-4 py-4 w-8">
                            <input type="checkbox" id="bulk-page" class="rounded text-emerald-600" aria-label="Select all mosques on this page">
                        </th>
                        <th class="px-4 py-4">Mosque</th>
                        <th class="px-4 py-4">Jama'ah times</th>
                        <th class="px-4 py-4">Trust</th>
                        <th class="px-4 py-4">Reports</th>
                        <th class="px-4 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mosques as $mosque)
                        <tr class="hover:bg-slate-50/50 transition-colors align-top has-[.bulk-row:checked]:bg-emerald-50/60">
                            <td class="pl-4 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $mosque->id }}" form="bulk-form"
                                       class="bulk-row rounded text-emerald-600" aria-label="Select {{ $mosque->name }}">
                            </td>
                            <td class="px-4 py-4 text-xs max-w-[260px]">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-slate-900 text-sm">{{ $mosque->name }}</span>
                                    @if($mosque->is_verified)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">✓ Verified</span>
                                    @endif
                                    @if($mosque->status === \App\Models\Mosque::STATUS_HIDDEN)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Hidden</span>
                                    @endif
                                    @if($mosque->osm_id)
                                        <a href="{{ $mosque->osmUrl() }}" target="_blank" rel="noopener"
                                           class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 hover:bg-sky-200"
                                           title="{{ $mosque->source === \App\Models\Mosque::SOURCE_OSM ? 'Imported from' : 'Linked to' }} OpenStreetMap">OSM ↗</a>
                                    @endif
                                </div>
                                <div class="text-slate-500 mt-1">{{ collect([$mosque->address, $mosque->locality, $mosque->city, $mosque->country])->filter()->implode(', ') ?: 'No address given' }}</div>
                                <div class="text-slate-600 mt-1">
                                    {{ $mosque->place_type === 'musalla' ? 'Musalla' : 'Masjid' }}
                                    · {{ \App\Models\Mosque::sectLabel($mosque->sect) }}
                                    @if($mosque->asr_method) · Asr {{ ucfirst($mosque->asr_method) }} @endif
                                </div>
                                @if($mosque->pending_sect)
                                    <div class="mt-1.5 p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-900">
                                        <div class="font-bold">Proposed: {{ \App\Models\Mosque::sectLabel($mosque->pending_sect) }}</div>
                                        <div class="text-amber-700">by {{ $mosque->pendingSectBy?->name ?? 'deleted user' }} · {{ $mosque->pending_sect_at?->diffForHumans() }}</div>
                                    </div>
                                @endif
                                @php
                                    $knownFacilities = collect($mosque->facilityMap())->filter(fn ($v) => $v !== null);
                                @endphp
                                @if($knownFacilities->isNotEmpty())
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach($knownFacilities as $key => $has)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $has ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500 line-through' }}">{{ $facilityLabels[$key] ?? $key }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $mosque->latitude }},{{ $mosque->longitude }}" target="_blank" rel="noopener"
                                   class="text-emerald-600 font-semibold hover:underline mt-1 inline-block">{{ number_format($mosque->latitude, 5) }}, {{ number_format($mosque->longitude, 5) }} ↗</a>
                                <div class="text-slate-400 mt-1">
                                    @if($mosque->source === \App\Models\Mosque::SOURCE_OSM)
                                        Imported from OpenStreetMap
                                    @else
                                        Added by {{ $mosque->creator?->name ?? 'deleted user' }}
                                    @endif
                                    · {{ $mosque->created_at?->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 font-mono text-slate-700">
                                    @foreach($prayerLabels as $key => $label)
                                        <span class="text-slate-500 font-sans">{{ $label }}</span>
                                        <span>
                                            @if($key === 'maghrib' && $mosque->maghrib_offset_minutes !== null)
                                                sunset +{{ $mosque->maghrib_offset_minutes }}m
                                            @elseif($key === 'jumuah' && $mosque->jumuah_time_2)
                                                {{ $mosque->jumuah_time ?? '—' }} & {{ $mosque->jumuah_time_2 }}
                                            @else
                                                {{ $mosque->{$key . '_time'} ?? '—' }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                                <div class="text-slate-400 mt-1">
                                    @if($mosque->times_updated_at)
                                        Updated {{ $mosque->times_updated_at->diffForHumans() }} by {{ $mosque->timesUpdater?->name ?? 'deleted user' }}
                                    @else
                                        No times yet
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-600 whitespace-nowrap">
                                <div>{{ $mosque->confirmations_count }} confirmations</div>
                                <div>{{ $mosque->regulars_count }} regulars</div>
                                <div class="text-slate-400">Women's area: {{ $mosque->has_women_area === null ? 'unknown' : ($mosque->has_women_area ? 'yes' : 'no') }}</div>
                            </td>
                            <td class="px-4 py-4 text-xs max-w-[260px]">
                                @if($mosque->reports_count > 0)
                                    <div class="font-bold text-rose-600 mb-1">{{ $mosque->reports_count }} open</div>
                                @endif
                                @foreach(($reports[$mosque->id] ?? collect())->take(4) as $report)
                                    <div class="mb-1.5 text-slate-600">
                                        <span class="font-semibold">{{ $reasonLabels[$report->payload['reason'] ?? 'other'] ?? 'Other' }}</span>
                                        <span class="text-slate-400">· {{ $report->user?->name ?? 'user' }} · {{ $report->created_at?->diffForHumans() }}</span>
                                        @if($report->note)
                                            <div class="text-slate-500 italic">“{{ $report->note }}”</div>
                                        @endif
                                    </div>
                                @endforeach
                                @if(($reports[$mosque->id] ?? collect())->isEmpty())
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex flex-col items-end gap-1.5">
                                    <a href="{{ route('admin.mosques.edit', [$mosque, 'return' => request()->fullUrl()]) }}"
                                       class="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-bold">Edit</a>
                                    @if($mosque->pending_sect)
                                        <form action="{{ route('admin.mosques.moderate', $mosque) }}" method="POST" class="inline-flex gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <button name="action" value="approve_sect" class="text-xs px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg font-bold">Approve sect</button>
                                            <button name="action" value="reject_sect" class="text-xs px-2.5 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg font-bold">Reject</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.mosques.moderate', $mosque) }}" method="POST" class="inline-flex gap-1">
                                        @csrf
                                        @method('PATCH')
                                        @if($mosque->is_verified)
                                            <button name="action" value="unverify" class="text-xs px-2.5 py-1 bg-slate-50 text-slate-600 hover:bg-slate-100 rounded-lg font-bold">Unverify</button>
                                        @else
                                            <button name="action" value="verify" class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg font-bold">Verify</button>
                                        @endif
                                        @if($mosque->status === \App\Models\Mosque::STATUS_HIDDEN)
                                            <button name="action" value="restore" class="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-bold">Restore</button>
                                        @else
                                            <button name="action" value="hide" class="text-xs px-2.5 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg font-bold">Hide</button>
                                        @endif
                                        @if($mosque->reports_count > 0)
                                            <button name="action" value="clear_reports" class="text-xs px-2.5 py-1 bg-slate-50 text-slate-600 hover:bg-slate-100 rounded-lg font-bold">Clear reports</button>
                                        @endif
                                    </form>
                                    <form action="{{ route('admin.mosques.destroy', $mosque) }}" method="POST"
                                          onsubmit="return confirm('Delete {{ addslashes($mosque->name) }}? Favourites and contribution history for it are removed too. This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg font-bold">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400 font-medium">
                                No mosques match this view.
                                @if($activeFilterCount > 0)
                                    <a href="{{ route('admin.mosques.index', ['filter' => $filter]) }}" class="text-emerald-600 font-bold hover:underline">Reset filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mosques->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $mosques->links() }}
            </div>
        @endif
    </div>
    <p class="mt-3 text-[11px] text-slate-400">
        Entries marked OSM include data {{ \App\Models\Mosque::OSM_ATTRIBUTION }},
        available under the <a href="{{ \App\Models\Mosque::OSM_COPYRIGHT_URL }}" target="_blank" rel="noopener" class="underline">Open Database License</a>.
    </p>
</div>

<script>
(function () {
    const form = document.getElementById('bulk-form');
    if (!form) return;

    const rows = Array.from(document.querySelectorAll('.bulk-row'));
    const pageBox = document.getElementById('bulk-page');
    const allMatching = document.getElementById('bulk-all-matching');
    const countEl = document.getElementById('bulk-count');
    const offer = document.getElementById('bulk-offer');
    const allNote = document.getElementById('bulk-all-note');
    const selectAllBtn = document.getElementById('bulk-select-all');
    const clearBtn = document.getElementById('bulk-clear');
    const action = document.getElementById('bulk-action');
    const apply = document.getElementById('bulk-apply');
    const valueBox = document.getElementById('bulk-value');
    const total = Number(form.dataset.total);

    // Actions that need a typed value, and what to suggest for it.
    const valueActions = {
        set_city: { placeholder: 'New city name', list: 'bulk-city-names', max: 120, label: 'city' },
        set_country: { placeholder: 'New country name', list: 'bulk-country-names', max: 80, label: 'country' },
    };

    const ticked = () => rows.filter((r) => r.checked).length;
    const selected = () => (allMatching.value === '1' ? total : ticked());
    const needsValue = () => Object.prototype.hasOwnProperty.call(valueActions, action.value);

    function refresh() {
        const n = ticked();
        if (n < rows.length) allMatching.value = '0';
        pageBox.checked = rows.length > 0 && n === rows.length;
        pageBox.indeterminate = n > 0 && n < rows.length;
        offer.hidden = !(n === rows.length && rows.length > 0 && total > rows.length && allMatching.value !== '1');
        allNote.hidden = allMatching.value !== '1';
        const count = selected();
        countEl.textContent = count > 0
            ? count + (count === 1 ? ' mosque selected' : ' mosques selected')
            : 'Tick mosques to act on several at once';

        const spec = valueActions[action.value];
        valueBox.hidden = !spec;
        valueBox.required = Boolean(spec);
        if (spec) {
            valueBox.placeholder = spec.placeholder;
            valueBox.maxLength = spec.max;
            valueBox.setAttribute('list', spec.list);
        }
        apply.disabled = count === 0 || action.value === '' || (needsValue() && valueBox.value.trim() === '');
    }

    rows.forEach((r) => r.addEventListener('change', refresh));
    action.addEventListener('change', () => {
        refresh();
        if (needsValue()) valueBox.focus();
    });
    valueBox.addEventListener('input', refresh);
    pageBox.addEventListener('change', () => {
        rows.forEach((r) => { r.checked = pageBox.checked; });
        allMatching.value = '0';
        refresh();
    });
    if (selectAllBtn) selectAllBtn.addEventListener('click', () => { allMatching.value = '1'; refresh(); });
    clearBtn.addEventListener('click', () => {
        allMatching.value = '0';
        rows.forEach((r) => { r.checked = false; });
        refresh();
    });

    form.addEventListener('submit', (event) => {
        const n = selected();
        const label = action.options[action.selectedIndex].text;
        const noun = n === 1 ? 'mosque' : 'mosques';
        const value = valueBox.value.trim();
        let message;
        if (action.value === 'delete') {
            message = 'Permanently delete ' + n + ' ' + noun + '? Their favourites and history are removed too. This cannot be undone.';
        } else if (needsValue()) {
            message = 'Set the ' + valueActions[action.value].label + ' to “' + value + '” on ' + n + ' ' + noun + '?';
        } else {
            message = label + ' — apply to ' + n + ' ' + noun + '?';
        }
        if (n === 0 || action.value === '' || (needsValue() && value === '') || !window.confirm(message)) event.preventDefault();
    });

    refresh();
})();
</script>
@endsection
