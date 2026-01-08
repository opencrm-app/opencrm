<script setup lang="ts">
import { ref, watch } from 'vue';
import { 
  DateFormatter, 
  getLocalTimeZone, 
  today, 
  parseDate, 
  type DateValue 
} from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';

const props = defineProps<{
  modelValue?: string;
  id?: string;
  hasError?: boolean;
  maxDate?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const defaultPlaceholder = today(getLocalTimeZone());
// Computed max date for Calendar
import { computed } from 'vue';
const maxDateValue = computed(() => {
  if (props.maxDate) {
    try {
      return parseDate(props.maxDate);
    } catch {
      return undefined;
    }
  }
  return undefined;
});

const date = ref<DateValue>();

const df = new DateFormatter('en-US', {
  dateStyle: 'long',
});

// Watch modelValue to update internal date
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    try {
      date.value = parseDate(newVal);
    } catch (e) {
      console.error('Invalid date format:', newVal);
    }
  } else {
    date.value = undefined;
  }
}, { immediate: true });

// Watch internal date to emit modelValue
watch(date, (newDate) => {
  if (newDate) {
    emit('update:modelValue', newDate.toString());
  } else {
    emit('update:modelValue', '');
  }
});

const isOpen = ref(false);

</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        :id="id"
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !date && 'text-muted-foreground',
          hasError && 'border-destructive'
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ date ? df.format(date.toDate(getLocalTimeZone())) : "Pick a date" }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <Calendar
        v-model="date"
        :max-value="maxDateValue"
        :default-placeholder="defaultPlaceholder"
        initial-focus
        @update:model-value="isOpen = false"
      />
    </PopoverContent>
  </Popover>
</template>
