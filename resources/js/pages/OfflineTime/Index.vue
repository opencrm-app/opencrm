<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Filter, Edit2, Trash2, Clock, Calendar, FileText, Search, RotateCcw } from 'lucide-vue-next';
import DatePickerInput from '@/components/DatePickerInput.vue';

interface User {
	id: number;
	name: string;
	email?: string;
	role?: string;
}

interface OfflineTimeEntry {
	id: number;
	user_id: number;
	date: string;
	start_time: string;
	end_time: string;
	duration_minutes: number;
	purpose: string;
	description?: string;
	user?: User;
	created_at?: string;
	updated_at?: string;
}

interface PaginatedEntries {
	data: OfflineTimeEntry[];
	current_page: number;
	last_page: number;
	per_page: number;
	total: number;
	links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
	entries: PaginatedEntries;
	users: User[];
	filters: {
		user_id?: number;
		date_from?: string;
		date_to?: string;
		month?: string;
	};
    totalDuration: number;
}

const props = defineProps<Props>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user as User & { role: string });
const isAdmin = computed(() => currentUser.value?.role === 'admin');

// Filter state
const filterUserId = ref(props.filters.user_id?.toString() || '');
const filterDateFrom = ref(props.filters.date_from || '');
const filterDateTo = ref(props.filters.date_to || '');
const filterMonth = ref(props.filters.month || '');

// Delete confirmation dialog
const deleteDialogOpen = ref(false);
const entryToDelete = ref<OfflineTimeEntry | null>(null);

const formatDuration = (minutes: number | string): string => {
	const total = Math.abs(Number(minutes));
	if (!total || isNaN(total)) return '0m';
	
	const hours = Math.floor(total / 60);
	const mins = total % 60;

	if (hours > 0 && mins > 0) {
		return `${hours}h ${mins}m`;
	} else if (hours > 0) {
		return `${hours}h`;
	} else {
		return `${mins}m`;
	}
};

const formatDate = (dateString: string): string => {
	const date = new Date(dateString);
	return date.toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	});
};

const formatTime = (timeString: string): string => {
	// timeString is in format HH:MM:SS or just HH:MM
	const [hours, minutes] = timeString.split(':');
	const hour = parseInt(hours);
	const ampm = hour >= 12 ? 'PM' : 'AM';
	const hour12 = hour % 12 || 12;
	return `${hour12}:${minutes} ${ampm}`;
};

const applyFilters = () => {
	router.get(
		'/offline-time',
		{
			user_id: filterUserId.value || undefined,
			date_from: filterDateFrom.value || undefined,
			date_to: filterDateTo.value || undefined,
			month: filterMonth.value || undefined,
		},
		{
			preserveState: true,
			preserveScroll: true,
		}
	);
};

const clearFilters = () => {
	filterUserId.value = '';
	filterDateFrom.value = '';
	filterDateTo.value = '';
	filterMonth.value = '';
	router.get('/offline-time');
};

const confirmDelete = (entry: OfflineTimeEntry) => {
	entryToDelete.value = entry;
	deleteDialogOpen.value = true;
};

const deleteEntry = () => {
	if (entryToDelete.value) {
		router.delete(`/offline-time/${entryToDelete.value.id}`, {
			onSuccess: () => {
				deleteDialogOpen.value = false;
				entryToDelete.value = null;
			},
		});
	}
};

const getPurposeBadgeVariant = (purpose: string): 'default' | 'secondary' | 'outline' => {
	const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
		'Meeting': 'default',
		'Client Discussion': 'secondary',
		'Training': 'outline',
		'Other': 'outline',
	};
	return variants[purpose] || 'outline';
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
	{
		title: 'Offline Time Tracking',
		href: '/offline-time',
	}
];
</script>

