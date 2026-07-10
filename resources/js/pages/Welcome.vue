<script setup lang="ts">
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { Flame, LayoutGrid, Target, Trophy } from 'lucide-vue-next';
import 'vue-sonner/style.css';

const stats = [
    { n: '8', l: 'Active' },
    { n: '12', l: 'Completed' },
    { n: '61%', l: 'Completion' },
    { n: '20', l: 'Total' },
];

const previewGoals = [
    { title: 'Run a marathon', progress: 62 },
    { title: 'Launch portfolio', progress: 60 },
    { title: 'Daily meditation', progress: 71 },
];

const features = [
    {
        icon: Target,
        title: 'Visual progress',
        description:
            "Charts, bars and streaks make momentum tangible. See how far you've come at a glance.",
    },
    {
        icon: LayoutGrid,
        title: 'Four goal types',
        description:
            'Simple, quantifiable, recurring or multi-step. Model any ambition the right way.',
    },
    {
        icon: Trophy,
        title: 'Milestones & wins',
        description:
            'Break big goals into checkpoints and celebrate every completion.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Top nav (full-width border, centered content) -->
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-7 items-center justify-center rounded-md bg-primary text-primary-foreground"
                    >
                        <Flame class="size-4" />
                    </span>
                    <span class="font-display text-lg font-semibold">
                        Ignite
                    </span>
                </div>
                <nav class="flex items-center gap-2">
                    <template v-if="$page.props.auth.user">
                        <Button as-child>
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <LanguageSwitcher />
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="register()">Get started</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Body (max width) -->
        <main class="mx-auto w-full max-w-5xl flex-1 px-4 sm:px-6">
            <!-- Hero -->
            <section class="px-2 py-14 text-center sm:py-20">
                <p
                    class="text-xs font-semibold tracking-wide text-primary uppercase"
                >
                    Goal tracking that sticks
                </p>
                <h1
                    class="mx-auto mt-3 max-w-2xl font-display text-4xl font-bold text-balance sm:text-5xl"
                >
                    Turn intentions into
                    <em class="text-primary italic">momentum</em>.
                </h1>
                <p
                    class="mx-auto mt-4 max-w-xl text-pretty text-muted-foreground sm:text-lg"
                >
                    Track any goal (simple, measurable, recurring or multi-step)
                    with visual progress, milestones and streaks that keep you
                    going.
                </p>
                <div class="mt-7 flex justify-center gap-3">
                    <Button size="lg" as-child>
                        <Link :href="register()">Start free</Link>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <a href="#features">See how it works</a>
                    </Button>
                </div>
            </section>

            <!-- Product preview -->
            <section
                class="rounded-t-xl border border-b-0 bg-muted/40 p-4 sm:p-6"
            >
                <div class="mb-3 grid grid-cols-4 gap-2 sm:gap-3">
                    <div
                        v-for="stat in stats"
                        :key="stat.l"
                        class="rounded-lg bg-muted px-3 py-2"
                    >
                        <p class="font-display text-base font-semibold">
                            {{ stat.n }}
                        </p>
                        <p class="text-[10px] text-muted-foreground">
                            {{ stat.l }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        v-for="goal in previewGoals"
                        :key="goal.title"
                        class="rounded-lg border bg-card p-3"
                    >
                        <p class="mb-2 font-display text-xs font-semibold">
                            {{ goal.title }}
                        </p>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${goal.progress}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Value props -->
            <section
                id="features"
                class="grid gap-8 border-t py-14 sm:grid-cols-3 sm:gap-6"
            >
                <div v-for="feature in features" :key="feature.title">
                    <span
                        class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <component :is="feature.icon" class="size-5" />
                    </span>
                    <h4 class="mt-3 font-display text-base font-semibold">
                        {{ feature.title }}
                    </h4>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ feature.description }}
                    </p>
                </div>
            </section>
        </main>

        <!-- Footer (full-width border, centered content) -->
        <footer class="border-t">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-5 text-xs text-muted-foreground sm:px-6"
            >
                <span>© Ignite</span>
                <span>Privacy · Terms</span>
            </div>
        </footer>
    </div>

    <Toaster
        position="top-right"
        close-button
        close-button-position="top-right"
        theme="system"
    />
</template>
