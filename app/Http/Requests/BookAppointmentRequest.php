<?php

namespace App\Http\Requests;

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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bookable_type' => ['required', Rule::in(['service', 'package'])],
            'bookable_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date', 'after:now'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['bookable_type', 'bookable_id'])) {
                return;
            }

            $bookable = $this->input('bookable_type') === 'package'
                ? Package::find($this->input('bookable_id'))
                : Service::find($this->input('bookable_id'));

            if (! $bookable || ! $bookable->is_active) {
                $validator->errors()->add('bookable_id', 'The selected item is not available for booking.');
            }
        });
    }
}
