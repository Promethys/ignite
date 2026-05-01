<script setup lang="ts">
import { TriangleAlertIcon } from 'lucide-vue-next';

defineProps<{
    error: string;
}>();
</script>

<template>
    <div class="flex items-center gap-1 text-red-400 text-sm">
        <TriangleAlertIcon class="w-4 h-4" />
        <span>{{ error }}</span>
    </div>
</template>
