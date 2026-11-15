{{-- Ranked horizontal bars: one hue, largest first. Rows: label, icon?, value, meta?, filter? --}}
@php
    $rowMax = max(1, (int) collect($rows)->max('value'));
    $rowTotal = max(1, (int) ($total ?? collect($rows)->sum('value')));
@endphp
<div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5">
    <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-sm font-bold text-slate-800">{{ $title }}</h2>
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Visits</span>
    </div>
    @if(collect($rows)->isEmpty())
        <p class="text-xs text-slate-400 py-6 text-center">{{ $empty ?? 'No data yet.' }}</p>
    @else
        <ul class="space-y-3">
            @foreach($rows as $row)
                <li>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <div class="min-w-0 flex items-center gap-1.5">
                            @if(!empty($row['icon']))
                                <span class="text-base leading-none" aria-hidden="true">{{ $row['icon'] }}</span>
                            @endif
                            @if(!empty($row['filter']))
                                <a href="{{ $filterUrl($row['filter']) }}" class="font-semibold text-slate-800 hover:text-emerald-700 hover:underline truncate" title="Show only {{ $row['label'] }}">{{ $row['label'] }}</a>
                            @else
                                <span class="font-semibold text-slate-800 truncate">{{ $row['label'] }}</span>
                            @endif
                            @if(!empty($row['meta']))
                                <span class="text-slate-400 truncate hidden sm:inline">{{ $row['meta'] }}</span>
                            @endif
                        </div>
                        <div class="shrink-0 tabular-nums text-slate-700">
                            <span class="font-bold">{{ number_format($row['value']) }}</span>
                            <span class="text-slate-400 ml-1">{{ round(100 * $row['value'] / $rowTotal) }}%</span>
                        </div>
                    </div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-emerald-50" aria-hidden="true">
                        <div class="h-1.5 rounded-full bg-emerald-600" style="width: max(2px, {{ round(100 * $row['value'] / $rowMax, 2) }}%)"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
