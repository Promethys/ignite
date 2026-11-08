<script setup lang="ts">
import { cn } from '@/lib/utils'
import { reactiveOmit } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import {
  AccordionHeader,
  AccordionTrigger,
  type AccordionTriggerProps,
} from 'reka-ui'
import type { HTMLAttributes } from 'vue'
const props = defineProps<
  AccordionTriggerProps & { class?: HTMLAttributes['class'] }
>()
const delegatedProps = reactiveOmit(props, 'class')
</script>
<template>
  <AccordionHeader class="flex"
    ><AccordionTrigger
      data-slot="accordion-trigger"
      v-bind="delegatedProps"
      :class="
        cn(
          'flex flex-1 items-center justify-between gap-4 py-4 text-left font-medium hover:underline [&[data-state=open]>svg]:rotate-180',
          props.class,
        )
      "
      ><slot /><ChevronDown
        class="size-4 shrink-0 transition-transform motion-reduce:transition-none"
        aria-hidden="true" /></AccordionTrigger
  ></AccordionHeader>
</template>
