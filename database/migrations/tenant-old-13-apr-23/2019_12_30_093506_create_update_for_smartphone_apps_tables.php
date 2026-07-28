<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdateForSmartphoneAppsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('update_for_smartphone_apps', function(Blueprint $table)
        {
            $table->increments('id', true);
            $table->string('iphone')->nullable();;
            $table->string('master_data_tablet')->nullable();;
            $table->string('waiting_no_tablet')->nullable();;
            $table->string('singdoc_tablet')->nullable();;
            $table->string('andoid')->nullable();;
            $table->text('text')->nullable();;
            $table->timestamps();
            $table->softDeletes();
        });
        $permissions = DB::connection('system')->table('update_for_smartphone_apps')->get();

        foreach ($permissions as $key => $value) {
            $tmp = [];
            $tmp['iphone'] = $value->iphone;
            $tmp['master_data_tablet'] = $value->master_data_tablet;
            $tmp['waiting_no_tablet'] = $value->waiting_no_tablet;
            $tmp['singdoc_tablet'] = $value->singdoc_tablet;
            $tmp['andoid'] = $value->andoid;
            $tmp['text'] = $value->text;
            DB::table("update_for_smartphone_apps")->insert($tmp);
        }   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('update_for_smartphone_apps');
    }
}
