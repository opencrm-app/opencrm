<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { AlertTriangle, Clock, RefreshCw, Target } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    offlineMinutes: number;
}>();

// State
const onlineMinutes = ref(0);
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
    <Card class="col-span-full">
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
                    <span>{{ formatTime(totalMinutes) }} worked</span>
                    <span>{{ formatTime(remainingMinutes) }} remaining</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <!-- Offline Time -->
                <div class="rounded-lg border p-3 text-center">
                    <p class="text-xs text-muted-foreground">Offline</p>
                    <p class="text-lg font-semibold">
                        {{ formatTime(offlineMinutes) }}
                    </p>
                </div>

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
                    <p class="text-xs text-muted-foreground">Total</p>
                    <p class="text-lg font-semibold text-primary">
                        {{ formatTime(totalMinutes) }}
                    </p>
                </div>

                <!-- Target -->
                <div class="rounded-lg border p-3 text-center">
                    <p class="text-xs text-muted-foreground">Target</p>
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
        </CardContent>
    </Card>
</template>
