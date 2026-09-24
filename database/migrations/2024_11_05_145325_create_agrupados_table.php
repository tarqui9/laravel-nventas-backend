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
        Schema::create('agrupados', function (Blueprint $table) {
            $table->id('id_agrupado');
            $table->datetime('fecha');
            $table->string('numero')->nullable();
            $table->string('estado')->nullable();
            $table->double('total')->nullable();

            $table->bigInteger('sucursal_id')->unsigned();
            $table->foreign('sucursal_id')->references('id')
            ->on('sucursals')->onDelete('cascade');
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
        Schema::dropIfExists('agrupados');
    }
};
