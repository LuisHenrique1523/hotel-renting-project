<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20);
            $table->text('description');
            $table->unsignedSmallInteger('capacity');
            $table->decimal('hourly_price', 10, 2);
            $table->json('amenities')->nullable();
            $table->string('image')->nullable();
            $table->unsignedTinyInteger('opens_hour')->default(8);
            $table->unsignedTinyInteger('closes_hour')->default(22);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
