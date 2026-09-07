<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Plus, Pencil, Trash2, Search, RotateCcw } from '@lucide/vue';
import DataTable, { type DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import { toast } from 'vue-sonner';

import { destroy, index } from '@/routes/categories';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
        ],
    },
});

interface CategoryItem {
    id: number;
    code: string;
    name: string;
    created_at?: string;
    updated_at?: string;
}

// State
const items = ref<CategoryItem[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const page = ref(1);
const perPage = ref(10);
const searchQuery = ref('');

// Delete Dialog State
const isDeleteOpen = ref(false);
const isDeleting = ref(false);
const deleteError = ref<string | null>(null);
const selectedItem = ref<CategoryItem | null>(null);

// Fetch Datatable Data
async function fetchData() {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            per_page: String(perPage.value),
            search: searchQuery.value,
        });

        const res = await fetch(`/categories/datatable?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) throw new Error(`HTTP error: ${res.status}`);

        const json = await res.json();
        if (json && json.data && Array.isArray(json.data)) {
            items.value = json.data;
            totalRecords.value = json.total ?? json.data.length;
        } else if (Array.isArray(json)) {
            items.value = json;
            totalRecords.value = json.length;
        } else {
            items.value = [];
            totalRecords.value = 0;
        }
    } catch (err: any) {
        console.error('Failed to fetch categories:', err);
        toast.error('Failed to load category data.');
    } finally {
        loading.value = false;
    }
}

function onPage(event: DataTablePageEvent) {
    page.value = event.page + 1;
    perPage.value = event.rows;
    fetchData();
}

let searchTimeout: any = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchData();
    }, 400);
}

// Delete Actions
function openDeleteModal(item: CategoryItem) {
    selectedItem.value = item;
    deleteError.value = null;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!selectedItem.value) return;
    isDeleting.value = true;
    deleteError.value = null;

    router.delete(destroy(selectedItem.value.id).url, {
        onSuccess: () => {
            isDeleteOpen.value = false;
            toast.success('Category deleted successfully.');
            fetchData();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            deleteError.value = typeof firstError === 'string' ? firstError : 'Failed to delete category.';
            toast.error(deleteError.value);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head title="Categories" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Categories</h1>
                <p class="text-muted-foreground text-sm">Manage product categories and classifications</p>
            </div>
            <Button as-child>
                <Link href="/categories/create">
                    <Plus class="mr-2 size-4" />
                    Add Category
                </Link>
            </Button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-card flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:w-80">
                <Search class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                <Input
                    v-model="searchQuery"
                    placeholder="Search by code or name..."
                    class="pl-9"
                    @input="onSearchInput"
                />
            </div>
            <Button variant="outline" size="sm" :disabled="loading" @click="fetchData">
                <Spinner v-if="loading" class="mr-2" />
                <RotateCcw v-else class="mr-2 size-4" />
                Refresh
            </Button>
        </div>

        <!-- DataTable -->
        <div class="bg-card rounded-lg border overflow-hidden">
            <DataTable
                :value="items"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :total-records="totalRecords"
                :loading="loading"
                :rows-per-page-options="[5, 10, 20, 50]"
                paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                current-page-report-template="Showing {first} to {last} of {totalRecords} entries"
                class="text-sm"
                @page="onPage"
            >
                <template #empty>
                    <div class="text-muted-foreground py-8 text-center">
                        No categories found.
                    </div>
                </template>

                <Column field="code" header="Code" style="width: 20%">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.code }}</span>
                    </template>
                </Column>

                <Column field="name" header="Category Name" style="width: 45%" />

                <Column field="created_at" header="Date Created" style="width: 20%">
                    <template #body="{ data }">
                        <span>{{ data.created_at ? new Date(data.created_at).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '-' }}</span>
                    </template>
                </Column>

                <Column header="Actions" style="width: 15%">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="size-8 p-0"
                                title="Edit"
                                as-child
                            >
                                <Link :href="`/categories/${data.id}/edit`">
                                    <Pencil class="size-3.5" />
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                class="size-8 p-0"
                                title="Delete"
                                @click="openDeleteModal(data)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle>Delete Category</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete category
                        <strong v-if="selectedItem">"{{ selectedItem.name }}"</strong>?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <Alert v-if="deleteError" variant="destructive" class="my-2">
                    <AlertTitle>Deletion Failed</AlertTitle>
                    <AlertDescription>{{ deleteError }}</AlertDescription>
                </Alert>

                <DialogFooter class="pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isDeleting"
                        @click="isDeleteOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        <Spinner v-if="isDeleting" class="mr-2" />
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
