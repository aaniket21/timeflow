<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'color' => '#'.ltrim($this->faker->hexColor(), '#'),
            'icon' => $this->faker->boolean(50) ? $this->faker->word() : null,
            'parent_id' => null,
            'archived' => false,
            'created_at' => now(),
        ];
    }
}
