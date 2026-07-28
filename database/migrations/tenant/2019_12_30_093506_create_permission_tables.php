<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreatePermissionTables extends Migration
{
    public function up()
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        // =========================
        // 1. PERMISSIONS TABLE
        // =========================
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('module_slug');
            $table->string('name');
            $table->string('title');
            $table->string('guard_name');
            $table->timestamps();
        });

        // Import permissions from system DB
        $permissions = \App\Helpers\MigrationHelper::getFromSystem('permissions');
        $permissionIdMap = [];

        foreach ($permissions as $value) {
            $newPermissionId = DB::connection('tenant')->table($tableNames['permissions'])->insertGetId([
                'module_slug' => $value->module_slug,
                'name' => $value->name,
                'title' => $value->title,
                'guard_name' => $value->guard_name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permissionIdMap[$value->id] = $newPermissionId; // map system ID -> tenant ID
        }

        // =========================
        // 2. ROLES TABLE
        // =========================
        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->string('guard_name');
            $table->timestamps();
        });

        // Import roles from system DB
        $roles = \App\Helpers\MigrationHelper::getFromSystem('roles');
        $roleIdMap = [];

        foreach ($roles as $value) {
            $newRoleId = DB::connection('tenant')->table($tableNames['roles'])->insertGetId([
                'name' => $value->name,
                'identifier' => $value->identifier,
                'guard_name' => $value->guard_name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleIdMap[$value->id] = $newRoleId; // map system ID -> tenant ID
        }

        // =========================
        // 3. MODEL_HAS_PERMISSIONS
        // =========================
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        // =========================
        // 4. MODEL_HAS_ROLES
        // =========================
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        // Import model_has_roles from system DB
        $modelHasRoles = \App\Helpers\MigrationHelper::getFromSystem('model_has_roles');

        foreach ($modelHasRoles as $value) {
            if (isset($roleIdMap[$value->role_id])) {
                DB::connection('tenant')->table($tableNames['model_has_roles'])->insert([
                    'role_id' => $roleIdMap[$value->role_id],
                    'model_type' => $value->model_type,
                    'model_id' => $value->model_id,
                ]);
            } else {
                Log::warning("Skipped model_has_roles insert due to missing role_id mapping: {$value->role_id}");
            }
        }

        // =========================
        // 5. ROLE_HAS_PERMISSIONS
        // =========================
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

        // Import role_has_permissions from system DB
        $roleHasPermissions = \App\Helpers\MigrationHelper::getFromSystem('role_has_permissions');

        foreach ($roleHasPermissions as $value) {
            $tenantRoleId = $roleIdMap[$value->role_id] ?? null;
            $tenantPermissionId = $permissionIdMap[$value->permission_id] ?? null;

            if ($tenantRoleId && $tenantPermissionId) {
                DB::connection('tenant')->table($tableNames['role_has_permissions'])->insert([
                    'role_id' => $tenantRoleId,
                    'permission_id' => $tenantPermissionId,
                ]);
            } else {
                Log::warning("Skipped role_has_permissions insert due to missing mapping: role_id = {$value->role_id}, permission_id = {$value->permission_id}");
            }
        }

        // =========================
        // 6. CLEAR CACHE
        // =========================
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down()
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
}
