<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import type { CurrencyFormat } from '@/packages/ui/src/utils/money';
import { getOrganizationCurrencyString } from '@/utils/money';
import { computed, onMounted, provide, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import { useReportingStore } from '@/utils/useReporting';
import { Head } from '@inertiajs/vue3';
import { useTheme } from '@/utils/theme';

const sharedSecret = ref<string | null>(null);

const hasSharedSecret = computed(() => {
    return sharedSecret.value !== null;
});

const { data: sharedReportResponseData } = useQuery({
    enabled: hasSharedSecret,
    queryKey: ['reporting', sharedSecret],
    queryFn: () =>
        api.getPublicReport({
            headers: {
                'X-Api-Key': sharedSecret.value,
            },
        }),
});

onMounted(() => {
    const currentUrl = window.location.href;
    // check if # exists exactly once in the URL
    if (currentUrl.split('#').length === 2) {
        sharedSecret.value = currentUrl.split('#')[1];
    }
});

const reportCurrency = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.currency;
    }
    return 'EUR';
});

const reportIntervalFormat = computed(() => {
    return sharedReportResponseData.value?.interval_format;
});

const reportNumberFormat = computed(() => {
    return sharedReportResponseData.value?.number_format;
});

const reportCurrencyFormat = computed(() => {
    return (sharedReportResponseData.value?.currency_format ??
        'symbol-before') as CurrencyFormat;
});

const reportDateFormat = computed(() => {
    return sharedReportResponseData.value?.date_format;
});

const reportCurrencySymbol = computed(() => {
    return sharedReportResponseData.value?.currency_symbol;
});

provide(
    'organization',
    computed(() => ({
        'number_format': reportNumberFormat.value,
        'interval_format': reportIntervalFormat.value,
        'currency_format': reportCurrencyFormat.value,
        'currency_symbol': reportCurrencySymbol.value,
        'date_format': reportDateFormat.value,
    }))
);

const aggregatedTableTimeEntries = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.data;
    }
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});
const aggregatedGraphTimeEntries = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.history_data;
    }
    // Placeholder Data
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});

const group = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.properties.group;
    }
    return 'billable';
});

const subGroup = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.properties.sub_group;
    }
    return 'project';
});
const { emptyPlaceholder } = useReportingStore();

const tableData = computed(() => {
    return aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
        return {
            seconds: entry.seconds,
            cost: entry.cost,
            description:
                entry.description ??
                emptyPlaceholder[
                    aggregatedTableTimeEntries.value?.grouped_type ?? 'project'
                ],
            grouped_data:
                entry.grouped_data?.map((el) => {
                    return {
                        seconds: el.seconds,
                        cost: el.cost,
                        description:
                            el.description ??
                            emptyPlaceholder[entry.grouped_type ?? 'project'],
                    };
                }) ?? [],
        };
    });
});

const { groupByOptions } = useReportingStore();

function getGroupLabel(key: string) {
    return groupByOptions.find((option) => {
        return option.value === key;
    })?.label;
}

onMounted(async () => {
    useTheme();
});
</script>

<template>
    <Head :title="sharedReportResponseData?.name" />

    <div class="text-text-secondary">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart
                    :grouped-type="aggregatedGraphTimeEntries?.grouped_type"
                    :grouped-data="
                        aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="pt-6 items-start">
                <div
                    class="bg-card-background rounded-lg border border-card-border pt-3">
                    <div
                        class="text-sm flex text-text-primary items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                        <span>{{ sharedReportResponseData?.name }}</span>
                    </div>
                    <div class="px-6 pt-6 pb-3">
                        <template
                            v-for="reportingRowEntry in tableData"
                            :key="reportingRowEntry.description">
                            <ReportingRow
                                :entry="reportingRowEntry"
                                :currency="getOrganizationCurrencyString()"></ReportingRow>
                        </template>
                        <div
                            v-if="
                                aggregatedTableTimeEntries &&
                                aggregatedTableTimeEntries.grouped_data &&
                                aggregatedTableTimeEntries.grouped_data.length >
                                    0
                            "
                            class="border-t border-background-separator pt-3 mt-6 text-sm space-y-2">
                            <div class="justify-between items-center flex">
                                <div class="font-medium">Total</div>
                                <div class="font-medium">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds,
                                            reportIntervalFormat,
                                            'german'
                                        )
                                    }}
                                </div>
                            </div>
                            <div
                                v-if="aggregatedTableTimeEntries.cost !== null"
                                class="justify-between items-center flex">
                                <div class="font-medium">Total Billable</div>
                                <div class="justify-end pr-6 flex items-center font-medium">
                                    {{
                                        formatCents(
                                            aggregatedTableTimeEntries.cost,
                                            reportCurrency,
                                            reportCurrencyFormat,
                                            reportCurrencySymbol
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="chart flex flex-col items-center justify-center py-12">
                            <p class="text-lg text-text-primary font-semibold">
                                No time entries found
                            </p>
                            <p>Try to change the filters and time range</p>
                        </div>
                    </div>
                </div>
            </div>
        </MainContainer>
    </div>
</template>
