<script setup lang="ts">
import { TriangleAlertIcon } from 'lucide-vue-next';

defineProps<{
    error: string;
}>();
</script>

<template>
    <div class="flex items-center gap-1 text-sm text-red-400">
        <TriangleAlertIcon class="h-4 w-4" />
        <span>{{ error }}</span>
    </div>
</template>
