<script setup lang="ts">
import DatePickerInput from '@/components/DatePickerInput.vue';
import InputError from '@/components/InputError.vue';
import TimePickerInput from '@/components/TimePickerInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Clock,
    FileText,
    Save,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface OfflineTimeEntry {
    id?: number;
    team_id?: number;
    date: string;
    start_time: string;
    end_time: string;
    purpose: string;
    description?: string;
}

interface Props {
    entry?: OfflineTimeEntry;
    teams?: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.entry);

const user = computed(() => usePage().props.auth.user);
const authTeam = computed(() => usePage().props.auth.team as any);

const form = useForm({
    team_id:
        props.entry?.team_id?.toString() ||
        (authTeam.value?.data?.id || authTeam.value?.id)?.toString() ||
        'none',
    date: props.entry?.date || new Date().toISOString().split('T')[0],
    start_time: props.entry?.start_time?.substring(0, 5) || '',
    end_time: props.entry?.end_time?.substring(0, 5) || '',
    purpose: props.entry?.purpose || '',
    description: props.entry?.description || '',
});

// Duration management
const durationMinutes = ref<number | ''>('');
const lastEditedField = ref<'times' | 'duration'>('times');

const purposeOptions = [
    'Meeting',
    'Client Discussion',
    'Training',
    'Presentation',
    'Workshop',
    'Conference',
    'Team Building',
    'Other',
];

const calculatedDuration = computed(() => {
    if (!form.start_time || !form.end_time) return '';

    try {
        const startDate = new Date(`2000-01-01T${form.start_time}:00`);
        const endDate = new Date(`2000-01-01T${form.end_time}:00`);

        let diffMinutes = Math.floor(
            (endDate.getTime() - startDate.getTime()) / (1000 * 60),
        );
        let isOvernight = false;

        if (diffMinutes < 0) {
            diffMinutes += 24 * 60;
            isOvernight = true;
        } else if (diffMinutes === 0) {
            return 'Start and end time cannot be the same';
        }

        // Update duration field if last edit was on times (not duration)
        if (lastEditedField.value === 'times') {
            durationMinutes.value = diffMinutes;
        }

        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;

        let formatted = '';
        if (hours > 0 && mins > 0) {
            formatted = `${hours}h ${mins}m`;
        } else if (hours > 0) {
            formatted = `${hours}h`;
        } else {
            formatted = `${mins}m`;
        }

        return `${formatted} (${diffMinutes} mins${isOvernight ? ' - Overnight' : ''})`;
    } catch {
        return '';
    }
});

// Watch for manual duration input
watch(durationMinutes, (newVal) => {
    // Only trigger if user manually edited duration (not auto-updated from time changes)
    if (lastEditedField.value !== 'duration') return;

    // Handle empty input (backspace to empty)
    if (newVal === '' || newVal === null) {
        form.end_time = '';
        form.start_time = '';
        return;
    }

    // Parse input to ensure it's a number
    const newDuration = parseInt(String(newVal));

    if (isNaN(newDuration) || newDuration <= 0) return;

    // Get current time
    const now = new Date();
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();

    // Set end time to current time
    form.end_time = `${currentHours.toString().padStart(2, '0')}:${currentMinutes.toString().padStart(2, '0')}`;

    // Calculate start time = current time - duration
    const startTimeMs = now.getTime() - newDuration * 60 * 1000;
    const startTime = new Date(startTimeMs);
    const startHours = startTime.getHours();
    const startMinutes = startTime.getMinutes();

    form.start_time = `${startHours.toString().padStart(2, '0')}:${startMinutes.toString().padStart(2, '0')}`;

    // Reset to 'times' so next calculation updates duration
    // Removed setTimeout to prevent race condition where typing "60" gets overwritten back to "6"
    // Control should only return to 'times' when user explicitly interacts with time inputs (onTimeChange)
});

const onTimeChange = () => {
    lastEditedField.value = 'times';
};

const onDurationChange = () => {
    lastEditedField.value = 'duration';
};

const submit = () => {
    // Process form data before submission
    const submitData = { ...form.data() };
    if (submitData.team_id === 'none') {
        submitData.team_id = '';
    }

    if (isEditing.value && props.entry) {
        router.put(`/offline-time/${props.entry.id}`, submitData, {
            preserveScroll: true,
        });
    } else {
        router.post('/offline-time', submitData, {
            preserveScroll: true,
        });
    }
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Offline Time Tracking',
        href: '/offline-time',
    },
    {
        title: 'Add Offline Time',
        href: '/offline-time/create',
    },
];
</script>

