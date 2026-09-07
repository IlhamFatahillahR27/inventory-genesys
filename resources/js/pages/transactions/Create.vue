<script setup lang="ts">
import TransactionForm from './Form.vue';

defineProps<{
    products?: any[];
}>();
</script>

<template>
    <TransactionForm :products="products" />
</template>
