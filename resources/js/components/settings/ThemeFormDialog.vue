<script setup lang="ts">
import CharacterCounter from '@/components/CharacterCounter.vue';
import InputError from '@/components/InputError.vue';
import InputRequiredIndicator from '@/components/InputRequiredIndicator.vue';
import ThemePreview from '@/components/settings/ThemePreview.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FIELD_LIMITS } from '@/lib/field-limits';
import { store, update } from '@/routes/themes';
import type { Theme } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const DEFAULT_COLOR = '#2563eb';

const props = defineProps<{
    theme: Theme | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const action = props.theme ? update(props.theme.id) : store();

function initialValues() {
    const lightColor = props.theme?.light_color ?? DEFAULT_COLOR;

    return {
        name: props.theme?.name ?? '',
        light_color: lightColor,
        dark_color: props.theme?.dark_color ?? lightColor,
    };
}

const form = useForm(initialValues()).withPrecognition(action);

const usesDifferentDarkColor = ref(Boolean(props.theme?.dark_color));

form.transform((data) => ({
    ...data,
    dark_color: usesDifferentDarkColor.value ? data.dark_color : null,
}));

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    Object.assign(form, initialValues());
    form.clearErrors();
    usesDifferentDarkColor.value = Boolean(props.theme?.dark_color);
});

function submit(): void {
    form.submit(action, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{
                        $t(
                            theme
                                ? 'settings.themes.edit_title'
                                : 'settings.themes.create_title',
                        )
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ $t('settings.themes.dialog_description') }}
                </DialogDescription>
            </DialogHeader>

            <form id="theme-form" class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="theme_name">
                            <span>
                                {{ $t('settings.themes.name') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <CharacterCounter
                            :value="form.name"
                            :max="FIELD_LIMITS.themeName"
                        />
                    </div>
                    <Input
                        id="theme_name"
                        v-model="form.name"
                        type="text"
                        autocomplete="off"
                        aria-required="true"
                        :placeholder="$t('settings.themes.name_placeholder')"
                        @change="form.validate('name')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="theme_light_color">
                        {{
                            $t(
                                usesDifferentDarkColor
                                    ? 'settings.themes.light_color'
                                    : 'settings.themes.color',
                            )
                        }}
                    </Label>
                    <div class="flex items-center gap-3">
                        <Input
                            id="theme_light_color"
                            v-model="form.light_color"
                            type="color"
                            class="w-16 p-1"
                            @change="form.validate('light_color')"
                        />
                        <span
                            class="font-mono text-sm text-muted-foreground uppercase"
                        >
                            {{ form.light_color }}
                        </span>
                    </div>
                    <InputError :message="form.errors.light_color" />
                </div>

                <div class="flex items-start gap-2">
                    <Checkbox
                        id="theme_different_dark"
                        :model-value="usesDifferentDarkColor"
                        @update:model-value="
                            (checked) =>
                                (usesDifferentDarkColor = checked === true)
                        "
                    />
                    <Label
                        for="theme_different_dark"
                        class="text-sm leading-tight font-normal"
                    >
                        {{ $t('settings.themes.different_dark') }}
                    </Label>
                </div>

                <div v-if="usesDifferentDarkColor" class="grid gap-2">
                    <Label for="theme_dark_color">
                        {{ $t('settings.themes.dark_color') }}
                    </Label>
                    <div class="flex items-center gap-3">
                        <Input
                            id="theme_dark_color"
                            v-model="form.dark_color"
                            type="color"
                            class="w-16 p-1"
                            @change="form.validate('dark_color')"
                        />
                        <span
                            class="font-mono text-sm text-muted-foreground uppercase"
                        >
                            {{ form.dark_color }}
                        </span>
                    </div>
                    <InputError :message="form.errors.dark_color" />
                </div>

                <div class="grid gap-2">
                    <span class="text-sm font-medium">
                        {{ $t('settings.themes.preview') }}
                    </span>
                    <ThemePreview
                        :light-color="form.light_color"
                        :dark-color="
                            usesDifferentDarkColor
                                ? form.dark_color
                                : form.light_color
                        "
                    />
                </div>
            </form>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">
                        {{ $t('common.actions.cancel') }}
                    </Button>
                </DialogClose>
                <Button
                    type="submit"
                    form="theme-form"
                    :disabled="form.processing"
                >
                    {{ $t('common.actions.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
