<script setup lang="ts">
import PublicHero from './PublicHero.vue';

const ENV = import.meta.env;
const arcadePreviewEnabled = ENV.VITE_ARCADE_PREVIEW_ENABLED;
const arcadePreviewUrl = ENV.VITE_ARCADE_PREVIEW_URL;
</script>

<template>
    <PublicHero v-if="arcadePreviewEnabled !== 'true'">
        <img
            src="/demo-poster.png"
            :alt="$t('landing.demo.poster_alt')"
            class="size-full object-cover object-top"
        />
    </PublicHero>

    <div
        v-else
        style="
            position: relative;
            padding-bottom: calc(50.5208% + 41px);
            height: 0px;
            width: 100%;
        "
    >
        <iframe
            :src="arcadePreviewUrl"
            title="Track and Manage Personal Goals in Ignite"
            frameborder="0"
            loading="lazy"
            webkitallowfullscreen
            mozallowfullscreen
            allowfullscreen
            allow="clipboard-write; autoplay"
            style="
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            "
        ></iframe>
    </div>
</template>
