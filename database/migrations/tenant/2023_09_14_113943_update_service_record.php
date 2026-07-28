<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateServiceRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('update_service_record',function(Blueprint $table){
            $table->integer('id',true);
            $table->integer('user_id');
            $table->integer('service_id');
            $table->integer('start_patient_id');
            $table->integer('end_patient_id');
            $table->integer('max_patient_id');
            $table->tinyInteger('is_reminder_updated');
            $table->string('inserted_through',60)->nullable();
            $table->string('updated_by',100)->nullable();
            $table->enum('activated_reminder',array('general','age','checkup'))->nullable();
            $table->integer('recommanded_service_id')->default(0);
            $table->integer('general_period');
            $table->string('general_period_frequency_type',125);
            $table->integer('general_new_frequency');
            $table->string('general_new_frequency_type',125);
            $table->integer('general_first_frequency');
            $table->string('general_first_frequency_type',125);
            $table->integer('general_time_interval');
            $table->string('general_time_interval_frequency_type',125);
            $table->integer('general_number_of_interval');
            $table->integer('general_end_cycle')->nullable();
            $table->string('general_end_cycle_frequency_type')->nullable();
            $table->integer('age_from')->nullable();
            $table->integer('age_to')->nullable();
            $table->integer('age_period_controls');
            $table->string('age_period_frequency_type',125);
            $table->integer('age_new_frequency');
            $table->string('age_new_frequency_type',125);
            $table->integer('age_first_frequency');
            $table->string('age_first_frequency_type',125);
            $table->integer('age_time_interval');
            $table->string('age_time_interval_frequency_type',125);
            $table->integer('age_number_of_interval');
            $table->integer('age_end_cycle')->default(0);
            $table->string('age_end_cycle_frequency_type')->nullable();
            $table->integer('checkup_time_interval');
            $table->string('checkup_time_interval_frequency_type',125)->nullable();
            $table->integer('checkup_first_frequency');
            $table->string('checkup_first_frequency_type',125)->nullable();
            $table->integer('checkup_new_frequency');
            $table->string('checkup_new_frequency_type',125)->nullable();
            $table->integer('checkup_period_controls');
            $table->string('checkup_period_frequency_type',125)->nullable();
            $table->integer('checkup_number_of_interval');
            $table->integer('checkup_end_cycle')->default(0);
            $table->string('checkup_end_cycle_frequency_type')->nullable(); 
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
        Schema::drop('update_service_record');
    }
}
