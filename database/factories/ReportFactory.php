<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $from = $this->faker->dateTimeBetween('-30 days', '-7 days');
        $to = $this->faker->dateTimeBetween('-6 days', 'now');

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'date_from' => $from,
            'date_to' => $to,
            'project_ids' => null,
            'file_path' => null,
            'share_token' => null,
            'created_at' => now(),
        ];
    }
}
