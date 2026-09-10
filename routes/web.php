<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StepController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('projects.index'));

    // Projetos (fila priorizada)
    Route::get('/projetos', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projetos/novo', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projetos', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/projetos/reordenar', [ProjectController::class, 'reorder'])->name('projects.reorder');
    Route::get('/projetos/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projetos/{project}/editar', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projetos/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projetos/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projetos/{project}/status', [ProjectController::class, 'status'])->name('projects.status');
    Route::post('/projetos/{project}/mover', [ProjectController::class, 'move'])->name('projects.move');

    // Fases (colunas do kanban)
    Route::post('/projetos/{project}/fases', [PhaseController::class, 'store'])->name('phases.store');
    Route::post('/projetos/{project}/fases/reordenar', [PhaseController::class, 'reorder'])->name('phases.reorder');
    Route::put('/projetos/{project}/fases/{phase}', [PhaseController::class, 'update'])->name('phases.update');
    Route::delete('/projetos/{project}/fases/{phase}', [PhaseController::class, 'destroy'])->name('phases.destroy');
    Route::post('/projetos/{project}/fases/{phase}/toggle', [PhaseController::class, 'toggle'])->name('phases.toggle');

    // Passos (checklist)
    Route::post('/projetos/{project}/fases/{phase}/passos', [StepController::class, 'store'])->name('steps.store');
    Route::post('/projetos/{project}/fases/{phase}/passos/reordenar', [StepController::class, 'reorder'])->name('steps.reorder');
    Route::get('/projetos/{project}/passos/{step}', [StepController::class, 'show'])->name('steps.show');
    Route::post('/projetos/{project}/passos/{step}/registros', [StepController::class, 'comment'])->name('steps.comment');
    Route::post('/projetos/{project}/passos/{step}/toggle', [StepController::class, 'toggle'])->name('steps.toggle');
    Route::put('/projetos/{project}/passos/{step}', [StepController::class, 'update'])->name('steps.update');
    Route::delete('/projetos/{project}/passos/{step}', [StepController::class, 'destroy'])->name('steps.destroy');
});
