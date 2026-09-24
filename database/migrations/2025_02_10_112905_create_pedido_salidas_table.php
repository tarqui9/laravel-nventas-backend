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
        Schema::create('pedido_salidas', function (Blueprint $table) {
            $table->id();
            $table->double('cantidad')->unsigned();
            $table->double('precio')->unsigned();
            $table->double('utilidad')->nullable();
            $table->double('descuento')->unsigned();
            
            $table->bigInteger('pedido_id')->unsigned();
            $table->foreign('pedido_id')->references('id')
            ->on('pedidos')->onDelete('cascade');
            $table->bigInteger('ingreso_detalle_id')->unsigned();
            $table->foreign('ingreso_detalle_id')->references('id')
            ->on('ingreso_detalles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_salidas');
    }
};
