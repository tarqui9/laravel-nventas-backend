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
        Schema::create('cliente_tipopagos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cliente_id')->unsigned();
            $table->foreign('cliente_id')->references('id')
            ->on('clientes')->onDelete('cascade');
            $table->bigInteger('tipo_pago_id')->unsigned();
            $table->foreign('tipo_pago_id')->references('id')
            ->on('tipo_pagos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_tipopagos');
    }
};
