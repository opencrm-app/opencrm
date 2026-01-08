<?php

namespace App\Http\Requests;

use App\Models\OfflineTimeEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOfflineTimeEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $entry = $this->route('offline_time_entry');
        
        // User can only update their own entries, admin can update any
        return auth()->user()->isAdmin() || $entry->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasOverlappingEntry()) {
                $validator->errors()->add(
                    'start_time',
                    'This time entry overlaps with an existing entry.'
                );
            }
        });
    }

    /**
     * Check if there's an overlapping time entry for the same user and date.
     * Excludes the current entry being updated.
     */
    protected function hasOverlappingEntry(): bool
    {
        $entry = $this->route('offline_time_entry');
        $userId = $entry->user_id;
        $date = $this->input('date');
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        return OfflineTimeEntry::where('user_id', $userId)
            ->where('date', $date)
            ->where('id', '!=', $entry->id) // Exclude current entry
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
        ];
    }
}
