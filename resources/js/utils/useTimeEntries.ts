import { defineStore } from 'pinia';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';

import { reactive, ref } from 'vue';
import {
    api,
    type CreateTimeEntryBody,
    type TimeEntriesQueryParams,
    type TimeEntry,
} from '@/packages/api/src';
import dayjs from 'dayjs';
import { useNotificationsStore } from '@/utils/notification';
import type { UpdateMultipleTimeEntriesChangeset } from '@/packages/api/src';
import { useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';

export const useTimeEntriesStore = defineStore('timeEntries', () => {
    const timeEntries = ref<TimeEntry[]>(reactive([]));

    const allTimeEntriesLoaded = ref(false);
    const { handleApiRequestNotifications } = useNotificationsStore();

    const queryClient = useQueryClient();

    async function patchTimeEntries(
        queryParams: TimeEntriesQueryParams = {
            only_full_dates: 'true',
            member_id: getCurrentMembershipId(),
        }
    ) {
        const organizationId = getCurrentOrganizationId();

        if (organizationId) {
            const timeEntriesResponse = await handleApiRequestNotifications(
                () =>
                    api.getTimeEntries({
                        params: {
                            organization: organizationId,
                        },
                        queries: queryParams,
                    }),
                undefined,
                'Failed to fetch time entries'
            );
            if (timeEntriesResponse?.data) {
                // insert missing time entries
                const missingTimeEntries = timeEntriesResponse.data.filter(
                    (entry) => !timeEntries.value.find((e) => e.id === entry.id)
                );
                timeEntries.value = [...missingTimeEntries, ...timeEntries.value];
            }
        }
    }

    async function fetchTimeEntries(
        queryParams: TimeEntriesQueryParams = {
            only_full_dates: 'true',
            member_id: getCurrentMembershipId(),
        }
    ) {
        const organizationId = getCurrentOrganizationId();

        if (organizationId) {
            const timeEntriesResponse = await handleApiRequestNotifications(
                () =>
                    api.getTimeEntries({
                        params: {
                            organization: organizationId,
                        },
                        queries: queryParams,
                    }),
                undefined,
                'Failed to fetch time entries'
            );
            if (timeEntriesResponse?.data) {
                timeEntries.value = timeEntriesResponse.data;
            }
        }
    }

    async function fetchMoreTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const latestTimeEntry = timeEntries.value[timeEntries.value.length - 1];
            dayjs(latestTimeEntry.start).utc().format('YYYY-MM-DD');

            const timeEntriesResponse = await handleApiRequestNotifications(
                () =>
                    api.getTimeEntries({
                        params: {
                            organization: organizationId,
                        },
                        queries: {
                            only_full_dates: 'true',
                            member_id: getCurrentMembershipId(),
                            end: dayjs(latestTimeEntry.start).utc().format(),
                        },
                    }),
                undefined,
                'Failed to fetch time entries'
            );
            if (timeEntriesResponse?.data && timeEntriesResponse.data.length > 0) {
                timeEntries.value = timeEntries.value.concat(timeEntriesResponse.data);
            } else {
                allTimeEntriesLoaded.value = true;
            }
        }
    }

    async function updateTimeEntries(ids: string[], changes: UpdateMultipleTimeEntriesChangeset) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.updateMultipleTimeEntries(
                        {
                            ids: ids,
                            changes: changes,
                        },
                        {
                            params: {
                                organization: organizationId,
                            },
                        }
                    ),
                'Time entries updated successfully',
                'Failed to update time entries'
            );
        }
    }

    async function updateTimeEntry(timeEntry: TimeEntry) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.updateTimeEntry(timeEntry, {
                        params: {
                            organization: organizationId,
                            timeEntry: timeEntry.id,
                        },
                    }),
                'Time entry updated successfully',
                'Failed to update time entry'
            );
            timeEntries.value = timeEntries.value.map((entry) =>
                entry.id === timeEntry.id ? response.data : entry
            );
            queryClient.invalidateQueries({ queryKey: ['timeEntry'] });
        }
    }

    async function createTimeEntry(timeEntry: Omit<CreateTimeEntryBody, 'member_id'>) {
        const organizationId = getCurrentOrganizationId();
        const memberId = getCurrentMembershipId();
        if (organizationId && memberId !== undefined) {
            const newTimeEntry = {
                ...timeEntry,
                member_id: memberId,
            } as CreateTimeEntryBody;
            await handleApiRequestNotifications(
                () =>
                    api.createTimeEntry(newTimeEntry, {
                        params: {
                            organization: organizationId,
                        },
                    }),
                'Time entry created successfully',
                'Failed to create time entry'
            );
            await fetchTimeEntries();
        }
    }

    async function deleteTimeEntry(timeEntryId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            try {
                await api.deleteTimeEntry(undefined, {
                    params: {
                        organization: organizationId,
                        timeEntry: timeEntryId,
                    },
                });
                // Success - show success message
                const { addNotification } = useNotificationsStore();
                addNotification('success', 'Time entry deleted successfully');
                await fetchTimeEntries();
            } catch (error) {
                // Handle error manually
                const { addNotification } = useNotificationsStore();
                if (axios.isAxiosError(error)) {
                    if (error?.response?.status === 422) {
                        const message = error.response.data.message;
                        addNotification('error', message);
                    } else {
                        addNotification('error', 'Failed to delete time entry');
                    }
                } else {
                    addNotification('error', 'Failed to delete time entry');
                }
                throw error; // Re-throw so the modal knows it failed
            }
        }
    }

    async function deleteTimeEntries(timeEntries: TimeEntry[]) {
        const organizationId = getCurrentOrganizationId();
        const timeEntryIds = timeEntries.map((entry) => entry.id);
        if (organizationId) {
            try {
                const response = await api.deleteTimeEntries(undefined, {
                    queries: {
                        'ids[]': timeEntryIds,
                    },
                    params: {
                        organization: organizationId,
                    },
                });

                // Check if there were any errors in the bulk operation
                const { success, error } = response;
                const successCount = success.length;
                const errorCount = error.length;

                const { addNotification } = useNotificationsStore();

                if (errorCount > 0) {
                    // Some entries failed to delete (likely invoiced)
                    if (successCount > 0) {
                        addNotification(
                            'error',
                            `${successCount} time entries deleted successfully, ${errorCount} failed (invoiced entries cannot be deleted)`
                        );
                    } else {
                        addNotification('error', 'Cannot delete invoiced time entries');
                    }
                } else {
                    // All entries deleted successfully
                    addNotification('success', `${successCount} time entries deleted successfully`);
                }

                await fetchTimeEntries();
            } catch (error) {
                // Handle unexpected errors
                const { addNotification } = useNotificationsStore();
                addNotification('error', 'Failed to delete time entries');
                throw error;
            }
        }
    }

    return {
        timeEntries,
        fetchTimeEntries,
        updateTimeEntry,
        createTimeEntry,
        deleteTimeEntry,
        fetchMoreTimeEntries,
        allTimeEntriesLoaded,
        updateTimeEntries,
        deleteTimeEntries,
        patchTimeEntries,
    };
});
