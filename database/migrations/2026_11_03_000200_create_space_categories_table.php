<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorias de espaço cadastradas pelo admin (sala de reunião, coworking, escritório...).
 * A categoria define COMO o espaço é cobrado: por hora, por dia ou por mês.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('billing_unit', 10); // hour | day | month
            $table->unsignedSmallInteger('min_quantity')->default(1);
            $table->unsignedSmallInteger('max_quantity')->default(12);
            // Compartilhado (ex.: coworking): várias reservas ao mesmo tempo até lotar a capacidade, preço por pessoa
            $table->boolean('is_shared')->default(false);
            $table->string('icon', 60)->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_categories');
    }
};
