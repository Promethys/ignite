<script setup lang="ts">
import DeleteEntryDialog from '@/components/goal-entries/DeleteEntryDialog.vue';
import EntryNote from '@/components/goal-entries/EntryNote.vue';
import GoalEntryFormModal from '@/components/goal-entries/GoalEntryFormModal.vue';
import RecurringCheckInModal from '@/components/goal-entries/RecurringCheckInModal.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemFooter,
    ItemGroup,
    ItemTitle,
} from '@/components/ui/item';
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
import {
    today as currentDateInTimeZone,
    getLocalTimeZone,
} from '@internationalized/date';
import { useDebounceFn } from '@vueuse/core';
import { CalendarIcon, Pencil, XIcon } from 'lucide-vue-next';
import moment from 'moment';
import { DateValue } from 'reka-ui';
import { computed, ref } from 'vue';

const props = defineProps<{
    goal: Goal;
    entries: any;
    today?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'goals.breadcrumb.index',
        href: goals.index().url,
    },
    {
        title: `"${props.goal.title}"`,
        href: goals.show({ goal: props.goal }).url,
    },
    {
        title: 'goals.breadcrumb.all_entries',
        href: '',
    },
];

const isRecurring = computed(() => props.goal.type === 'recurring');

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

const defaultPlaceholder = currentDateInTimeZone(getLocalTimeZone());

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
    <Head :title="$t('goals.head.entries')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader :title="$t('goals.entries.title')">
                <template #actions>
                    <RecurringCheckInModal :goal :today v-if="isRecurring" />

                    <GoalEntryFormModal :goal v-else />
                </template>
            </PageHeader>
            <section class="space-y-2 text-sm">
                <div>
                    <div class="space-y-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                            <Input
                                type="search"
                                :placeholder="
                                    $t('goals.entries.search_placeholder')
                                "
                                v-model="searchInput"
                                class="w-full sm:flex-1"
                                @input="debouncedSearch"
                            />

                            <Popover v-model:open="dateFromCalendarOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="
                                            cn(
                                                'w-full justify-start text-left font-normal sm:w-[280px]',
                                                !dateFrom &&
                                                    'text-muted-foreground',
                                            )
                                        "
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{
                                            dateFrom
                                                ? dateFrom.toString()
                                                : $t('goals.entries.pick_date')
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

                            <Popover v-model:open="dateToCalendarOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="
                                            cn(
                                                'w-full justify-start text-left font-normal sm:w-[280px]',
                                                !dateTo &&
                                                    'text-muted-foreground',
                                            )
                                        "
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{
                                            dateTo
                                                ? dateTo.toString()
                                                : $t('goals.entries.pick_date')
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
                                            handleCalendarFilterUpdate('dateTo')
                                        "
                                    />
                                </PopoverContent>
                            </Popover>

                            <div v-if="hasActiveFilters">
                                <Button variant="link" @click="resetFilters">
                                    <XIcon />
                                    {{ $t('goals.entries.clear_filters') }}
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
                                                ? $t('common.status.loading')
                                                : $t(
                                                      'goals.entries.load_previous',
                                                  )
                                        }}
                                    </Button>
                                </div>
                            </template>

                            <ItemGroup
                                v-if="entries.data.length > 0"
                                class="gap-3 py-3"
                            >
                                <Item
                                    v-for="entry in entries.data"
                                    :key="entry.id"
                                    variant="outline"
                                >
                                    <ItemContent>
                                        <ItemTitle>
                                            {{
                                                moment(entry.entry_date).format(
                                                    'MMM DD, YYYY',
                                                )
                                            }}
                                        </ItemTitle>
                                        <ItemDescription v-if="!isRecurring">
                                            {{
                                                (entry.increment_value > 0
                                                    ? '+'
                                                    : '') +
                                                entry.increment_value
                                            }}
                                            {{ goal.unit }}
                                            <span class="text-xs">
                                                ({{ entry.previous_value }} →
                                                {{ entry.value }})
                                            </span>
                                        </ItemDescription>
                                    </ItemContent>

                                    <ItemActions>
                                        <RecurringCheckInModal
                                            :goal
                                            :today
                                            :record="entry"
                                            v-if="isRecurring"
                                        >
                                            <template #trigger>
                                                <Button size="sm">
                                                    <Pencil />
                                                    {{
                                                        $t(
                                                            'common.actions.edit',
                                                        )
                                                    }}
                                                </Button>
                                            </template>
                                        </RecurringCheckInModal>

                                        <GoalEntryFormModal
                                            :goal
                                            :record="entry"
                                            v-else
                                        >
                                            <template #trigger>
                                                <Button size="sm">
                                                    <Pencil />
                                                    {{
                                                        $t(
                                                            'common.actions.edit',
                                                        )
                                                    }}
                                                </Button>
                                            </template>
                                        </GoalEntryFormModal>

                                        <DeleteEntryDialog
                                            :goal
                                            :record="entry"
                                        />
                                    </ItemActions>

                                    <ItemFooter
                                        v-if="entry.note"
                                        class="items-start"
                                    >
                                        <EntryNote
                                            :note="entry.note"
                                            class="w-full"
                                        />
                                    </ItemFooter>
                                </Item>
                            </ItemGroup>

                            <Empty v-else>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <XIcon />
                                    </EmptyMedia>
                                    <EmptyTitle>
                                        {{ $t('goals.entries.no_result') }}
                                    </EmptyTitle>
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
                                            loading
                                                ? $t('common.status.loading')
                                                : $t('goals.entries.load_more')
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
