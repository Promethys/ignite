<script setup lang="ts">
import LocaleController from '@/actions/App/Http/Controllers/Settings/LocaleController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale } from '@/composables/useLocale';
import { formbricksEnabled } from '@/lib/formbricks';
import formbricks from '@formbricks/js';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Globe } from 'lucide-vue-next';
import { computed } from 'vue';

const { responsive = true } = defineProps<{
    responsive?: boolean;
}>();

const { current, supported, switchTo } = useLocale();
const page = usePage();

const currentLabel = computed(() => supported[current.value] ?? current.value);

async function change(code: string) {
    if (current.value === code) {
        return;
    }

    if (formbricksEnabled()) {
        void formbricks.setLanguage(code);
    }

    const isAuthenticated = !!page.props.auth.user;

    if (isAuthenticated) {
        await switchTo(code);
        router.patch(LocaleController.update.url(), { locale: code });
    } else {
        const maxAge = 365 * 24 * 60 * 60;
        document.cookie = `locale=${code};path=/;max-age=${maxAge};SameSite=Lax`;
        await switchTo(code);
        router.reload();
    }
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
                <span :class="{ 'hidden sm:inline': responsive }">{{
                    currentLabel
                }}</span>
                <ChevronDown
                    class="size-4 text-muted-foreground"
                    :class="{ 'hidden sm:inline': responsive }"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-40">
            <DropdownMenuRadioGroup :model-value="current">
                <DropdownMenuRadioItem
                    v-for="(label, code) in supported"
                    :key="code"
                    :value="code as string"
                    class="cursor-pointer"
                    @select="change(code as string)"
                >
                    {{ label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
