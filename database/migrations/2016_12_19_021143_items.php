<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Items extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('items', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->integer('topicID');
          $table->string('itemType');
          $table->string('itemBrand', 64);
          $table->string('itemName', 64);
          $table->string('itemLink', 128)->nullable();
          $table->string('itemSize', 64);
          $table->string('itemCoords', 64);
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
      Schema::drop('items');
    }
}
