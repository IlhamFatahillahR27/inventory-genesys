<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { ArrowLeft, PlusCircle, Save } from '@lucide/vue';
import { toast } from 'vue-sonner';

import { index as productIndex, store, update } from '@/routes/products';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import NumberInput from '@/components/NumberInput.vue';

interface CategoryOption {
    id: number;
    code: string;
    name: string;
}

interface ProductData {
    id?: number;
    category_id?: number | string;
    name?: string;
    description?: string;
    price?: number;
    stock?: number;
    discount?: number;
}

const props = defineProps<{
    product?: ProductData;
    categories?: CategoryOption[];
}>();

const isEdit = computed(() => Boolean(props.product?.id));
const isAddMore = ref(false);

const categoryOptions = ref<CategoryOption[]>(props.categories ?? []);

const form = useForm({
    category_id: props.product?.category_id ? String(props.product.category_id) : '',
    name: props.product?.name ?? '',
    description: props.product?.description ?? '',
    price: props.product?.price ?? 0,
    stock: props.product?.stock ?? 0,
    discount: props.product?.discount ?? 0,
});

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

async function fetchCategoriesIfEmpty() {
    if (categoryOptions.value.length === 0) {
        try {
            const res = await fetch('/categories/datatable?per_page=100', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                const json = await res.json();
                categoryOptions.value = json.data ?? (Array.isArray(json) ? json : []);
            }
        } catch (err) {
            console.error('Failed to load categories for select option', err);
        }
    }
}

function handleSave() {
    isAddMore.value = false;

    const payload = {
        category_id: Number(form.category_id),
        name: form.name,
        description: form.description,
        price: Number(form.price),
        stock: Number(form.stock),
        discount: Number(form.discount),
    };

    if (isEdit.value && props.product?.id) {
        form.transform(() => payload).put(update(props.product.id).url, {
            onSuccess: () => {
                toast.success('Product updated successfully.');
                router.visit('/products');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to update product.');
            },
        });
    } else {
        form.transform(() => payload).post(store().url, {
            onSuccess: () => {
                toast.success('Product created successfully.');
                router.visit('/products');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to create product.');
            },
        });
    }
}

function handleSaveAndAddMore() {
    isAddMore.value = true;

    const payload = {
        category_id: Number(form.category_id),
        name: form.name,
        description: form.description,
        price: Number(form.price),
        stock: Number(form.stock),
        discount: Number(form.discount),
    };

    form.transform(() => payload).post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'description', 'price', 'stock', 'discount');
            form.clearErrors();
            toast.success('Product created successfully. You can add another one.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(typeof firstError === 'string' ? firstError : 'Failed to create product.');
        },
        onFinish: () => {
            isAddMore.value = false;
        },
    });
}

onMounted(() => {
    fetchCategoriesIfEmpty();
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Product' : 'Create Product'" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl mx-auto w-full">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" as-child class="size-8 p-0">
                    <Link href="/products">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ isEdit ? 'Edit Product' : 'Create Product' }}
                </h1>
            </div>
            <p class="text-muted-foreground text-sm pl-10">
                {{ isEdit ? 'Update product pricing, category, and inventory details.' : 'Add a new product item to your inventory catalog.' }}
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-card rounded-xl border p-6 shadow-xs">
            <Alert v-if="form.hasErrors" variant="destructive" class="mb-6">
                <AlertTitle>Validation Error</AlertTitle>
                <AlertDescription>
                    Please resolve the highlighted errors before saving.
                </AlertDescription>
            </Alert>

            <form class="space-y-6" @submit.prevent="handleSave">
                <!-- Category Select -->
                <div class="space-y-2">
                    <Label for="category_id" class="text-sm font-semibold">
                        Category <span class="text-destructive">*</span>
                    </Label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.category_id }"
                        required
                    >
                        <option disabled value="">Select Category</option>
                        <option v-for="cat in categoryOptions" :key="cat.id" :value="String(cat.id)">
                            {{ cat.name }} ({{ cat.code }})
                        </option>
                    </select>
                    <p v-if="form.errors.category_id" class="text-destructive text-xs">
                        {{ form.errors.category_id }}
                    </p>
                </div>

                <!-- Product Name -->
                <div class="space-y-2">
                    <Label for="name" class="text-sm font-semibold">
                        Product Name <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Wireless Noise-Cancelling Headphones"
                        maxlength="255"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.name }"
                        required
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description" class="text-sm font-semibold">
                        Description <span class="text-destructive">*</span>
                    </Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Provide details about the product..."
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.description }"
                        required
                    />
                    <p v-if="form.errors.description" class="text-destructive text-xs">
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Numerical Fields with Helper -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <!-- Price -->
                    <div class="space-y-2">
                        <Label for="price" class="text-sm font-semibold">
                            Unit Price <span class="text-destructive">*</span>
                        </Label>
                        <NumberInput
                            id="price"
                            v-model="form.price"
                            prefix="Rp"
                            placeholder="0"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.price" class="text-destructive text-xs">
                            {{ form.errors.price }}
                        </p>
                    </div>

                    <!-- Stock -->
                    <div class="space-y-2">
                        <Label for="stock" class="text-sm font-semibold">
                            Stock Quantity <span class="text-destructive">*</span>
                        </Label>
                        <NumberInput
                            id="stock"
                            v-model="form.stock"
                            placeholder="0"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.stock" class="text-destructive text-xs">
                            {{ form.errors.stock }}
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

                <!-- Action Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                        :disabled="form.processing"
                    >
                        <Link href="/products">
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
                            {{ isEdit ? 'Save Changes' : 'Save' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
