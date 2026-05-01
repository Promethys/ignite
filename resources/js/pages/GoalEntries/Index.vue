<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal } from '@/types/models';
import { Head, InfiniteScroll, router } from '@inertiajs/vue3';
import { getLocalTimeZone, today } from '@internationalized/date';
import { useDebounceFn } from '@vueuse/core';
import { CalendarIcon, XIcon } from 'lucide-vue-next';
import moment from 'moment';
import { DateValue } from 'reka-ui';
import { computed, ref } from 'vue';

const props = defineProps<{
    goal: Goal;
    entries: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
    {
        title: `"${props.goal.title}"`,
        href: goals.show({ goal: props.goal }).url,
    },
    {
        title: `All Entries`,
        href: '',
    },
];

const searchInput = ref();
const dateFrom = ref<DateValue>();
const dateTo = ref<DateValue>();
const dateFromCalendarOpen = ref<boolean>(false);
const dateToCalendarOpen = ref<boolean>(false);
const isSearchLoading = ref(false);

const hasActiveFilters = computed(() => {
    return !!searchInput.value || !!dateFrom.value || !!dateTo.value;
});

const scrollKey = computed(
    () =>
        `${searchInput.value ?? ''}-${dateFrom.value?.toString() ?? ''}-${dateTo.value?.toString() ?? ''}`,
);

const defaultPlaceholder = today(getLocalTimeZone());

const debouncedSearch = useDebounceFn(() => {
    router.reload({
        only: ['entries'],
        reset: ['entries'],
        data: {
            search: searchInput.value,
            from: dateFrom.value?.toString(),
            to: dateTo.value?.toString(),
        },
        onStart: () => (isSearchLoading.value = true),
        onFinish: () => (isSearchLoading.value = false),
    });
}, 400);

const handleCalendarFilterUpdate = (ref: string) => {
    if (ref === 'dateFrom') {
        dateFromCalendarOpen.value = false;
    } else {
        dateToCalendarOpen.value = false;
    }

    debouncedSearch();
};

const resetFilters = () => {
    searchInput.value = undefined;
    dateFrom.value = undefined;
    dateTo.value = undefined;

    debouncedSearch();
};
</script>

<template>
    <Head title="Goal Entries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="space-y-2 text-sm">
                <h3 class="text-xl font-medium">All Entries</h3>
                <div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-4">
                            <Input
                                type="search"
                                placeholder="Search..."
                                v-model="searchInput"
                                class="max-w-md"
                                @input="debouncedSearch"
                            />

                            <div>
                                <Popover v-model:open="dateFromCalendarOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="
                                                cn(
                                                    'w-[280px] justify-start text-left font-normal',
                                                    !dateFrom &&
                                                        'text-muted-foreground',
                                                )
                                            "
                                        >
                                            <CalendarIcon
                                                class="mr-2 h-4 w-4"
                                            />
                                            {{
                                                dateFrom
                                                    ? dateFrom.toString()
                                                    : 'Pick a date'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0">
                                        <Calendar
                                            v-model="dateFrom"
                                            :default-placeholder="
                                                defaultPlaceholder
                                            "
                                            layout="month-and-year"
                                            @update:model-value="
                                                handleCalendarFilterUpdate(
                                                    'dateFrom',
                                                )
                                            "
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div>
                                <Popover v-model:open="dateToCalendarOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="
                                                cn(
                                                    'w-[280px] justify-start text-left font-normal',
                                                    !dateTo &&
                                                        'text-muted-foreground',
                                                )
                                            "
                                        >
                                            <CalendarIcon
                                                class="mr-2 h-4 w-4"
                                            />
                                            {{
                                                dateTo
                                                    ? dateTo.toString()
                                                    : 'Pick a date'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0">
                                        <Calendar
                                            v-model="dateTo"
                                            :default-placeholder="
                                                defaultPlaceholder
                                            "
                                            layout="month-and-year"
                                            @update:model-value="
                                                handleCalendarFilterUpdate(
                                                    'dateTo',
                                                )
                                            "
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div v-if="hasActiveFilters">
                                <Button variant="link" @click="resetFilters">
                                    <XIcon />
                                    Clear filters
                                </Button>
                            </div>
                        </div>
                        <InfiniteScroll
                            :key="scrollKey"
                            data="entries"
                            manual
                            class="relative"
                        >
                            <div
                                v-if="isSearchLoading"
                                class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-background/50 backdrop-blur-xs"
                            >
                                <Spinner class="h-6 w-6 text-primary" />
                            </div>

                            <template #previous="{ loading, fetch, hasMore }">
                                <div class="text-center">
                                    <Button
                                        v-if="hasMore"
                                        @click="fetch"
                                        :disabled="loading"
                                    >
                                        {{
                                            loading
                                                ? 'Loading...'
                                                : 'Load previous'
                                        }}
                                    </Button>
                                </div>
                            </template>

                            <template v-if="entries.data.length > 0">
                                <div
                                    v-for="entry in entries.data"
                                    :key="entry.id"
                                    class="my-3 space-y-2 rounded-lg border p-4"
                                >
                                    <!-- Entry Header: Date and Value -->
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div>
                                            <p class="font-medium">
                                                {{
                                                    moment(
                                                        entry.entry_date,
                                                    ).format('MMM DD, YYYY')
                                                }}
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                {{
                                                    (entry.increment_value > 0
                                                        ? '+'
                                                        : '') +
                                                    entry.increment_value
                                                }}
                                                {{ goal.unit }}
                                                <span class="text-xs">
                                                    ({{
                                                        entry.previous_value
                                                    }}
                                                    → {{ entry.value }})
                                                </span>
                                            </p>
                                        </div>
                                        <!-- Delete button -->
                                        <div class="max-w-md space-y-4">
                                            <Dialog>
                                                <DialogTrigger as-child>
                                                    <Button
                                                        variant="destructive"
                                                    >
                                                        Delete
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent
                                                    class="sm:max-w-[425px]"
                                                >
                                                    <DialogHeader>
                                                        <DialogTitle
                                                            >Delete
                                                            entry</DialogTitle
                                                        >
                                                        <DialogDescription>
                                                            Delete that entry
                                                            from progress
                                                            history?
                                                        </DialogDescription>
                                                    </DialogHeader>

                                                    <DialogFooter>
                                                        <DialogClose as-child>
                                                            <Button
                                                                type="button"
                                                                variant="secondary"
                                                            >
                                                                Cancel
                                                            </Button>
                                                        </DialogClose>
                                                        <Button
                                                            variant="destructive"
                                                            @click="
                                                                router.delete(
                                                                    goals.entries.destroy(
                                                                        {
                                                                            goal,
                                                                            goalEntry:
                                                                                entry.id,
                                                                        },
                                                                    ),
                                                                )
                                                            "
                                                        >
                                                            Delete
                                                        </Button>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </div>

                                    <!-- Entry Note (if exists) -->
                                    <p v-if="entry.note" class="text-sm">
                                        {{ entry.note }}
                                    </p>
                                </div>
                            </template>

                            <Empty v-else>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <XIcon />
                                    </EmptyMedia>
                                    <EmptyTitle> No result found. </EmptyTitle>
                                </EmptyHeader>
                            </Empty>

                            <template #next="{ loading, fetch, hasMore }">
                                <div class="text-center">
                                    <Button
                                        v-if="hasMore"
                                        @click="fetch"
                                        :disabled="loading"
                                    >
                                        {{
                                            loading ? 'Loading...' : 'Load more'
                                        }}
                                    </Button>
                                </div>
                            </template>
                        </InfiniteScroll>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
