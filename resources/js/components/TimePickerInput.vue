<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Clock } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    label?: string;
    id?: string;
    hasError?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'hour-complete']);

const hour = ref('');
const minute = ref('');
const period = ref<string>('AM');
const minuteInputRef = ref<HTMLInputElement | null>(null);

const getInputClass = () => {
    return props.hasError
        ? 'border-destructive w-16 text-center'
        : 'w-16 text-center';
};

const parseTime = (time: string) => {
    if (!time) {
        hour.value = '';
        minute.value = '';
        period.value = 'AM';
        return;
    }

    const [h, m] = time.split(':');
    let hNum = parseInt(h);

    // Update internal logic for 12h conversion without aggressive string padding
    if (hNum === 0) {
        hour.value = '12';
        period.value = 'AM';
    } else if (hNum === 12) {
        hour.value = '12';
        period.value = 'PM';
    } else if (hNum > 12) {
        hour.value = (hNum - 12).toString(); // REMOVED padStart
        period.value = 'PM';
    } else {
        hour.value = hNum.toString(); // REMOVED padStart
        period.value = 'AM';
    }

    minute.value = m; // m is usually 2 digits from DB/Time format, but we trust it or let it be.
};

const updateTime = () => {
    // Logic: Don't emit partials to prevent "Watch" loop from overwriting user input while typing
    if (hour.value === '' && minute.value === '') {
        emit('update:modelValue', ''); // Allow clearing
        return;
    }

    if (hour.value === '' || minute.value === '') {
        // One is missing, don't emit yet. Wait for user to fill both.
        return;
    }

    let hNum = parseInt(hour.value);
    const m = minute.value.padStart(2, '0');

    if (isNaN(hNum)) return;

    // Handle 12h to 24h conversion
    if (period.value === 'AM') {
        if (hNum === 12) {
            hNum = 0;
        }
    } else {
        // PM
        if (hNum !== 12) {
            hNum += 12;
        }
    }

    const timeString = `${hNum.toString().padStart(2, '0')}:${m}`;
    emit('update:modelValue', timeString);
};

// Validations and formatting on blur
const validateHour = (e: Event | string) => {
    let val = '';
    if (typeof e === 'string') {
        val = e;
    } else {
        const input = e.target as HTMLInputElement;
        val = input.value;
    }

    val = val.replace(/\D/g, ''); // Numeric only

    // Auto-focus to minute field when 2 digits are typed
    if (val.length === 2) {
        emit('hour-complete');
        // Focus minute input after a short delay to allow DOM update
        setTimeout(() => {
            (minuteInputRef.value?.$el as HTMLInputElement)?.focus();
        }, 10);
    }

    if (val.length > 2) val = val.slice(0, 2);

    hour.value = val;
    updateTime();
};

const formatHour = () => {
    let h = parseInt(hour.value);
    if (isNaN(h)) {
        hour.value = '';
    } else {
        // 12-hour clock logic: 1-12
        if (h < 1) h = 12; // Treat 0 as 12
        if (h > 12) h = 12;
        hour.value = h.toString().padStart(2, '0');
    }
    updateTime();
};

const validateMinute = (e: Event | string) => {
    let val = '';
    if (typeof e === 'string') {
        val = e;
    } else {
        const input = e.target as HTMLInputElement;
        val = input.value;
    }

    val = val.replace(/\D/g, ''); // Numeric only

    if (val.length > 2) val = val.slice(0, 2);

    minute.value = val;
    updateTime();
};

const formatMinute = () => {
    let m = parseInt(minute.value);
    if (isNaN(m)) {
        minute.value = '00';
    } else {
        if (m < 0) m = 0;
        if (m > 59) m = 59;
        minute.value = m.toString().padStart(2, '0');
    }
    updateTime();
};

