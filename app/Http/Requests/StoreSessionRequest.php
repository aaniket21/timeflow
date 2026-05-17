<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
            'type' => ['nullable', 'in:manual,timer,pomodoro'],
            'notes' => ['nullable', 'string', 'max:280'],
        ];
    }
}
