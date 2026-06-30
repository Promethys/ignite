<script setup lang="ts" generic="TModel">
import { createColumnHelper, FlexRender, getCoreRowModel, Table, useVueTable } from '@tanstack/vue-table';
import { computed, ref } from 'vue';
import { Button } from '../button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'
import { Checkbox } from '@/components/ui/checkbox';
import { ChevronDown, ChevronUp, RefreshCw, Search, X } from 'lucide-vue-next';
import { Input } from '../input';
import { Empty, EmptyMedia, EmptyTitle } from '../empty';

const props = defineProps<{
    table: Table<TModel>
}>();

// const data = ref<TModel[]>([]);

// const rerender = () => {
//     data.value = defaultValue;
// }

const shouldShowFooter = computed(() => {
    return props.table.getFooterGroups().some((footerGroup) => {
        return footerGroup.headers.some((header) => {
            return header.column.columnDef.footer != null;
        });
    });
});

const isTableEmpty = computed(() => {
    return props.table.getRowModel().rows.length === 0;
});

</script>

<template>
    <div class="relative overflow-x-auto bg-primary-foreground shadow-xs rounded-lg border border-border">
        <!-- <div class="p-4 flex items-center justify-between space-x-4">
            <label for="input-group" class="sr-only">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <Search class="size-4" />
                </div>
                <Input type="text" id="input-group" placeholder="Search" class="ps-9" />
            </div>

            <Select v-model="selectedCategoryId">
                <SelectTrigger>
                    <SelectValue placeholder="Category" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">
                        All categories
                    </SelectItem>
                    <SelectItem :value="null"> No category </SelectItem>
                    <template v-if="categories.length > 0">
                        <Separator class="my-2" />
                    </template>
                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div> -->
        <table class="w-full text-sm text-left rtl:text-right text-foreground">
            <caption v-if="$slots.caption">
                <slot name="caption" />
            </caption>
            <thead class="text-sm text-foreground bg-muted border-b">
                <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                    <!-- <th scope="col ">
                        <Checkbox id="table-checkbox" name="table-checkbox" value="" />
                        <label for="table-checkbox" class="sr-only">Table checkbox</label>
                    </th> -->
                    <th v-for="header in headerGroup.headers" :key="header.id" :colSpan="header.colSpan" scope="col" class="px-6 py-3 font-medium">
                        <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                            :props="header.getContext()" />
                        <!-- <a href="#">
                            <ChevronUp class="size-3 ms-2" />
                            <ChevronDown class="size-3 ms-2" />
                        </a> -->
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in table.getRowModel().rows" :key="row.id" class="bg-primary-foreground border-b border-border hover:bg-muted">
                    <!-- <td class="p-4 w-0">
                        <Checkbox id="table-checkbox-2" name="table-checkbox-2" value="" />
                        <label for="table-checkbox-2" class="sr-only">Table checkbox</label>
                    </td> -->
                    <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-6 py-4">
                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                    </td>
                </tr>
                <tr v-if="isTableEmpty">
                    <td :colspan="table.getAllLeafColumns().length">
                        <Empty>
                            <EmptyTitle>
                                <EmptyMedia class="mx-auto" variant="icon">
                                    <X />
                                </EmptyMedia>
                                No records yet.
                            </EmptyTitle>
                        </Empty>
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="shouldShowFooter">
                <tr v-for="footerGroup in table.getFooterGroups()" :key="footerGroup.id" class="font-semibold text-heading">
                    <th v-for="header in footerGroup.headers" :key="header.id" :colSpan="header.colSpan" scope="row" class="px-6 py-3 text-base">
                        <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.footer"
                            :props="header.getContext()" />
                    </th>
                </tr>
            </tfoot>
        </table>
        <!-- <nav v-if="!isTableEmpty"
            class="flex items-center flex-column flex-wrap md:flex-row justify-between px-6 py-3"
            aria-label="Table navigation"> -->
            <!-- <span class="text-sm font-normal text-foreground mb-4 md:mb-0 block w-full md:inline md:w-auto">Showing <span
                    class="font-semibold text-heading">1-10</span> of <span
                    class="font-semibold text-heading">1000</span></span> -->

            <!-- FIXME: conditionnal mx-auto here, when the pagination elements are shown -->
            <!-- <div class="text-center mx-auto">
                <Button @click="rerender" class="mx-auto" variant="link">
                    <RefreshCw class="size-4" />
                    Rerender
                </Button>
            </div> -->

            <!-- <div>
                <Pagination v-slot="{ page }" :items-per-page="10" :total="30" :default-page="2">
                    <PaginationContent v-slot="{ items }">
                        <PaginationPrevious />

                        <template v-for="(item, index) in items" :key="index">
                            <PaginationItem v-if="item.type === 'page'" :value="item.value"
                                :is-active="item.value === page">
                                {{ item.value }}
                            </PaginationItem>
                        </template>

                        <PaginationEllipsis :index="4" />

                        <PaginationNext />
                    </PaginationContent>
                </Pagination>
            </div> -->
        <!-- </nav> -->
    </div>
</template>
