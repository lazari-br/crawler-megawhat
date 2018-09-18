<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePlds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pld', function (Blueprint $table) {
            $table->increments('id');
            $table->string('fonte');
            $table->string('frequencia');
            $table->integer('ano')->nullable();
            $table->string('mes')->nullable();
            $table->integer('dia')->nullable();
            $table->date('inicio')->nullable();
            $table->date('fim')->nullable();
            $table->string('norte')->nullable();
            $table->string('nordeste')->nullable();
            $table->string('sul')->nullable();
            $table->string('sudeste_centro-oeste')->nullable();
            $table->string('leve')->nullable();
            $table->string('medio')->nullable();
            $table->string('pesado')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pld');
    }
}
