<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { PencilSquareIcon, TrashIcon, DocumentTextIcon } from '@heroicons/vue/20/solid';
import TimeEntryMassUpdateModal from '@/packages/ui/src/TimeEntry/TimeEntryMassUpdateModal.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    UpdateMultipleTimeEntriesChangeset,
} from '@/packages/api/src';
import { ref } from 'vue';
import { twMerge } from 'tailwind-merge';
import { Checkbox, InputLabel } from '@/packages/ui/src';
import dayjs from 'dayjs';
import { getCurrentRole } from '@/utils/useUser';

const props = defineProps<{
    selectedTimeEntries: TimeEntry[];
    deleteSelected: () => void;
    class?: string;
    allSelected: boolean;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    updateTimeEntries: (changeset: UpdateMultipleTimeEntriesChangeset) => Promise<void>;
    currency: string;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    selectAll: [];
    unselectAll: [];
}>();

const showMassUpdateModal = ref(false);

async function markAsInvoiced() {
    if (props.selectedTimeEntries.length === 0) return;
    await props.updateTimeEntries({
        invoiced_at: dayjs().utc().format('YYYY-MM-DDTHH:mm:ss[Z]'),
    });
    emit('submit');
}

const canMarkAsInvoiced = ['owner', 'administrator', 'manager'].includes(
    (getCurrentRole() || '').toString()
);
</script>

<template>
    <TimeEntryMassUpdateModal
        v-model:show="showMassUpdateModal"
        :projects
        :tasks
        :tags
        :clients
        :create-tag
        :create-project
        :create-client
        :update-time-entries
        :enable-estimated-time
        :can-create-project
        :currency
        :time-entries="selectedTimeEntries"
        @submit="emit('submit')"></TimeEntryMassUpdateModal>
    <MainContainer
        :class="
            twMerge(
                props.class,
                'text-sm py-1.5 font-medium bg-secondary flex items-center space-x-3'
            )
        ">
        <Checkbox
            id="selectAll"
            :checked="allSelected"
            @update:checked="allSelected ? emit('unselectAll') : emit('selectAll')">
        </Checkbox>
        <InputLabel
            v-if="selectedTimeEntries.length > 0"
            for="selectAll"
            class="select-none text-text-secondary">
            {{ selectedTimeEntries.length }} selected
        </InputLabel>
        <InputLabel v-else for="selectAll" class="text-text-secondary select-none"
            >Select All</InputLabel
        >
        <button
            v-if="selectedTimeEntries.length"
            class="text-text-tertiary flex space-x-1 items-center hover:text-text-secondary transition focus-visible:ring-2 outline-0 focus-visible:text-text-primary focus-visible:ring-ring rounded h-full px-2"
            @click="showMassUpdateModal = true">
            <PencilSquareIcon class="w-4"></PencilSquareIcon>
            <span> Edit </span>
        </button>
        <button
            v-if="selectedTimeEntries.length && canMarkAsInvoiced"
            class="text-emerald-500 h-full px-2 space-x-1 items-center flex hover:text-emerald-600 transition focus-visible:ring-2 outline-0 focus-visible:text-emerald-600 focus-visible:ring-ring rounded"
            @click="markAsInvoiced">
            <DocumentTextIcon class="w-4"></DocumentTextIcon>
            <span> Mark as invoiced </span>
        </button>
        <button
            v-if="selectedTimeEntries.length"
            class="text-red-400 h-full px-2 space-x-1 items-center flex hover:text-red-500 transition focus-visible:ring-2 outline-0 focus-visible:text-red-500 focus-visible:ring-ring rounded"
            @click="deleteSelected">
            <TrashIcon class="w-3.5"></TrashIcon>
            <span> Delete </span>
        </button>
    </MainContainer>
</template>

<style scoped></style>
