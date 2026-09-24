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
        Schema::create('movilidad_users', function (Blueprint $table) {
            $table->id();
            $table->datetime('fecha');
            $table->boolean('activo');
            $table->bigInteger('movilidad_id')->unsigned();
            $table->foreign('movilidad_id')->references('id')
            ->on('movilidads')->onDelete('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')
            ->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movilidad_users');
    }
};
