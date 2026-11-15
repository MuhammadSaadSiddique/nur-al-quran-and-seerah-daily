@extends('layouts.app')
@use('App\Models\Mosque')
@use('App\Models\MosqueContribution')

@section('title', 'Admin Panel - Edit Mosque')

@section('content')
@php
    $backUrl = $returnTo ?? route('admin.mosques.index');
    $facilityLabels = [
        'wheelchair_entrance' => 'Step-free (wheelchair) entrance',
        'accessible_wudu' => 'Accessible wudu & toilet',
        'prayer_chairs' => 'Chairs for seated prayer',
        'women_wudu' => "Women's wudu area",
        'wudu_area' => 'Wudu area',
        'toilets' => 'Toilets',
        'car_parking' => 'Car parking',
        'bike_parking' => 'Motorbike parking',
        'air_conditioning' => 'Air-conditioned',
    ];
    $languageLabels = [
        'ar' => 'Arabic', 'ur' => 'Urdu', 'en' => 'English', 'bn' => 'Bengali', 'pa' => 'Punjabi',
        'ps' => 'Pashto', 'sd' => 'Sindhi', 'fa' => 'Persian', 'tr' => 'Turkish', 'ms' => 'Malay',
        'id' => 'Indonesian', 'fr' => 'French', 'so' => 'Somali',
    ];
    $timeFields = [
        'fajr_time' => 'Fajr', 'dhuhr_time' => 'Dhuhr', 'asr_time' => 'Asr', 'maghrib_time' => 'Maghrib (fixed)',
        'isha_time' => 'Isha', 'jumuah_time' => "Jumu'ah", 'jumuah_time_2' => "Second Jumu'ah",
    ];
    $historyLabels = [
        MosqueContribution::TYPE_ADDED => 'Added the mosque',
        MosqueContribution::TYPE_TIMES_UPDATED => 'Updated times',
        MosqueContribution::TYPE_DETAILS_UPDATED => 'Updated details',
        MosqueContribution::TYPE_CONFIRMED => 'Confirmed times',
        MosqueContribution::TYPE_REPORTED => 'Reported',
        MosqueContribution::TYPE_SECT_PROPOSED => 'Proposed school of thought',
    ];

    // Tri-state values as the form uses them: "1", "0" or "" (not known).
    $tri = fn ($value) => $value === null ? '' : ($value ? '1' : '0');
    $facilities = $mosque->facilityMap();
    $languages = old('khutbah_languages', $mosque->khutbah_languages ?? []);
    $languages = is_array($languages) ? $languages : array_filter(explode(',', (string) $languages));

    $eid = is_array($mosque->eid_times) ? $mosque->eid_times : [];
    $thisYear = (int) now()->year;
    $storedEidYear = $eid['fitr']['year'] ?? $eid['adha']['year'] ?? null;
    $eidYears = array_values(array_unique(array_filter([$thisYear, $thisYear + 1, $storedEidYear])));
    sort($eidYears);

    $input = 'w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500';
    $label = 'block text-xs font-bold text-slate-600 mb-1';
    $card = 'bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5 space-y-4';
    $cardTitle = 'text-base font-extrabold text-slate-800';
