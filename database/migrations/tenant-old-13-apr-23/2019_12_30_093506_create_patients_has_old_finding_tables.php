<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsHasOldFindingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_has_old_finding', function(Blueprint $table)
        {
            $table->increments('id', true);
            $table->integer('fk_patient_id');
            $table->timestamp('appoinmant_date');
            $table->enum('imported_flag', array('0','1','2'))->default('0'); 
            $table->timestamps();
            $table->softDeletes();
        });
        $patients_has_old_finding = DB::connection('system')->table('patients_has_old_finding')->get();

        foreach ($patients_has_old_finding as $key => $value) {
            $tmp = [];
            $tmp['fk_patient_id'] = $value->fk_patient_id;
            $tmp['appoinmant_date'] = $value->appoinmant_date;
            $tmp['imported_flag'] = $value->imported_flag;
            DB::table("patients_has_old_finding")->insert($tmp);
        }   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('patients_has_old_finding');
    }
}
