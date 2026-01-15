<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { AlertTriangle, Clock, RefreshCw, Target } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    offlineMinutes: number;
    monthFormatted: string;
    statsTodayFormatted?: string;
    offlineStartProp?: string | null; // From OfflineTimeEntries
    adminStats?: {
        total_users?: number;
        active_users_today?: number;
    };
    isAdmin?: boolean;
}>();

// State
const onlineMinutes = ref(0);
const startTime = ref<string | null>(null); // ISO or time string
const loading = ref(false);
const error = ref<string | null>(null);
const configured = ref(false);
const lastUpdated = ref<Date | null>(null);

// Constants
const DAILY_TARGET_MINUTES = 8 * 60; // 8 hours

// Computed - ensure numbers are coerced properly
const totalMinutes = computed(
    () => Number(props.offlineMinutes || 0) + Number(onlineMinutes.value || 0),
);
const remainingMinutes = computed(() =>
    Math.max(0, DAILY_TARGET_MINUTES - totalMinutes.value),
);
const progressPercent = computed(() =>
    Math.min(100, (totalMinutes.value / DAILY_TARGET_MINUTES) * 100),
);

const formatTime = (minutes: number): string => {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};

// Format time string (e.g. "09:30 AM")
const formatTimeOfDay = (dateStr: string | Date): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const estimatedEndTime = computed(() => {
    if (remainingMinutes.value <= 0) return null;
    const now = new Date();
    const end = new Date(now.getTime() + remainingMinutes.value * 60000);
    return formatTimeOfDay(end);
});

const startTimeFormatted = computed(() => {
    // Compare SSM startTime and props.offlineStartProp
    // We want the EARLIER of the two
    const ssmStart = startTime.value;
    const offlineStart = props.offlineStartProp;

    let finalStart = null;

    // Helper to parse offlineStart which might be ISO string or HH:MM:SS
    const parseOfflineStart = (val: string): Date => {
        // If it looks like a full date (has - and :)
        if (val.includes('-') && val.includes(':')) {
            return new Date(val);
        }
        // Assume HH:MM:SS
        const [h, m, s] = val.split(':').map(Number);
        const d = new Date();
        d.setHours(h, m, s || 0);
        return d;
    };

    console.log('Start Time Debug:', { ssmStart, offlineStart });

    if (ssmStart && offlineStart) {
        const ssmDate = new Date(ssmStart);
        const offlineDate = parseOfflineStart(offlineStart);

        if (ssmDate < offlineDate) {
            finalStart = ssmStart;
        } else {
            finalStart = offlineDate.toISOString();
        }
    } else if (ssmStart) {
        finalStart = ssmStart;
    } else if (offlineStart) {
        finalStart = parseOfflineStart(offlineStart).toISOString();
    }

    console.log('Final Start:', finalStart);

    if (!finalStart) return null;
    return formatTimeOfDay(finalStart);
});

const fetchData = async (forceRefresh = false) => {
    loading.value = true;
    error.value = null;

    try {
        const url = forceRefresh
            ? '/api/ssm/daily-stats?refresh=1'
            : '/api/ssm/daily-stats';
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();

        configured.value = data.configured ?? false;
        onlineMinutes.value = data.online_minutes ?? 0;
        startTime.value = data.start_time ?? null;

        if (data.error) {
            error.value = data.error;
        }

        lastUpdated.value = new Date();
    } catch (e) {
        error.value = 'Could not sync with ScreenshotMonitor';
        console.error('SSM Fetch Error:', e);
    } finally {
        loading.value = false;
    }
};

const handleRefresh = () => {
    fetchData(true);
};

