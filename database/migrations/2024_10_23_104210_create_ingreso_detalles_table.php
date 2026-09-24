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
        Schema::create('ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->string('lote')->nullable();
            $table->double('cantidad')->nullable();
            $table->double('cantidad_stock')->nullable();
            $table->double('costo_unitario')->nullable();
            $table->double('costo_total')->nullable();
            $table->string('fecha_vencimiento')->nullable();
            // $table->double('factor')->nullable();
            
            $table->bigInteger('ingreso_id')->unsigned();
            $table->foreign('ingreso_id')->references('id')
            ->on('ingresos')->onDelete('cascade');
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
        Schema::dropIfExists('ingreso_detalles');
    }
};
