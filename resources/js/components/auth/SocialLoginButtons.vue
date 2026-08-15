<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Github from '@/components/ui/icon/Github.vue';
import Google from '@/components/ui/icon/Google.vue';
import { providerLabel } from '@/lib/sso';
import { redirect } from '@/routes/sso';
import type { SsoProvider } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    providers: SsoProvider[];
}>();

const visible = computed(() => props.providers.length > 0);
</script>

<template>
    <div v-if="visible">
        <div
            class="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border"
        >
            <span
                class="relative z-10 bg-background px-2 text-muted-foreground"
            >
                {{ $t('auth_ui.sso.divider') }}
            </span>
        </div>

        <div
            class="mt-4 grid gap-3"
            :class="providers.length > 1 ? 'grid-cols-2' : 'grid-cols-1'"
        >
            <Button
                v-for="provider in providers"
                :key="provider"
                as="a"
                :href="redirect({ provider }).url"
                variant="outline"
                class="w-full"
            >
                <Github class="size-4" v-if="provider === 'github'" />
                <Google class="size-4" v-else-if="provider === 'google'" />

                {{ providerLabel(provider) }}
            </Button>
        </div>
    </div>
</template>
