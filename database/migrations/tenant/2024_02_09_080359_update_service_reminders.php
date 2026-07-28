<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateServiceReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('update_service_reminders',function(Blueprint $table){
            $table->integer('id',true);
            $table->integer('patient_id');
            $table->integer('service_id');
            $table->string('type',100);
            $table->string('deleted_through',60)->nullable();
            $table->integer('update_service_id')->nullable();
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
         Schema::drop('update_service_reminders');
    }
}
