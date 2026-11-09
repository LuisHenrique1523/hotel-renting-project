<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Unidades da rede: cada endereço físico (prédio/andar) com os seus espaços. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city', 80)->index();
            $table->char('state', 2);
            $table->string('neighborhood', 80)->nullable();
            $table->string('address');
            $table->string('zip_code', 9)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('description');
            $table->string('image')->nullable();
            $table->json('amenities')->nullable();
            $table->unsignedTinyInteger('opens_hour')->default(8);
            $table->unsignedTinyInteger('closes_hour')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
