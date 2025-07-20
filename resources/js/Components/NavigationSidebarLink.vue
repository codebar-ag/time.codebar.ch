<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
defineProps<{
    title: string;
    icon?: Component;
    current?: boolean;
    href: string;
    count?: number;
}>();
</script>

<template>
    <Link :href="href" class="block group">
        <div
            :class="[
                current
                    ? 'bg-menu-active text-text-primary'
                    : 'text-text-secondary group-hover:text-text-primary group-hover:bg-menu-active ',
                'group flex gap-x-2 rounded-md transition leading-6 py-0.5 px-2 font-medium text-sm items-center justify-between',
            ]">
            <div class="flex items-center gap-x-2">
                <component
                    :is="icon"
                    v-if="icon"
                    :class="[
                        current
                            ? 'text-icon-active'
                            : 'text-icon-default group-hover:text-icon-active',
                        'transition h-4 w-4 shrink-0',
                    ]"
                    aria-hidden="true" />
                {{ title }}
            </div>
            <div
                v-if="count !== undefined && count >= 0"
                :class="[
                    current
                        ? 'bg-background text-text-secondary'
                        : 'bg-default-background text-text-tertiary group-hover:bg-background group-hover:text-text-secondary',
                    'transition rounded-full px-1.5 py-0.5 text-xs font-medium min-w-5 text-center leading-none',
                ]">
                {{ count > 99 ? '99+' : count }}
            </div>
        </div>
    </Link>
</template>

<style scoped></style>
