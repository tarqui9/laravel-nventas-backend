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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->datetime('fecha');
            $table->datetime('fecha_entrega')->nullable();
            $table->string('estado')->nullable();
            $table->string('pagos')->nullable();
            // $table->string('nro_comprobante')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nit_ci')->nullable();
            $table->double('subtotal')->nullable();
            $table->double('descuento')->nullable();
            $table->double('deuda')->nullable();
            $table->double('total')->nullable();
            $table->text('observacion')->nullable();
            $table->string('tipo')->nullable();

            $table->bigInteger('sucursal_id')->unsigned();
            $table->foreign('sucursal_id')->references('id')
            ->on('sucursals')->onDelete('cascade');
            $table->bigInteger('cliente_id')->unsigned();
            $table->foreign('cliente_id')->references('id')
            ->on('clientes')->onDelete('cascade');
            $table->bigInteger('tipo_pago_id')->unsigned();
            $table->foreign('tipo_pago_id')->references('id')
            ->on('tipo_pagos')->onDelete('cascade');
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
        Schema::dropIfExists('pedidos');
    }
};
