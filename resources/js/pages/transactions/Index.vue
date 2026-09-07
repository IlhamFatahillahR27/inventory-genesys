<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Plus, Pencil, Trash2, Search, RotateCcw } from '@lucide/vue';
import DataTable, { type DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import { toast } from 'vue-sonner';

import { index as transactionIndex } from '@/routes/transactions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency, formatNumber } from '@/lib/numberHelper';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: transactionIndex(),
            },
        ],
    },
});

interface ProductOption {
    id: number;
    name: string;
    price: number;
    stock: number;
}

interface TransactionItem {
    id: number;
    user_id: number;
    product_id: number;
    product?: ProductOption;
    type: 'buy' | 'sell';
    quantity: number;
    unit_price: number;
    discount: number;
    total_price: number;
    created_at?: string;
    updated_at?: string;
}

// State
const items = ref<TransactionItem[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const page = ref(1);
const perPage = ref(10);
const searchQuery = ref('');
const selectedTypeFilter = ref<string>('');

// Fetch transactions for datatable
async function fetchData() {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            per_page: String(perPage.value),
            search: searchQuery.value,
        });

        if (selectedTypeFilter.value) {
            params.append('type', selectedTypeFilter.value);
        }

        const res = await fetch(`/transactions/datatable?${params.toString()}`, {
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
        console.error('Failed to fetch transactions:', err);
        toast.error('Failed to load transaction records.');
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

function onTypeFilterChange() {
    page.value = 1;
    fetchData();
}

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head title="Transactions" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Transactions</h1>
                <p class="text-muted-foreground text-sm">Track purchasing (Buy) and sales (Sell) transactions</p>
            </div>
            <Button as-child>
                <Link href="/transactions/create">
                    <Plus class="mr-2 size-4" />
                    Record Transaction
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
                        placeholder="Search transactions..."
                        class="pl-9"
                        @input="onSearchInput"
                    />
                </div>

                <!-- Type Filter -->
                <div class="w-full sm:w-48">
                    <select
                        v-model="selectedTypeFilter"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @change="onTypeFilterChange"
                    >
                        <option value="">All Types</option>
                        <option value="buy">Purchase (Buy)</option>
                        <option value="sell">Sale (Sell)</option>
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
                        No transactions found.
                    </div>
                </template>

                <Column field="created_at" header="Date" style="width: 15%">
                    <template #body="{ data }">
                        <span>{{ data.created_at ? new Date(data.created_at).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}</span>
                    </template>
                </Column>

                <Column field="product" header="Product" style="width: 25%">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.product?.name ?? 'Product #' + data.product_id }}</span>
                    </template>
                </Column>

                <Column field="type" header="Type" style="width: 12%">
                    <template #body="{ data }">
                        <Badge
                            :variant="data.type === 'buy' ? 'secondary' : 'default'"
                            :class="[
                                data.type === 'buy'
                                    ? 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300'
                                    : 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                            ]"
                        >
                            {{ data.type === 'buy' ? 'Buy' : 'Sell' }}
                        </Badge>
                    </template>
                </Column>

                <Column field="quantity" header="Quantity" style="width: 10%">
                    <template #body="{ data }">
                        <span>{{ formatNumber(data.quantity) }}</span>
                    </template>
                </Column>

                <Column field="unit_price" header="Unit Price" style="width: 15%">
                    <template #body="{ data }">
                        <span>{{ formatCurrency(data.unit_price) }}</span>
                    </template>
                </Column>

                <Column field="discount" header="Discount" style="width: 8%">
                    <template #body="{ data }">
                        <span v-if="data.discount > 0">{{ data.discount }}%</span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                </Column>

                <Column field="total_price" header="Total" style="width: 15%">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ formatCurrency(data.total_price) }}</span>
                    </template>
                </Column>

                <Column header="Actions" style="width: 10%">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="size-8 p-0"
                                title="Edit"
                                as-child
                            >
                                <Link :href="`/transactions/${data.id}/edit`">
                                    <Pencil class="size-3.5" />
                                </Link>
                            </Button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
