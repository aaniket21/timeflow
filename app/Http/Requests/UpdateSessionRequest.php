<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'started_at' => ['sometimes', 'required', 'date'],
            'ended_at' => ['nullable', 'date', 'after:started_at'],
            'type' => ['sometimes', 'in:manual,timer,pomodoro'],
            'notes' => ['nullable', 'string', 'max:280'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
