@extends('layouts.app')

@section('title', 'Admin Panel - Send Announcement & Push Notifications')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Admin Dashboard</h1>
            <p class="text-slate-500 mt-1">Platform management, notifications, and user outreach.</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl">
                📲
            </div>
            <div>
                <p class="text-xs font-black uppercase text-slate-400">Mobile Devices</p>
                <p class="text-2xl font-black text-slate-800">{{ number_format($activeDevicesCount) }}</p>
                <p class="text-[11px] text-emerald-600 font-bold">Ready for push alerts</p>
            </div>
        </div>

        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl">
                ✉️
            </div>
            <div>
                <p class="text-xs font-black uppercase text-slate-400">Registered Users</p>
                <p class="text-2xl font-black text-slate-800">{{ number_format($usersCount) }}</p>
                <p class="text-[11px] text-blue-600 font-bold">Email recipients</p>
            </div>
        </div>

        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl">
                📢
            </div>
            <div>
                <p class="text-xs font-black uppercase text-slate-400">Total Broadcasts</p>
                <p class="text-2xl font-black text-slate-800">{{ number_format($recentAnnouncements->total()) }}</p>
                <p class="text-[11px] text-purple-600 font-bold">Published to date</p>
            </div>
        </div>
    </div>

    {{-- Main Compose Card --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-2xl">📢</span>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Compose & Dispatch Notification</h2>
                <p class="text-xs text-slate-500 font-medium">Broadcast announcements directly to mobile app devices and user emails.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm font-bold mb-6 flex items-center gap-3">
                <span class="text-lg">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-bold mb-6 flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <span>{{ session('error') }}</span>
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
                <label class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Announcement / Notification Title</label>
                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="e.g. New Surah Study Tools Available!" required
                    class="w-full p-4 rounded-xl border-2 border-slate-100 bg-white font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all text-sm shadow-sm" />
            </div>

            <div class="space-y-2">
                <label class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Message Content</label>
                <textarea name="content" rows="6" placeholder="Type the message to be received by app users and email subscribers..." required
                    class="w-full p-4 rounded-xl border-2 border-slate-100 bg-white font-medium text-slate-800 focus:border-emerald-500 outline-none transition-all text-sm leading-relaxed shadow-sm">{{ old('content') }}</textarea>
            </div>

            {{-- Channel Selectors --}}
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <p class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Delivery Channels</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 p-3 rounded-lg border bg-white border-emerald-200 cursor-pointer hover:bg-emerald-50/50 transition">
                        <input type="checkbox" name="send_push" value="1" {{ old('send_push', '1') == '1' ? 'checked' : '' }}
                            class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500 border-slate-300">
                        <div>
                            <span class="text-sm font-bold text-slate-800 block">📲 Send Push to Mobile App</span>
                            <span class="text-xs text-slate-500">Alerts Android app users instantly in the notification shade & app</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border bg-white border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                        <input type="checkbox" name="send_email" value="1" {{ old('send_email') ? 'checked' : '' }}
                            class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500 border-slate-300">
                        <div>
                            <span class="text-sm font-bold text-slate-800 block">✉️ Send Email to Registered Users</span>
                            <span class="text-xs text-slate-500">Sends formatted email to registered users</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/30 hover:-translate-y-0.5 flex items-center gap-2">
                    <span>Send Notification & Broadcast</span>
                    <span>🚀</span>
                </button>
            </div>
        </form>
    </div>

    {{-- History Table --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-8">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span>📜</span>
            <span>Broadcast History & Past Notifications</span>
        </h3>

        @if($recentAnnouncements->isEmpty())
            <div class="text-center py-12 text-slate-400">
                <span class="text-4xl block mb-2">📭</span>
                <p class="font-medium text-sm">No notifications or announcements sent yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                            <th class="py-3 px-4">Title / Subject</th>
                            <th class="py-3 px-4">Message Snippet</th>
                            <th class="py-3 px-4">Channels</th>
                            <th class="py-3 px-4">Status / Devices</th>
                            <th class="py-3 px-4">Date Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($recentAnnouncements as $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-4 font-bold text-slate-800">
                                    {{ $item->title }}
                                </td>
                                <td class="py-4 px-4 text-slate-600 max-w-xs truncate">
                                    {{ Str::limit($item->message, 80) }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        @if($item->send_push)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800">📲 App</span>
                                        @endif
                                        @if($item->send_email)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-800">✉️ Email</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-xs font-bold text-slate-700">
                                    @if($item->send_push)
                                        <span class="text-emerald-600">✅ {{ $item->delivered_devices_count }} device(s)</span>
                                    @else
                                        <span class="text-slate-400">Email only</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-xs text-slate-500">
                                    {{ $item->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $recentAnnouncements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
