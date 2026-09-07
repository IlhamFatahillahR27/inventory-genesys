<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { ArrowLeft, PlusCircle, Save } from '@lucide/vue';
import { toast } from 'vue-sonner';

import { store, index as transactionIndex, update } from '@/routes/transactions';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import NumberInput from '@/components/NumberInput.vue';
import { formatCurrency } from '@/lib/numberHelper';

interface ProductOption {
    id: number;
    name: string;
    price: number;
    stock: number;
    discount?: number;
}

interface TransactionData {
    id?: number;
    product_id?: number | string;
    type?: 'buy' | 'sell';
    quantity?: number;
    unit_price?: number;
    discount?: number;
    total_price?: number;
}

const props = defineProps<{
    transaction?: TransactionData;
    products?: ProductOption[];
}>();

const isEdit = computed(() => Boolean(props.transaction?.id));
const isAddMore = ref(false);

const productOptions = ref<ProductOption[]>(props.products ?? []);

const form = useForm({
    product_id: props.transaction?.product_id ? String(props.transaction.product_id) : '',
    type: (props.transaction?.type ?? 'sell') as 'buy' | 'sell',
    quantity: props.transaction?.quantity ?? 1,
    unit_price: props.transaction?.unit_price ?? 0,
    discount: props.transaction?.discount ?? 0,
    total_price: props.transaction?.total_price ?? 0,
});

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

// Dynamic calculation of total_price
watch(
    () => [form.quantity, form.unit_price, form.discount],
    ([qty, price, disc]) => {
        const subtotal = Number(qty || 0) * Number(price || 0);
        const discountAmount = Math.round((subtotal * Number(disc || 0)) / 100);
        form.total_price = Math.max(0, subtotal - discountAmount);
    },
    { immediate: true },
);

function onProductChange() {
    const prod = productOptions.value.find((p) => p.id === Number(form.product_id));
    if (prod) {
        form.unit_price = prod.price;
        form.discount = prod.discount ?? 0;
    }
}

async function fetchProductsIfEmpty() {
    if (productOptions.value.length === 0) {
        try {
            const res = await fetch('/products/datatable?per_page=100', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                const json = await res.json();
                productOptions.value = json.data ?? (Array.isArray(json) ? json : []);
            }
        } catch (err) {
            console.error('Failed to load products for select option', err);
        }
    }
}

function handleSave() {
    isAddMore.value = false;

    const payload = {
        product_id: Number(form.product_id),
        type: form.type,
        quantity: Number(form.quantity),
        unit_price: Number(form.unit_price),
        discount: Number(form.discount),
        total_price: Number(form.total_price),
    };

    if (isEdit.value && props.transaction?.id) {
        form.transform(() => payload).put(update(props.transaction.id).url, {
            onSuccess: () => {
                toast.success('Transaction updated successfully.');
                router.visit('/transactions');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to update transaction.');
            },
        });
    } else {
        form.transform(() => payload).post(store().url, {
            onSuccess: () => {
                toast.success('Transaction recorded successfully.');
                router.visit('/transactions');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to record transaction.');
            },
        });
    }
}

function handleSaveAndAddMore() {
    isAddMore.value = true;

    const payload = {
        product_id: Number(form.product_id),
        type: form.type,
        quantity: Number(form.quantity),
        unit_price: Number(form.unit_price),
        discount: Number(form.discount),
        total_price: Number(form.total_price),
    };

    form.transform(() => payload).post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('product_id', 'quantity', 'unit_price', 'discount', 'total_price');
            form.clearErrors();
            toast.success('Transaction recorded successfully. You can add another one.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(typeof firstError === 'string' ? firstError : 'Failed to record transaction.');
        },
        onFinish: () => {
            isAddMore.value = false;
        },
    });
}

