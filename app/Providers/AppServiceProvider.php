<?php

namespace App\Providers;

use App\Models\Project;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lista de projetos para a sidebar, em toda tela logada.
        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                return;
            }

            $view->with('navProjects', Project::query()
                ->where('status', '!=', 'concluido')
                ->orderBy('rank')
                ->get(['id', 'name', 'slug', 'rank', 'status', 'due_date']));
            $view->with('navDoneCount', Project::where('status', 'concluido')->count());
        });
    }
}
