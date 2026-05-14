<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recycling_activities', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('device_model');
            $table->string('device_category');
            $table->string('condition')->nullable();
            $table->string('recommended_action')->default('recycle');
            $table->unsignedSmallInteger('eco_score');
            $table->unsignedInteger('points_awarded');
            $table->decimal('ewaste_kg', 8, 2);
            $table->decimal('co2_kg', 8, 2);
            $table->decimal('pollution_prevented_kg', 8, 2);
            $table->json('materials_recovered')->nullable();
            $table->json('hazards')->nullable();
            $table->json('facility')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycling_activities');
    }
};
