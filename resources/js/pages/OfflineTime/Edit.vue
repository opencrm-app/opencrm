<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Save, Clock, Calendar, FileText } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TimePickerInput from '@/components/TimePickerInput.vue';
import DatePickerInput from '@/components/DatePickerInput.vue';

interface OfflineTimeEntry {
	id: number;
	date: string;
	start_time: string;
	end_time: string;
	purpose: string;
	description?: string;
}

interface Props {
	entry: OfflineTimeEntry;
}

const props = defineProps<Props>();

const form = useForm({
	date: props.entry.date,
	start_time: props.entry.start_time.substring(0, 5), 
	end_time: props.entry.end_time.substring(0, 5),
	purpose: props.entry.purpose,
	description: props.entry.description || '',
});

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

		let diffMinutes = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60));
		let isOvernight = false;

		if (diffMinutes < 0) {
			diffMinutes += 24 * 60;
			isOvernight = true;
		} else if (diffMinutes === 0) {
			return 'Start and end time cannot be the same';
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

const submit = () => {
	form.put(`/offline-time/${props.entry.id}`, {
		preserveScroll: true,
	});
};

const breadcrumbs = [
	{
		title: 'Dashboard',
		href: '/dashboard'	
	},
	{
		title: 'Offline Time Tracking',
		href: '/offline-time'
	},
	{
		title: 'Edit Offline Time',
		href: '/offline-time/edit'
	}
]
</script>

<template>
	<Head title="Edit Time Entry" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="container mx-auto py-6 px-4 max-w-2xl">
			<!-- Header -->
			<div class="mb-6">
				<Link href="/offline-time" class="inline-flex items-center text-xs text-muted-foreground hover:text-foreground mb-3">
					<ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
					Back to Time Entries
				</Link>
				<h1 class="text-2xl font-bold tracking-tight">Edit Time Entry</h1>
				<p class="text-sm text-muted-foreground mt-1">Update your offline working time details</p>
			</div>

			<!-- Form Card -->
			<Card class="shadow-sm border-slate-200">
				<CardHeader class="pb-4">
					<CardTitle class="text-lg">Time Entry Details</CardTitle>
					<CardDescription class="text-xs">
						Update the details of your offline work time. All fields marked with * are required.
					</CardDescription>
				</CardHeader>
				<CardContent>
					<form @submit.prevent="submit" class="space-y-6">
						<!-- Date -->
						<div class="space-y-2">
							<Label for="date" class="flex items-center gap-2">
								<Calendar class="h-4 w-4" />
								Date *
							</Label>
							<DatePickerInput
								id="date"
								v-model="form.date"
								:max-date="new Date().toISOString().split('T')[0]"
								:has-error="!!form.errors.date"
							/>
							<InputError :message="form.errors.date" />
							<p class="text-sm text-muted-foreground">
								Select the date when you worked offline
							</p>
						</div>

						<!-- Time Range -->
						<div class="grid grid-cols-2 gap-4">
							<!-- Start Time -->
							<div class="space-y-2">
								<Label for="start_time" class="flex items-center gap-2">
									<Clock class="h-4 w-4" />
									Start Time *
								</Label>
								<TimePickerInput
									id="start_time"
									v-model="form.start_time"
									:has-error="!!form.errors.start_time"
								/>
								<InputError :message="form.errors.start_time" />
							</div>

							<!-- End Time -->
							<div class="space-y-2">
								<Label for="end_time" class="flex items-center gap-2">
									<Clock class="h-4 w-4" />
									End Time *
								</Label>
								<TimePickerInput
									id="end_time"
									v-model="form.end_time"
									:has-error="!!form.errors.end_time"
								/>
								<InputError :message="form.errors.end_time" />
							</div>
						</div>

						<!-- Duration Display -->
						<Alert v-if="calculatedDuration" :class="calculatedDuration.includes('must be') ? 'border-destructive bg-destructive/10' : 'border-blue-500 bg-blue-50'">
							<AlertDescription :class="calculatedDuration.includes('must be') ? 'text-destructive' : 'text-blue-800'">
								<span class="font-medium">Duration:</span> {{ calculatedDuration }}
							</AlertDescription>
						</Alert>

						<!-- Purpose -->
						<div class="space-y-2">
							<Label for="purpose" class="flex items-center gap-2">
								<FileText class="h-4 w-4" />
								Purpose *
							</Label>
							<Select v-model="form.purpose" required>
								<SelectTrigger id="purpose" :class="form.errors.purpose ? 'border-destructive' : ''">
									<SelectValue placeholder="Select a purpose" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem v-for="option in purposeOptions" :key="option" :value="option">
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
							<Label for="description">Description (Optional)</Label>
							<Textarea
								id="description"
								v-model="form.description"
								placeholder="Add additional notes or details about this offline time..."
								rows="4"
								:class="form.errors.description ? 'border-destructive' : ''"
							/>
							<InputError :message="form.errors.description" />
							<p class="text-sm text-muted-foreground">
								Provide more details about the activity or task
							</p>
						</div>

						<!-- Form Actions -->
						<div class="flex items-center gap-3 pt-4">
							<Button type="submit" :disabled="form.processing" class="min-w-[120px]">
								<Save class="mr-2 h-4 w-4" />
								{{ form.processing ? 'Updating...' : 'Update Entry' }}
							</Button>
							<Link href="/offline-time">
								<Button type="button" variant="outline">Cancel</Button>
							</Link>
						</div>
					</form>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
