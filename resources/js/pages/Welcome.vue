<script setup lang="ts">
import DemoSlot from '@/components/public/DemoSlot.vue';
import FeatureRows from '@/components/public/FeatureRows.vue';
import PublicShell from '@/components/public/PublicShell.vue';
import { Button } from '@/components/ui/button';
import { register } from '@/routes';
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
                <div
                    class="public-surface-glow pointer-events-none absolute -top-40 left-[15%] h-[26rem] w-[42rem] max-w-full"
                />

                <section class="relative px-2 py-7 sm:py-10">
                    <p
                        class="flex items-center gap-2 font-mono text-xs tracking-widest text-primary uppercase"
                    >
                        <span
                            class="size-2 shrink-0 bg-primary shadow-[0_0_16px_1px] shadow-primary/70"
                        />
                        {{ $t('landing.hero.eyebrow') }}
                    </p>
                    <h1
                        class="mt-3 max-w-2xl font-display text-4xl font-bold text-balance sm:text-5xl"
                    >
                        {{ $t('landing.hero.title_lead') }}
                        <em
                            class="bg-[linear-gradient(96deg,hsl(262_83%_68%)_10%,hsl(292_80%_68%)_90%)] bg-clip-text text-transparent italic"
                            >{{ $t('landing.hero.title_highlight') }}</em
                        >.
                    </h1>
                    <p class="mt-7 max-w-xl text-pretty text-muted-foreground">
                        {{ $t('landing.hero.subtitle') }}
                    </p>
                    <div class="mt-7 flex gap-3">
                        <Button size="lg" variant="inverse" as-child>
                            <Link :href="register()">
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

            <FeatureRows />
        </main>
    </PublicShell>
</template>
