<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recycling_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('city')->index();
            $table->string('state')->nullable();
            $table->string('pincode', 12)->nullable();
            $table->text('address');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('accepted_categories')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('opening_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recycling_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->index();
            $table->string('brand')->nullable();
            $table->string('model');
            $table->string('condition')->default('unknown');
            $table->decimal('estimated_weight_kg', 8, 2)->default(0);
            $table->unsignedInteger('points_preview')->default(0);
            $table->string('status')->default('submitted')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('recycling_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shop_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recycling_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_number')->unique();
            $table->string('pickup_address')->nullable();
            $table->string('preferred_slot')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('reward_points')->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reward_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recycling_request_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('type')->default('earned')->index();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info')->index();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reward_points');
        Schema::dropIfExists('recycling_requests');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('recycling_centers');
    }
};
