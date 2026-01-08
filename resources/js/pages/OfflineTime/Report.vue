<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, FileText, Calendar, Users } from 'lucide-vue-next';

interface UserSummary {
	user: {
		id: number;
		name: string;
	};
	total_minutes: number;
	total_hours: number;
	formatted: string;
}

interface Props {
	month: string;
	summary: UserSummary[];
}

const props = defineProps<Props>();

const selectedMonth = ref(props.month);

// Watch for month changes to reload data
watch(selectedMonth, (newMonth) => {
	router.get(
		'/offline-time/report',
		{ month: newMonth },
		{ preserveState: true, preserveScroll: true }
	);
});

const formatMonth = (monthStr: string) => {
	const [year, month] = monthStr.split('-');
	const date = new Date(parseInt(year), parseInt(month) - 1, 1);
	return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const totalHoursThisMonth = computed(() => {
	const total = props.summary.reduce((sum, item) => sum + item.total_minutes, 0);
	const hours = Math.floor(total / 60);
	const minutes = total % 60;
	
	if (hours > 0 && minutes > 0) {
		return `${hours}h ${minutes}m`;
	} else if (hours > 0) {
		return `${hours}h`;
	} else {
		return `${minutes}m`;
	}
});


const getMaxMinutes = computed(() => {
	return Math.max(...props.summary.map(item => item.total_minutes), 1);
});

const getWidthPercentage = (minutes: number) => {
	return `${Math.max((minutes / getMaxMinutes.value) * 100, 0)}%`;
};
</script>

<template>
	<Head title="Monthly Offline Time Report" />

	<AppLayout>
		<div class="container mx-auto py-8 px-4 max-w-5xl">
			<!-- Header -->
			<div class="mb-8">
				<Link href="/offline-time" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4">
					<ArrowLeft class="mr-2 h-4 w-4" />
					Back to Time Entries
				</Link>
				<div class="flex flex-col md:flex-row md:items-center md:justify-between">
					<div>
						<h1 class="text-3xl font-bold tracking-tight">Monthly Report</h1>
						<p class="text-muted-foreground mt-2">
							User-wise offline time summary for {{ formatMonth(month) }}
						</p>
					</div>
					<div class="mt-4 md:mt-0 flex items-center gap-4">
						<div class="flex items-center gap-2">
							<Label for="month-select" class="whitespace-nowrap">Select Month:</Label>
							<Input 
								id="month-select" 
								type="month" 
								v-model="selectedMonth" 
								class="w-auto"
							/>
						</div>
					</div>
				</div>
			</div>

			<!-- Summary Stats -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
				<Card>
					<CardContent class="pt-6">
						<div class="flex items-center gap-4">
							<div class="p-3 bg-primary/10 rounded-full">
								<Calendar class="h-6 w-6 text-primary" />
							</div>
							<div>
								<p class="text-sm font-medium text-muted-foreground">Month</p>
								<p class="text-2xl font-bold">{{ formatMonth(month) }}</p>
							</div>
						</div>
					</CardContent>
				</Card>
				
				<Card>
					<CardContent class="pt-6">
						<div class="flex items-center gap-4">
							<div class="p-3 bg-blue-100 rounded-full">
								<FileText class="h-6 w-6 text-blue-600" />
							</div>
							<div>
								<p class="text-sm font-medium text-muted-foreground">Total Offline Time</p>
								<p class="text-2xl font-bold">{{ totalHoursThisMonth }}</p>
							</div>
						</div>
					</CardContent>
				</Card>
				
				<Card>
					<CardContent class="pt-6">
						<div class="flex items-center gap-4">
							<div class="p-3 bg-green-100 rounded-full">
								<Users class="h-6 w-6 text-green-600" />
							</div>
							<div>
								<p class="text-sm font-medium text-muted-foreground">Active Users</p>
								<p class="text-2xl font-bold">{{ summary.filter(s => s.total_minutes > 0).length }}</p>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>

			<!-- User Breakdown Table -->
			<Card>
				<CardHeader>
					<CardTitle>User Breakdown</CardTitle>
					<CardDescription>
						Detailed breakdown of offline hours by user.
					</CardDescription>
				</CardHeader>
				<CardContent>
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>User</TableHead>
								<TableHead>Total Hours</TableHead>
								<TableHead class="w-[40%]">Visual Distribution</TableHead>
								<TableHead class="text-right">Actions</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							<TableRow v-if="summary.length === 0">
								<TableCell colspan="4" class="text-center py-8 text-muted-foreground">
									No data available for this month.
								</TableCell>
							</TableRow>
							<TableRow v-for="item in summary" :key="item.user.id">
								<TableCell class="font-medium">{{ item.user.name }}</TableCell>
								<TableCell>
									<div class="font-semibold">{{ item.formatted }}</div>
									<div class="text-xs text-muted-foreground">{{ item.total_minutes }} minutes</div>
								</TableCell>
								<TableCell>
									<div class="w-full bg-muted rounded-full h-2.5 overflow-hidden">
										<div 
											class="bg-primary h-2.5 rounded-full" 
											:style="{ width: getWidthPercentage(item.total_minutes) }"
										></div>
									</div>
								</TableCell>
								<TableCell class="text-right">
									<Link :href="`/offline-time?user_id=${item.user.id}&month=${month}`">
										<Button variant="ghost" size="sm">
											View Details
										</Button>
									</Link>
								</TableCell>
							</TableRow>
						</TableBody>
					</Table>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
