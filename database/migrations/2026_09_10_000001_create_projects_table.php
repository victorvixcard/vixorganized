<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // aguardando | em_andamento | pausado | concluido
            $table->string('status', 20)->default('aguardando')->index();
            // rank ordenável: 1 = maior prioridade. Só existe um projeto por posição.
            $table->unsignedInteger('rank')->default(0)->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('current_phase_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'position']);
        });

        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['phase_id', 'position']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('description', 500);
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('steps');
        Schema::dropIfExists('phases');
        Schema::dropIfExists('projects');
    }
};
