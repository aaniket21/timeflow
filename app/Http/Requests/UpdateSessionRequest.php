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
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'started_at' => ['sometimes', 'date'],
            'ended_at' => ['sometimes', 'date'],
            'type' => ['sometimes', 'in:manual,timer,pomodoro'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:280'],
        ];
    }
}
