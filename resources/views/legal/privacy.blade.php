@extends('layouts.app')
@section('title', 'Privacy Policy - The Eternal Echo')

@section('content')
<div class="max-w-4xl mx-auto space-y-12 pb-20 animate-fadeIn">
    <div class="text-center space-y-4">
        <h2 class="text-3xl font-extrabold text-slate-900 md:text-5xl">Privacy Policy</h2>
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
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">3. How We Use Your Information</h3>
            <p>We use personal information collected via our App for a variety of business purposes described below:</p>
            <ul class="list-disc pl-6 space-y-2">
                <li>To facilitate account creation and logon process.</li>
                <li>To provide you with the learning services (quizzes, insights).</li>
                <li>To display your global ranking on the platform leaderboard.</li>
                <li>To improve our AI-powered question generation and content delivery.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">4. Will Your Information Be Shared With Anyone?</h3>
            <p>We only share information with your consent, to comply with laws, to provide you with services, to protect your rights, or to fulfill business obligations. Your leaderboard data (display name and scores) is visible to other registered members of the platform.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">5. Security of Your Information</h3>
            <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900">6. Contact Us</h3>
            <p>If you have questions or comments about this policy, you may email us or contact us through our project website asloobulhayat.com.</p>
        </section>
    </div>
</div>
@endsection