onMounted(() => {
    fetchProductsIfEmpty();
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Transaction' : 'Record Transaction'" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl mx-auto w-full">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" as-child class="size-8 p-0">
                    <Link href="/transactions">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ isEdit ? 'Edit Transaction' : 'Record Transaction' }}
                </h1>
            </div>
            <p class="text-muted-foreground text-sm pl-10">
                {{ isEdit ? 'Update transaction details.' : 'Record a new purchase (Buy) or sale (Sell) transaction.' }}
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-card rounded-xl border p-6 shadow-xs">
            <Alert v-if="form.hasErrors" variant="destructive" class="mb-6">
                <AlertTitle>Validation Error</AlertTitle>
                <AlertDescription>
                    Please resolve the errors below before submitting the form.
                </AlertDescription>
            </Alert>

            <form class="space-y-6" @submit.prevent="handleSave">
                <!-- Product Selection -->
                <div class="space-y-2">
                    <Label for="product_id" class="text-sm font-semibold">
                        Product <span class="text-destructive">*</span>
                    </Label>
                    <select
                        id="product_id"
                        v-model="form.product_id"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.product_id }"
                        required
                        @change="onProductChange"
                    >
                        <option disabled value="">Select a Product</option>
                        <option v-for="prod in productOptions" :key="prod.id" :value="String(prod.id)">
                            {{ prod.name }} (Stock: {{ prod.stock }}, Price: {{ formatCurrency(prod.price) }})
                        </option>
                    </select>
                    <p v-if="form.errors.product_id" class="text-destructive text-xs">
                        {{ form.errors.product_id }}
                    </p>
                </div>

                <!-- Transaction Type -->
                <div class="space-y-2">
                    <Label for="type" class="text-sm font-semibold">
                        Transaction Type <span class="text-destructive">*</span>
                    </Label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                        required
                    >
                        <option value="sell">Sale (Sell)</option>
                        <option value="buy">Purchase (Buy)</option>
                    </select>
                    <p v-if="form.errors.type" class="text-destructive text-xs">
                        {{ form.errors.type }}
                    </p>
                </div>

                <!-- Numbers Row -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <!-- Quantity -->
                    <div class="space-y-2">
                        <Label for="quantity" class="text-sm font-semibold">
                            Quantity <span class="text-destructive">*</span>
                        </Label>
                        <NumberInput
                            id="quantity"
                            v-model="form.quantity"
                            placeholder="1"
                            :min="1"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.quantity" class="text-destructive text-xs">
                            {{ form.errors.quantity }}
                        </p>
                    </div>

                    <!-- Unit Price -->
                    <div class="space-y-2">
                        <Label for="unit_price" class="text-sm font-semibold">
                            Unit Price <span class="text-destructive">*</span>
                        </Label>
                        <NumberInput
                            id="unit_price"
                            v-model="form.unit_price"
                            prefix="Rp"
                            placeholder="0"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.unit_price" class="text-destructive text-xs">
                            {{ form.errors.unit_price }}
                        </p>
                    </div>

                    <!-- Discount -->
                    <div class="space-y-2">
                        <Label for="discount" class="text-sm font-semibold">
                            Discount (%)
                        </Label>
                        <NumberInput
                            id="discount"
                            v-model="form.discount"
                            placeholder="0"
                            :max="100"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.discount" class="text-destructive text-xs">
                            {{ form.errors.discount }}
                        </p>
                    </div>
                </div>

                <!-- Total Price Live Calculation Display -->
                <div class="bg-muted/50 rounded-lg p-4 border flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium block">Total Price:</span>
                        <span class="text-muted-foreground text-xs">
                            Qty ({{ form.quantity }}) &times; Price ({{ formatCurrency(form.unit_price) }}) - Disc ({{ form.discount }}%)
                        </span>
                    </div>
                    <span class="text-xl font-bold text-primary">
                        {{ formatCurrency(form.total_price) }}
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                        :disabled="form.processing"
                    >
                        <Link href="/transactions">
                            <ArrowLeft class="mr-2 size-4" />
                            Back
                        </Link>
                    </Button>

                    <div class="flex items-center gap-3 justify-end">
                        <!-- Save & Add More (Only in Create mode) -->
                        <!-- <Button
                            v-if="!isEdit"
                            type="button"
                            variant="secondary"
                            :disabled="form.processing"
                            @click="handleSaveAndAddMore"
                        >
                            <Spinner v-if="form.processing && isAddMore" class="mr-2" />
                            <PlusCircle v-else class="mr-2 size-4" />
                            Save & Add More
                        </Button> -->

                        <!-- Standard Save / Update -->
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing && !isAddMore" class="mr-2" />
                            <Save v-else class="mr-2 size-4" />
                            {{ isEdit ? 'Save Changes' : 'Save Transaction' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
