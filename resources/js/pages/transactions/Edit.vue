<script setup lang="ts">
import TransactionForm from './Form.vue';

defineProps<{
    transaction?: any;
    products?: any[];
}>();
</script>

<template>
    <TransactionForm :transaction="transaction" :products="products" />
</template>
