<script setup lang="ts">
import { cn } from '@/lib/utils'
import { reactiveOmit } from '@vueuse/core'
import { AccordionItem, type AccordionItemProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
const props = defineProps<
  AccordionItemProps & { class?: HTMLAttributes['class'] }
>()
const delegatedProps = reactiveOmit(props, 'class')
</script>
<template>
  <AccordionItem
    data-slot="accordion-item"
    v-bind="delegatedProps"
    :class="cn('border-b', props.class)"
    ><slot
  /></AccordionItem>
</template>
