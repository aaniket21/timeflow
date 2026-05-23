<?php

namespace Tests\Feature\Projects;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectCategoryTest extends TestCase
{
    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Client App',
            'color' => '#1F2937',
            'client_name' => 'Acme',
            'budget_hours' => 12.5,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/projects', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.project.name', 'Client App')
            ->assertJsonPath('data.project.category_id', $category->id);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Client App',
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_list_projects(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Project::factory()->count(2)->for($user)->create();
        Project::factory()->for($other)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_update_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'name' => 'Legacy',
            'budget_hours' => 5,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Revamp',
            'budget_hours' => 8,
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.project.name', 'Revamp')
            ->assertJsonPath('data.project.budget_hours', '8.00');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Revamp',
            'budget_hours' => 8.0,
        ]);
    }

    public function test_user_can_archive_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'is_archived' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.project.is_archived', true);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => true,
        ]);
    }

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Deep Work',
            'color' => '#7C5CFC',
        ];

        $response = $this->postJson('/api/categories', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.category.name', 'Deep Work');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Deep Work',
        ]);
    }

    public function test_user_can_list_categories(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->count(2)->for($user)->create();
        Category::factory()->for($other)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'name' => 'Reading',
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Reading Focus',
            'color' => '#111827',
        ];

        $response = $this->putJson("/api/categories/{$category->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.category.name', 'Reading Focus')
            ->assertJsonPath('data.category.color', '#111827');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Reading Focus',
        ]);
    }

    public function test_user_can_archive_category(): void
    {
        $this->markTestSkipped('V2 categories do not have an archived column');
    }
}
