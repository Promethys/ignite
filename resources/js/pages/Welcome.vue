<script setup lang="ts">
import DemoSlot from '@/components/public/DemoSlot.vue';
import FeatureGrid from '@/components/public/FeatureGrid.vue';
import HeroProgressTrack from '@/components/public/HeroProgressTrack.vue';
import PublicShell from '@/components/public/PublicShell.vue';
import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import 'vue-sonner/style.css';

const trust = [
    'landing.trust.source_available',
    'landing.trust.self_hostable',
    'landing.trust.ai_ready',
];
</script>

<template>
    <PublicShell title="Welcome">
        <main class="relative mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6">
            <div class="relative grid grid-cols-1 items-center lg:grid-cols-2">
                <section class="relative px-2 py-7 sm:py-10">
                    <p
                        class="flex items-center gap-2 text-xs font-semibold tracking-widest text-primary uppercase"
                    >
                        <span class="size-2 shrink-0 bg-primary" />
                        {{ $t('landing.hero.eyebrow') }}
                    </p>
                    <h1
                        class="mt-3 max-w-2xl font-display text-4xl font-bold text-balance sm:text-5xl"
                    >
                        {{ $t('landing.hero.title_lead') }}
                        <em class="italic">{{
                            $t('landing.hero.title_highlight')
                        }}</em
                        >.
                    </h1>
                    <HeroProgressTrack class="mt-7" />
                    <p class="mt-7 max-w-xl text-pretty text-muted-foreground">
                        {{ $t('landing.hero.subtitle') }}
                    </p>
                    <div class="mt-7 flex gap-3">
                        <Button size="lg" as-child>
                            <Link
                                v-if="$page.props.auth.user"
                                :href="dashboard()"
                            >
                                {{ $t('common.nav.dashboard') }}
                            </Link>
                            <Link v-else :href="register()">
                                {{ $t('landing.hero.start') }}
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <a href="#features">
                                {{ $t('landing.hero.how_it_works') }}
                            </a>
                        </Button>
                    </div>
                    <ul
                        class="mt-7 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground"
                    >
                        <li
                            v-for="(item, index) in trust"
                            :key="item"
                            class="flex items-center gap-3"
                        >
                            <span v-if="index > 0" aria-hidden="true">·</span>
                            {{ $t(item) }}
                        </li>
                    </ul>
                </section>

                <DemoSlot />
            </div>

            <FeatureGrid />
        </main>
    </PublicShell>
</template>
