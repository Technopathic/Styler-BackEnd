<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Topics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('topics', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->string('topicTags', 300)->nullable();
          $table->integer('topicReplies')->default(0);
          $table->integer('topicVotes')->default(0);
          $table->boolean('topicFlag')->default(0);
          $table->boolean('topicFeature')->default(0);
          $table->boolean('allowReplies')->default(1);
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
      Schema::drop('topics');
    }
}
