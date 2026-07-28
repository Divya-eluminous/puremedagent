<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id(); // equivalent to int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
            $table->string('ordination_id')->nullable();
            $table->string('tenant_id')->unique(); // NOT NULL + should be unique
            $table->string('ordination_name')->nullable();
            $table->string('calendar_id')->nullable();

            // JSON column with MySQL native JSON type (handles json_valid automatically)
            $table->json('data')->nullable();

            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at
            $table->string('uuid')->nullable();
            $table->string('tenancy_db_name')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}; 