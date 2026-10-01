<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->boolean('notify_mail')->default(true);
            $table->boolean('notify_telegram')->default(false);
            $table->unsignedTinyInteger('day_start_hour')->default(7);
            $table->string('timezone')->default('UTC');
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_link_token')->nullable()->unique();
        });

        Schema::create('todo_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->json('days')->nullable();
            $table->boolean('random_day')->default(false);
            $table->unsignedInteger('every_value')->nullable();
            $table->string('every_unit')->nullable();
            $table->tinyInteger('day_of_month')->nullable(); // -1 = last day of the month
            $table->json('months')->nullable();
            $table->date('start_after')->nullable();
            $table->decimal('chance', 5, 4)->default(1);
            $table->boolean('allow_duplicates')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('reward_cost')->nullable();
            $table->timestamps();
        });

        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->date('date');
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('hub_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('rules');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('imports_count')->default(0);
            $table->timestamps();
        });
    }
};
