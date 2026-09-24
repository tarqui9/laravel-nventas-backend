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
        Schema::create('producto_unidads', function (Blueprint $table) {
            $table->id();
            // $table->string('tag')->nullable();
            $table->double('factor')->nullable();
            $table->double('precio')->nullable();
            $table->boolean('habilitado');

            $table->bigInteger('suministro_id')->unsigned();
            $table->foreign('suministro_id')->references('id')
            ->on('suministros')->onDelete('cascade');
            $table->bigInteger('unidad_id')->unsigned();
            $table->foreign('unidad_id')->references('id')
            ->on('unidads')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_unidads');
    }
};
