<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale } from '@/composables/useLocale';
import { router } from '@inertiajs/vue3';
import { ChevronDown, Globe } from 'lucide-vue-next';
import { computed } from 'vue';

const { current, supported, switchTo } = useLocale();

const currentLabel = computed(() => supported[current.value] ?? current.value);

async function change(code: string) {
    if (current.value === code) {
        return;
    }

    const maxAge = 365 * 24 * 60 * 60;

    document.cookie = `locale=${code};path=/;max-age=${maxAge};SameSite=Lax`;

    await switchTo(code);

    router.reload();
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="gap-2"
                :aria-label="`Change language, current: ${currentLabel}`"
            >
                <Globe class="size-4" />
                <span class="hidden sm:inline">{{ currentLabel }}</span>
                <ChevronDown
                    class="hidden size-4 text-muted-foreground sm:inline"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-40">
            <DropdownMenuRadioGroup :model-value="current">
                <DropdownMenuRadioItem
                    v-for="(label, code) in supported"
                    :key="code"
                    :value="(code as string)"
                    class="cursor-pointer"
                    @select="change(code as string)"
                >
                    {{ label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
