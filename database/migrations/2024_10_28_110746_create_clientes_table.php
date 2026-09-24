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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('contacto')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nit_ci')->nullable();
            $table->string('direccion')->nullable();
            // $table->string('ubicacion')->nullable();
            $table->string('celular')->nullable();
            $table->string('telefono')->nullable();
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            
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
        Schema::dropIfExists('clientes');
    }
};