<template>
	<Head title="Offline Time Tracking" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="container mx-auto max-w-7xl p-4">
			<!-- Header -->
			<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
				<div>
					<h1 class="text-lg font-semibold tracking-tight">Offline Time Tracking</h1>
					<p class="text-sm text-muted-foreground">
						{{ isAdmin ? 'Manage all offline time entries' : 'Track your offline working hours' }}
					</p>
				</div>
				<div class="flex gap-2">
                    <Link v-if="isAdmin" href="/offline-time/report">
                        <Button variant="outline">
                            <FileText class="h-4 w-4" />
                            Monthly Reports
                        </Button>
                    </Link>
					<Link href="/offline-time/create">
						<Button>
							<Plus class="h-4 w-4" />
							Add Entry
						</Button>
					</Link>
				</div>
			</div>

			<!-- Success Alert -->
			<Alert v-if="($page.props.flash as any)?.success" class="mb-6 border-green-500 bg-green-50">
				<AlertDescription class="text-green-800">
					{{ ($page.props.flash as any).success }}
				</AlertDescription>
			</Alert>

			<!-- Summary Card -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
				<Card class="shadow-none p-2">
					<CardContent class="p-0 flex items-center gap-3">
						<div class="p-2 bg-gray-100 rounded-lg">
							<Clock class="h-5 w-5 text-black" />
						</div>
						<div>
							<p class="text-xs font-medium text-black uppercase tracking-wider">Total Duration</p>
							<p class="text-xl font-bold text-black leading-none mt-1">
								{{ formatDuration(totalDuration) }}
							</p>
						</div>
					</CardContent>
				</Card>

				<Card class="shadow-none p-2">
					<CardContent class="p-0 flex items-center gap-3">
						<div class="p-2 bg-slate-100 rounded-lg">
							<FileText class="h-5 w-5 text-black" />
						</div>
						<div>
							<p class="text-xs font-medium text-black uppercase tracking-wider">Total Entries</p>
							<p class="text-xl font-bold text-black leading-none mt-1">
								{{ entries.total }}
							</p>
						</div>
					</CardContent>
				</Card>
			</div>

			<!-- Filters Card -->
			<Card class="border-none shadow-none py-4">
				<CardContent class="p-0">
					<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
						<!-- User Filter (Admin Only) - Currently commented out in original -->
						
						<div class="space-y-1.5 text-sm">
							<Label for="filter-date-from">From Date</Label>
							<DatePickerInput id="filter-date-from" v-model="filterDateFrom" />
						</div>

						<div class="space-y-1.5 text-sm">
							<Label for="filter-date-to">To Date</Label>
							<DatePickerInput id="filter-date-to" v-model="filterDateTo" />
						</div>

						<div class="space-y-1.5 text-sm">
							<Label for="filter-month">Month</Label>
							<Input id="filter-month" v-model="filterMonth" type="month" class="h-9" />
						</div>

						<div class="flex items-end gap-2">
							<Button size="sm" @click="applyFilters" class="flex-1">
								<Search class="mr-1.5 h-3.5 w-3.5" />
								Search
							</Button>
							<Button size="sm" variant="outline" @click="clearFilters">
								<RotateCcw class="mr-1.5 h-3.5 w-3.5" />
								Reset
							</Button>
						</div>
					</div>
				</CardContent>
			</Card>

			

			<!-- Time Entries Table -->
			<Card class="p-0">
				<CardContent class="p-0">
					<div class="overflow-x-auto">
						<Table>
							<TableHeader class="bg-muted">
								<TableRow>
									<TableHead v-if="isAdmin" class="text-xs font-semibold uppercase py-3">User</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3">Date</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3">Time Range</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3">Duration</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3">Purpose</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3 max-w-[200px]">Description</TableHead>
									<TableHead class="text-xs font-semibold uppercase py-3 text-right">Actions</TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								<TableRow v-if="entries.data.length === 0">
									<TableCell :colspan="isAdmin ? 7 : 6" class="text-center py-12">
										<div class="flex flex-col items-center justify-center text-muted-foreground">
											<Calendar class="h-12 w-12 mb-4 opacity-20" />
											<p class="text-lg font-medium">No time entries found</p>
											<p class="text-sm mt-2">
												{{ filters.user_id || filters.date_from || filters.date_to || filters.month
													? 'Try adjusting your filters'
													: 'Start by adding your first offline time entry' }}
											</p>
										</div>
									</TableCell>
								</TableRow>
								<TableRow v-for="entry in entries.data" :key="entry.id" class="hover:bg-muted/50 border-b last:border-0">
									<TableCell v-if="isAdmin" class="font-medium text-sm py-3">
										{{ entry.user?.name || 'Unknown' }}
									</TableCell>
									<TableCell class="py-3 text-sm">
										<div class="flex items-center gap-1.5">
											<Calendar class="h-3.5 w-3.5 text-muted-foreground" />
											{{ formatDate(entry.date) }}
										</div>
									</TableCell>
									<TableCell class="py-3 text-sm">
										<div class="flex items-center gap-1.5">
											<Clock class="h-3.5 w-3.5 text-muted-foreground" />
											{{ formatTime(entry.start_time) }} - {{ formatTime(entry.end_time) }}
										</div>
									</TableCell>
									<TableCell class="py-3">
										<Badge variant="secondary" class="font-mono text-[11px] h-5 px-1.5">
											{{ formatDuration(entry.duration_minutes) }}
										</Badge>
									</TableCell>
									<TableCell class="py-3">
										<Badge :variant="getPurposeBadgeVariant(entry.purpose)" class="text-[11px] h-5 px-1.5">
											{{ entry.purpose }}
										</Badge>
									</TableCell>
									<TableCell class="py-3 max-w-[200px]">
										<p class="text-xs text-muted-foreground truncate" :title="entry.description || 'N/A'">
											{{ entry.description || 'N/A' }}
										</p>
									</TableCell>
									<TableCell class="py-3 text-right">
										<div class="flex items-center justify-end gap-1">
											<Link :href="`/offline-time/${entry.id}/edit`">
												<Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-slate-100">
													<Edit2 class="h-3.5 w-3.5 text-slate-600" />
												</Button>
											</Link>
											<Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-red-50" @click="confirmDelete(entry)">
												<Trash2 class="h-3.5 w-3.5 text-red-500" />
											</Button>
										</div>
									</TableCell>
								</TableRow>
							</TableBody>
						</Table>
					</div>

					<!-- Pagination -->
					<div
						v-if="entries.last_page > 1"
						class="flex items-center justify-between px-6 py-4 border-t"
					>
						<div class="text-sm text-muted-foreground">
							Showing {{ (entries.current_page - 1) * entries.per_page + 1 }} to
							{{ Math.min(entries.current_page * entries.per_page, entries.total) }} of
							{{ entries.total }} entries
						</div>
						<div class="flex gap-2">
							<Link
								v-for="link in entries.links"
								:key="link.label"
								:href="link.url || '#'"
								:class="[
									'px-3 py-1 rounded',
									link.active
										? 'bg-primary text-primary-foreground'
										: 'hover:bg-muted',
									!link.url && 'opacity-50 cursor-not-allowed',
								]"
								:disabled="!link.url"
								v-html="link.label"
							/>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>

		<!-- Delete Confirmation Dialog -->
		<Dialog v-model:open="deleteDialogOpen">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Delete Time Entry</DialogTitle>
					<DialogDescription>
						Are you sure you want to delete this time entry? This action cannot be undone.
					</DialogDescription>
				</DialogHeader>
				<div v-if="entryToDelete" class="py-4 space-y-2">
					<p class="text-sm">
						<span class="font-medium">Date:</span> {{ formatDate(entryToDelete.date) }}
					</p>
					<p class="text-sm">
						<span class="font-medium">Time:</span>
						{{ formatTime(entryToDelete.start_time) }} - {{ formatTime(entryToDelete.end_time) }}
					</p>
					<p class="text-sm">
						<span class="font-medium">Purpose:</span> {{ entryToDelete.purpose }}
					</p>
				</div>
				<DialogFooter>
					<Button variant="outline" @click="deleteDialogOpen = false">Cancel</Button>
					<Button variant="destructive" @click="deleteEntry">Delete</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</AppLayout>
</template>
