<script setup lang="ts">
import PublicHero from './PublicHero.vue';
</script>

<template>
    <PublicHero>
        <img
            src="/demo-poster.png"
            :alt="$t('landing.demo.poster_alt')"
            class="size-full object-cover object-top"
        />
    </PublicHero>
</template>
