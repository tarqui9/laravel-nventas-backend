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
        Schema::create('agrupado_detalles', function (Blueprint $table) {
            $table->id();
            $table->double('cantidad')->nullable();
            $table->double('precio')->nullable();
            $table->double('total')->nullable();
            // $table->double('factor')->nullable();
            
            $table->bigInteger('grupo_id')->unsigned();
            $table->foreign('grupo_id')->references('id_agrupado')
            ->on('agrupados')->onDelete('cascade');
            $table->bigInteger('suministro_id')->unsigned();
            $table->foreign('suministro_id')->references('id')
            ->on('suministros')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agrupado_detalles');
    }
};
