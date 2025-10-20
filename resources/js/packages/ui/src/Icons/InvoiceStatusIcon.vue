<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircleIcon, DocumentTextIcon } from '@heroicons/vue/20/solid';

const props = defineProps<{
    invoiced: boolean;
    size?: 'small' | 'base';
}>();

const IconComponent = computed(() => {
    return props.invoiced ? CheckCircleIcon : DocumentTextIcon;
});

const iconColorClasses = computed(() => {
    if (props.invoiced) {
        return 'text-green-500';
    } else {
        return 'text-icon-default';
    }
});

const iconSizeClasses = computed(() => {
    if (props.size === 'small') {
        return 'w-4 h-4';
    } else {
        return 'w-5 h-5';
    }
});
</script>

<template>
    <component
        :is="IconComponent"
        :class="[iconColorClasses, iconSizeClasses]"
        :title="invoiced ? 'Invoiced' : 'Not invoiced'"
    />
</template>

<style scoped></style>
