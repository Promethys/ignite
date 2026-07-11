<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

const knownStatuses = [403, 404, 500, 503];

const statusKey = computed(() =>
    knownStatuses.includes(props.status) ? String(props.status) : 'generic',
);

const titleKey = computed(() => `errors.${statusKey.value}.title`);
const descriptionKey = computed(() => `errors.${statusKey.value}.description`);
</script>

<template>
    <Head :title="$t(titleKey)" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div
            class="flex w-full max-w-md flex-col items-center gap-6 text-center"
        >
            <Link
                :href="home().url"
                class="flex items-center gap-2 font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon
                        class="size-9 fill-current text-black dark:text-white"
                    />
                </div>
            </Link>

            <p class="text-6xl font-bold tracking-tight text-primary">
                {{ status }}
            </p>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-foreground">
                    {{ $t(titleKey) }}
                </h1>
                <p class="text-muted-foreground">
                    {{ $t(descriptionKey) }}
                </p>
            </div>

            <Button as-child class="w-full sm:w-auto">
                <Link :href="home().url">
                    {{ $t('errors.actions.home') }}
                </Link>
            </Button>
        </div>
    </div>
</template>
