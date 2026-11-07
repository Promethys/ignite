<script setup lang="ts">
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Link } from '@inertiajs/vue3';
defineProps<{
    items: {
        id: string;
        question: string;
        answer: string;
        href?: string;
        linkLabel?: string;
    }[];
}>();
</script>
<template>
    <Accordion type="single" collapsible class="border-t">
        <AccordionItem v-for="item in items" :key="item.id" :value="item.id">
            <AccordionTrigger
                class="py-5 font-display text-lg font-semibold hover:no-underline"
                >{{ item.question }}</AccordionTrigger
            >
            <AccordionContent
                class="max-w-2xl pb-6 text-base leading-relaxed text-muted-foreground"
                ><p>{{ item.answer }}</p>
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="mt-2 inline-flex min-h-11 items-center text-[var(--primary-text)] underline underline-offset-4"
                    >{{ item.linkLabel }}</Link
                ></AccordionContent
            >
        </AccordionItem>
    </Accordion>
</template>
