<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Profiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('profiles', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('userID');
          $table->string('profileName', 32);
          $table->string('profileTitle', 32)->nullable();
          $table->string('profilePhone', 11)->nullable();
          $table->string('profileSocial', 100)->nullable();
          $table->string('profileLocation', 64)->nullable();
          $table->string('profileDesc', 500)->nullable();
          $table->string('profileLanguage', 250)->nullable();
          $table->string('profileWebsite', 100)->nullable();
          $table->integer('profileTopics')->default(0);
          $table->integer('profileVotes')->default(0);
          $table->integer('profileReplies')->default(0);
          $table->integer('profileScore')->default(0);
          $table->boolean('profileFlag')->default(0);
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
      Schema::drop('profiles');
    }
}
