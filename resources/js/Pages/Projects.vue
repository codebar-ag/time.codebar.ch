<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/20/solid';
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import ProjectTable from '@/Components/Common/Project/ProjectTable.vue';
import { computed } from 'vue';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useProjectsStore } from '@/utils/useProjects';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateProjects } from '@/utils/permissions';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useClientsStore } from '@/utils/useClients';
import type { CreateClientBody, Client, CreateProjectBody, Project } from '@/packages/api/src';
import { getOrganizationCurrencyString } from '@/utils/money';
import { getCurrentOrganizationId, getCurrentRole } from '@/utils/useUser';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useStorage } from '@vueuse/core';
import ProjectsFilterDropdown from '@/Components/Common/Project/ProjectsFilterDropdown.vue';
import ProjectStatusFilterBadge from '@/Components/Common/Project/ProjectStatusFilterBadge.vue';
import ProjectClientFilterBadge from '@/Components/Common/Project/ProjectClientFilterBadge.vue';
import { NO_CLIENT_ID } from '@/Components/Common/Project/constants';
import type { SortColumn, SortDirection } from '@/Components/Common/Project/ProjectTable.vue';

// Fetch data using TanStack Query
const { projects } = useProjectsQuery();
const { clients } = useClientsQuery();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

// Table state persisted in localStorage
interface ProjectTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
    filters: {
        clientIds: string[];
        status: 'active' | 'archived' | 'all';
    };
}

const tableState = useStorage<ProjectTableState>(
    'project-table-state',
    {
        sortColumn: 'name',
        sortDirection: 'asc',
        filters: {
            clientIds: [],
            status: 'all',
        },
    },
    undefined,
    { mergeDefaults: true }
);

const searchQuery = useStorage('project-search-query', '');

function handleSort(column: SortColumn, direction: SortDirection) {
    tableState.value.sortColumn = column;
    tableState.value.sortDirection = direction;
}

// Filter projects based on current filters
const filteredProjects = computed(() => {
    return projects.value.filter((project) => {
        // Status filter
        if (tableState.value.filters.status === 'active' && project.is_archived) {
            return false;
        }
        if (tableState.value.filters.status === 'archived' && !project.is_archived) {
            return false;
        }

        // Client filter
        const hasClientFilter = tableState.value.filters.clientIds.length > 0;
        if (hasClientFilter) {
            const matchesNoClient =
                tableState.value.filters.clientIds.includes(NO_CLIENT_ID) && !project.client_id;
            const matchesClientId =
                project.client_id && tableState.value.filters.clientIds.includes(project.client_id);

            if (!matchesNoClient && !matchesClientId) {
                return false;
            }
        }

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase().trim();
            const projectMatches = project.name.toLowerCase().includes(query);
            const clientName = clients.value.find((client) => client.id === project.client_id)?.name ?? '';
            const clientMatches = clientName.toLowerCase().includes(query);

            if (!projectMatches && !clientMatches) {
                return false;
            }
        }

        return true;
    });
});

// Helper functions for active filters
function removeStatusFilter() {
    tableState.value.filters.status = 'all';
}

function removeClientFilter() {
    tableState.value.filters.clientIds = [];
}

const showCreateProjectModal = useStorage('project-create-modal-open', false);

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(client: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}

const showBillableRate = computed(() => {
    return !!(
        getCurrentRole() !== 'employee' || organization.value?.employees_can_see_billable_rates
    );
});
</script>

<template>
    <AppLayout title="Projects" data-testid="projects_view">
        <MainContainer
            class="py-3 sm:pt-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="FolderIcon" title="Projects"></PageTitle>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-text-secondary" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search projects or clients..."
                        class="block w-64 pl-10 pr-3 py-2 border border-input-border rounded-md leading-5 bg-input-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-500 focus:border-accent-500 sm:text-sm" />
                </div>
                <SecondaryButton
                    v-if="canCreateProjects()"
                    :icon="PlusIcon"
                    @click="showCreateProjectModal = true"
                    >Create Project
                </SecondaryButton>
            </div>
            <ProjectCreateModal
                v-model:show="showCreateProjectModal"
                :create-project
                :enable-estimated-time="isAllowedToPerformPremiumAction()"
                :create-client
                :currency="getOrganizationCurrencyString()"
                :clients="clients"
                @submit="createProject"></ProjectCreateModal>
        </MainContainer>
        <MainContainer>
            <div class="flex items-center gap-2 py-1">
                <ProjectsFilterDropdown
                    :filters="tableState.filters"
                    :clients="clients"
                    @update:filters="tableState.filters = $event" />

                <!-- Active Filters -->
                <ProjectStatusFilterBadge
                    v-if="tableState.filters.status !== 'all'"
                    data-testid="status-filter-badge"
                    :value="tableState.filters.status"
                    @remove="removeStatusFilter"
                    @update:value="
                        tableState.filters.status = $event as 'active' | 'archived' | 'all'
                    " />

                <ProjectClientFilterBadge
                    v-if="tableState.filters.clientIds.length > 0"
                    data-testid="client-filter-badge"
                    :value="tableState.filters.clientIds"
                    :clients="clients"
                    @remove="removeClientFilter"
                    @update:value="tableState.filters.clientIds = $event as string[]" />
            </div>
        </MainContainer>

        <ProjectTable
            :show-billable-rate="showBillableRate"
            :projects="filteredProjects"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></ProjectTable>
    </AppLayout>
</template>