onMounted(() => {
    fetchData(false);
});
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Target class="h-5 w-5 text-primary" />
                    Daily Goal Progress
                </CardTitle>
                <Button
                    variant="ghost"
                    size="icon"
                    :disabled="loading"
                    @click="handleRefresh"
                    class="h-8 w-8"
                >
                    <RefreshCw
                        class="h-4 w-4"
                        :class="{ 'animate-spin': loading }"
                    />
                </Button>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Progress Bar -->
            <div class="space-y-2">
                <Progress :model-value="progressPercent" class="h-3" />
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span>
                        <span
                            v-if="startTimeFormatted"
                            class="mr-1 font-medium text-primary"
                            >{{ startTimeFormatted }} -
                        </span>
                        {{ formatTime(totalMinutes) }} worked
                    </span>
                    <span>
                        {{ formatTime(remainingMinutes) }} remaining
                        <span
                            v-if="estimatedEndTime"
                            class="ml-1 font-medium text-primary"
                        >
                            - {{ estimatedEndTime }}</span
                        >
                    </span>
                </div>
            </div>
            <!-- Stats Grid (3 columns) -->
            <div class="grid grid-cols-3 gap-3">
                <!-- Online Time (SSM) -->
                <div class="rounded-lg border p-3 text-center">
                    <p class="text-xs text-muted-foreground">Online (SSM)</p>
                    <p class="text-lg font-semibold">
                        <span v-if="configured">{{
                            formatTime(onlineMinutes)
                        }}</span>
                        <span v-else class="text-sm text-muted-foreground"
                            >—</span
                        >
                    </p>
                </div>

                <!-- Total -->
                <div class="rounded-lg border bg-primary/5 p-3 text-center">
                    <p class="text-xs text-muted-foreground">Total Today</p>
                    <p class="text-lg font-semibold text-primary">
                        {{ formatTime(totalMinutes) }}
                    </p>
                </div>

                <!-- Target -->
                <div class="rounded-lg border p-3 text-center">
                    <p class="text-xs text-muted-foreground">Daily Target</p>
                    <p class="text-lg font-semibold">8h</p>
                </div>
            </div>
            <!-- Warning/Info Messages -->
            <div
                v-if="error"
                class="flex items-center gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200"
            >
                <AlertTriangle class="h-4 w-4 flex-shrink-0" />
                <span>{{ error }}</span>
            </div>

            <div
                v-else-if="!configured"
                class="flex items-center gap-2 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-200"
            >
                <Clock class="h-4 w-4 flex-shrink-0" />
                <span
                    >Setup ScreenshotMonitor in
                    <a href="/settings/profile" class="font-medium underline"
                        >Settings</a
                    >
                    to track online time.</span
                >
            </div>

            <!-- General Stats Divider -->
            <!-- Only show if we have data to show (always true for This Month) -->
            <template v-if="monthFormatted || (isAdmin && adminStats)">
                <Separator class="my-4" />

                <!-- Global Stats Section (Styled like top grid) -->
                <!-- Admin: 4 cards in 1 row. User: 2 cards in 1 row (on larger screens) -->
                <div
                    class="grid gap-2"
                    :class="[
                        isAdmin && adminStats
                            ? 'grid-cols-2 sm:grid-cols-4'
                            : 'grid-cols-2',
                    ]"
                >
                    <!-- Card 1: Logged Today (For everyone) -->
                    <div class="rounded-lg border bg-primary/5 p-3 text-center">
                        <p class="text-xs text-muted-foreground">
                            Logged Today
                        </p>
                        <div class="flex items-center justify-center gap-2">
                            <p class="text-lg font-semibold text-primary">
                                <!-- Use Global Stats if provided (Admin), else calculated Personal -->
                                {{
                                    statsTodayFormatted ||
                                    formatTime(totalMinutes)
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: This Month (For everyone) -->
                    <div class="rounded-lg border p-3 text-center">
                        <p class="text-xs text-muted-foreground">This Month</p>
                        <div class="flex items-center justify-center gap-2">
                            <p class="text-lg font-semibold">
                                {{ monthFormatted }}
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Active Today (Admin Only) -->
                    <div
                        v-if="isAdmin && adminStats"
                        class="rounded-lg border p-3 text-center"
                    >
                        <p class="text-xs text-muted-foreground">
                            Active Users
                        </p>
                        <div class="flex items-center justify-center gap-2">
                            <p class="text-lg font-semibold">
                                {{ adminStats.active_users_today }}
                            </p>
                        </div>
                    </div>

                    <!-- Card 4: Total Users (Admin Only) -->
                    <div
                        v-if="isAdmin && adminStats"
                        class="rounded-lg border p-3 text-center"
                    >
                        <p class="text-xs text-muted-foreground">Total Users</p>
                        <div class="flex items-center justify-center gap-2">
                            <p class="text-lg font-semibold">
                                {{ adminStats.total_users }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </CardContent>
    </Card>
</template>
