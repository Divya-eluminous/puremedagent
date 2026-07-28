<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->create('country_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('country_name_de', 100);
            $table->string('country_name_en', 100);
            $table->string('iso_code', 5);
            $table->string('phone_code', 10);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        // seed initial list
        $rows = [
            ['country_name_de' => 'Österreich', 'country_name_en' => 'Austria',      'iso_code' => 'AT', 'phone_code' => '+43', 'is_active' => 1],
            ['country_name_de' => 'Deutschland', 'country_name_en' => 'Germany',     'iso_code' => 'DE', 'phone_code' => '+49', 'is_active' => 1],
            ['country_name_de' => 'Italien',     'country_name_en' => 'Italy',       'iso_code' => 'IT', 'phone_code' => '+39', 'is_active' => 1],
            ['country_name_de' => 'Frankreich',  'country_name_en' => 'France',      'iso_code' => 'FR', 'phone_code' => '+33', 'is_active' => 1],
            ['country_name_de' => 'Niederlande','country_name_en' => 'Netherlands', 'iso_code' => 'NL', 'phone_code' => '+31', 'is_active' => 1],
            ['country_name_de' => 'Polen',      'country_name_en' => 'Poland',      'iso_code' => 'PL', 'phone_code' => '+48', 'is_active' => 1],
            ['country_name_de' => 'Schweiz',    'country_name_en' => 'Switzerland', 'iso_code' => 'CH', 'phone_code' => '+41', 'is_active' => 1],
        ];

        foreach ($rows as $row) {
            DB::connection('tenant')->table('country_codes')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->dropIfExists('country_codes');
    }
}
