<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { formatNumber, parseNumber, filterNumericInput } from '@/lib/numberHelper';

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        placeholder?: string;
        disabled?: boolean;
        prefix?: string;
        min?: number;
        max?: number;
        id?: string;
    }>(),
    {
        modelValue: 0,
        placeholder: '',
        disabled: false,
        prefix: '',
        min: 0,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const displayValue = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === 0) {
        return '';
    }
    return formatNumber(props.modelValue);
});

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const rawNum = parseNumber(target.value);
    let clampedNum = rawNum;

    if (props.min !== undefined && clampedNum < props.min) {
        clampedNum = props.min;
    }
    if (props.max !== undefined && clampedNum > props.max) {
        clampedNum = props.max;
    }

    emit('update:modelValue', clampedNum);
    // Update input display to formatted
    target.value = clampedNum ? formatNumber(clampedNum) : '';
}
</script>

<template>
    <div class="relative flex items-center">
        <span
            v-if="prefix"
            class="text-muted-foreground pointer-events-none absolute left-3 select-none text-sm font-medium"
        >
            {{ prefix }}
        </span>
        <Input
            :id="id"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            type="text"
            inputmode="numeric"
            :class="[prefix ? 'pl-9' : '', 'text-right']"
            @input="handleInput"
            @keydown="filterNumericInput"
        />
    </div>
</template>
