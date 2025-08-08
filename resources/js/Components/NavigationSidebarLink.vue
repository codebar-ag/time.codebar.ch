<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
defineProps<{
    title: string;
    icon?: Component;
    current?: boolean;
    href: string;
    count?: number | string;
}>();
</script>

<template>
    <Link :href="href" class="block group">
        <div
            :class="[
                current
                    ? 'bg-menu-active text-text-primary'
                    : 'text-text-secondary group-hover:text-text-primary group-hover:bg-menu-active ',
                'group flex gap-x-2 rounded-md transition leading-6 py-0.5 px-2 font-medium text-sm items-center',
            ]">
            <component
                :is="icon"
                v-if="icon"
                :class="[
                    current ? 'text-icon-active' : 'text-icon-default group-hover:text-icon-active',
                    'transition h-4 w-4 shrink-0',
                ]"
                aria-hidden="true" />
            <span class="flex-1 truncate">{{ title }}</span>
            <span
                v-if="typeof count !== 'undefined'"
                class="ml-2 px-1.5 rounded bg-quaternary text-text-tertiary text-[10px] leading-5 min-w-[1.25rem] text-center">
                {{ count }}
            </span>
        </div>
    </Link>
</template>

<style scoped></style>
