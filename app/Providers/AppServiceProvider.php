<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\View::composer(['layouts.app', 'auth.login'], function ($view) {
            $view->with('logoAgenciaUrl', \App\Models\LandingSetting::current()->logoUrl());
        });
        foreach (\App\Models\Role::permissionKeys() as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, fn (\App\Models\User $user) => $user->hasPermission($permission));
        }
    }
}
