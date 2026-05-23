<?php

namespace Database\Factories;

use App\Models\ReportToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReportTokenFactory extends Factory
{
    protected $model = ReportToken::class;

    public function definition(): array
    {
        $from = $this->faker->dateTimeBetween('-30 days', '-7 days');
        $to = $this->faker->dateTimeBetween('-6 days', 'now');

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'date_from' => $from,
            'date_to' => $to,
            'status' => 'ready',
            'token' => Str::random(64),
            'file_path' => null,
            'expires_at' => now()->addDays(7),
        ];
    }
}
