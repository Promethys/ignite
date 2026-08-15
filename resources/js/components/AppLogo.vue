<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    hideLabelOnMobile?: boolean;
}

withDefaults(defineProps<Props>(), {
    hideLabelOnMobile: false,
});
</script>

<template>
    <div class="group/logo flex items-center gap-2 overflow-hidden">
        <AppLogoIcon class="size-8 shrink-0" />
        <div
            class="grid flex-1 text-left text-base"
            :class="hideLabelOnMobile && 'max-sm:hidden'"
        >
            <span
                class="mb-0.5 truncate font-display leading-tight font-semibold tracking-tight"
                >Ignite</span
            >
        </div>
    </div>
</template>
