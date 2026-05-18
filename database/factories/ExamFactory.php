<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => $this->faker->words(2, true),
            'exam_date' => $this->faker->dateTimeBetween('+2 days', '+30 days')->format('Y-m-d'),
            'notes' => $this->faker->boolean(50) ? $this->faker->sentence() : null,
            'created_at' => now(),
        ];
    }
}
