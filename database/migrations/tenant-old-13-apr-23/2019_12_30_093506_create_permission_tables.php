<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('module_slug');
            $table->string('name'); 
            $table->string('title');
            $table->string('guard_name');
            $table->timestamps();
        });
        $permissions = DB::connection('system')->table('permissions')->get();

        foreach ($permissions as $key => $value) {
            $tmp = [];
            $tmp['module_slug'] = $value->module_slug;
            $tmp['name'] = $value->name;
            $tmp['title'] = $value->title;
            $tmp['guard_name'] = $value->guard_name;           
            DB::table("permissions")->insert($tmp);
        }   

        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->string('guard_name');
            $table->timestamps();
        });
        $roles = DB::connection('system')->table('roles')->get();

        foreach ($roles as $key => $value) {
            $tmp = [];
            $tmp['name'] = $value->name;
            $tmp['identifier'] = $value->identifier;
            $tmp['guard_name'] = $value->guard_name;           
            DB::table("roles")->insert($tmp);
        }  

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type', ], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type', ], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
        });
        $model_has_roles = DB::connection('system')->table('model_has_roles')
        ->where(['role_id'=>1,'model_id'=>1])->get();

        foreach ($model_has_roles as $key => $value) {
            $tmp = [];
            $tmp['role_id'] = $value->role_id;
            $tmp['model_type'] = $value->model_type;
            $tmp['model_id'] = $value->model_id;           
            DB::table("model_has_roles")->insert($tmp);
        }  

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedInteger('permission_id');
            $table->unsignedInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });
        $role_has_permissions = DB::connection('system')->table('role_has_permissions')->where('role_id',5)->get();

        foreach ($role_has_permissions as $key => $value) {
            $tmp = [];
            $tmp['permission_id'] = $value->permission_id;
            $tmp['role_id'] = 1;          
            DB::table("role_has_permissions")->insert($tmp);
        }  


        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
}
