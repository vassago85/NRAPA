@extends('layouts.info')

@section('title', 'Choosing Your First PRS Rifle & Equipment Setup in South Africa')
@section('description', 'A practical beginner guide to choosing a PRS or PR22 rifle setup in South Africa, covering rifles, optics, calibres, bipods, bags, ballistic tools, and match preparation.')
@section('heading', 'Getting Started in Precision Rifle Shooting')
@section('subheading', 'Learn what equipment is commonly used in PRS and PR22 competition in South Africa — from rifles and optics to support gear, ballistic tools, and match-day preparation.')
@section('breadcrumb', 'PRS rifle & equipment guide')

@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'What calibre is best for beginner PRS shooters?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '6.5 Creedmoor is widely regarded as the best starting calibre for PRS. It offers low recoil, excellent ballistics, widely available factory ammunition in South Africa, and good barrel life. For rimfire divisions, .22LR is the standard.']],
        ['@type' => 'Question', 'name' => 'Can I start PRS with a hunting rifle?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. Many shooters start with a bolt-action hunting rifle. You may find limitations in ergonomics, magazine capacity, and barricade support, but it is absolutely possible to compete and learn the fundamentals before investing in a dedicated chassis setup.']],
        ['@type' => 'Question', 'name' => 'Do I need a chassis rifle?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. A chassis is not required to start. Many competitive shooters use traditional stocks. However, a chassis system offers adjustability, repeatable positioning, and better barricade support which becomes increasingly valuable as you progress.']],
        ['@type' => 'Question', 'name' => 'What scope magnification is recommended?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Most PRS shooters use scopes in the 5-25x or 4-24x range. High magnification helps at distance, while the lower end is useful for close barricade stages. First focal plane (FFP) scopes are strongly preferred for PRS.']],
        ['@type' => 'Question', 'name' => 'Should I choose MIL or MOA?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Either system works. MIL is more common in the PRS community in South Africa, which makes it easier to share data and corrections with other shooters. Choose one and learn it well rather than switching between systems.']],
        ['@type' => 'Question', 'name' => 'What is PR22?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'PR22 (Precision Rimfire) is a division of precision rifle competition using .22LR rifles. It follows similar stage formats to centrefire PRS but at shorter distances. PR22 is an excellent and affordable entry point into precision rifle competition.']],
        ['@type' => 'Question', 'name' => 'How many rounds are typically used in a match?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'A typical PRS match uses between 80 and 150 rounds depending on the match format and number of stages. Always bring more than you expect to need, plus extra for zero confirmation.']],
        ['@type' => 'Question', 'name' => 'What equipment should I prioritise first?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Prioritise a reliable rifle, quality optic, a sturdy bipod, and a rear bag. These four items have the most direct impact on your shooting. Other accessories can be added over time as you identify what your shooting needs.']],
        ['@type' => 'Question', 'name' => 'Do I need a ballistic calculator?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'A ballistic calculator is highly recommended once you start shooting beyond 300 metres. Free apps like Strelok work well for beginners. A Kestrel with Applied Ballistics is the gold standard but not essential to start.']],
        ['@type' => 'Question', 'name' => 'What should I bring to my first match?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Bring your rifle, ammunition (more than the minimum), bipod, rear bag, hearing and eye protection, sunscreen, water, a hat, snacks, and a positive attitude. Arrive early, introduce yourself, and ask experienced shooters for help — the PRS community is welcoming to newcomers.']],
    ],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')

    {{-- Hero CTA buttons --}}
    <div class="not-prose mb-8 flex flex-wrap gap-3">
        <a href="https://pretoriaprecisionrifle.co.za" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-5 py-2.5 text-sm font-semibold text-white hover:bg-nrapa-blue-dark transition">
            Visit Pretoria Precision Rifle Club
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
        </a>
        <a href="{{ route('info.shooting-exercises') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/80 border border-zinc-200 px-5 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-700 transition">
            View Shooting Activities
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>

    {{-- ===== 1. WHAT IS PRS? ===== --}}
    <h2>What is PRS?</h2>
    <p>PRS (Precision Rifle Series) is a practical precision rifle competition format that tests a shooter's ability to engage targets at varying distances under time pressure. Unlike benchrest shooting, PRS emphasises real-world shooting positions, movement between stages, and decision-making under the clock.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Positional shooting</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Shooters engage targets from standing, kneeling, sitting, and prone positions — often using barricades, props, and improvised supports.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Timed stages</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Each stage has a time limit — typically 90 to 120 seconds. Speed, accuracy, and efficiency all matter.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Target transitions</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Stages often require engaging multiple targets at different distances, testing both ballistic knowledge and wind-reading ability.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Barricades &amp; props</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Stages incorporate barricades, rooftops, tank traps, ladders, and other obstacles that test adaptability and equipment handling.</p>
        </div>
    </div>

    <div class="info-card">
        <h4>What about PR22?</h4>
        <p>PR22 (Precision Rimfire) follows the same match format as centrefire PRS but uses .22LR rifles at shorter distances. It is an excellent and affordable entry point — lower ammunition cost, minimal recoil, and the same positional shooting skills transfer directly to centrefire PRS.</p>
        <p>Many clubs in South Africa, including <a href="https://pretoriaprecisionrifle.co.za" target="_blank" rel="noopener">Pretoria Precision Rifle Club</a>, host PR22 alongside centrefire PRS events.</p>
    </div>

    {{-- ===== 2. CHOOSING YOUR FIRST RIFLE ===== --}}
    <h2>Choosing your first rifle</h2>
    <p>Calibre choice affects recoil, ammunition cost, barrel life, and long-range performance. Here is a practical comparison of the most common PRS calibres available in South Africa.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 text-xs font-bold">.22</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">.22 LR</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Virtually no recoil. Very affordable to shoot. Ideal for PR22 division and building fundamentals. Limited to shorter distances (typically under 200m).</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Beginner-friendly</span>
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Low cost</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 text-xs font-bold">.223</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">.223 Remington</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Low recoil, widely available, affordable ammunition. Effective to around 600m. Excellent barrel life (5,000+ rounds). Good stepping stone from rimfire to centrefire PRS.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Beginner-friendly</span>
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Long barrel life</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 text-xs font-bold">.308</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">.308 Winchester</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">The most widely available centrefire calibre in South Africa. Moderate recoil. Good barrel life (4,000–5,000 rounds). Heavier bullet drop at distance means more adjustment, which teaches wind and elevation well.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Widely available</span>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Moderate recoil</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue text-xs font-bold">6.5</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">6.5 Creedmoor</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">The most popular PRS calibre worldwide. Low recoil, excellent ballistics, factory ammo available in SA. Good barrel life (2,500–3,000 rounds). The recommended starting point for most new PRS shooters.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-nrapa-blue/10 px-2.5 py-0.5 text-xs font-medium text-nrapa-blue">Recommended</span>
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Beginner-friendly</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 text-xs font-bold">6D</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">6 Dasher</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Very low recoil with excellent ballistic performance. Requires handloading — no factory ammo available. Barrel life around 2,500–3,000 rounds. Popular among experienced PRS competitors.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Handload only</span>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Experienced</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 text-xs font-bold">6GT</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">6GT</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Designed specifically for PRS. Low recoil, flat trajectory, mild on brass. Handloading preferred but some factory loads exist internationally. Barrel life around 2,000–2,500 rounds.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Handload preferred</span>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Experienced</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:col-span-2">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 text-xs font-bold">6CM</span>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">6 Creedmoor</h3>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Necked-down 6.5 Creedmoor — faster, flatter, less recoil but shorter barrel life (1,500–2,000 rounds). Popular at competitive level. Requires handloading for best results. A strong choice for shooters ready to optimise performance.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Handload preferred</span>
                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400">Shorter barrel life</span>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Competitive</span>
            </div>
        </div>
    </div>

    {{-- ===== 3. CHASSIS VS TRADITIONAL STOCKS ===== --}}
    <h2>Chassis vs traditional stocks</h2>
    <p>One of the first decisions new PRS shooters face is whether to use a chassis system or a traditional stock. Both work — the choice depends on your budget, preferences, and how seriously you plan to compete.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Adjustability</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Chassis systems allow adjustment of length-of-pull and cheek height, enabling consistent head position behind the scope across different shooting positions.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Barricade support</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Flat-bottomed chassis forends and integrated ARCA rails provide stable contact surfaces on barricades and props — a significant advantage in PRS stages.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Repeatable positioning</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Once dialled in, a chassis provides the same ergonomic setup every time you shoulder the rifle — reducing variables between stages.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Traditional stocks</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Lighter, often more affordable, and familiar to hunters moving into PRS. Many competitive shooters still perform well with quality traditional stocks. Start with what you have.</p>
        </div>
    </div>

    {{-- ===== 4. OPTICS GUIDE ===== --}}
    <h2>Optics guide</h2>
    <p>Your scope is arguably the most important piece of equipment after the rifle itself. A quality optic with reliable tracking and clear glass will serve you far better than an expensive rifle with a poor scope.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">FFP vs SFP</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300"><strong>First focal plane (FFP)</strong> is strongly preferred for PRS. The reticle scales with magnification, so holdovers and wind holds are accurate at any power setting. SFP reticles are only accurate at one magnification.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Magnification</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">A 5-25x or 4-24x scope covers the full range of PRS stages. High magnification helps at distance; lower magnification is useful for close, fast barricade stages where field of view matters.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">MIL vs MOA</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Both systems work. MIL is more common in the South African PRS community, making it easier to share data and corrections. Pick one and commit to it — consistency matters more than the system itself.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Tracking &amp; turrets</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Reliable turret tracking is essential. Exposed turrets allow quick elevation adjustments between targets. A locking turret prevents accidental bumps. Test your scope's tracking before your first match.</p>
        </div>
    </div>

    <div class="info-card">
        <h4>Reticle choice</h4>
        <p>Choose a reticle with a Christmas-tree or grid-style holdover pattern. These allow quick wind and elevation holds without dialling turrets on every shot — saving valuable seconds during timed stages. Simpler crosshair reticles work but are slower in practice.</p>
    </div>

    {{-- ===== 5. ESSENTIAL PRS GEAR ===== --}}
    <h2>Essential PRS gear</h2>
    <p>Beyond your rifle and optic, several accessories are considered essential for PRS competition. South African range conditions — intense sun, wind, dust, and heat — make some items even more important.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 9.75V10.5"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Bipod</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Your primary front support. Look for a sturdy bipod with smooth pan and cant. Atlas, Harris, and MDT are popular choices in SA.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Rear bag</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Used to support the buttstock in prone and positional shooting. A squeeze bag like the Wiebad or Tab Gear is standard kit.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Ballistic calculator</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Essential for calculating drop and wind at distance. Free apps like Strelok work well. A Kestrel with Applied Ballistics is the gold standard.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Kestrel / wind meter</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Accurate wind and environmental data. A Kestrel 5700 with Applied Ballistics combines weather station and ballistic solver in one unit.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12h.008v.007H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Hearing protection</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Electronic hearing protection allows you to hear range commands while protecting your hearing. Essential safety equipment at any match.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Data card holder</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Attach your DOPE card and stage information to your rifle stock or wrist. Quick reference saves time under pressure.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Magazine pouches</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Keep loaded magazines accessible on your belt or chest rig. Quick mag changes matter when the clock is running.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5l16.5-4.125M12 6.75c-2.708 0-5.363.224-7.948.655C2.999 7.58 2.25 8.507 2.25 9.574v9.176A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V9.574c0-1.067-.75-1.994-1.802-2.169A48.329 48.329 0 0012 6.75zm-1.683 6.443l-.005.005-.006-.005.006-.005.005.005zm-.005 2.127l-.005-.006.005-.005.005.005-.005.006zm2.116-2.127l-.006.005-.005-.005.005-.005.006.005zm-.005 2.127l-.006-.006.006-.005.005.005-.005.006z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Shooting mat</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Protects you and your equipment from hot, rocky, or thorny South African terrain. Also provides a consistent prone surface.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Sunscreen &amp; hat</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">South African range conditions are harsh. Full-day matches in direct sun require proper sun protection — SPF50, wide-brim hat, and long sleeves.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Hydration &amp; snacks</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Matches run 4–8 hours. Bring plenty of water (2L+), electrolytes, and energy-dense snacks. Dehydration kills concentration.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384 3.162 1.412-5.987L2.202 8.07l6.137-.464L11.42 2.25l3.082 5.356 6.137.464-5.246 4.275 1.412 5.987-5.384-3.162z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Batteries &amp; tools</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Spare batteries for optic, Kestrel, and rangefinder. A torque wrench and Allen keys for scope mounts and action screws. Murphy's law applies at matches.</p>
        </div>
    </div>

    {{-- ===== 6. BALLISTIC CALCULATORS ===== --}}
    <h2>Ballistic calculators</h2>
    <p>Once you start shooting beyond 300 metres, a ballistic calculator becomes essential. It uses your rifle's ballistic profile and environmental conditions to predict bullet drop and wind drift.</p>

    <div class="info-card">
        <h4>Key ballistic concepts</h4>
        <ul class="checklist">
            <li><strong>Muzzle velocity</strong> — the speed of your bullet leaving the barrel. This is the single most important input for your ballistic solver and should be measured with a chronograph.</li>
            <li><strong>Zero validation</strong> — confirm your rifle's zero at a known distance before every match. A shifted zero invalidates all your DOPE.</li>
            <li><strong>Environmental conditions</strong> — temperature, pressure, altitude, and humidity all affect bullet flight. Input accurate conditions for accurate solutions.</li>
            <li><strong>DOPE validation</strong> — verify your calculated solutions at real distances. Shoot your DOPE chart at 400m, 600m, 800m+ and confirm your data matches reality.</li>
        </ul>
    </div>

    <p>Free apps like <strong>Strelok</strong> and <strong>Applied Ballistics Mobile</strong> are excellent starting points. A <strong>Kestrel 5700 Elite</strong> with Applied Ballistics is the gold standard — combining weather station and ballistic solver in a rugged, field-ready device.</p>

    {{-- ===== 7. MATCH PREPARATION CHECKLIST ===== --}}
    <h2>Match preparation checklist</h2>
    <p>Preparation separates a good match from a frustrating one. Use this checklist before every PRS or PR22 event.</p>

    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Confirm your zero</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Shoot a group at your zero distance the day before or morning of the match. Do not assume your last zero still holds.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Check mounts &amp; screws</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Verify all scope mount screws, action screws, and bipod attachments are torqued to spec. Loose hardware causes unexplained misses.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Prepare magazines</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Load magazines the night before. Know how many rounds each stage requires and have spares ready.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Count your ammo</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Bring more than you need. A typical match uses 80–150 rounds plus extra for zero confirmation. Running short is not recoverable.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Pack hydration &amp; weather gear</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Water, sunscreen, hat, snacks, rain gear. Check the weather forecast. South African conditions can change quickly.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Arrive early</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Give yourself time to sign in, walk the stages, confirm zero, set up your gear, and attend the safety briefing without rushing.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:col-span-2">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Ask experienced shooters for help</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">The PRS community is welcoming. Introduce yourself, mention it is your first match, and you will find experienced shooters happy to help with stage plans, wind calls, and general advice.</p>
            </div>
        </div>
    </div>

    {{-- ===== 8. NEW SHOOTER ADVICE ===== --}}
    <h2>New shooter advice</h2>
    <div class="info-card">
        <h4>You do not need expensive gear to start</h4>
        <p>One of the most common misconceptions about PRS is that you need a high-end custom rifle, premium optic, and a full kit of accessories before your first match. This is not true.</p>
        <p>Many successful shooters started with a factory rifle, a mid-range scope, and a basic bipod. <strong>Reliability and consistency matter far more than price tags.</strong> A shooter who knows their rifle's limitations and has solid fundamentals will outperform someone with expensive equipment and poor technique.</p>
        <p>Focus on:</p>
        <ul class="checklist">
            <li>Learning your ballistics and confirming your DOPE</li>
            <li>Practising positional shooting — not just prone</li>
            <li>Understanding wind reading fundamentals</li>
            <li>Building smooth, repeatable stage plans</li>
            <li>Attending local club matches regularly to build experience</li>
        </ul>
    </div>

    {{-- ===== 9. PPRC CALLOUT ===== --}}
    <div class="not-prose mt-10 rounded-2xl border border-nrapa-blue/20 bg-gradient-to-r from-nrapa-blue/10 to-nrapa-orange/10 p-6 dark:border-nrapa-blue/40 dark:from-nrapa-blue/20 dark:to-nrapa-orange/20">
        <h2 class="m-0 text-xl font-bold text-zinc-900 dark:text-white">Want to experience PRS in Pretoria?</h2>
        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-200">Pretoria Precision Rifle Club hosts PRS and PR22 style matches and provides a community environment for shooters wanting to grow their practical precision rifle skills and competition experience.</p>
        <div class="mt-5">
            <a href="https://pretoriaprecisionrifle.co.za" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-5 py-2.5 text-sm font-semibold text-white hover:bg-nrapa-blue-dark transition">
                Visit Pretoria Precision Rifle Club
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </a>
        </div>
    </div>

    {{-- ===== 10. FAQ ===== --}}
    <h2>Frequently asked questions</h2>

    <div class="not-prose mt-5 space-y-3">
        @php
            $faqs = [
                ['q' => 'What calibre is best for beginner PRS shooters?', 'a' => '6.5 Creedmoor is widely regarded as the best starting calibre for PRS. It offers low recoil, excellent ballistics, widely available factory ammunition in South Africa, and good barrel life. For rimfire divisions, .22LR is the standard.'],
                ['q' => 'Can I start PRS with a hunting rifle?', 'a' => 'Yes. Many shooters start with a bolt-action hunting rifle. You may find limitations in ergonomics, magazine capacity, and barricade support, but it is absolutely possible to compete and learn the fundamentals before investing in a dedicated chassis setup.'],
                ['q' => 'Do I need a chassis rifle?', 'a' => 'No. A chassis is not required to start. Many competitive shooters use traditional stocks. However, a chassis system offers adjustability, repeatable positioning, and better barricade support which becomes increasingly valuable as you progress.'],
                ['q' => 'What scope magnification is recommended?', 'a' => 'Most PRS shooters use scopes in the 5-25x or 4-24x range. High magnification helps at distance, while the lower end is useful for close barricade stages. First focal plane (FFP) scopes are strongly preferred for PRS.'],
                ['q' => 'Should I choose MIL or MOA?', 'a' => 'Either system works. MIL is more common in the PRS community in South Africa, which makes it easier to share data and corrections with other shooters. Choose one and learn it well rather than switching between systems.'],
                ['q' => 'What is PR22?', 'a' => 'PR22 (Precision Rimfire) is a division of precision rifle competition using .22LR rifles. It follows similar stage formats to centrefire PRS but at shorter distances. PR22 is an excellent and affordable entry point into precision rifle competition.'],
                ['q' => 'How many rounds are typically used in a match?', 'a' => 'A typical PRS match uses between 80 and 150 rounds depending on the match format and number of stages. Always bring more than you expect to need, plus extra for zero confirmation.'],
                ['q' => 'What equipment should I prioritise first?', 'a' => 'Prioritise a reliable rifle, quality optic, a sturdy bipod, and a rear bag. These four items have the most direct impact on your shooting. Other accessories can be added over time as you identify what your shooting needs.'],
                ['q' => 'Do I need a ballistic calculator?', 'a' => 'A ballistic calculator is highly recommended once you start shooting beyond 300 metres. Free apps like Strelok work well for beginners. A Kestrel with Applied Ballistics is the gold standard but not essential to start.'],
                ['q' => 'What should I bring to my first match?', 'a' => 'Bring your rifle, ammunition (more than the minimum), bipod, rear bag, hearing and eye protection, sunscreen, water, a hat, snacks, and a positive attitude. Arrive early, introduce yourself, and ask experienced shooters for help — the PRS community is welcoming to newcomers.'],
            ];
        @endphp

        @foreach($faqs as $faq)
        <details class="group rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <summary class="flex cursor-pointer items-center justify-between gap-4 bg-zinc-50 px-5 py-4 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700/60 transition list-none [&::-webkit-details-marker]:hidden">
                <span>{{ $faq['q'] }}</span>
                <svg class="size-5 shrink-0 text-zinc-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="border-t border-zinc-200 bg-white px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed">{{ $faq['a'] }}</p>
            </div>
        </details>
        @endforeach
    </div>

    {{-- ===== NEXT STEPS ===== --}}
    <div class="not-prose mt-8 rounded-2xl border border-nrapa-blue/20 bg-gradient-to-r from-nrapa-blue/10 to-nrapa-orange/10 p-6 dark:border-nrapa-blue/40 dark:from-nrapa-blue/20 dark:to-nrapa-orange/20">
        <h2 class="m-0 text-xl font-bold text-zinc-900 dark:text-white">Next steps</h2>
        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-200">Continue exploring NRAPA resources and the Pretoria Precision Rifle Club ecosystem.</p>
        <ul class="link-list mt-4">
            <li><a href="https://pretoriaprecisionrifle.co.za" target="_blank" rel="noopener">Pretoria Precision Rifle Club</a></li>
            <li><a href="{{ route('info.shooting-exercises') }}">Shooting activities &amp; records</a></li>
            <li><a href="{{ route('info.dedicated-sport-shooter-south-africa') }}">Dedicated sport shooter</a></li>
            <li><a href="{{ route('info.membership-benefits') }}">NRAPA membership benefits</a></li>
            <li><a href="{{ route('info.endorsements') }}">Endorsement letters</a></li>
            <li><a href="{{ route('info.faq') }}">General FAQ</a></li>
        </ul>
    </div>
@endsection
