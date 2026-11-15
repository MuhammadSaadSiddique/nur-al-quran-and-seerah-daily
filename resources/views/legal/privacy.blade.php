@extends('layouts.app')
@section('title', 'Privacy Policy - The Eternal Echo')
@section('meta_description', 'Privacy Policy for The Eternal Echo platform. Learn how we handle your personal data, quiz progression, and ensure transparency.')
@section('canonical', route('privacy'))

@section('content')
<div class="max-w-4xl mx-auto space-y-12 pb-20 animate-fadeIn">
    <div class="text-center space-y-4">
        <h1 class="text-3xl font-extrabold text-slate-900 md:text-5xl">Privacy Policy</h1>
        <p class="text-slate-500 font-medium">Last Updated: {{ date('F d, Y') }}</p>
    </div>

    <div class="bg-white rounded-[3rem] p-10 md:p-16 shadow-xl border border-slate-100 space-y-8 text-slate-700 leading-relaxed">
        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">1. Introduction</h3>
            <p>Welcome to The Eternal Echo. We are committed to protecting your personal information and your right to privacy. If you have any questions or concerns about our policy, or our practices with regards to your personal information, please contact us.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">2. Information We Collect</h3>
            <p>We collect personal information that you voluntarily provide to us when registering at the App, expressing an interest in obtaining information about us or our products and services, or otherwise contacting us.</p>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Authentication Data:</strong> We collect email addresses and use OTP (One-Time Password) systems or third-party authentication (like Quran.com) to verify your identity.</li>
                <li><strong>Learning Progress:</strong> We track your quiz scores, completed Paras, and Seerah knowledge insights to provide you with personalized learning paths and rankings.</li>
                <li><strong>Usage Data:</strong> We may collect information about how you interact with our platform to improve the user experience.</li>
                <li><strong>Website Visit Statistics:</strong> When you visit our website we count the visit, as described in section 3.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">3. Website Visit Statistics and Cookies</h3>
            <p>To understand how many people use our website, where they are, and how they find us, we record a short summary of each visit. This is only shown to our administrators and is never sold or used for advertising.</p>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>What we record:</strong> the page you arrive on, the last page you view and how many pages you open; the website or app that sent you (for example a search engine or a social network) and the address of the page that linked to us, without anything after a &ldquo;?&rdquo; in that address; campaign tags in the link you followed (such as <code>utm_source</code>); your approximate location (country, region and city); and your device type, browser and operating system. If you are signed in, the visit is also linked to your account.</li>
                @php($geoService = ['ipinfo' => 'ipinfo.io', 'ipapi' => 'ipapi.co'][config('visitors.geo.driver')] ?? null)
                <li><strong>How we find your approximate location:</strong> from your IP address. When our hosting network already provides the country and city, we use that.@if ($geoService) Otherwise we send your IP address to an IP-location service (currently {{ $geoService }}) to look it up.@endif We do not store your IP address.</li>
                <li><strong>Cookies:</strong> we set two cookies of our own. <code>{{ \App\Services\VisitTracker::VISITOR_COOKIE }}</code> holds a random ID so we can tell new visitors from returning ones; it expires after 2 years. <code>{{ \App\Services\VisitTracker::VISIT_COOKIE }}</code> groups the pages you view into one visit; it expires after {{ config('visitors.session_minutes') }} minutes without activity. Neither contains your name or email, and neither is used to follow you on other websites. We also use the essential cookies the site needs to keep you signed in and to protect forms.</li>
                <li><strong>What we don't record:</strong> visits from search-engine crawlers and other bots, and visits by our administrators.</li>
                <li><strong>How long we keep it:</strong> visit records are deleted automatically after {{ config('visitors.retention_days') }} days.</li>
                <li><strong>Your choices:</strong> you can block or delete these cookies in your browser settings, and the website will keep working. Without them, each page you open may be counted as a separate visit.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">4. How We Use Your Information</h3>
            <p>We use personal information collected via our App for a variety of business purposes described below:</p>
            <ul class="list-disc pl-6 space-y-2">
                <li>To facilitate account creation and logon process.</li>
                <li>To provide you with the learning services (quizzes, insights).</li>
                <li>To display your global ranking on the platform leaderboard.</li>
                <li>To improve our AI-powered question generation and content delivery.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">5. Will Your Information Be Shared With Anyone?</h3>
            <p>We only share information with your consent, to comply with laws, to provide you with services, to protect your rights, or to fulfill business obligations. Your leaderboard data (display name and scores) is visible to other registered members of the platform.@if ($geoService) To find a visitor's approximate location we may send their IP address to an IP-location service, as described in section 3.@endif</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">6. Security of Your Information</h3>
            <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">7. Account Deletion and Data Retention</h3>
            <p>You have the absolute right to request the deletion of your account and all associated personal data at any time. In compliance with Google Play Store, Apple App Store developer policies, and privacy regulations (GDPR/CCPA):</p>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>What Is Deleted:</strong> When your deletion request is processed, we permanently erase your user profile, authentication credentials (email and password hashes), quiz responses, progress and scores, leaderboard history, Para mastery, bookmarks, researcher contributions, website visits recorded while you were signed in, and mobile device notification tokens.</li>
                <li><strong>Retention Period:</strong> None. Deletion is instantaneous and permanent upon confirmation. We do not retain residual copies of your personal data.</li>
                <li><strong>How to Request Deletion:</strong> You can initiate account deletion directly from within our mobile application (via your profile/account settings) or through our dedicated web portal: <a href="{{ route('account.delete') }}" class="text-emerald-600 hover:text-emerald-700 underline font-bold">The Eternal Echo Account Deletion Portal</a>.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">8. Contact Us</h3>
            <p>If you have questions or comments about this policy, or need assistance deleting your account, you may email us or contact us through our project website asloobulhayat.com.</p>
        </section>
    </div>
</div>
@endsection
