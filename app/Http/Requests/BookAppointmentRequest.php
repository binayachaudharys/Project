<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('items') && is_array($this->input('items'))) {
            return;
        }

        if ($this->filled('bookable_type') && $this->filled('bookable_id')) {
            $this->merge([
                'items' => [[
                    'bookable_type' => $this->input('bookable_type'),
                    'bookable_id' => $this->input('bookable_id'),
                ]],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.bookable_type' => ['required', Rule::in(['service', 'package'])],
            'items.*.bookable_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date', 'after:now'],
            'staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [UserRole::Staff->value, UserRole::Owner->value])
                ),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('items') || $validator->errors()->has('items.*')) {
                return;
            }

            foreach ($this->input('items', []) as $index => $item) {
                $type = $item['bookable_type'] ?? null;
                $id = $item['bookable_id'] ?? null;

                $bookable = $type === 'package'
                    ? Package::find($id)
                    : Service::find($id);

                if (! $bookable || ! $bookable->is_active) {
                    $validator->errors()->add(
                        "items.{$index}.bookable_id",
                        'The selected item is not available for booking.'
                    );
                }
            }
        });
    }
}
