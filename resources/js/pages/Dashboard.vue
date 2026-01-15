<script setup lang="ts">
import DailyGoalWidget from '@/components/DailyGoalWidget.vue';
import MonthlyPaceWidget from '@/components/MonthlyPaceWidget.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Clock, Plus, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface TimeEntry {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    purpose: string;
    duration_minutes: number;
    user?: {
        name: string;
    };
}

interface ChartData {
    date: string;
    full_date: string;
    minutes: number;
    hours: number;
}

const props = defineProps<{
    stats: {
        today_minutes: number;
        personal_today_minutes?: number;
        personal_offline_start?: string | null; // Added
        today_formatted: string;
        month_minutes: number;
        month_formatted: string;
    };
    recentEntries: TimeEntry[];
    chartData: ChartData[];
    adminStats: {
        total_users?: number;
        active_users_today?: number;
    };
    isAdmin: boolean;
    monthlyPace: {
        monthly_target_minutes: number;
        monthly_target_formatted: string;
        total_worked_minutes: number;
        total_worked_formatted: string;
        remaining_minutes: number;
        remaining_formatted: string;
        remaining_working_days: number;
        total_working_days: number;
        required_daily_minutes: number;
        required_daily_formatted: string;
        status: 'on_track' | 'behind' | 'missed' | 'completed';
        ssm_configured: boolean;
    };
}>();

const formatTime = (time: string) => {
    // Convert HH:MM:SS to h:mm A
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const formatDuration = (minutes: number | string) => {
    const total = Math.abs(Number(minutes));
    if (!total || isNaN(total)) return '0m';
    const h = Math.floor(total / 60);
    const m = total % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};

// Calculate max hours for chart scaling
const maxHours = computed(() => {
    return Math.max(...props.chartData.map((d) => d.hours), 1);
});

const getPurposeBadgeVariant = (
    purpose: string,
): 'default' | 'secondary' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
        Meeting: 'default',
        'Client Discussion': 'secondary',
        Training: 'outline',
        Other: 'outline',
    };
    return variants[purpose] || 'outline';
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto space-y-6 p-4">
            <!-- Header -->
            <div
                class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Overview of your offline working activity.
                    </p>
                </div>
                <Link href="/offline-time/create">
                    <Button>
                        <Plus class="h-5 w-5" />
                        Log Offline Time
                    </Button>
                </Link>
            </div>

            <!-- Main Content Area: Compact Single Row for Trackers -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Daily Goal Widget (Includes Stats Overview via Partition) -->
                <DailyGoalWidget
                    :offline-minutes="
                        Number(
                            stats.personal_today_minutes ||
                                stats.today_minutes ||
                                0,
                        )
                    "
                    :offline-start-prop="stats.personal_offline_start"
                    :stats-today-formatted="stats.today_formatted"
                    :month-formatted="stats.month_formatted"
                    :admin-stats="adminStats"
                    :is-admin="isAdmin"
                />

                <!-- Monthly Pace Widget -->
                <MonthlyPaceWidget :pace="monthlyPace" />
            </div>

            <!-- Bottom Row: Charts & Recent (2/3 & 1/3) -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Weekly Activity Chart (Spans 2 columns) -->
                <Card class="shadow-sm lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <TrendingUp class="h-5 w-5 text-primary" />
                            Activity (Last 7 Days)
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <div
                            class="flex h-[200px] w-full items-end justify-between gap-2 pt-4"
                        >
                            <div
                                v-for="(day, i) in chartData"
                                :key="i"
                                class="group flex flex-1 flex-col items-center gap-2"
                            >
                                <div
                                    class="relative flex h-[160px] w-full items-end justify-center"
                                >
                                    <div
                                        class="min-h-[4px] w-full max-w-[40px] rounded-t-md bg-primary/90 transition-all group-hover:bg-primary"
                                        :style="{
                                            height: `${(day.hours / maxHours) * 100}%`,
                                        }"
                                    ></div>
                                    <!-- Tooltip -->
                                    <div
                                        class="absolute -top-8 rounded bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground opacity-0 shadow transition-opacity group-hover:opacity-100"
                                    >
                                        {{ day.hours }} hrs
                                    </div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{
                                    day.date
                                }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity List (Spans 1 column) -->
                <Card class="shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardTitle class="text-base">Recent Entries</CardTitle>
                        <Link
                            href="/offline-time"
                            class="flex items-center text-sm text-primary hover:underline"
                        >
                            View all <ArrowRight class="ml-1 h-3 w-3" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentEntries.length > 0" class="space-y-3">
                            <div
                                v-for="entry in recentEntries"
                                :key="entry.id"
                                class="flex items-start gap-4 rounded-lg border border-transparent p-2 transition-colors hover:border-border hover:bg-muted/50"
                            >
                                <div class="rounded-full bg-primary/10 p-2">
                                    <Clock class="h-4 w-4 text-primary" />
                                </div>
                                <div class="flex-1 space-y-1">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-sm leading-none font-medium"
                                        >
                                            {{ formatDate(entry.date) }}
                                        </p>
                                        <span
                                            class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDuration(
                                                    entry.duration_minutes,
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="line-clamp-1 text-xs text-muted-foreground"
                                    >
                                        {{ entry.purpose }}
                                        <span
                                            v-if="entry.user && isAdmin"
                                            class="ml-1 font-medium text-foreground"
                                        >
                                            by {{ entry.user.name }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatTime(entry.start_time) }} -
                                        {{ formatTime(entry.end_time) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="flex h-[200px] flex-col items-center justify-center text-muted-foreground"
                        >
                            <Clock class="mb-3 h-8 w-8 opacity-20" />
                            <p>No recent activity</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
