<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/two-factor/login';
import { Form, Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface AuthConfigContent {
    title: string;
    description: string;
    toggleText: string;
}

const authConfigContent = computed<AuthConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: trans('auth_ui.two_factor.recovery_title'),
            description: trans('auth_ui.two_factor.recovery_description'),
            toggleText: trans('auth_ui.two_factor.toggle_to_auth'),
        };
    }

    return {
        title: trans('auth_ui.two_factor.auth_title'),
        description: trans('auth_ui.two_factor.auth_description'),
        toggleText: trans('auth_ui.two_factor.toggle_to_recovery'),
    };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = [];
};

const code = ref<number[]>([]);
const codeValue = computed<string>(() => code.value.join(''));
</script>

<template>
    <AuthLayout
        :title="authConfigContent.title"
        :description="authConfigContent.description"
    >
        <Head :title="$t('auth_ui.two_factor.head')" />

        <div class="space-y-6">
            <template v-if="!showRecoveryInput">
                <Form
                    v-bind="store.form()"
                    class="space-y-4"
                    reset-on-error
                    @error="code = []"
                    #default="{ errors, processing, clearErrors }"
                >
                    <input type="hidden" name="code" :value="codeValue" />
                    <div
                        class="flex flex-col items-center justify-center space-y-3 text-center"
                    >
                        <div class="flex w-full items-center justify-center">
                            <PinInput
                                id="otp"
                                placeholder="○"
                                v-model="code"
                                type="number"
                                otp
                            >
                                <PinInputGroup>
                                    <PinInputSlot
                                        v-for="(id, index) in 6"
                                        :key="id"
                                        :index="index"
                                        :disabled="processing"
                                        autofocus
                                    />
                                </PinInputGroup>
                            </PinInput>
                        </div>
                        <InputError :message="errors.code" />
                    </div>
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="processing"
                        >{{ $t('auth_ui.two_factor.continue') }}</Button
                    >
                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ $t('auth_ui.two_factor.or_you_can') }} </span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </Form>
            </template>

            <template v-else>
                <Form
                    v-bind="store.form()"
                    class="space-y-4"
                    reset-on-error
                    #default="{ errors, processing, clearErrors }"
                >
                    <Input
                        name="recovery_code"
                        type="text"
                        :placeholder="
                            $t('auth_ui.two_factor.recovery_placeholder')
                        "
                        :autofocus="showRecoveryInput"
                        required
                    />
                    <InputError :message="errors.recovery_code" />
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="processing"
                        >{{ $t('auth_ui.two_factor.continue') }}</Button
                    >

                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ $t('auth_ui.two_factor.or_you_can') }} </span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </Form>
            </template>
        </div>
    </AuthLayout>
</template>
