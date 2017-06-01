<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Collections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('collections', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->string('collectionName', 64);
          $table->string('collectionSlug', 64);
          $table->integer('collectionCount')->default(0);
          $table->boolean('collectionPrivate')->default(0);
          $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::drop('collections');
    }
}
