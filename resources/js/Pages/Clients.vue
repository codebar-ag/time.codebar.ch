<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';
import { useClientsQuery } from '@/utils/useClientsQuery';
import ClientTable from '@/Components/Common/Client/ClientTable.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateClients } from '@/utils/permissions';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { useStorage } from '@vueuse/core';
import type { SortColumn, SortDirection } from '@/Components/Common/Client/ClientTable.vue';

const { clients } = useClientsQuery();

const activeTab = ref<'active' | 'archived'>('active');

const createClient = ref(false);
const searchQuery = ref('');

interface ClientTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}

const tableState = useStorage<ClientTableState>(
    'client-table-state',
    {
        sortColumn: 'name',
        sortDirection: 'asc',
    },
    undefined,
    { mergeDefaults: true }
);

function handleSort(column: SortColumn, direction: SortDirection) {
    tableState.value.sortColumn = column;
    tableState.value.sortDirection = direction;
}

const shownClients = computed(() => {
    return clients.value.filter((client) => {
        if (activeTab.value === 'active') {
            if (client.is_archived) return false;
        } else if (!client.is_archived) {
            return false;
        }

        if (!searchQuery.value.trim()) return true;

        return client.name.toLowerCase().includes(searchQuery.value.toLowerCase().trim());
    });
});
</script>

<template>
    <AppLayout title="Clients" data-testid="clients_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="active">Active</TabBarItem>
                    <TabBarItem value="archived"> Archived </TabBarItem>
                </TabBar>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-text-secondary" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search clients..."
                        class="block w-64 pl-10 pr-3 py-2 border border-input-border rounded-md leading-5 bg-input-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-500 focus:border-accent-500 sm:text-sm" />
                </div>
                <SecondaryButton v-if="canCreateClients()" :icon="PlusIcon" @click="createClient = true"
                    >Create Client</SecondaryButton
                >
            </div>
            <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
        </MainContainer>
        <ClientTable
            :clients="shownClients"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></ClientTable>
    </AppLayout>
</template>
