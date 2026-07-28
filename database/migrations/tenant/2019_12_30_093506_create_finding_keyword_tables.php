<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFindingKeyWordTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finding_keyword', function(Blueprint $table)
        {
            $table->increments('id', true);
            $table->string('keyword');
            $table->enum('status', array('W','B'))->default('W'); 
            $table->timestamps();
            $table->softDeletes();
        });
        $permissions = \App\Helpers\MigrationHelper::getFromSystem('finding_keyword');

        foreach ($permissions as $key => $value) {
            $tmp = [];
            $tmp['keyword'] = $value->keyword;
            $tmp['status'] = $value->status;
            DB::connection('tenant')->table("finding_keyword")->insert($tmp);
        }   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('finding_keyword');
    }
}
