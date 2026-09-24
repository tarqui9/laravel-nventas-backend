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
        Schema::create('agrupado_salidas', function (Blueprint $table) {
            $table->id();
            $table->double('cantidad')->unsigned();
            $table->double('disponible')->unsigned();
            $table->integer('devuelto')->unsigned()->nullable();
            
            $table->bigInteger('grupo_id')->unsigned();
            $table->foreign('grupo_id')->references('id_agrupado')
            ->on('agrupados')->onDelete('cascade');
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
        Schema::dropIfExists('agrupado_salidas');
    }
};
