<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEna extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        Schema::create('ena', function (Blueprint $table) {
            $table->increments('id');
            $table->string('fonte');
            $table->string('frequencia');
            $table->string('subsistema');
            $table->integer('ano')->nullable();
            $table->string('mes')->nullable();
            $table->integer('dia')->nullable();
            $table->date('inicio')->nullable();
            $table->date('fim')->nullable();
            $table->string('percent_mlt')->nullable();
            $table->string('percent_mlt_armazenavel')->nullable();
            $table->string('mwmed')->nullable();
            $table->string('mwmed_armazenavel')->nullable();
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
        Schema::dropIfExists('ena');
    }
}
