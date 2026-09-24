<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movilidads', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->nullable();
            $table->text('descripcion')->nullable();
            // $table->string('marca')->nullable();
            // $table->string('modelo')->nullable();
            $table->string('color')->nullable();
            $table->string('estado')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('habilitado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movilidads');
    }
};
