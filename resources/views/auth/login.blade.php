@extends('layouts.app')
@section('title', 'Login - The Eternal Echo')

@section('content')
    <div class="fixed inset-0 flex items-center justify-center bg-emerald-900 p-6 z-[60] overflow-hidden"
        x-data="loginForm()">
        <div class="bg-white p-10 rounded-[3.5rem] shadow-2xl max-w-md w-full space-y-8 animate-slideUp relative">
            <div class="text-center space-y-2">
                <div
                    class="bg-emerald-900 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6 text-emerald-700 shadow-inner">
                    <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo" class="w-12 h-12">
                </div>

                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-none">The Eternal Echo</h2>
                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em] mt-2">Spiritual Knowledge Journey</p>
            </div>

            {{-- Method Selection Tabs --}}
            <div x-show="!loading && phase === 'email'" class="flex bg-slate-50 p-1 rounded-xl border border-slate-100">
                <button type="button" @click="method = 'otp'" 
                        :class="method === 'otp' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all">
                    🔑 OTP Login
                </button>
                <button type="button" @click="method = 'password'" 
                        :class="method === 'password' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all">
                    🔒 Password
                </button>
            </div>

            {{-- Loading State --}}
            <div x-show="loading" class="flex flex-col items-center py-10 space-y-4">
                <div class="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-emerald-800 font-black uppercase text-[10px] tracking-widest">Opening Portal...</p>
            </div>

            {{-- OTP Phase: Email Input --}}
            <form x-show="!loading && phase === 'email' && method === 'otp'" @submit.prevent="requestOtp" class="space-y-5">
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email</label>
                    <input type="email" x-model="email" placeholder="your@email.com"
                        class="w-full p-5 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-emerald-500 font-bold text-slate-800 transition-all"
                        required>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-lg hover:bg-emerald-800 transition-all shadow-xl shadow-slate-200 active:scale-[0.98]">
                    Send Verification Code
                </button>

                <div class="relative py-2 flex items-center">
                    <div class="flex-grow border-t border-slate-100"></div>
                    <span class="flex-shrink-0 mx-4 text-slate-300 text-[9px] font-black uppercase tracking-[0.2em]">Or</span>
                    <div class="flex-grow border-t border-slate-100"></div>
                </div>

                <a href="{{ route('quran.redirect') }}"
                    class="w-full flex items-center justify-center gap-3 bg-[#114030] text-[#E0F2EB] py-4 rounded-2xl font-black text-lg hover:bg-[#1a5a44] transition-all shadow-lg shadow-[#114030]/20 active:scale-[0.98]">
                    <svg class="w-6 h-6 opacity-90" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                    </svg>
                    <span>Login with Quran.com</span>
                </a>
            </form>

            {{-- Password Phase: Email & Password Input --}}
            <form x-show="!loading && phase === 'email' && method === 'password'" @submit.prevent="submitPassword" class="space-y-5">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email</label>
                        <input type="email" x-model="email" placeholder="your@email.com"
                            class="w-full p-5 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-emerald-500 font-bold text-slate-800 transition-all"
                            required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Password</label>
                        <input type="password" x-model="password" placeholder="••••••"
                            class="w-full p-5 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-emerald-500 font-bold text-slate-800 transition-all"
                            required>
                    </div>
                </div>

                <p x-show="error" class="text-rose-500 text-xs font-bold text-center leading-relaxed" x-text="error"></p>

                <button type="submit"
                    class="w-full bg-emerald-600 text-white py-5 rounded-2xl font-black text-lg hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-100 active:scale-[0.98]">
                    Sign In / Register
                </button>
                
                <p class="text-[9px] text-slate-400 text-center font-bold leading-relaxed">
                    💡 New users will be registered immediately. If you registered previously using OTP, please log in with OTP first and set a password in your Profile.
                </p>
            </form>

            {{-- OTP Code Verification Phase --}}
            <div x-show="!loading && phase === 'otp'" class="space-y-6 animate-fadeIn">
                <div class="bg-emerald-50 p-5 rounded-2xl border border-emerald-100 text-center">
                    <p class="text-emerald-800 text-sm font-bold leading-relaxed italic" x-text="'\"' + welcome + ' \"'">
                    </p>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 text-center block tracking-widest">Verification Code</label>
                    <input type="text" x-model="otp" placeholder="000000" maxlength="6"
                        class="w-full p-5 rounded-2xl bg-slate-50 border-2 border-slate-100 text-center text-4xl tracking-[0.4em] font-black text-slate-900 placeholder:tracking-normal placeholder:font-bold placeholder:text-slate-200">
                </div>
                <p x-show="error" class="text-rose-500 text-sm font-bold text-center" x-text="error"></p>
                <button @click="verifyOtp"
                    class="w-full bg-emerald-600 text-white py-5 rounded-2xl font-black text-lg shadow-xl shadow-emerald-100 hover:bg-emerald-700 transition-all active:scale-[0.98]">
                    Verify & Enter
                </button>
                <button @click="phase = 'email'; error = ''"
                    class="w-full text-slate-400 font-black uppercase text-[10px] tracking-[0.2em] hover:text-rose-600 transition-colors">
                    Back to Entry Form
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function loginForm() {
            return {
                phase: 'email',
                method: 'otp', // 'otp' or 'password'
                email: '',
                password: '',
                otp: '',
                welcome: 'Welcome back to your spiritual journey.',
                loading: false,
                error: '',

                async requestOtp() {
                    if (!this.email || !this.email.includes('@')) {
                        alert('Valid email required.');
                        return;
                    }
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('/otp/request', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: this.email }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.welcome = data.welcome || this.welcome;
                            this.phase = 'otp';
                            alert(data.message);
                        } else {
                            alert('Failed to send OTP. Please try again.');
                        }
                    } catch (e) {
                        alert('Request failed. Check your connection.');
                    }
                    this.loading = false;
                },

                async verifyOtp() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('/otp/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: this.email, otp: this.otp }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            this.error = data.error || 'Incorrect code. Please try again.';
                            this.otp = '';
                            this.loading = false;
                        }
                    } catch (e) {
                        this.error = 'Verification failed. Please try again.';
                        this.loading = false;
                    }
                },

                async submitPassword() {
                    if (!this.email || !this.email.includes('@')) {
                        alert('Valid email required.');
                        return;
                    }
                    if (!this.password || this.password.length < 6) {
                        alert('Password must be at least 6 characters.');
                        return;
                    }
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('{{ route("login.password") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: this.email, password: this.password }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            this.error = data.error || 'Invalid credentials.';
                            this.loading = false;
                        }
                    } catch (e) {
                        this.error = 'Login failed. Please check details or connection.';
                        this.loading = false;
                    }
                }
            };
        }
    </script>
@endpush