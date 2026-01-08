<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
  modelValue: string;
  label?: string;
  id?: string;
  hasError?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

const hour = ref('');
const minute = ref('');
const period = ref<string>('AM');

const getInputClass = () => {
  return props.hasError ? 'border-destructive w-16 text-center' : 'w-16 text-center';
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
  
  if (hNum === 0) {
    hour.value = '12';
    period.value = 'AM';
  } else if (hNum === 12) {
    hour.value = '12';
    period.value = 'PM';
  } else if (hNum > 12) {
    hour.value = (hNum - 12).toString().padStart(2, '0');
    period.value = 'PM';
  } else {
    hour.value = hNum.toString().padStart(2, '0');
    period.value = 'AM';
  }
  
  minute.value = m;
};

const updateTime = () => {
  let hNum = parseInt(hour.value);
  const m = minute.value.padStart(2, '0');
  
  if (isNaN(hNum)) return; // Don't emit if hour is invalid yet

  // Handle 12h to 24h conversion
  if (period.value === 'AM') {
    if (hNum === 12) {
      hNum = 0;
    }
  } else { // PM
    if (hNum !== 12) {
      hNum += 12;
    }
  }
  
  const timeString = `${hNum.toString().padStart(2, '0')}:${m}`;
  emit('update:modelValue', timeString);
};

// Validations and formatting on blur
const validateHour = (e: Event) => {
  const input = e.target as HTMLInputElement;
  let val = input.value.replace(/\D/g, ''); // Numeric only
  
  if (val.length > 2) val = val.slice(0, 2);
  
  const h = parseInt(val);
  if (!isNaN(h)) {
    if (h > 12) val = '12';
    // Allow '0' while typing but we'll fix on blur
  }
  
  hour.value = val;
  updateTime();
};

const formatHour = () => {
  let h = parseInt(hour.value);
  if (isNaN(h)) {
    hour.value = '';
  } else {
    if (h < 1) h = 1;
    if (h > 12) h = 12;
    hour.value = h.toString().padStart(2, '0');
  }
  updateTime();
};

const validateMinute = (e: Event) => {
  const input = e.target as HTMLInputElement;
  let val = input.value.replace(/\D/g, ''); // Numeric only
  
  if (val.length > 2) val = val.slice(0, 2);
  
  const m = parseInt(val);
  if (!isNaN(m)) {
    if (m > 59) val = '59';
  }
  
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

watch(() => props.modelValue, (newVal) => {
  // complex logic to avoid cursor jumping or circular updates if not careful, 
  // but for simple inputs it's usually okay to re-parse if values mismatch significantly.
  // We'll simplisticly parse whenever external model changes.
    // Construct current internal 24h representation to check against newVal
    let currentH = 0; 
    let hTemp = parseInt(hour.value);
    if (!isNaN(hTemp)) {
        if (period.value === 'AM' && hTemp === 12) currentH = 0;
        else if (period.value === 'AM') currentH = hTemp;
        else if (period.value === 'PM' && hTemp === 12) currentH = 12;
        else if (period.value === 'PM') currentH = hTemp + 12;
    }
    const currentM = minute.value;
    const currentStr = `${currentH.toString().padStart(2, '0')}:${currentM}`;

    // Only update internals if the new value is different from what we think we have
    if (newVal !== currentStr) {
        parseTime(newVal);
    }
}, { immediate: true });

// Also watch period explicitly because it's a select
watch(period, () => {
  updateTime();
});

</script>

<template>
  <div class="space-y-2">
    <Label v-if="label" :for="id">{{ label }}</Label>
    <div class="flex items-end gap-2">
      <!-- Hours -->
      <div class="flex flex-col gap-1.5 text-center">
        <Label class="text-xs text-muted-foreground font-normal">Hours</Label>
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
        <Label class="text-xs text-muted-foreground font-normal">Minutes</Label>
        <Input
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
        <Label class="text-xs text-muted-foreground font-normal">Period</Label>
        <Select v-model="period">
          <SelectTrigger :class="[hasError ? 'border-destructive' : '', 'w-[80px]']">
            <SelectValue placeholder="AM" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="AM">AM</SelectItem>
            <SelectItem value="PM">PM</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  </div>
</template>