@endphp
<div class="max-w-5xl mx-auto animate-fadeIn space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ $backUrl }}" class="hover:text-emerald-600 transition-colors">Mosque Directory</a>
                <span>/</span>
                <span>Edit #{{ $mosque->id }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $mosque->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="https://www.google.com/maps/search/?api=1&query={{ $mosque->latitude }},{{ $mosque->longitude }}" target="_blank" rel="noopener"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">View on map ↗</a>
            <a href="{{ $backUrl }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">← Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium">{{ session('success') }}</div>
    @endif

    {{-- Provenance --}}
    <div class="bg-slate-900 text-white rounded-2xl p-5 shadow-sm grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Added by</div>
            @if($mosque->source === Mosque::SOURCE_OSM)
                <div class="font-semibold">OpenStreetMap import</div>
            @else
                <div class="font-semibold">{{ $mosque->creator?->name ?? 'deleted user' }}</div>
            @endif
            @if($mosque->osm_id)
                <a href="{{ $mosque->osmUrl() }}" target="_blank" rel="noopener" class="text-sky-300 text-xs font-semibold hover:underline">
                    OSM {{ $mosque->osm_type ?? 'feature' }} {{ $mosque->osm_id }} ↗
                </a>
            @endif
            <div class="text-slate-400 text-xs">{{ $mosque->created_at?->format('M d, Y') }} · {{ $mosque->created_at?->diffForHumans() }}</div>
        </div>
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Times last updated</div>
            @if($mosque->times_updated_at)
                <div class="font-semibold">{{ $mosque->times_updated_at->diffForHumans() }}</div>
                <div class="text-slate-400 text-xs">by {{ $mosque->timesUpdater?->name ?? 'deleted user' }}</div>
            @else
                <div class="font-semibold">Never</div>
            @endif
        </div>
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Trust</div>
            <div class="font-semibold">{{ $mosque->confirmations_count }} confirmations</div>
            <div class="text-slate-400 text-xs">{{ $mosque->favorites()->distinct()->count('user_id') }} regulars</div>
        </div>
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</div>
            <div class="flex flex-wrap gap-1 mt-0.5">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $mosque->status === Mosque::STATUS_HIDDEN ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300' }}">{{ $mosque->status === Mosque::STATUS_HIDDEN ? 'Hidden' : 'Live' }}</span>
                @if($mosque->is_verified)
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">✓ Verified</span>
                @endif
                @if($mosque->reports_count > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300">{{ $mosque->reports_count }} reports</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Pending sect proposal: decided here or by changing the field below --}}
    @if($mosque->pending_sect)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="text-sm">
                <div class="font-bold">School of thought change waiting for review</div>
                <div>{{ Mosque::sectLabel($mosque->sect) }} → <span class="font-bold">{{ Mosque::sectLabel($mosque->pending_sect) }}</span></div>
                <div class="text-amber-700 text-xs">Proposed by {{ $mosque->pendingSectBy?->name ?? 'deleted user' }} · {{ $mosque->pending_sect_at?->diffForHumans() }}</div>
            </div>
            <form action="{{ route('admin.mosques.moderate', $mosque) }}" method="POST" class="flex gap-2">
                @csrf
                @method('PATCH')
                <button name="action" value="approve_sect" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs">Approve</button>
                <button name="action" value="reject_sect" class="px-4 py-2 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-bold rounded-xl text-xs">Reject</button>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">
            <p class="font-bold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.mosques.update', $mosque) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @if($returnTo)
            <input type="hidden" name="return" value="{{ $returnTo }}">
        @endif

        {{-- Identity & location --}}
        <section class="{{ $card }}">
            <h2 class="{{ $cardTitle }}">Name & location</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="name">Mosque name *</label>
                    <input id="name" name="name" value="{{ old('name', $mosque->name) }}" required minlength="3" maxlength="150" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="locality">Area / neighbourhood</label>
                    <input id="locality" name="locality" value="{{ old('locality', $mosque->locality) }}" maxlength="120" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $mosque->city) }}" maxlength="120" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="address">Street address or landmark</label>
                    <input id="address" name="address" value="{{ old('address', $mosque->address) }}" maxlength="255" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="country">Country</label>
                    <input id="country" name="country" value="{{ old('country', $mosque->country) }}" maxlength="80" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="latitude">Latitude *</label>
                    <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $mosque->latitude) }}" required class="{{ $input }} font-mono">
                </div>
                <div>
                    <label class="{{ $label }}" for="longitude">Longitude *</label>
                    <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $mosque->longitude) }}" required class="{{ $input }} font-mono">
                </div>
            </div>
            <p class="text-xs text-slate-500">Tip: in Google Maps, long-press the building and copy the two numbers shown.</p>
        </section>

        {{-- Status --}}
        <section class="{{ $card }}">
            <h2 class="{{ $cardTitle }}">Visibility & trust</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="{{ $label }}" for="status">Status</label>
                    <select id="status" name="status" class="{{ $input }}">
                        <option value="{{ Mosque::STATUS_ACTIVE }}" @selected(old('status', $mosque->status) === Mosque::STATUS_ACTIVE)>Live in the app</option>
                        <option value="{{ Mosque::STATUS_HIDDEN }}" @selected(old('status', $mosque->status) === Mosque::STATUS_HIDDEN)>Hidden</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 pb-2">
                    <input type="hidden" name="is_verified" value="0">
                    <input type="checkbox" name="is_verified" value="1" @checked((string) old('is_verified', $mosque->is_verified ? '1' : '0') === '1') class="rounded text-emerald-600">
                    Verified mosque (trusted; not auto-hidden by reports)
                </label>
            </div>
        </section>

        {{-- Place & school of thought --}}
        <section class="{{ $card }}">
            <h2 class="{{ $cardTitle }}">Place & school of thought</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}" for="place_type">Type of place</label>
                    <select id="place_type" name="place_type" class="{{ $input }}">
                        <option value="masjid" @selected(old('place_type', $mosque->place_type) === 'masjid')>Masjid</option>
                        <option value="musalla" @selected(old('place_type', $mosque->place_type) === 'musalla')>Musalla / prayer room</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="sect">School of thought</label>
                    <select id="sect" name="sect" class="{{ $input }}">
                        <option value="" @selected(old('sect', $mosque->sect) === null || old('sect', $mosque->sect) === '')>Not specified</option>
                        @foreach(Mosque::SECTS as $sect)
                            <option value="{{ $sect }}" @selected(old('sect', $mosque->sect) === $sect)>{{ Mosque::sectLabel($sect) }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Changing this settles any pending proposal.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="asr_method">Asr timing at this mosque</label>
                    <select id="asr_method" name="asr_method" class="{{ $input }}">
                        <option value="" @selected(old('asr_method', $mosque->asr_method) === null || old('asr_method', $mosque->asr_method) === '')>Not known</option>
                        <option value="hanafi" @selected(old('asr_method', $mosque->asr_method) === 'hanafi')>Hanafi (later)</option>
                        <option value="standard" @selected(old('asr_method', $mosque->asr_method) === 'standard')>Standard</option>
                    </select>
                </div>
            </div>
        </section>

        {{-- Times --}}
        <section class="{{ $card }}">
            <div class="flex items-center justify-between">
                <h2 class="{{ $cardTitle }}">Jama'ah times</h2>
                <span class="text-xs text-slate-500">Local time, 24-hour. Leave blank if not known.</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($timeFields as $field => $fieldLabel)
                    <div>
                        <label class="{{ $label }}" for="{{ $field }}">{{ $fieldLabel }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="time" value="{{ old($field, $mosque->{$field}) }}" class="{{ $input }} font-mono">
                    </div>
                @endforeach
                <div>
                    <label class="{{ $label }}" for="maghrib_offset_minutes">Maghrib: minutes after adhan</label>
                    <input id="maghrib_offset_minutes" name="maghrib_offset_minutes" type="number" min="0" max="90" value="{{ old('maghrib_offset_minutes', $mosque->maghrib_offset_minutes) }}" class="{{ $input }}">
                </div>
            </div>
            <p class="text-xs text-slate-500">If "minutes after adhan" is set, it replaces the fixed Maghrib time (it stays right as sunset moves). Changing any time resets confirmations.</p>
        </section>

        {{-- Accessibility & facilities --}}
        <section class="{{ $card }}">
            <h2 class="{{ $cardTitle }}">Accessibility & facilities</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                <div class="flex items-center justify-between gap-3">
                    <label class="text-sm font-semibold text-slate-700" for="has_women_area">Prayer area for women</label>
                    <select id="has_women_area" name="has_women_area" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-sm">
                        @foreach(['' => 'Not sure', '1' => 'Yes', '0' => 'No'] as $value => $text)
                            <option value="{{ $value }}" @selected((string) old('has_women_area', $tri($mosque->has_women_area)) === (string) $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach($facilityLabels as $key => $facilityLabel)
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-sm font-semibold text-slate-700" for="facility_{{ $key }}">{{ $facilityLabel }}</label>
                        <select id="facility_{{ $key }}" name="facilities[{{ $key }}]" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-sm">
                            @foreach(['' => 'Not sure', '1' => 'Yes', '0' => 'No'] as $value => $text)
                                <option value="{{ $value }}" @selected((string) old("facilities.$key", $tri($facilities[$key] ?? null)) === (string) $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Jumu'ah khutbah --}}
        <section class="{{ $card }}">
            <div class="flex items-center justify-between">
                <h2 class="{{ $cardTitle }}">Jumu'ah khutbah language</h2>
                <span class="text-xs text-slate-500">Up to 4</span>
            </div>
            {{-- Sent even when nothing is ticked, so unticking everything clears the list. --}}
            <input type="hidden" name="khutbah_languages" value="">
            <div class="flex flex-wrap gap-2">
                @foreach($languageLabels as $code => $languageLabel)
                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-sm cursor-pointer has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-400">
                        <input type="checkbox" name="khutbah_languages[]" value="{{ $code }}" @checked(in_array($code, $languages, true)) class="rounded text-emerald-600">
                        {{ $languageLabel }}
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Ramadan & Eid --}}
        <section class="{{ $card }}">
            <h2 class="{{ $cardTitle }}">Ramadan & Eid</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}" for="taraweeh_rakats">Taraweeh rakats</label>
                    <select id="taraweeh_rakats" name="taraweeh_rakats" class="{{ $input }}">
                        <option value="" @selected((string) old('taraweeh_rakats', $mosque->taraweeh_rakats) === '')>Not known / none</option>
                        @foreach(Mosque::TARAWEEH_RAKATS as $rakats)
                            <option value="{{ $rakats }}" @selected((string) old('taraweeh_rakats', $mosque->taraweeh_rakats) === (string) $rakats)>{{ $rakats }} rakats</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="taraweeh_khatm">Whole Quran completed</label>
                    <select id="taraweeh_khatm" name="taraweeh_khatm" class="{{ $input }}">
                        @foreach(['' => 'Not sure', '1' => 'Yes', '0' => 'No'] as $value => $text)
                            <option value="{{ $value }}" @selected((string) old('taraweeh_khatm', $tri($mosque->taraweeh_khatm)) === (string) $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="eid_year">Eid times are for</label>
                    <select id="eid_year" name="eid_year" class="{{ $input }}">
                        @foreach($eidYears as $year)
                            <option value="{{ $year }}" @selected((string) old('eid_year', $storedEidYear ?? $thisYear) === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}" for="eid_fitr_times">Eid ul-Fitr jama'ah times</label>
                        <input id="eid_fitr_times" name="eid_fitr_times" placeholder="07:00, 08:30" value="{{ old('eid_fitr_times', implode(', ', $eid['fitr']['times'] ?? [])) }}" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="eid_adha_times">Eid ul-Adha jama'ah times</label>
                        <input id="eid_adha_times" name="eid_adha_times" placeholder="06:45, 08:00" value="{{ old('eid_adha_times', implode(', ', $eid['adha']['times'] ?? [])) }}" class="{{ $input }} font-mono">
                    </div>
                </div>
            </div>
            <p class="text-xs text-slate-500">Up to 4 times each, 24-hour, separated by commas. Clear a box to remove that Eid's times.</p>
        </section>

        <div class="flex items-center justify-between pt-2">
            <span class="text-xs text-slate-500">Your edit is saved to this mosque's history.</span>
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">Cancel</a>
                <button type="submit" class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-lg hover:shadow-emerald-500/30 transition-all">Save changes</button>
            </div>
        </div>
    </form>

    {{-- History --}}
    <section class="{{ $card }}">
        <h2 class="{{ $cardTitle }}">Recent history</h2>
        @forelse($history as $entry)
            <div class="flex items-start justify-between gap-3 text-sm border-b border-slate-100 pb-2 last:border-0">
                <div>
                    @if($entry->user_id === null && ($entry->payload['source'] ?? null) === 'openstreetmap')
                        <span class="font-semibold text-slate-800">OpenStreetMap</span>
                        <span class="text-slate-600">· Imported</span>
                    @else
                        <span class="font-semibold text-slate-800">{{ $entry->user?->name ?? 'deleted user' }}</span>
                        <span class="text-slate-600">· {{ $historyLabels[$entry->type] ?? $entry->type }}</span>
                    @endif
                    @if(!empty($entry->payload['by_admin']))
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">admin</span>
                    @endif
                    @if($entry->type === MosqueContribution::TYPE_REPORTED)
                        <span class="text-rose-600">({{ str_replace('_', ' ', $entry->payload['reason'] ?? 'other') }})</span>
                    @endif
                    @if($entry->type === MosqueContribution::TYPE_SECT_PROPOSED)
                        <span class="text-slate-500">({{ Mosque::sectLabel($entry->payload['from'] ?? null) }} → {{ Mosque::sectLabel($entry->payload['to'] ?? null) }})</span>
                    @endif
                    @if($entry->type === MosqueContribution::TYPE_DETAILS_UPDATED && !empty($entry->payload['after']))
                        <span class="text-slate-500">— {{ implode(', ', array_map(fn ($k) => str_replace('_', ' ', $k), array_keys($entry->payload['after']))) }}</span>
                    @endif
                    @if($entry->note)
                        <div class="text-slate-500 italic text-xs">“{{ $entry->note }}”</div>
                    @endif
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap">{{ $entry->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">No activity yet.</p>
        @endforelse
    </section>

    @if($mosque->usesOsmData())
        <p class="text-[11px] text-slate-400">
            Includes data {{ Mosque::OSM_ATTRIBUTION }}, available under the
            <a href="{{ Mosque::OSM_COPYRIGHT_URL }}" target="_blank" rel="noopener" class="underline">Open Database License</a>.
            Deleting it stops future OpenStreetMap imports from adding it again.
        </p>
    @endif

    {{-- Danger zone --}}
    <section class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-rose-900">
            <div class="font-bold">Delete this mosque</div>
            <div class="text-rose-700">Removes it from the app along with everyone's favourites and its history. This cannot be undone. Hiding is usually enough.</div>
        </div>
        <form action="{{ route('admin.mosques.destroy', $mosque) }}" method="POST"
              onsubmit="return confirm('Delete {{ addslashes($mosque->name) }}? Favourites and contribution history for it are removed too. This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-sm">Delete mosque</button>
        </form>
    </section>
</div>
@endsection
