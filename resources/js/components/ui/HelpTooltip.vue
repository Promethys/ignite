<script setup lang="ts">
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { CircleQuestionMark } from 'lucide-vue-next';
</script>

<template>
    <Popover>
        <PopoverTrigger
            tabindex="-1"
            :aria-label="$t('common.actions.help')"
            class="inline-flex cursor-pointer"
        >
            <slot name="trigger">
                <CircleQuestionMark class="h-4 w-4 text-muted-foreground/50" />
            </slot>
        </PopoverTrigger>
        <PopoverContent class="max-h-48 overflow-y-auto p-3 text-xs">
            <slot></slot>
        </PopoverContent>
    </Popover>
</template>
