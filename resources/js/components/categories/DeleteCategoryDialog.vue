<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import categories from '@/routes/categories';
import { Category } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Trash } from 'lucide-vue-next';

const props = defineProps<{ record: Category }>();

const deleteCategory = () => router.delete(categories.destroy(props.record));
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <slot name="trigger">
                <Button variant="ghost" size="sm" class="text-destructive">
                    <Trash />
                    {{ $t('common.actions.delete') }}
                </Button>
            </slot>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    {{ $t('common.confirm.title') }}
                </AlertDialogTitle>
                <AlertDialogDescription>
                    {{ $t('categories.delete.description') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>
                    {{ $t('common.actions.cancel') }}
                </AlertDialogCancel>
                <AlertDialogAction
                    variant="destructive"
                    @click="deleteCategory"
                >
                    {{ $t('common.actions.delete') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
