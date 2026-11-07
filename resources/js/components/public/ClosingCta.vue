<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';
import type { AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<AppPageProps>();
</script>

<template>
    <div class="mt-16 flex flex-col gap-6 border bg-card p-6 sm:p-8">
        <h2 class="font-display text-2xl font-semibold">
            {{ $t('public.cta.title') }}
        </h2>
        <div class="flex flex-col gap-3 sm:flex-row">
            <Button size="lg" variant="default" as-child
                ><Link
                    :href="page.props.auth.user ? dashboard() : register()"
                    >{{
                        $t(
                            page.props.auth.user
                                ? 'common.nav.dashboard'
                                : 'landing.hero.start',
                        )
                    }}</Link
                ></Button
            >
            <Button size="lg" variant="ghost" as-child
                ><a
                    :href="page.props.githubUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    >{{ $t('public.cta.code') }}</a
                ></Button
            >
        </div>
    </div>
</template>
