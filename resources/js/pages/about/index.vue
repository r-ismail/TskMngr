<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        title: 'About',
        href: '/about',
    },
});
</script>

<template>

    <Head title="About" />

    <div class="relative overflow-hidden bg-slate-50 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <span
                        class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sm font-medium text-sky-700 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                        About our company
                    </span>
                    <br />
                    <Link>
                        <Button> Learn more </Button>
                    </Link>
                    <h1
                        class="mt-6 text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl">
                        We build smarter operations for ambitious teams.
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        We help businesses organize work, improve collaboration, and turn complexity into clarity.
                        From planning to execution, our platform gives teams the visibility and tools they need to move
                        faster with confidence.
                    </p>

                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="/"
                            class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-slate-800 dark:bg-sky-500 dark:text-slate-950 dark:hover:bg-sky-400">
                            Explore platform
                        </a>
                        <a href="/contact"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-500 dark:hover:text-white">
                            Meet the team
                        </a>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Our mission</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">Create momentum.</h2>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" aria-hidden="true">
                                <path d="M5 12.5 10 17l9-10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Focus</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">Operational clarity</p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Approach</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">People-first systems
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Impact</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">Sustainable growth</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">8+</p>
                    <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Years of experience</p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">120+</p>
                    <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Teams supported</p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">96%</p>
                    <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Client satisfaction</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
            <div class="mb-10 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-400">Why us</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    Built for clarity, trust, and lasting progress.
                </h2>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div
                        class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M8 7V5a4 4 0 1 1 8 0v2M6 9h12v10H6z" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Reliable systems</h3>
                    <p class="mt-3 text-slate-600 dark:text-slate-300">
                        We design streamlined workflows that reduce friction, increase accountability, and keep your
                        team aligned.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div
                        class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M12 3v18M3 12h18" stroke-linecap="round" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Intentional growth</h3>
                    <p class="mt-3 text-slate-600 dark:text-slate-300">
                        Every decision is informed by practical insight, measurable outcomes, and a long-term vision for
                        sustainable success.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div
                        class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M6 19V9m6 10V5m6 14v-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Visible results</h3>
                    <p class="mt-3 text-slate-600 dark:text-slate-300">
                        We focus on outcomes that matter: more efficiency, better communication, and stronger
                        performance across the organization.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
