<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReminderPatients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('reminder_patients',function(Blueprint $table){
            $table->integer('id',true);
            $table->integer('patient_id');
            $table->integer('service_id');
            $table->string('type',100);
            $table->integer('cycle_no')->default(1);
            $table->enum('status',array('pending','completed'));
            $table->timestamps();
            $table->softDeletes();

            //New column added on 22-sept-25
            $table->tinyInteger('is_processed')->default(0); 

         });   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::drop('reminder_patients');
    }
}
