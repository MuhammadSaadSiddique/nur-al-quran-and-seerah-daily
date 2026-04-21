@extends('layouts.app')
@section('title', 'Terms of Service - The Eternal Echo')

@section('content')
<div class="max-w-4xl mx-auto space-y-12 pb-20 animate-fadeIn">
    <div class="text-center space-y-4">
        <h2 class="text-3xl font-extrabold text-slate-900 md:text-5xl">Terms of Service</h2>
        <p class="text-slate-500 font-medium">Agreement Updated: {{ date('F d, Y') }}</p>
    </div>

    <div class="bg-white rounded-[3rem] p-10 md:p-16 shadow-xl border border-slate-100 space-y-8 text-slate-700 leading-relaxed text-sm">
        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">1. Agreement to Terms</h3>
            <p>These Terms of Service constitute a legally binding agreement made between you, whether personally or on behalf of an entity (“you”) and The Eternal Echo (“we,” “us” or “our”), concerning your access to and use of our application.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">2. User Representations</h3>
            <p>By using the App, you represent and warrant that:</p>
            <ul class="list-disc pl-6 space-y-2">
                <li>All registration information you submit will be true, accurate, current, and complete.</li>
                <li>You will maintain the accuracy of such information and promptly update such registration information as necessary.</li>
                <li>You have the legal capacity and you agree to comply with these Terms of Service.</li>
                <li>You will not use the App for any illegal or unauthorized purpose.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">3. Prohibited Activities</h3>
            <p>You may not access or use the App for any purpose other than that for which we make the App available. The App may not be used in connection with any commercial endeavors except those that are specifically endorsed or approved by us.</p>
            <p>Specifically, you agree not to use AI generation features to generate content that is disrespectful of Islamic values, inaccurate, or harmful.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">4. Intellectual Property Rights</h3>
            <p>Unless otherwise indicated, the App is our proprietary property and all source code, databases, functionality, software, website designs, audio, video, text, photographs, and graphics on the App (collectively, the “Content”) and the trademarks, service marks, and logos contained therein (the “Marks”) are owned or controlled by us or licensed to us.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">5. AI Content Disclaimer</h3>
            <p>The Eternal Echo uses AI to generate educational content. While we strive for accuracy, AI models may occasionally produce incorrect information. These resources are intended as a study aid and should be cross-referenced with established scholarly Islamic resources.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">6. Limitation of Liability</h3>
            <p>In no event will we or our directors, employees, or agents be liable to you or any third party for any direct, indirect, consequential, exemplary, incidental, special, or punitive damages arising from your use of the App.</p>
        </section>

        <section class="space-y-4">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">7. Modifications and Interruptions</h3>
            <p>We reserve the right to change, modify, or remove the contents of the App at any time or for any reason at our sole discretion without notice.</p>
        </section>
    </div>
</div>
@endsection
