<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Replies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('replies', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->integer('topicID');
          $table->string('replyBody', 250);
          $table->integer('replyVotes')->default(0);
          $table->boolean('replyFlag')->default(0);
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
      Schema::drop('replies');
    }
}
