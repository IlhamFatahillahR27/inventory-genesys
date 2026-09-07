<script setup lang="ts">
import ProductForm from './Form.vue';

defineProps<{
    categories?: any[];
}>();
</script>

<template>
    <ProductForm :categories="categories" />
</template>
