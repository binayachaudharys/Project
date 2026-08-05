<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'salon_open' => ['required', 'date_format:H:i'],
            'salon_close' => ['required', 'date_format:H:i', 'after:salon_open'],
            'slot_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'package_duration' => ['required', 'integer', 'min:5', 'max:480'],
            'auto_confirm' => ['required', 'boolean'],
            'max_concurrent' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('auto_confirm')) {
            $this->merge([
                'auto_confirm' => filter_var($this->input('auto_confirm'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }
}
