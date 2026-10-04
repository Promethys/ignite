<script setup lang="ts">
import { PrimitiveProps } from 'reka-ui';
import { HTMLAttributes } from 'vue';

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="currentColor"
        viewBox="0 0 22 28"
        :class
    >
        <path
            d="M14.6579 2.84175L2.93749 15.0764L10.1882 16.9344L6.93985 26.2607L18.6603 14.026L11.4096 12.168L14.6579 2.84175Z"
        ></path>
    </svg>
</template>
