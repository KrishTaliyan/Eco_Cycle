<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recycling_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recycling_activity_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->index();
            $table->string('certificate_number')->unique();
            $table->string('holder_name')->nullable();
            $table->string('verification_token')->unique();
            $table->text('qr_payload');
            $table->json('impact_summary');
            $table->timestamp('issued_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycling_certificates');
    }
};
