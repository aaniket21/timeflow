<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Report;
use App\Models\TimeSession;
use App\Policies\ProjectPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        TimeSession::class => SessionPolicy::class,
        Report::class => ReportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
