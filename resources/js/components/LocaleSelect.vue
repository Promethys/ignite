<script setup lang="ts">
import LocaleController from '@/actions/App/Http/Controllers/Settings/LocaleController';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/composables/useLocale';
import { router } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';

const { current, supported, switchTo } = useLocale();

async function change(code: string) {
    if (current.value === code) {
        return;
    }

    await switchTo(code);

    router.patch(LocaleController.update.url(), { locale: code });
}
</script>

<template>
    <Select :model-value="current" @update:model-value="change">
        <SelectTrigger :aria-label="$t('settings.appearance.language')">
            <span class="flex items-center gap-2">
                <Globe class="size-4 shrink-0 text-muted-foreground" />
                <SelectValue
                    :placeholder="$t('settings.appearance.select_language')"
                />
            </span>
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="(label, code) in supported"
                :key="code"
                :value="code as string"
            >
                {{ label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