watch(
    () => props.modelValue,
    (newVal) => {
        // Construct current internal 24h representation to check against newVal
        let currentH = 0;
        let hTemp = parseInt(hour.value);
        if (!isNaN(hTemp)) {
            if (period.value === 'AM' && hTemp === 12) currentH = 0;
            else if (period.value === 'AM') currentH = hTemp;
            else if (period.value === 'PM' && hTemp === 12) currentH = 12;
            else if (period.value === 'PM') currentH = hTemp + 12;
        }
        const currentM = minute.value.padStart(2, '0'); // Compare against standardized minute
        const currentStr = `${currentH.toString().padStart(2, '0')}:${currentM}`;

        // Only update internals if the new value is different from what we think we have
        // AND if the new value is not just the formatted version of our partial
        // But since we don't emit partials, newVal should only come in if valid.
        if (newVal && newVal !== currentStr) {
            parseTime(newVal);
        } else if (!newVal && (hour.value !== '' || minute.value !== '')) {
            // External clear
            parseTime('');
        }
    },
    { immediate: true },
);

// Also watch period explicitly because it's a select
watch(period, () => {
    updateTime();
});

// Quick Select Logic
const timeOptions = computed(() => {
    const options: string[] = [];
    const periods = ['AM', 'PM'];
    const hours = [
        '12',
        '01',
        '02',
        '03',
        '04',
        '05',
        '06',
        '07',
        '08',
        '09',
        '10',
        '11',
    ];
    const minutes = ['00', '15', '30', '45'];

    periods.forEach((p) => {
        hours.forEach((h) => {
            minutes.forEach((m) => {
                options.push(`${h}:${m} ${p}`);
            });
        });
    });
    return options;
});

const quickTimeModel = computed({
    get: () => {
        if (!hour.value || !minute.value) return '';
        const h = hour.value.toString().padStart(2, '0');
        const m = minute.value.toString().padStart(2, '0');
        const val = `${h}:${m} ${period.value}`;
        return timeOptions.value.includes(val) ? val : '';
    },
    set: (val: string) => {
        if (!val) return;
        const [timeStr, p] = val.split(' ');
        const [h, m] = timeStr.split(':');

        // Quick select is forced trusted source, so we set explicitly
        hour.value = h.replace(/^0/, ''); // Remove leading zero for consistency with unpadded mode
        minute.value = m;
        period.value = p;
        updateTime();
    },
});
</script>

<template>
    <div class="space-y-2">
        <Label v-if="label" :for="id">{{ label }}</Label>
        <div class="flex items-end gap-2">
            <!-- Hours -->
            <div class="flex flex-col gap-1.5 text-center">
                <Label class="text-xs font-normal text-muted-foreground"
                    >Hours</Label
                >
                <Input
                    :id="id ? `${id}-hours` : undefined"
                    v-model="hour"
                    type="text"
                    inputmode="numeric"
                    placeholder="12"
                    :class="getInputClass()"
                    @input="validateHour"
                    @blur="formatHour"
                />
            </div>

            <!-- Minutes -->
            <div class="flex flex-col gap-1.5 text-center">
                <Label class="text-xs font-normal text-muted-foreground"
                    >Minutes</Label
                >
                <Input
                    ref="minuteInputRef"
                    v-model="minute"
                    type="text"
                    inputmode="numeric"
                    placeholder="00"
                    :class="getInputClass()"
                    @input="validateMinute"
                    @blur="formatMinute"
                />
            </div>

            <!-- Period -->
            <div class="flex flex-col gap-1.5 text-center">
                <Label class="text-xs font-normal text-muted-foreground"
                    >Period</Label
                >
                <Select v-model="period">
                    <SelectTrigger
                        :class="[
                            hasError ? 'border-destructive' : '',
                            'w-[80px]',
                        ]"
                    >
                        <SelectValue placeholder="AM" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="AM">AM</SelectItem>
                        <SelectItem value="PM">PM</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Quick Select -->
            <div class="flex flex-col gap-1.5 text-center">
                <Label class="text-xs font-normal text-muted-foreground"
                    >Quick</Label
                >
                <Select v-model="quickTimeModel">
                    <SelectTrigger
                        :class="[
                            hasError ? 'border-destructive' : '',
                            'flex w-[45px] justify-center px-2',
                        ]"
                    >
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </SelectTrigger>
                    <SelectContent class="h-[200px]">
                        <SelectItem
                            v-for="t in timeOptions"
                            :key="t"
                            :value="t"
                        >
                            {{ t }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    </div>
</template>
