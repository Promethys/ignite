<script setup lang="ts">
import { Goal } from '@/types/models';
import { Flame } from 'lucide-vue-next';

defineProps<{ item: Goal }>();

// Streak / per-period completion is not yet provided by the backend.
// Render a 7-slot week scaffold; wire counts when the data lands.
const slots = 7;
const completed = 0;
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-1.5 text-sm font-medium">
            <Flame class="size-4 text-primary" />
            {{ completed }}-day streak
        </div>
        <div class="flex gap-1">
            <span
                v-for="i in slots"
                :key="i"
                class="size-2 rounded-full"
                :class="i <= completed ? 'bg-primary' : 'bg-muted'"
            />
        </div>
    </div>
</template>
