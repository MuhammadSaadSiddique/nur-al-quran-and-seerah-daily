<div class="flex space-x-4 mb-8 border-b border-slate-200 overflow-x-auto custom-scrollbar whitespace-nowrap">
    <a href="{{ route('admin.questions.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.questions.*') && !request()->routeIs('admin.feedback.*') && !request()->routeIs('admin.duplicates') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Manage Questions</a>
    <a href="{{ route('admin.users.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.users.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">User Statistics</a>
    <a href="{{ route('admin.themes.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.themes.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Manage Themes</a>
    <a href="{{ route('admin.testimonials.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.testimonials.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Testimonials</a>
    <a href="{{ route('admin.feedback.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.feedback.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Feedback</a>
    <a href="{{ route('admin.duplicates') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.duplicates') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Duplicates</a>
    <a href="{{ route('admin.announcements.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.announcements.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Send Announcements</a>
    @php
        $pendingDuasBadge = \App\Models\Dua::pending()->count();
    @endphp
    <a href="{{ route('admin.duas.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm inline-flex items-center space-x-1.5 {{ request()->routeIs('admin.duas.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
        <span>🤲 Review & Manage Duas</span>
        @if($pendingDuasBadge > 0)
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-black leading-none text-amber-900 bg-amber-200 rounded-full">{{ $pendingDuasBadge }} pending</span>
        @endif
    </a>
    <a href="{{ route('admin.categories.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm inline-flex items-center space-x-1.5 {{ request()->routeIs('admin.categories.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
        <span>🏷️ Manage Categories</span>
    </a>
</div>
