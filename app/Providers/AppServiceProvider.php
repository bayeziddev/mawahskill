<?php

namespace App\Providers;

use App\Models\LeaveNotification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Employee — সব view এ notification count
        View::composer('employee.*', function ($view) {
            if (auth('employee')->check()) {
                $unreadCount = LeaveNotification::where('recipient_type', 'employee')
                    ->where('recipient_id', auth('employee')->id())
                    ->whereNull('read_at')
                    ->count();

                $view->with('unreadCount', $unreadCount);
            }
        });

        View::composer('team_lead.*', function ($view) {
            if (auth('tl')->check()) {
                $tlId = auth('tl')->user()->employee->id ?? null;

                $tlUnreadCount = LeaveNotification::where('recipient_type', 'tl')
                    ->where('recipient_id', $tlId)
                    ->whereNull('read_at')
                    ->count();

                $view->with('tlUnreadCount', $tlUnreadCount);
            }
        });

        Paginator::useBootstrapFive();

        Relation::morphMap([
    'employee' => \App\Models\Employee::class,
    'tl'       => \App\Models\Tl::class,
    'hr'       => \App\Models\HrAdmin::class,
]);
    }

    
    

    
}
