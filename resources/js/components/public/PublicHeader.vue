<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dashboard, features, home, login, register, selfHost } from '@/routes';
import type { AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage<AppPageProps>();
const open = ref(false);
const links = computed(() => [
    { key: 'features', href: features().url, external: false },
    {
        key: 'docs',
        href: 'https://ignite-docs.promethys.dev/guide/',
        external: true,
    },
    { key: 'self_host', href: selfHost().url, external: false },
    { key: 'github', href: page.props.githubUrl, external: true },
]);
const isActive = (href: string) => page.url.split(/[?#]/)[0] === href;
</script>

<template>
    <header class="z-30 border-b bg-background">
        <div
            class="mx-auto flex w-full max-w-7xl items-center gap-6 px-4 py-4 sm:px-6"
        >
            <Link :href="home()" :aria-label="$t('public.nav.home')"
                ><AppLogo hide-label-on-mobile
            /></Link>
            <nav :aria-label="$t('public.nav.main')" class="hidden xl:block">
                <ul class="flex items-center gap-4">
                    <li v-for="link in links" :key="link.key">
                        <component
                            :is="link.external ? 'a' : Link"
                            :href="link.href"
                            :target="link.external ? '_blank' : undefined"
                            :rel="
                                link.external
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                            :aria-current="
                                !link.external && isActive(link.href)
                                    ? 'page'
                                    : undefined
                            "
                            class="inline-flex min-h-11 items-center border-b-2 border-transparent text-sm text-muted-foreground hover:text-foreground aria-[current=page]:border-primary aria-[current=page]:text-foreground"
                        >
                            {{ $t(`public.nav.${link.key}`) }}
                        </component>
                    </li>
                </ul>
            </nav>
            <div class="ml-auto flex items-center gap-2">
                <LanguageSwitcher />
                <div class="hidden items-center gap-2 md:flex">
                    <Button
                        v-if="page.props.auth.user"
                        as-child
                        variant="outline"
                        ><Link :href="dashboard()">{{
                            $t('common.nav.dashboard')
                        }}</Link></Button
                    >
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex min-h-11 items-center px-3 text-sm hover:underline"
                            >{{ $t('common.actions.log_in') }}</Link
                        >
                        <Button as-child variant="default"
                            ><Link :href="register()">{{
                                $t('common.actions.get_started')
                            }}</Link></Button
                        >
                    </template>
                </div>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child
                        ><Button
                            variant="outline"
                            size="icon"
                            class="size-11 xl:hidden"
                            :aria-label="$t('public.nav.open_menu')"
                            ><Menu aria-hidden="true" /></Button
                    ></SheetTrigger>
                    <SheetContent
                        class="public-surface dark w-full bg-background text-foreground sm:max-w-sm [&>button:last-child]:hidden"
                    >
                        <div class="absolute top-3 right-3">
                            <SheetClose as-child
                                ><Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-11"
                                    :aria-label="$t('public.nav.close_menu')"
                                    ><X aria-hidden="true" /></Button
                            ></SheetClose>
                        </div>
                        <SheetHeader>
                            <SheetTitle>{{ $t('public.nav.menu') }}</SheetTitle>
                            <SheetDescription class="sr-only">{{
                                $t('public.nav.main')
                            }}</SheetDescription>
                        </SheetHeader>
                        <nav :aria-label="$t('public.nav.main')" class="px-4">
                            <component
                                :is="link.external ? 'a' : Link"
                                v-for="link in links"
                                :key="link.key"
                                :href="link.href"
                                :target="link.external ? '_blank' : undefined"
                                :rel="
                                    link.external
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                :aria-current="
                                    !link.external && isActive(link.href)
                                        ? 'page'
                                        : undefined
                                "
                                class="flex min-h-12 items-center border-b py-3 aria-[current=page]:text-[var(--primary-text)]"
                                @click="open = false"
                                >{{ $t(`public.nav.${link.key}`) }}</component
                            >
                        </nav>
                        <div class="mt-auto flex flex-col gap-3 p-4">
                            <Button
                                v-if="page.props.auth.user"
                                as-child
                                variant="outline"
                                ><Link
                                    :href="dashboard()"
                                    @click="open = false"
                                    >{{ $t('common.nav.dashboard') }}</Link
                                ></Button
                            >
                            <template v-else>
                                <Link
                                    :href="login()"
                                    class="py-3 text-center"
                                    @click="open = false"
                                    >{{ $t('common.actions.log_in') }}</Link
                                >
                                <Button as-child variant="default"
                                    ><Link
                                        :href="register()"
                                        @click="open = false"
                                        >{{
                                            $t('common.actions.get_started')
                                        }}</Link
                                    ></Button
                                >
                            </template>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
