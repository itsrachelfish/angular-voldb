<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table)
         {
             $table->increments('id');
             $table->string('type');
             $table->string('status');
             $table->text('layout');
             $table->json('metadata');
             $table->integer('user_from')->unsigned()->nullable();
             $table->foreign('user_from')->references('id')->on('users')->onDelete('set null');
             $table->integer('user_to')->unsigned();
             $table->foreign('user_to')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('notifications');
    }
}
