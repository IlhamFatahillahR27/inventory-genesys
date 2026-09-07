<script setup lang="ts">
import ProductForm from './Form.vue';

defineProps<{
    product?: any;
    categories?: any[];
}>();
</script>

<template>
    <ProductForm :product="product" :categories="categories" />
</template>
