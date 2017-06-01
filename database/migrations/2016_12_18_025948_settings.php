<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Settings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('settings', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->boolean('theme')->default(0);
          $table->boolean('autoImg')->default(1);
          $table->boolean('notiVote')->default(1);
          $table->boolean('notiReply')->default(1);
          $table->boolean('notiBounce')->default(1);
          $table->boolean('notiMention')->default(1);
          $table->boolean('notiWeekly')->default(1);
          $table->boolean('profPrivate')->default(0);
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
      Schema::drop('settings');
    }
}
