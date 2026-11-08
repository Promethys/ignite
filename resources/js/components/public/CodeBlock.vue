<script setup lang="ts">
import CopyButton from '@/components/public/docs/CopyButton.vue';

defineProps<{ code: string }>();
</script>

<template>
    <div class="public-code-block">
        <pre tabindex="0"><code>{{ code }}</code></pre>
        <CopyButton :code="code" />
    </div>
</template>
