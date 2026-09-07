<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, PlusCircle, Save } from '@lucide/vue';
import { toast } from 'vue-sonner';

import { index, store, update } from '@/routes/categories';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';

interface CategoryData {
    id?: number;
    code?: string;
    name?: string;
}

const props = defineProps<{
    category?: CategoryData;
}>();

const isEdit = computed(() => Boolean(props.category?.id));
const isAddMore = ref(false);

const form = useForm({
    code: props.category?.code ?? '',
    name: props.category?.name ?? '',
});

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

function handleSave() {
    isAddMore.value = false;

    if (isEdit.value && props.category?.id) {
        form.put(update(props.category.id).url, {
            onSuccess: () => {
                toast.success('Category updated successfully.');
                router.visit('/categories');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to update category.');
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                toast.success('Category created successfully.');
                router.visit('/categories');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Failed to create category.');
            },
        });
    }
}

function handleSaveAndAddMore() {
    isAddMore.value = true;

    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.clearErrors();
            toast.success('Category created successfully. You can add another one.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(typeof firstError === 'string' ? firstError : 'Failed to create category.');
        },
        onFinish: () => {
            isAddMore.value = false;
        },
    });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Category' : 'Create Category'" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl mx-auto w-full">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" as-child class="size-8 p-0">
                    <Link href="/categories">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ isEdit ? 'Edit Category' : 'Create Category' }}
                </h1>
            </div>
            <p class="text-muted-foreground text-sm pl-10">
                {{ isEdit ? 'Update existing product category details.' : 'Fill in the information below to add a new category.' }}
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
                <!-- Category Code -->
                <div class="space-y-2">
                    <Label for="code" class="text-sm font-semibold">
                        Category Code <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="code"
                        v-model="form.code"
                        placeholder="e.g. ELEC"
                        maxlength="10"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.code }"
                        required
                    />
                    <p v-if="form.errors.code" class="text-destructive text-xs">
                        {{ form.errors.code }}
                    </p>
                    <p v-else class="text-muted-foreground text-xs">
                        Short unique identifier code (max 10 characters).
                    </p>
                </div>

                <!-- Category Name -->
                <div class="space-y-2">
                    <Label for="name" class="text-sm font-semibold">
                        Category Name <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Electronics & Gadgets"
                        maxlength="100"
                        :disabled="form.processing"
                        :class="{ 'border-destructive': form.errors.name }"
                        required
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                        :disabled="form.processing"
                    >
                        <Link href="/categories">
                            <ArrowLeft class="mr-2 size-4" />
                            Back
                        </Link>
                    </Button>

                    <div class="flex items-center gap-3 justify-end">
                        <!-- Save & Add More (Only shown in Create mode) -->
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
