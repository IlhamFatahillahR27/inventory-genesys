<script setup lang="ts">
import CategoryForm from './Form.vue';

defineProps<{
    category?: any;
}>();
</script>

<template>
    <CategoryForm :category="category" />
</template>
