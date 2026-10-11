<script setup lang="ts">
import ThemeFormDialog from '@/components/settings/ThemeFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { activate, destroy, useDefault } from '@/routes/themes';
import type { Theme } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    themes: Theme[];
    themeLimit: number;
}>();

const defaultIsActive = computed(
    () => !props.themes.some((theme) => theme.is_active),
);
const limitReached = computed(() => props.themes.length >= props.themeLimit);

const formOpen = ref(false);
const themeBeingEdited = ref<Theme | null>(null);

function openForm(theme: Theme | null): void {
    themeBeingEdited.value = theme;
    formOpen.value = true;
}

function useTheme(theme: Theme): void {
    router.patch(activate(theme.id).url, {}, { preserveScroll: true });
}

function useDefaultTheme(): void {
    router.patch(useDefault().url, {}, { preserveScroll: true });
}

const themeBeingRemoved = ref<Theme | null>(null);

function remove(): void {
    if (!themeBeingRemoved.value) {
        return;
    }

    router.delete(destroy(themeBeingRemoved.value.id).url, {
        preserveScroll: true,
        onFinish: () => (themeBeingRemoved.value = null),
    });
}
</script>

<template>
    <div class="@container space-y-4">
        <Item variant="outline" data-test="theme-default">
            <ItemMedia>
                <span
                    class="size-8 border bg-[var(--brand-mark-base)]"
                    aria-hidden="true"
                />
            </ItemMedia>
            <ItemContent class="min-w-0">
                <ItemTitle>
                    {{ $t('settings.themes.default_name') }}
                    <Badge v-if="defaultIsActive">
                        {{ $t('settings.themes.in_use') }}
                    </Badge>
                </ItemTitle>
                <ItemDescription>
                    {{ $t('settings.themes.default_description') }}
                </ItemDescription>
            </ItemContent>
            <ItemActions v-if="!defaultIsActive">
                <Button variant="outline" size="sm" @click="useDefaultTheme">
                    {{ $t('settings.themes.use') }}
                </Button>
            </ItemActions>
        </Item>

        <Item
            v-for="theme in themes"
            :key="theme.id"
            variant="outline"
            :data-test="`theme-${theme.id}`"
            class="@lg:flex-nowrap"
        >
            <ItemMedia>
                <span class="flex size-8 border" aria-hidden="true">
                    <span
                        class="h-full flex-1"
                        :style="{ backgroundColor: theme.light_color }"
                    />
                    <span
                        v-if="theme.dark_color"
                        class="h-full flex-1"
                        :style="{ backgroundColor: theme.dark_color }"
                    />
                </span>
            </ItemMedia>
            <ItemContent class="min-w-0">
                <ItemTitle>
                    <span class="truncate">{{ theme.name }}</span>
                    <Badge v-if="theme.is_active">
                        {{ $t('settings.themes.in_use') }}
                    </Badge>
                </ItemTitle>
                <ItemDescription class="uppercase">
                    {{
                        theme.dark_color
                            ? `${theme.light_color} / ${theme.dark_color}`
                            : theme.light_color
                    }}
                </ItemDescription>
            </ItemContent>
            <ItemActions
                class="basis-full flex-wrap pl-12 @lg:basis-auto @lg:flex-nowrap @lg:pl-0"
            >
                <Button
                    v-if="!theme.is_active"
                    variant="outline"
                    size="sm"
                    @click="useTheme(theme)"
                >
                    {{ $t('settings.themes.use') }}
                </Button>
                <Button variant="outline" size="sm" @click="openForm(theme)">
                    {{ $t('common.actions.edit') }}
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="themeBeingRemoved = theme"
                >
                    {{ $t('common.actions.delete') }}
                </Button>
            </ItemActions>
        </Item>

        <div class="flex flex-wrap items-center gap-3">
            <Button
                size="sm"
                :disabled="limitReached"
                data-test="theme-new"
                @click="openForm(null)"
            >
                {{ $t('settings.themes.new') }}
            </Button>
            <p class="text-sm text-muted-foreground">
                {{
                    $t('settings.themes.limit_note', {
                        max: String(themeLimit),
                    })
                }}
            </p>
        </div>

        <ThemeFormDialog
            :key="themeBeingEdited?.id ?? 'new'"
            v-model:open="formOpen"
            :theme="themeBeingEdited"
        />

        <Dialog
            :open="themeBeingRemoved !== null"
            @update:open="(open) => !open && (themeBeingRemoved = null)"
        >
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>
                        {{ $t('settings.themes.remove_title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            $t('settings.themes.remove_confirm', {
                                name: themeBeingRemoved?.name ?? '',
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            {{ $t('common.actions.cancel') }}
                        </Button>
                    </DialogClose>
                    <Button variant="destructive" @click="remove">
                        {{ $t('common.actions.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
