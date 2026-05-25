<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\DailyChallenge;
use App\Models\Badge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChallenges();
        $this->seedBadges();
        $this->seedDemoUser();
    }

    private function seedChallenges(): void
    {
        $difficulties = ['easy', 'medium', 'hard'];
        for ($i = 1; $i <= 50; $i++) {
            $diff = $difficulties[$i % 3];
            $value = match($diff) {
                'easy' => rand(1, 2),
                'medium' => rand(3, 5),
                'hard' => rand(6, 8),
            };
            DailyChallenge::firstOrCreate(['slug' => "challenge-$i"], [
                'title' => "Challenge $i",
                'description' => "Log $value hours of focus time.",
                'difficulty' => $diff,
                'condition_type' => 'hours_logged',
                'condition_value' => $value,
                'xp_reward' => match($diff) { 'easy' => 25, 'medium' => 50, 'hard' => 100 },
                'icon' => 'StarIcon',
            ]);
        }
    }

    private function seedBadges(): void
    {
        $badges = [
            ['slug' => 'tomato_head', 'name' => 'Tomato Head', 'description' => 'Complete your first pomodoro.', 'icon' => 'ClockIcon'],
            ['slug' => 'streak_7', 'name' => '7 Day Streak', 'description' => 'Maintain a 7 day streak.', 'icon' => 'FireIcon'],
            ['slug' => 'streak_30', 'name' => '30 Day Streak', 'description' => 'Maintain a 30 day streak.', 'icon' => 'FireIcon'],
            ['slug' => 'night_owl', 'name' => 'Night Owl', 'description' => 'Log 5 hours after 10 PM.', 'icon' => 'MoonIcon'],
            ['slug' => 'early_bird', 'name' => 'Early Bird', 'description' => 'Log 5 hours before 8 AM.', 'icon' => 'SunIcon'],
        ];

        for ($i = 6; $i <= 25; $i++) {
            $badges[] = [
                'slug' => "badge_$i",
                'name' => "Secret Badge $i",
                'description' => "Keep grinding to unlock.",
                'icon' => 'SparklesIcon',
            ];
        }

        foreach ($badges as $b) {
            Badge::firstOrCreate(['slug' => $b['slug']], $b);
        }
    }

    private function seedDemoUser(): void
    {
        $user = User::firstOrCreate(['email' => 'demo@timeflow.app'], [
            'name' => 'Demo User',
            'password' => Hash::make('password'),
            'timezone' => 'America/New_York',
            'xp_total' => 1250,
            'level' => 4,
            'streak_current' => 12,
            'streak_longest' => 15,
            'is_admin' => true,
        ]);

        $categories = [
            ['name' => 'Development', 'color' => '#3B82F6'],
            ['name' => 'Design', 'color' => '#EC4899'],
            ['name' => 'Marketing', 'color' => '#F59E0B'],
            ['name' => 'Reading', 'color' => '#10B981'],
            ['name' => 'Health', 'color' => '#EF4444'],
            ['name' => 'Admin', 'color' => '#6B7280'],
        ];

        $catModels = [];
        foreach ($categories as $c) {
            $catModels[] = Category::firstOrCreate(['user_id' => $user->id, 'name' => $c['name']], $c);
        }

        $projects = [
            ['name' => 'Timeflow V2', 'category_id' => $catModels[0]->id, 'color' => '#3B82F6'],
            ['name' => 'UI Mockups', 'category_id' => $catModels[1]->id, 'color' => '#EC4899'],
            ['name' => 'SEO Audit', 'category_id' => $catModels[2]->id, 'color' => '#F59E0B'],
            ['name' => 'Atomic Habits', 'category_id' => $catModels[3]->id, 'color' => '#10B981'],
        ];

        $projModels = [];
        foreach ($projects as $p) {
            $projModels[] = Project::firstOrCreate(['user_id' => $user->id, 'name' => $p['name']], $p);
        }

        // Seed 30 days of realistic data
        $now = Carbon::now();
        for ($i = 30; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            
            // Randomly skip some days to break streaks (but keep recent 12 days intact)
            if ($i > 12 && rand(1, 10) > 8) {
                continue;
            }

            // 1 to 4 sessions a day
            $sessionsCount = rand(1, 4);
            for ($s = 0; $s < $sessionsCount; $s++) {
                $proj = $projModels[array_rand($projModels)];
                $start = $date->copy()->addHours(rand(9, 20))->addMinutes(rand(0, 50));
                $duration = rand(15, 120) * 60;
                
                TimeSession::create([
                    'user_id' => $user->id,
                    'project_id' => $proj->id,
                    'is_pomodoro' => rand(0, 1) == 1,
                    'started_at' => $start,
                    'ended_at' => $start->copy()->addSeconds($duration),
                    'duration_seconds' => $duration,
                    'notes' => 'Focused work',
                ]);
            }
        }
    }
}
