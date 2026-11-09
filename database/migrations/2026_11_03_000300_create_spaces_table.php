<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Espaços reserváveis: pertencem a uma unidade e a uma categoria. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            // Unidade/categoria com espaços não podem ser apagadas (o painel avisa antes)
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('space_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('size_m2')->nullable();
            $table->string('floor', 20)->nullable();
            $table->decimal('price', 10, 2); // valor por hora, dia ou mês (conforme a categoria)
            $table->json('amenities')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['unit_id', 'space_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
