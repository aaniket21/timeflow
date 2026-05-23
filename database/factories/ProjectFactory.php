<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'name' => $this->faker->words(3, true),
            'color' => '#'.ltrim($this->faker->hexColor(), '#'),
            'client_name' => $this->faker->boolean(30) ? $this->faker->name() : null,
            'budget_hours' => $this->faker->boolean(40) ? $this->faker->randomFloat(2, 1, 40) : null,
            'is_archived' => false,
            'created_at' => now(),
        ];
    }
}
