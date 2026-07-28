$arrAllPermissions = [];

$arrAllPermissions[] = array('name' => 'dashboard', 'module_slug'=>'dashboard', 'title' => 'Dashboard');
$arrAllPermissions[] = array('name' => 'total-users', 'module_slug'=>'dashboard', 'title' => 'Total Users');

$arrAllPermissions[] = array('name' => 'manage-users', 'module_slug'=>'manage-users', 'title' => 'Manage Users');
$arrAllPermissions[] = array('name' => 'users-listing', 'module_slug'=>'manage-users', 'title' => 'View Users');
$arrAllPermissions[] = array('name' => 'users-add', 'module_slug'=>'manage-users', 'title' => 'Add User');
$arrAllPermissions[] = array('name' => 'manage-roles', 'module_slug'=>'manage-users', 'title' => 'Manage Roles');
$arrAllPermissions[] = array('name' => 'manage-permissions', 'module_slug'=>'manage-users', 'title' => 'Manage Permissions');
$arrAllPermissions[] = array('name' => 'activity-logs', 'module_slug'=>'manage-users', 'title' => 'Activity Logs');


$arrAllPermissions[] = array('name' => 'manage-exams', 'module_slug'=>'manage-exams', 'title' => 'Manage Examinations');
$arrAllPermissions[] = array('name' => 'exams-listing', 'module_slug'=>'manage-exams', 'title' => 'View Examinations');
$arrAllPermissions[] = array('name' => 'exams-add', 'module_slug'=>'manage-exams', 'title' => 'Add Examinations');

$arrAllPermissions[] = array('name' => 'manage-profile-templates', 'module_slug'=>'manage-profile-templates', 'title' => 'Manage Profile Templates');
$arrAllPermissions[] = array('name' => 'profile-templates-listing', 'module_slug'=>'manage-profile-templates', 'title' => 'View Profile Templates');
$arrAllPermissions[] = array('name' => 'profile-templates-add', 'module_slug'=>'manage-profile-templates', 'title' => 'Add Profile Templates');

$arrAllPermissions[] = array('name' => 'manage-settings', 'module_slug'=>'manage-settings', 'title' => 'Manage Settings');
$arrAllPermissions[] = array('name' => 'setting-listing', 'module_slug'=>'manage-settings', 'title' => 'View Settings');
$arrAllPermissions[] = array('name' => 'setting-add', 'module_slug'=>'manage-settings', 'title' => 'Add Settings');


$arrAllPermissions[] = array('name' => 'manage-appointment-types', 'module_slug'=>'manage-appointment-types', 'title' => 'Manage Appointment Types');
$arrAllPermissions[] = array('name' => 'appointment-types-listing', 'module_slug'=>'manage-appointment-types', 'title' => 'View Appointment Types');
$arrAllPermissions[] = array('name' => 'appointment-types-add', 'module_slug'=>'manage-appointment-types', 'title' => 'Add Appointment Types');


$arrAllPermissions[] = array('name' => 'manage-patients', 'module_slug'=>'manage-patients', 'title' => 'Manage Patients');
$arrAllPermissions[] = array('name' => 'patients-listing', 'module_slug'=>'manage-patients', 'title' => 'View Patients');
$arrAllPermissions[] = array('name' => 'patients-add', 'module_slug'=>'manage-patients', 'title' => 'Add Patients');

$arrAllPermissions[] = array('name' => 'manage-menu-setting', 'module_slug'=>'manage-menu-setting', 'title' => 'Manage Menus Settings');
$arrAllPermissions[] = array('name' => 'menu-setting-listing', 'module_slug'=>'manage-menu-setting', 'title' => 'View Menus Settings');
$arrAllPermissions[] = array('name' => 'menu-setting-add', 'module_slug'=>'manage-menu-setting', 'title' => 'Add Menus Settings');

$arrAllPermissions[] = array('name' => 'manage-roster', 'module_slug'=>'manage-roster', 'title' => 'Manage Roster');
$arrAllPermissions[] = array('name' => 'roster-listing', 'module_slug'=>'manage-roster', 'title' => 'View Roster');
$arrAllPermissions[] = array('name' => 'roster-add', 'module_slug'=>'manage-roster', 'title' => 'Add Roster');

$arrAllPermissions[] = array('name' => 'manage-appointment', 'module_slug'=>'manage-appointment', 'title' => 'Manage Appointment');
$arrAllPermissions[] = array('name' => 'appointment-listing', 'module_slug'=>'manage-appointment', 'title' => 'View Appointment');
$arrAllPermissions[] = array('name' => 'appointment-add', 'module_slug'=>'manage-appointment', 'title' => 'Add Appointment');


$arrAllPermissions[] = array('name' => 'manage-diagnostic-finding-types', 'module_slug'=>'manage-diagnostic-finding-types', 'title' => 'Manage Diagnostic Finding Types');
$arrAllPermissions[] = array('name' => 'diagnostic-finding-types-listing', 'module_slug'=>'manage-diagnostic-finding-types', 'title' => 'View  Diagnostic Finding Types');
$arrAllPermissions[] = array('name' => 'diagnostic-finding-types-add', 'module_slug'=>'manage-diagnostic-finding-types', 'title' => 'Add Diagnostic Finding Types');



// use commend : php artisan permission:cache-reset or below function to reset cache
    dump(app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions());
    // $permission = Permission::create($arrAllPermissions);

    foreach ($arrAllPermissions as $key => $value) 
    { 
        // dd($value);           
        $permission = Permission::create($value);
    }
    dd('pass');

    