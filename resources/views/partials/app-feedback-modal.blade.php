{{-- Global App Experience Feedback Modal (Triggered after scrolling) --}}
<div x-data="appFeedbackModal()"
     x-init="init()"
     @scroll.window.passive="onScroll()"
     x-cloak
     class="relative z-50">

    {{-- Floating Pill Trigger (Available on all pages for manual feedback) --}}
    <button @click="openModal()"
            type="button"
            x-show="!isOpen && hasScrolledEnough"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="fixed bottom-20 md:bottom-6 right-4 sm:right-6 z-40 inline-flex items-center space-x-2 px-3.5 py-2 sm:px-4 sm:py-2.5 bg-slate-900/95 hover:bg-slate-800 text-emerald-400 hover:text-emerald-300 text-xs font-bold rounded-full border border-emerald-500/40 shadow-2xl backdrop-blur-md transition-all hover:scale-105 hover:border-emerald-400 group">
        <span class="text-base group-hover:rotate-12 transition-transform">⭐</span>
        <span>Feedback</span>
    </button>

    {{-- Modal Overlay & Dialog --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm flex items-end sm:items-center justify-center p-4 sm:p-6 z-50"
         @keydown.escape.window="dismissModal()">

        {{-- Backdrop Click Area --}}
        <div class="fixed inset-0" @click="dismissModal()"></div>

        {{-- Dialog Panel --}}
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
             class="relative w-full max-w-lg max-h-full bg-slate-900 border border-emerald-500/30 rounded-3xl shadow-2xl overflow-y-auto p-6 sm:p-8 text-white z-10"
             @click.stop>

            {{-- Decorative Header Glow --}}
            <div class="absolute -top-12 -right-12 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Close Button --}}
            <button @click="dismissModal()"
                    type="button"
                    class="absolute top-5 right-5 text-slate-400 hover:text-white p-1 rounded-full hover:bg-white/10 transition-colors"
                    aria-label="Close feedback modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            {{-- Form View --}}
            <div x-show="!isSubmitted">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-xl text-emerald-400">
                        ✨
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white tracking-tight">How is your experience?</h3>
                        <p class="text-xs text-slate-400">Your feedback helps us continuously improve The Eternal Echo.</p>
                    </div>
                </div>

                <form @submit.prevent="submitFeedback()" class="mt-5 space-y-4">
                    {{-- Star Rating --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Rate your experience</label>
                        <div class="flex items-center justify-center space-x-2 py-2 bg-slate-800/60 border border-white/5 rounded-2xl">
                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                <button type="button"
                                        @click="rating = star"
                                        @mouseenter="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="p-1 focus:outline-none transition-transform hover:scale-125">
                                    <svg class="w-8 h-8 transition-colors"
                                         :class="(hoverRating ? hoverRating >= star : rating >= star) ? 'text-amber-400 fill-amber-400' : 'text-slate-600 fill-transparent'"
                                         stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </button>
                            </template>
                            <span class="ml-2 text-xs font-extrabold text-amber-400" x-text="getRatingLabel()"></span>
                        </div>
                    </div>

                    {{-- Category Selector --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Category</label>
                        <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
                            <button type="button"
                                    @click="category = 'app_experience'"
                                    :class="category === 'app_experience' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-300 border-white/5 hover:bg-slate-700'"
                                    class="py-2 px-3 rounded-xl border text-center transition-all">
                                🌟 Overall Experience
                            </button>
                            <button type="button"
                                    @click="category = 'content'"
                                    :class="category === 'content' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-300 border-white/5 hover:bg-slate-700'"
                                    class="py-2 px-3 rounded-xl border text-center transition-all">
                                📖 Quran / Content
                            </button>
                            <button type="button"
                                    @click="category = 'feature_request'"
                                    :class="category === 'feature_request' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-300 border-white/5 hover:bg-slate-700'"
                                    class="py-2 px-3 rounded-xl border text-center transition-all">
                                💡 Idea / Feature
                            </button>
                            <button type="button"
                                    @click="category = 'bug'"
                                    :class="category === 'bug' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-300 border-white/5 hover:bg-slate-700'"
                                    class="py-2 px-3 rounded-xl border text-center transition-all">
                                🐞 Bug / Issue
                            </button>
                        </div>
                    </div>

                    {{-- Feedback Message --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Your thoughts or suggestions (Optional)</label>
                        <textarea x-model="message"
                                  rows="3"
                                  :placeholder="rating >= 4 ? 'Write a short review. We will copy it so you can paste it on Google Play.' : 'Share what you like or how we can make your Quranic learning experience even better...'"
                                  class="w-full bg-slate-800/80 border border-slate-700/80 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors resize-none"></textarea>
                        <p x-show="rating >= 4" class="mt-2 text-[11px] text-emerald-300/90">
                            A 4 or 5 star note is copied for you, ready to paste on Google Play.
                        </p>
                    </div>

                    {{-- Name & Email for Guests (hidden if already logged in) --}}
                    @guest
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div>
                            <input type="text"
                                   x-model="userName"
                                   placeholder="Your Name (Optional)"
                                   class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <input type="email"
                                   x-model="userEmail"
                                   placeholder="Your Email (Optional)"
                                   class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>
                    @endguest

                    <div x-show="errorMessage" class="p-2.5 bg-rose-500/20 border border-rose-500/30 text-rose-300 rounded-xl text-xs font-medium" x-text="errorMessage"></div>

                    {{-- Action Buttons --}}
                    <div class="pt-2 flex items-center justify-end space-x-3">
                        <button type="button"
                                @click="dismissModal()"
                                class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition-colors">
                            Maybe Later
                        </button>
                        <button type="submit"
                                :disabled="submitting"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2">
                            <svg x-show="submitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Submitting...' : 'Submit Feedback'"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Success View --}}
            <div x-show="isSubmitted" class="py-8 text-center space-y-4">
                <div class="w-16 h-16 bg-emerald-500/20 border border-emerald-500/30 rounded-full flex items-center justify-center text-3xl mx-auto text-emerald-400 animate-bounce">
                    ✨
                </div>
                <h4 class="text-2xl font-black text-white">JazakAllahu Khairan!</h4>
                <p class="text-sm text-slate-300 max-w-sm mx-auto leading-relaxed" x-text="successMessage()"></p>
                <div x-show="rating >= 4 && reviewText() !== ''" class="pt-1">
                    <button type="button"
                            @click="copyReview()"
                            class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-white/10 transition-all">
                        <span x-text="reviewCopied ? 'Review copied' : 'Copy review'"></span>
                    </button>
                </div>
                <div x-show="rating >= 4" class="pt-1 flex flex-col items-center gap-2">
                    <a :href="playStoreUrl"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg transition-all">
                        Open Google Play
                    </a>
                    <button type="button"
                            @click="isOpen = false"
                            class="px-6 py-2 text-xs font-bold text-slate-400 hover:text-white transition-colors">
                        Close
                    </button>
                </div>
                <div x-show="rating < 4" class="pt-2">
                    <button type="button"
                            @click="isOpen = false"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg transition-all">
                        Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function appFeedbackModal() {
    return {
        isOpen: false,
        hasTriggered: false,
        hasScrolledEnough: false,
        isSubmitted: false,
        submitting: false,
        reviewCopied: false,
        playStoreUrl: 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho',
        rating: 5,
        hoverRating: 0,
        category: 'app_experience',
        message: '',
        userName: '{{ auth()->user()->display_name ?? auth()->user()->name ?? "" }}',
        userEmail: '{{ auth()->user()->email ?? "" }}',
        errorMessage: '',

        init() {
            // Smoothly reveal floating pill after a short delay so it is accessible on all pages
            setTimeout(() => {
                this.hasScrolledEnough = true;
            }, 1500);
        },

        onScroll() {
            const scrollY = window.scrollY || document.documentElement.scrollTop;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;

            if (scrollY > 150) {
                this.hasScrolledEnough = true;
            }

            // Trigger automatically once user scrolls down past 35% or 500px
            if (!this.hasTriggered && !this.isOpen) {
                if (localStorage.getItem('the_eternal_echo_feedback_submitted') === 'true') {
                    return;
                }
                const dismissedAt = localStorage.getItem('the_eternal_echo_feedback_dismissed');
                if (dismissedAt) {
                    const diffDays = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60 * 24);
                    if (diffDays < 7) {
                        return;
                    }
                }

                if (scrollY > 500 || (docHeight > 0 && (scrollY / docHeight) >= 0.35)) {
                    this.hasTriggered = true;
                    setTimeout(() => {
                        this.isOpen = true;
                    }, 600);
                }
            }
        },

        openModal() {
            this.isOpen = true;
        },

        dismissModal() {
            this.isOpen = false;
            localStorage.setItem('the_eternal_echo_feedback_dismissed', Date.now().toString());
        },

        getRatingLabel() {
            const current = this.hoverRating || this.rating;
            switch(current) {
                case 5: return 'Exceptional! ⭐ 5/5';
                case 4: return 'Great! 👍 4/5';
                case 3: return 'Good 🙂 3/5';
                case 2: return 'Needs Work 🛠️ 2/5';
                case 1: return 'Poor ⚠️ 1/5';
                default: return '';
            }
        },

        reviewText() {
            return (this.message || '').trim();
        },

        successMessage() {
            if (this.rating >= 4 && this.reviewText() !== '') {
                return 'Your review is copied. Open Google Play and paste it.';
            }
            if (this.rating >= 4) {
                return 'A rating on Google Play helps others find The Eternal Echo.';
            }
            return 'Thank you for taking the time to share your feedback. May Allah bless you in your quest for sacred knowledge.';
        },

        async copyReview() {
            const text = this.reviewText();
            if (!text) {
                return;
            }
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.left = '-9999px';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
            }
            this.reviewCopied = true;
        },

        async submitFeedback() {
            this.submitting = true;
            this.errorMessage = '';

            const payload = {
                rating: this.rating,
                category: this.category,
                message: this.message,
                platform: 'web',
                user_name: this.userName,
                user_email: this.userEmail,
                device_info: navigator.userAgent
            };

            const endpoints = [
                '{{ route("app-feedback.store") }}',
                '/api/app-feedback',
                '/app-feedback'
            ];

            let success = false;
            let lastErrorMessage = '';

            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    let data = {};
                    try {
                        data = await response.json();
                    } catch (e) {
                        data = {};
                    }

                    if (response.ok && (data.status === 'success' || response.status === 200 || response.status === 201)) {
                        success = true;
                        this.isSubmitted = true;
                        localStorage.setItem('the_eternal_echo_feedback_submitted', 'true');
                        if (this.rating >= 4 && this.reviewText() !== '') {
                            await this.copyReview();
                        }
                        if (this.rating < 4) {
                            setTimeout(() => {
                                this.isOpen = false;
                            }, 3000);
                        }
                        break;
                    } else if (data.message) {
                        lastErrorMessage = data.message;
                    }
                } catch (err) {
                    // Try next fallback endpoint
                }
            }

            if (!success) {
                this.errorMessage = lastErrorMessage || 'Unable to submit feedback right now. Please try again.';
            }

            this.submitting = false;
        }
    };
}
</script>
