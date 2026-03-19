<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MaD-ACCESS — LOLER Inspection Management</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">

        {{-- Navigation bar --}}
        <nav class="bg-brand-navy">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                <span class="inline-flex items-baseline gap-0.5 font-bold tracking-tight text-xl select-none">
                    <span class="text-white">MaD-</span><span class="text-brand-red">ACCESS</span>
                </span>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="px-4 py-2 text-sm font-semibold text-white border border-white/30 rounded-md hover:bg-white/10 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 text-sm font-semibold text-white bg-brand-red rounded-md hover:bg-red-700 transition">
                            Sign In
                        </a>
                    @endauth
                @endif
            </div>
        </nav>

        {{-- Hero --}}
        <div class="bg-brand-navy">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
                <p class="text-brand-yellow text-xs font-bold uppercase tracking-widest mb-4">LOLER Inspection Management</p>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
                    MaD-<span class="text-brand-red">ACCESS</span>
                </h1>
                <p class="mt-6 max-w-2xl mx-auto text-lg text-white/70 leading-relaxed">
                    Digital inspection records, compliance tracking and thorough examination reports
                    for lifting equipment under the Lifting Operations and Lifting Equipment Regulations 1998.
                </p>
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="mt-8 inline-flex items-center gap-2 px-8 py-3.5 bg-brand-red text-white font-semibold rounded-lg hover:bg-red-700 transition text-lg shadow-lg">
                        Go to Dashboard
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="mt-8 inline-flex items-center gap-2 px-8 py-3.5 bg-brand-red text-white font-semibold rounded-lg hover:bg-red-700 transition text-lg shadow-lg">
                        Sign In
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Hi-vis divider --}}
        <div class="h-1.5 bg-brand-yellow"></div>

        {{-- Feature cards --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-red">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">LOLER Thorough Examinations</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Conduct and record thorough examinations for all lifting equipment. Generate compliant
                        PDF certificates with inspector signatures and photographic evidence.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-navy">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">Automated Due-Date Tracking</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Never miss an inspection. The system automatically tracks next-due dates,
                        flags overdue items and sends email alerts before deadlines.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-yellow">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.243m-4.243 0l-3.536 3.536m0-7.072A5 5 0 0112 17m0 0v-5m0 5H7m5 0a9 9 0 100-18 9 9 0 000 18z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">Mobile QR Inspections</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Inspectors scan QR codes on equipment to launch a mobile-optimised checklist.
                        Capture photos and signatures on-site, no paperwork required.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-navy">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">Multi-Client Management</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Manage equipment registers across multiple client sites from a single dashboard.
                        Client-viewer accounts give site managers read access to their own kit lists.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-red">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">Inspection Reports &amp; Audit Trail</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Download monthly inspection summaries per client. Full audit logging records
                        every action taken within the system for accountability and compliance.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-brand-yellow">
                    <div class="w-10 h-10 bg-brand-navy/10 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-2">Competent Person Records</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Track inspector qualifications and certification expiry dates. Alerts flag
                        upcoming renewals so your team stays compliant at all times.
                    </p>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <footer class="bg-brand-navy mt-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="inline-flex items-baseline gap-0.5 font-bold tracking-tight text-lg select-none">
                    <span class="text-white">MaD-</span><span class="text-brand-red">ACCESS</span>
                </span>
                <p class="text-white/50 text-xs text-center">
                    LOLER Inspection Management &mdash; Built for construction health &amp; safety compliance.
                </p>
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-white/60 hover:text-white text-sm transition">Dashboard →</a>
                @else
                    <a href="{{ route('login') }}" class="text-white/60 hover:text-white text-sm transition">Sign In →</a>
                @endauth
            </div>
        </footer>

    </body>
</html>
