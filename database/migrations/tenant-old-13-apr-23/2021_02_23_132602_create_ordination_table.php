<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Session;

class CreateOrdinationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ordination', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('name');
			$table->string('text_color_code');
			$table->string('background_color')->nullable();
			$table->string('logo')->nullable();
			$table->string('logo_path')->nullable();
			$table->smallInteger('status');
			$table->string('email')->nullable();
			$table->string('address')->nullable();
			$table->string('postal_code')->nullable();
			$table->string('mobile_no')->nullable();
			$table->string('button_colors')->nullable();
			$table->string('screen_bg_color')->nullable();
			$table->string('app_bar_color')->nullable();
			$table->string('tabs_selection_color')->nullable();
			$table->string('home_screen_options_color')->nullable();
			$table->string('menu_header_colors')->nullable();
			$table->string('menu_bg_color')->nullable();
			$table->string('dark_text_color')->nullable();
			$table->string('light_text_color')->nullable();
			$table->string('latitude')->nullable();
			$table->string('longitude')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});


		$ordination = DB::connection('system')
		->table('ordination')
		->where('id',Session::get('insert_ordination_id'))
		->first();

		if(!empty($ordination ))
		{
        	$tmp = [];          
	        $tmp['name'] = $ordination->name;
	        $tmp['text_color_code'] = $ordination->text_color_code ?? '';
	        $tmp['background_color'] = $ordination->background_color ?? '' ;
	        $tmp['logo'] = $ordination->logo ?? '';
	        $tmp['logo_path'] = $ordination->logo_path ?? '';  
	        $tmp['email'] = $ordination->email ?? '';
	        $tmp['address'] = $ordination->address ?? '';
	        $tmp['postal_code'] = $ordination->postal_code ?? '';
	        $tmp['mobile_no'] = $ordination->mobile_no ?? '';
	        $tmp['status'] = $ordination->logo_path ?? '';
	        $tmp['button_colors'] = $ordination->button_colors ?? '';
	        $tmp['screen_bg_color'] = $ordination->screen_bg_color ?? '';
	        $tmp['app_bar_color'] = $ordination->app_bar_color ?? '';
	        $tmp['tabs_selection_color'] = $ordination->tabs_selection_color ?? '';
	        $tmp['home_screen_options_color'] = $ordination->home_screen_options_color ?? '';
	        $tmp['menu_header_colors'] = $ordination->menu_header_colors ?? '';
	        $tmp['menu_bg_color'] = $ordination->menu_bg_color ?? '';
	        $tmp['dark_text_color'] = $ordination->dark_text_color ?? '';
	        $tmp['light_text_color'] = $ordination->light_text_color ?? '';                 
	        DB::table("ordination")->insert($tmp);
   		}


	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ordination');
	}

}