<template>
    <Head :title="isEditing ? 'Edit Time Entry' : 'Add Time Entry'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-2xl px-4 py-6">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/offline-time"
                    class="mb-3 inline-flex items-center text-xs text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                    Back to Time Entries
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ isEditing ? 'Edit Time Entry' : 'Add New Time Entry' }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        isEditing
                            ? 'Update your offline working time details'
                            : 'Log your offline working hours'
                    }}
                </p>
            </div>

            <!-- Form Card -->
            <Card class="border-slate-200 shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-lg">Time Entry Details</CardTitle>
                    <CardDescription class="text-xs">
                        Fill in the details of your offline work time. All
                        fields marked with * are required.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Team -->
                        <div v-if="user?.role === 'admin'" class="space-y-2">
                            <Label for="team" class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                Team (Optional)
                            </Label>
                            <Select v-model="form.team_id">
                                <SelectTrigger
                                    id="team"
                                    :class="
                                        form.errors.team_id
                                            ? 'border-destructive'
                                            : ''
                                    "
                                >
                                    <SelectValue
                                        placeholder="Personal / No Team"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none"
                                        >Personal / No Team</SelectItem
                                    >
                                    <SelectItem
                                        v-for="team in teams"
                                        :key="team.id"
                                        :value="team.id.toString()"
                                    >
                                        {{ team.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.team_id" />
                            <p
                                class="text-sm text-[11px] text-muted-foreground"
                            >
                                Optionally associate this time with a team
                            </p>
                        </div>

                        <!-- Duration & Date Row -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Duration Input -->
                            <div class="space-y-2">
                                <Label
                                    for="duration"
                                    class="flex items-center gap-2"
                                >
                                    <Clock class="h-4 w-4" />
                                    Duration (Minutes)
                                </Label>
                                <Input
                                    id="duration"
                                    v-model="durationMinutes"
                                    type="number"
                                    inputmode="numeric"
                                    placeholder="e.g. 30"
                                    min="1"
                                    @input="onDurationChange"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Calculated:
                                    {{ calculatedDuration || 'N/A' }}
                                </p>
                            </div>

                            <!-- Date -->
                            <div class="space-y-2">
                                <Label
                                    for="date"
                                    class="flex items-center gap-2"
                                >
                                    <Calendar class="h-4 w-4" />
                                    Date *
                                </Label>
                                <DatePickerInput
                                    id="date"
                                    v-model="form.date"
                                    :max-date="
                                        new Date().toISOString().split('T')[0]
                                    "
                                    :has-error="!!form.errors.date"
                                />
                                <InputError :message="form.errors.date" />
                            </div>
                        </div>

                        <!-- Time Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start Time -->
                            <div class="space-y-2">
                                <Label
                                    for="start_time"
                                    class="flex items-center gap-2"
                                >
                                    <Clock class="h-4 w-4" />
                                    Start Time *
                                </Label>
                                <TimePickerInput
                                    id="start_time"
                                    v-model="form.start_time"
                                    :has-error="!!form.errors.start_time"
                                    @update:modelValue="onTimeChange"
                                />
                                <InputError :message="form.errors.start_time" />
                            </div>

                            <!-- End Time -->
                            <div class="space-y-2">
                                <Label
                                    for="end_time"
                                    class="flex items-center gap-2"
                                >
                                    <Clock class="h-4 w-4" />
                                    End Time *
                                </Label>
                                <TimePickerInput
                                    id="end_time"
                                    v-model="form.end_time"
                                    :has-error="!!form.errors.end_time"
                                    @update:modelValue="onTimeChange"
                                />
                                <InputError :message="form.errors.end_time" />
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="space-y-2">
                            <Label
                                for="purpose"
                                class="flex items-center gap-2"
                            >
                                <FileText class="h-4 w-4" />
                                Purpose *
                            </Label>
                            <Select v-model="form.purpose" required>
                                <SelectTrigger
                                    id="purpose"
                                    :class="
                                        form.errors.purpose
                                            ? 'w-full border-destructive'
                                            : 'w-full'
                                    "
                                >
                                    <SelectValue
                                        placeholder="Select a purpose"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in purposeOptions"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.purpose" />
                            <p class="text-sm text-muted-foreground">
                                What were you doing during this offline time?
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description"
                                >Description (Optional)</Label
                            >
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Add additional notes or details about this offline time..."
                                rows="4"
                                :class="
                                    form.errors.description
                                        ? 'border-destructive'
                                        : ''
                                "
                            />
                            <InputError :message="form.errors.description" />
                            <p class="text-sm text-muted-foreground">
                                Provide more details about the activity or task
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center gap-3 pt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="min-w-[120px]"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : isEditing
                                          ? 'Update Entry'
                                          : 'Save Entry'
                                }}
                            </Button>
                            <Link href="/offline-time">
                                <Button type="button" variant="outline"
                                    >Cancel</Button
                                >
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
