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
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();
            $table->double('cantidad')->nullable();
            $table->double('entregado')->nullable();
            $table->double('precio')->nullable();
            $table->double('total')->nullable();
            $table->double('est_utilidad')->nullable();
            // $table->double('factor')->nullable();
            
            $table->bigInteger('pedido_id')->unsigned();
            $table->foreign('pedido_id')->references('id')
            ->on('pedidos')->onDelete('cascade');
            $table->bigInteger('suministro_id')->unsigned();
            $table->foreign('suministro_id')->references('id')
            ->on('suministros')->onDelete('cascade');
            // $table->bigInteger('unidad_id')->unsigned();
            // $table->foreign('unidad_id')->references('id')
            // ->on('unidads')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
