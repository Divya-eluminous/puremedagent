<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDismissalTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dismissal', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->text('name', 65535);
			$table->enum('status', array('1','2'));
			$table->timestamps();
			$table->softDeletes();
		});

       //start Added below code on 10-sept-24
		$dismissal = \App\Helpers\MigrationHelper::getFromSystem('dismissal');

		foreach ($dismissal as $key => $value) {
            $tmp = [];
            $tmp['name'] = $value->name;
            $tmp['status'] = $value->status;
            DB::table("dismissal")->insert($tmp);
        }
        //end Added below code on 10-sept-24
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('dismissal');
	}

}
