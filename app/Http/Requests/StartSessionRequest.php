<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
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
            'started_at' => ['nullable', 'date'],
            'type' => ['nullable', 'in:manual,timer,pomodoro'],
            'notes' => ['nullable', 'string', 'max:280'],
        ];
    }
}
