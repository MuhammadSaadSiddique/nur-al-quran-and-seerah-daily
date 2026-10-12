@extends('layouts.app')
@section('title', 'Delete Account & Data Deletion Request - The Eternal Echo')
@section('meta_description', 'Request permanent deletion of your The Eternal Echo account and all associated personal data in accordance with Google Play and Apple App Store policies.')

@section('content')
<div class="max-w-4xl mx-auto space-y-10 pb-20 animate-fadeIn">
    {{-- Header Banner --}}
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-rose-100 text-rose-600 mb-2 shadow-inner">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900 md:text-5xl">Account & Data Deletion</h1>
        <p class="text-slate-500 font-medium max-w-2xl mx-auto">
            Manage your right to data erasure. You can permanently delete your <span class="font-bold text-slate-700">The Eternal Echo</span> account and purge all associated learning records at any time.
        </p>
    </div>

    {{-- Policy Disclosure Card (Google Play & App Store Compliance) --}}
    <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100 space-y-8 text-slate-700 leading-relaxed">
        <section class="space-y-3">
            <h2 class="text-xl font-black text-slate-900 flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                <span>Data Deletion & Privacy Commitment</span>
            </h2>
            <p>
                In compliance with Google Play Store, Apple App Store developer policies, and global privacy standards (GDPR/CCPA), users of <strong>The Eternal Echo</strong> mobile application and web platform have complete control over their account data.
            </p>
        </section>

        {{-- Grid of What is Deleted vs Retained --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            <div class="bg-rose-50/60 border border-rose-100 rounded-2xl p-6 space-y-3">
                <h3 class="font-black text-rose-800 text-sm uppercase tracking-wider flex items-center space-x-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>What Data Is Permanently Deleted</span>
                </h3>
                <ul class="text-sm space-y-2 text-rose-950 font-medium list-disc pl-5">
                    <li>Account profile (Name, email address, password hash)</li>
                    <li>All quiz submissions, answer histories, and scores</li>
                    <li>Accuracy analytics, Para mastery, and spiritual rankings</li>
                    <li>Personal bookmarked questions and saved Quran notes</li>
                    <li>OAuth tokens linked with Quran.com or third-party providers</li>
                    <li>Push notification tokens and associated device identifiers</li>
                    <li>Feedback entries and researcher contributions submitted by your account</li>
                </ul>
            </div>

            <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-6 space-y-3">
                <h3 class="font-black text-emerald-800 text-sm uppercase tracking-wider flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Data Retention & Timeframe</span>
                </h3>
                <ul class="text-sm space-y-2 text-emerald-950 font-medium list-disc pl-5">
                    <li><strong>Retention Period:</strong> None. Deletion is instantaneous and permanent.</li>
                    <li><strong>No Hidden Backups:</strong> Personal identity and identifiers are completely expunged from the active database.</li>
                    <li><strong>Irreversible:</strong> Once confirmed, deleted accounts and scores cannot be recovered or restored.</li>
                    <li><strong>Mobile App Sync:</strong> You will be immediately logged out of all mobile and web sessions.</li>
                </ul>
            </div>
        </div>

        {{-- Interactive Deletion Actions --}}
        <div class="pt-6 border-t border-slate-100">
            @if(Auth::check())
                {{-- Logged-In User Direct Deletion Flow --}}
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-8 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <span class="text-xs font-black uppercase text-slate-400 tracking-wider">Signed in as</span>
                            <h3 class="text-lg font-black text-slate-900">{{ Auth::user()->name }} ({{ Auth::user()->email }})</h3>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 self-start sm:self-auto">
                            Active Session
                        </span>
                    </div>

                    <form action="{{ route('account.delete.confirm') }}" method="POST" class="space-y-5" onsubmit="return confirm('WARNING: Are you absolutely certain you want to permanently delete your account? This action cannot be reversed.')">
                        @csrf

                        @if(!empty(Auth::user()->password))
                            <div class="space-y-2">
                                <label for="password" class="text-xs font-black uppercase text-slate-600">
                                    Enter Your Password to Confirm Deletion
                                </label>
                                <input type="password" name="password" id="password" required
                                    placeholder="Enter your current password..."
                                    class="w-full p-4 rounded-xl border-2 border-slate-200 font-bold text-slate-800 focus:border-rose-500 outline-none transition-all">
                                @error('password')
                                    <p class="text-rose-600 text-xs font-bold">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div class="flex items-start space-x-3 bg-amber-50 p-4 rounded-xl border border-amber-200">
                                <input type="checkbox" name="confirm_deletion" value="1" id="confirm_deletion" required
                                    class="mt-1 w-5 h-5 rounded border-amber-300 text-rose-600 focus:ring-rose-500">
                                <label for="confirm_deletion" class="text-sm font-bold text-amber-900 cursor-pointer">
                                    I understand and confirm that this action is irreversible. All my quiz history, rankings, and account data will be permanently wiped.
                                </label>
                            </div>
                        @endif

                        <button type="submit"
                            class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-4 px-6 rounded-xl shadow-lg shadow-rose-600/20 transition-all flex items-center justify-center space-x-2 active:scale-[0.99]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Permanently Delete My Account Now</span>
                        </button>
                    </form>
                </div>
            @else
                {{-- Guest / External Visitor Flow (Directly from App Store or Web) via OTP --}}
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-8 space-y-6" id="otpDeletionContainer">
                    <div class="space-y-2">
                        <h3 class="text-lg font-black text-slate-900">Request Deletion via Registered Email</h3>
                        <p class="text-sm text-slate-600">
                            If you do not have the app open or are not signed in, enter your registered email address below. We will send a secure verification code to verify ownership before permanently wiping your account data.
                        </p>
                    </div>

                    {{-- Step 1: Request OTP --}}
                    <div id="emailStep" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-600">Your Registered Email Address</label>
                            <input type="email" id="deleteEmail" placeholder="you@example.com"
                                class="w-full p-4 rounded-xl border-2 border-slate-200 font-bold text-slate-800 focus:border-rose-500 outline-none transition-all">
                        </div>
                        <button type="button" id="btnRequestOtp"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white font-black py-4 px-6 rounded-xl transition-all flex items-center justify-center space-x-2">
                            <span>Send Verification Code</span>
                        </button>
                    </div>

                    {{-- Step 2: Verify OTP & Confirm Deletion (Initially Hidden) --}}
                    <div id="otpStep" class="space-y-4 hidden">
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm font-bold" id="otpNotice">
                            Verification code sent to your email.
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-600">Enter 6-Digit Verification Code</label>
                            <input type="text" id="deleteOtp" maxlength="6" placeholder="123456"
                                class="w-full p-4 rounded-xl border-2 border-slate-200 font-bold text-slate-800 tracking-widest text-center text-xl focus:border-rose-500 outline-none transition-all">
                        </div>
                        <button type="button" id="btnConfirmDeleteOtp"
                            class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-4 px-6 rounded-xl shadow-lg shadow-rose-600/20 transition-all flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Verify Code & Permanently Delete Account</span>
                        </button>
                        <button type="button" id="btnBackToEmail" class="w-full text-slate-500 hover:text-slate-700 font-bold text-xs text-center pt-2">
                            ← Enter a different email
                        </button>
                    </div>

                    {{-- Status / Error Feedback --}}
                    <div id="deletionFeedback" class="hidden p-4 rounded-xl font-bold text-sm"></div>

                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500">
                            Already logged in on another device or prefer to sign in first?
                            <a href="{{ route('login') }}" class="text-emerald-600 hover:underline font-bold">Sign In Here</a>
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailStep = document.getElementById('emailStep');
    const otpStep = document.getElementById('otpStep');
    const deleteEmailInput = document.getElementById('deleteEmail');
    const deleteOtpInput = document.getElementById('deleteOtp');
    const btnRequestOtp = document.getElementById('btnRequestOtp');
    const btnConfirmDeleteOtp = document.getElementById('btnConfirmDeleteOtp');
    const btnBackToEmail = document.getElementById('btnBackToEmail');
    const deletionFeedback = document.getElementById('deletionFeedback');
    const otpNotice = document.getElementById('otpNotice');

    function showFeedback(message, isError) {
        if (!deletionFeedback) return;
        deletionFeedback.classList.remove('hidden', 'bg-rose-100', 'text-rose-700', 'border', 'border-rose-200', 'bg-emerald-100', 'text-emerald-700', 'border-emerald-200');
        if (isError) {
            deletionFeedback.classList.add('bg-rose-100', 'text-rose-700', 'border', 'border-rose-200');
        } else {
            deletionFeedback.classList.add('bg-emerald-100', 'text-emerald-700', 'border', 'border-emerald-200');
        }
        deletionFeedback.textContent = message;
    }

    if (btnRequestOtp) {
        btnRequestOtp.addEventListener('click', async function() {
            const email = (deleteEmailInput.value || '').trim();
            if (!email || !email.includes('@')) {
                showFeedback('Please enter a valid email address.', true);
                return;
            }

            btnRequestOtp.disabled = true;
            btnRequestOtp.textContent = 'Sending Verification Code...';

            try {
                const res = await fetch("{{ route('account.delete.request-otp') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    emailStep.classList.add('hidden');
                    otpStep.classList.remove('hidden');
                    otpNotice.textContent = data.message || `Verification code sent to ${email}.`;
                    showFeedback('', false);
                    deletionFeedback.classList.add('hidden');
                } else {
                    showFeedback(data.error || 'Failed to send verification code.', true);
                }
            } catch (err) {
                showFeedback('Network error. Please try again.', true);
            } finally {
                btnRequestOtp.disabled = false;
                btnRequestOtp.textContent = 'Send Verification Code';
            }
        });
    }

    if (btnBackToEmail) {
        btnBackToEmail.addEventListener('click', function() {
            otpStep.classList.add('hidden');
            emailStep.classList.remove('hidden');
            deletionFeedback.classList.add('hidden');
        });
    }

    if (btnConfirmDeleteOtp) {
        btnConfirmDeleteOtp.addEventListener('click', async function() {
            const email = (deleteEmailInput.value || '').trim();
            const otp = (deleteOtpInput.value || '').trim();

            if (!otp || otp.length < 4) {
                showFeedback('Please enter the verification code sent to your email.', true);
                return;
            }

            if (!confirm('Are you absolutely certain you want to permanently delete your account and all associated data?')) {
                return;
            }

            btnConfirmDeleteOtp.disabled = true;
            btnConfirmDeleteOtp.textContent = 'Deleting Account...';

            try {
                const res = await fetch("{{ route('account.delete.verify-otp') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ email: email, otp: otp })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    otpStep.classList.add('hidden');
                    showFeedback('Success: Your account and all associated data have been permanently deleted.', false);
                } else {
                    showFeedback(data.error || 'Failed to verify code and delete account.', true);
                }
            } catch (err) {
                showFeedback('Network error during account deletion. Please try again.', true);
            } finally {
                btnConfirmDeleteOtp.disabled = false;
                btnConfirmDeleteOtp.textContent = 'Verify Code & Permanently Delete Account';
            }
        });
    }
});
</script>
@endpush
@endsection
