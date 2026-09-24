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
        Schema::create('suministros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('codigo')->nullable();
            $table->double('precio')->nullable();
            $table->string('descripcion')->nullable();
            $table->text('foto')->nullable();
            $table->double('stock')->nullable();
            $table->integer('cantidad_unidades')->unsigned()->nullable();
            $table->string('estado')->nullable();
            $table->string('cantidad_total')->nullable();
            $table->boolean('habilitado');
            
            $table->bigInteger('categoria_id')->unsigned();
            $table->foreign('categoria_id')->references('id')
            ->on('categorias')->onDelete('cascade');
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
        Schema::dropIfExists('suministros');
    }
};
