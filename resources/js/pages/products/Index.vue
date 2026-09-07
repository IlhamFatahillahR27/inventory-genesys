<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Plus, Pencil, Trash2, Search, RotateCcw } from '@lucide/vue';
import DataTable, { type DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import { toast } from 'vue-sonner';

import { destroy, index as productIndex } from '@/routes/products';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency, formatNumber } from '@/lib/numberHelper';
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
                title: 'Products',
                href: productIndex(),
            },
        ],
    },
});

interface CategoryOption {
    id: number;
    code: string;
    name: string;
}

interface ProductItem {
    id: number;
    category_id: number;
    category?: CategoryOption;
    name: string;
    description: string;
    price: number;
    stock: number;
    discount: number;
    created_at?: string;
    updated_at?: string;
}

// State
const items = ref<ProductItem[]>([]);
const categories = ref<CategoryOption[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const page = ref(1);
const perPage = ref(10);
const searchQuery = ref('');
const selectedCategoryFilter = ref<string>('');

// Delete State
const isDeleteOpen = ref(false);
const isDeleting = ref(false);
const deleteError = ref<string | null>(null);
const selectedItem = ref<ProductItem | null>(null);

// Fetch categories for select filter
async function fetchCategories() {
    try {
        const res = await fetch('/categories/datatable?per_page=100', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            const json = await res.json();
            categories.value = json.data ?? (Array.isArray(json) ? json : []);
        }
    } catch (err) {
        console.error('Failed to load categories for filter', err);
    }
}

// Fetch products for datatable
async function fetchData() {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            per_page: String(perPage.value),
            search: searchQuery.value,
        });

        if (selectedCategoryFilter.value) {
            params.append('category_id', selectedCategoryFilter.value);
        }

        const res = await fetch(`/products/datatable?${params.toString()}`, {
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
    } catch (err) {
        console.error('Failed to fetch products:', err);
        toast.error('Failed to load product data.');
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

function onCategoryFilterChange() {
    page.value = 1;
    fetchData();
}

// Delete Actions
function openDeleteModal(item: ProductItem) {
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
            toast.success('Product deleted successfully.');
            fetchData();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            deleteError.value = typeof firstError === 'string' ? firstError : 'Failed to delete product.';
            toast.error(deleteError.value);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

onMounted(() => {
    fetchCategories();
    fetchData();
});
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Products</h1>
                <p class="text-muted-foreground text-sm">Manage inventory catalog, pricing, and stock levels</p>
            </div>
            <Button as-child>
                <Link href="/products/create">
                    <Plus class="mr-2 size-4" />
                    Add Product
                </Link>
            </Button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-card flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <Search class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search by name or description..."
                        class="pl-9"
                        @input="onSearchInput"
                    />
                </div>

                <!-- Category Filter -->
                <div class="w-full sm:w-56">
                    <select
                        v-model="selectedCategoryFilter"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @change="onCategoryFilterChange"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }} ({{ cat.code }})
                        </option>
                    </select>
                </div>
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
                        No products found.
                    </div>
                </template>

                <Column field="name" header="Product Name" style="width: 25%">
                    <template #body="{ data }">
                        <div>
                            <span class="font-semibold block">{{ data.name }}</span>
                            <span class="text-muted-foreground text-xs line-clamp-1">{{ data.description }}</span>
                        </div>
                    </template>
                </Column>

                <Column field="category" header="Category" style="width: 15%">
                    <template #body="{ data }">
                        <Badge variant="secondary">
                            {{ data.category?.name ?? 'Category #' + data.category_id }}
                        </Badge>
                    </template>
                </Column>

                <Column field="price" header="Price" style="width: 15%">
                    <template #body="{ data }">
                        <div class="font-medium">
                            {{ formatCurrency(data.price) }}
                        </div>
                    </template>
                </Column>

                <Column field="discount" header="Discount" style="width: 10%">
                    <template #body="{ data }">
                        <span v-if="data.discount > 0" class="text-emerald-600 font-semibold dark:text-emerald-400">
                            {{ data.discount }}%
                        </span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                </Column>

                <Column field="stock" header="Stock" style="width: 15%">
                    <template #body="{ data }">
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                data.stock <= 5 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-secondary text-secondary-foreground'
                            ]"
                        >
                            {{ formatNumber(data.stock) }} units
                        </span>
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
                                <Link :href="`/products/${data.id}/edit`">
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
                    <DialogTitle>Delete Product</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete product
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
