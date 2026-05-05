<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { CircleQuestionMark } from 'lucide-vue-next';
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger>
                <CircleQuestionMark
                    class="h-4 w-4 text-muted-foreground/50"
                />
            </TooltipTrigger>
            <TooltipContent class="max-w-md max-h-48 overflow-y-scroll">
                <slot></slot>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
