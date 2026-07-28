<?php

Route::get('/ReminderDaily', function() 
{
	$exitCode = Artisan::call('reminder:daily');
	
});
Route::get('/Reminder', function() 
{
	$exitCode = Artisan::call('ReminderStatus:update');
	
});

Route::get('/ReminderDaily', function() 
{
	$exitCode = Artisan::call('reminder:daily');
	
});

Route::get('/tablesdata','Admin\AuthController@languageTranslation')->name('german');  

Route::get('/calendar/oauth','Admin\DashboardController@_getAuthenticationForToken')->name('oauthCallback');  
Route::get('/calendar/store','Admin\DashboardController@eventStore'); 
// ['as' => 'oauthCallback', 'uses' => 'DashboardController@_getAuthenticationForToken']); 
Route::get('/gdpr-details','Admin\SettingController@getGDPRDetails');  
Route::get('/waiting-screen','Admin\WaitingQueueNumberController@getLatestWaitingRecords')->name('waiting.screen');  
Route::post('/get-waiting-record','Admin\WaitingQueueNumberController@getWaitingRecord');  

Route::get('/', 'Web\AppointmentWebController@home');
Route::get('/online-appointments/{enc_doctor_id?}','Web\AppointmentWebController@index');  
Route::post('/online-appointments/arrange','Web\AppointmentWebController@arrangeTimeSlot');  
Route::post('/get-doctor-slots','Web\AppointmentWebController@getDoctorSlots');  
Route::post('/get-all-doctor-slots','Web\AppointmentWebController@getAllDoctorSlots');
Route::get('/online-appointment/login', 'Web\AppointmentWebController@login');
Route::post('/online-appointment/book', 'Web\AppointmentWebController@bookWebAppointment');
Route::post('/online-appointment/generate-check-listPdf', 'Web\AppointmentWebController@generateCheckListPdf');
Route::post('/online-appointment/temp', 'Web\AppointmentWebController@temp');
Route::post('/online-appointment/generate-Document-listPdf', 'Web\AppointmentWebController@generateDocumentListPdf');
Route::post('/online-appointment/generate-single-document', 'Web\AppointmentWebController@generate_single_document');
Route::post('/online-appointment/get-examination', 'Web\AppointmentWebController@getExamination');
Route::post('/online-appointment/get-all-examination', 'Web\AppointmentWebController@submitExamination');

Route::post('/online-appointment/get-document-examination', 'Web\AppointmentWebController@getDocumentExamination');
Route::get('/online-appointment/get-check-list', 'Web\AppointmentWebController@getCheckList');
Route::post('/online-appointment/send-otp','Web\AppointmentWebController@sendOtp');  
Route::get('/online-appointment/register', 'Web\AppointmentWebController@register');
Route::get('/online-appointment/getDocument', 'Web\AppointmentWebController@get_document');
Route::get('/online-appointment/getPerformanceChecklist', 'Web\AppointmentWebController@getPerformanceChecklist');
Route::post('/online-appointment/register', 'Web\AppointmentWebController@registerAndBookAppointment');
Route::get('/{slug}','Web\PagesWebController@index');


// Route::post('/login', 'Web\LoginWebController@checkLogin');

// Route::get('/logout', 'Web\LoginWebController@logout');

/*  
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/


	Route::group(['prefix' => 'admin','middleware' => ['AdminGeneral', 'EnforceTenancy'],'namespace'=>'Admin'], function () 
	{
		/*-----------------------------------
		|	Guest Routes
		-------------------------------------------------*/
			Route::group(['middleware' => 'AdminRedirectIfAuthenticated'],function()
			{

				$BASECONTROLLER = 'AuthController@';
				$PREFIX = 'admin.auth.';

				// Default Route
				Route::redirect('/','admin/login'); 

				// Login
				Route::get('/login', $BASECONTROLLER.'login')->name($PREFIX.'login');
				Route::post('/login', $BASECONTROLLER.'checkLogin')->name($PREFIX.'check.login'); 
				
				// Forgot password
				Route::get('/forgot-password',  	$BASECONTROLLER.'forgotPassword')->name($PREFIX.'forgot.password');
				Route::post('/forgot-password',  	$BASECONTROLLER.'forgotPasswordSubmit')->name($PREFIX.'forgot.password');

				// Reset password
				Route::get('/reset-password/{id}', 	$BASECONTROLLER.'resetPassword')->name($PREFIX.'reset.password');
				Route::post('/reset-password/{id}', $BASECONTROLLER.'resetPasswordSubmit')->name($PREFIX.'reset.password');
			}); 

		/*-----------------------------------
		|	Auth Routes
		-------------------------------------------------*/
			Route::group(['middleware' => ['AdminAuthenticate']],function()
			{
				$PREFIX = 'admin';	

				// Logout
				Route::get('/logout',  'AuthController@logout')->name($PREFIX.'.logout'); 

				// Dashboard
				/*Route::group(['middleware' => ['permission:store-dashboard']], function () use($PREFIX)
				{*/
					Route::get('/calendar/getEvents', 'DashboardController@getEvents');  
					Route::get('/dashboard',  'DashboardController@index')->name($PREFIX.'.dashboard');  
					Route::post('/dashboardstore',  'DashboardController@store')->name($PREFIX.'.dashboardstore');  
					Route::get('/dashboard/patients',  'DashboardController@getPatients');
					Route::get('/dashboard/doctors',  'DashboardController@getDoctors');
					Route::get('/dashboard/create',  'DashboardController@create'); 
					Route::get('/dashboard/edit/{id}',  'DashboardController@edit');
					Route::get('/dashboard/view/{id}',  'DashboardController@view'); 
					Route::get('/dashboard/destroy/{id}',  'DashboardController@destroy')->name('dashboard');  
					Route::post('/dashboard/redirect/{id}',  'DashboardController@redirectToPatient');  
					Route::post('/dashboardupdate/{endID}',  'DashboardController@update')->name($PREFIX.'.dashboardupdate'); 
					Route::get('/calendar/getpatientsdata/{id}',  'DashboardController@getPatientsData')->name('dashboard');
					Route::get('/calendar/getdoctorsdata/{id}',  'DashboardController@getDoctorsData')->name('dashboard');    
					Route::post('/dashboard/getSpecificDateRecords', 'DashboardController@getSpecificDateRecords');
					Route::post('/dashboard/addUpdateNotices', 'DashboardController@addUpdateNotices'); 

				// });
				Route::group(['middleware' => ['permission:assistant-dashboard']], function () use($PREFIX)
				{
					Route::get('/assistant-dashboard/calendar/getEvents', 'AssistantDashboardController@getEvents');  

					Route::get('/assistant-dashboard/getDismissalCount', 'AssistantDashboardController@getDismissalCount');
					
					Route::get('/assistant-dashboard/getTodoListCount', 'AssistantDashboardController@getTodoListCount');

					Route::get('/assistant-dashboard/getDismissalRefreshData', 'AssistantDashboardController@getDismissalRefreshData');  

					Route::get('/assistant-dashboard',  'AssistantDashboardController@index')->name($PREFIX.'.assistant-dashboard');  

					Route::get('/assistant-dashboard/todoList',  'AssistantDashboardController@todoList');  

					Route::post('/assistant-dashboard/adashboardstore',  'AssistantDashboardController@store')->name($PREFIX.'.adashboardstore'); 
					Route::get('/assistant-dashboard/patients',  'AssistantDashboardController@getPatients');

					Route::post('/assistant-dashboard/adashboardstore',  'AssistantDashboardController@store')->name($PREFIX.'.adashboardstore'); 

					Route::post('/assistant-dashboard/getOldAppoinmant',  'AssistantDashboardController@getOldAppoinmant'); 

					Route::post('/assistant-dashboard/clearTodoList',  'AssistantDashboardController@clearTodoList');
					Route::post('/assistant-dashboard/importFinding',  'AssistantDashboardController@importFinding');
					Route::post('/assistant-dashboard/sendFindingEmail',  'AssistantDashboardController@sendFindingEmail');
					
					Route::post('/assistant-dashboard/pushNotificationForPetient', 'AssistantDashboardController@pushNotificationForPetient'); 

					Route::get('/assistant-dashboard/getRecordsForWaitingList',  'AssistantDashboardController@getRecordsForWaitingList');

					Route::get('/assistant-dashboard/getTodoList',  'AssistantDashboardController@getTodoList');

					Route::get('/assistant-dashboard/viewAppoinmant/{id}',  'AssistantDashboardController@viewAppoinmant');

					Route::get('/assistant-dashboard/viewPatientDetails/{id}',  'AssistantDashboardController@viewPatientDetails');
					
					Route::get('/assistant-dashboard/doctors',  'AssistantDashboardController@getDoctors');
					Route::get('/assistant-dashboard/create',  'AssistantDashboardController@create'); 
					Route::get('/assistant-dashboard/edit/{id}',  'AssistantDashboardController@edit');
					Route::get('/assistant-dashboard/view/{id}',  'AssistantDashboardController@view'); 
					Route::get('/assistant-dashboard/destroy/{id}',  'AssistantDashboardController@destroy')->name('assistant-dashboard');  
					Route::post('/assistant-dashboard/redirect/{id}',  'AssistantDashboardController@redirectToPatient');  
					Route::post('/dashboardupdate/{endID}',  'AssistantDashboardController@update')->name($PREFIX.'.dashboardupdate'); 
					Route::get('/calendar/getpatientsdata/{id}',  'AssistantDashboardController@getPatientsData')->name('assistant-dashboard');
					Route::get('/calendar/getdoctorsdata/{id}',  'AssistantDashboardController@getDoctorsData')->name('assistant-dashboard');    
					Route::post('/assistant-dashboard/getSpecificDateRecords', 'AssistantDashboardController@getSpecificDateRecords');
					Route::post('/assistant-dashboard/addUpdateNotices', 'AssistantDashboardController@addUpdateNotices'); 
					
					Route::post('/assistant-dashboard/dismissalDone', 'AssistantDashboardController@dismissalDone'); 

					Route::post('/assistant-dashboard/checkRecordWithGanymed', 'AssistantDashboardController@checkRecordWithGanymed');
				});	

				// Users
				Route::group(['middleware' => ['permission:manage-users']], function () use($PREFIX)
				{
					//Users 
					Route::get('/users/getRecords', 'UsersController@getRecords')->name('admin.users.getRecords'); 
					Route::resource('users', 'UsersController', ['as' => $PREFIX]); 
					
					//Roles 
					Route::get('/roles/getRecords', 'RolesController@getRecords')->name('admin.roles.getRecords');
					Route::post('/roles/updateRole/{endID}', 'RolesController@updateRole')->name('admin.roles.updateRole'); 
					Route::resource('roles', 'RolesController', ['as' => $PREFIX]);
					
					// Permissions
					Route::post('permissions/byRole', 'PermissionsController@byRole')->name('admin.permissions.byrole');
					Route::get('permissions/getRole', 'PermissionsController@getRole')->name('admin.permissions.getRole');
					Route::resource('permissions', 'PermissionsController', ['as' => $PREFIX]); 

					// Activity Logs    
					Route::get('activity-logs/export', 'ActivityLogsController@exportActivityLogs')->name('admin.activity-logs.export');
					Route::get('/activity-logs/getRecords', 'ActivityLogsController@getRecords')->name('admin.activity-logs.getRecords'); 
					Route::resource('activity-logs', 'ActivityLogsController', ['as' => $PREFIX]);  
					Route::get('/activity-logs/getdata/{id}', 'ActivityLogsController@getdata');    
				});

				// Examinations 
				Route::group(['middleware' => ['permission:manage-exams']], function () use($PREFIX)
				{
					Route::get('/examinations/getRecords', 'ExaminationsController@getRecords')->name('admin.examinations.getRecords'); 
					Route::get('/examinations/checkList/{id}', 'ExaminationsController@checkList')->name('admin.examinations.getRecords');

					Route::post('/examinations/updateReminder/{encID}', 'ExaminationsController@updateReminder')->name('admin.examinations.updateReminder'); 

					Route::post('/examinations/getAllActivecheckList/', 'ExaminationsController@getAllActivecheckList'); 
					Route::post('/examinations/getAllActiveDocumentList/', 'ExaminationsController@getAllActiveDocumentList'); 

					Route::resource('examinations', 'ExaminationsController', ['as' => $PREFIX]);
				});

				// Check list
				Route::group(['middleware' => ['permission:manage-check-list']], function () use($PREFIX)
				{
					Route::get('/check-list/getRecords', 'CheckListController@getRecords')->name('admin.check-list.getRecords'); 
					Route::get('/check-list/view/{id}', 'CheckListController@view'); 

					Route::get('/document-list/view/{id}', 'CheckListController@documentView'); 

					Route::post('/check-list/check_list_delete/', 'CheckListController@check_list_delete'); 
					Route::post('/check-lidocumentst/check_list_question_delete/', 'CheckListController@check_list_question_delete'); 
					Route::resource('check-list', 'CheckListController', ['as' => $PREFIX]);
				});


				// Profile Templates 
				Route::group(['middleware' => ['permission:manage-profile-templates']], function () use($PREFIX)
				{
					Route::get('/profile-templates/getRecords', 'ProfileTemplatesController@getRecords')->name('admin.profile-templates.getRecords'); 
					Route::resource('profile-templates', 'ProfileTemplatesController', ['as' => $PREFIX]); 
				});
				
				Route::post('/users/updatePassword', 'UsersController@updatePassword');
				Route::post('/users/updateLanguage', 'UsersController@updateLanguage');
				Route::post('/settings/updateBeacons/{encID}', 'SettingController@updateBeacons')->name('admin.settings.updateBeacons'); 
				Route::post('/settings/updateDismissal/{encID}', 'SettingController@updateDismissal')->name('admin.settings.updateDismissal'); 
				Route::post('/settings/updateExportPath/{encID}', 'SettingController@updateExportPath')->name('admin.settings.updateExportPath');
				Route::post('/settings/updateFindings/{encID}', 'SettingController@updateFindings')->name('admin.settings.updateFindings');
				Route::post('/settings/updateReminder/{encID}', 'SettingController@updateReminder')->name('admin.settings.updateReminder');
				Route::post('/settings/updateSmartphoneApps/{encID}', 'SettingController@updateSmartphoneApp');  
				// Settings 
				Route::group(['middleware' => ['permission:manage-settings']], function () use($PREFIX)
				{
					Route::get('/settings/getRecords', 'SettingController@getRecords')->name('admin.settings.getRecords'); 
					
					Route::resource('settings', 'SettingController', ['as' => $PREFIX]);
				}); 

				// Appoitment Types Settings
				Route::group(['middleware' => ['permission:manage-appointment-types']], function () use($PREFIX) 
				{ 
					Route::get('/appointment-types/getRecords', 'AppointmentTypesController@getRecords')->name('admin.apointment-types.getRecords'); 
					Route::resource('apointment-types', 'AppointmentTypesController', ['as' => $PREFIX]);
				});   

				// Patients
				Route::group(['middleware' => ['permission:manage-patients']], function () use($PREFIX)   
				{
					Route::get('/patients/getRecords', 'PatientsController@getrecords')->name('admin.patients.getrecords');  
					Route::post('patients/import', 'PatientsController@importdata')->name('admin.patients.import'); 
					Route::resource('patients', 'PatientsController', ['as' => $PREFIX]);   
					// Route::get('/examination/getdata/{id}', 'PatientsController@getdata'); 
					Route::get('/getexamination/{id}', 'PatientsController@getExamination')->name('admin.patients.examination');
					Route::get('/examination/{id}', 'PatientsController@Examinationindex')->name('admin.patients.examination.index'); 
					
					Route::get('/getdocument/{id}', 'PatientsController@getDocument')->name('admin.patients.document');
					Route::get('/document/{id}', 'PatientsController@Documentindex')->name('admin.patients.document.index'); 

							
					
				}); 

				// Menus Settings 
				Route::group(['middleware' => ['permission:manage-menu-setting']], function () use($PREFIX)
				{
					
					Route::get('/menus-settings/getRecords', 'MenuSettingsController@getRecords')->name('admin.menus-settings.getRecords'); 
					Route::resource('menus-settings', 'MenuSettingsController', ['as' => $PREFIX]);
				});


				// Support Settings 
				Route::group(['middleware' => ['permission:manage-support-setting']], function () use($PREFIX)
				{					
					Route::get('/support-settings/getRecords', 'SupportSettingsController@getRecords')->name('admin.support-settings.getRecords'); 
					Route::resource('support-settings', 'SupportSettingsController', ['as' => $PREFIX]);
				});

				// Menus ordination 
				
				Route::group(['middleware' => ['permission:manage-ordination']], function () use($PREFIX)
				{
					Route::get('/ordination/getRecords', 'OrdinationsController@getRecords'); 
					Route::resource('ordination', 'OrdinationsController', ['as' => $PREFIX]);
				});

				// Menus specialist 
				
				Route::group(['middleware' => ['permission:manage-specialist']], function () use($PREFIX)
				{

					Route::get('/specialist/getRecords', 'SpecialistController@getRecords')->name('admin.specialist.getRecords');

					Route::get('/specialist/checklist/{id}', 'SpecialistController@checklist');
					Route::get('/specialist/appointment_types/{id}', 'SpecialistController@appointment_types');

					// Document
					Route::get('/specialist/documents/{id}', 'SpecialistController@documents');
					Route::get('/specialist/documentsView/{id}', 'SpecialistController@documentsView');

					Route::get('/specialist/getDocumentRecords', 'SpecialistController@getDocumentRecords');
					Route::get('/specialist/document/create/{id}', 'SpecialistController@documetCreate');
					Route::post('/specialist/documentStore', 'SpecialistController@documentStore');

					Route::get('/specialist/documentEdit/{id}', 'SpecialistController@documentEdit');
					
					Route::post('/specialist/documentUpdate/{id}', 'SpecialistController@documentupdate');
					Route::get('/specialist/documentDelete/{id}', 'SpecialistController@documentDelete');

					Route::post('/specialist/SetSession', 'SpecialistController@SetSession');
					Route::post('/specialist/assignedSpecialist', 'SpecialistController@assignedSpecialist');
					
					Route::get('/specialist/getSpecilistRecord', 'SpecialistController@getSpecilistRecord');

					Route::resource('specialist', 'SpecialistController', ['as' => $PREFIX]);
				});

				// Finding Services
				Route::group(['middleware' => ['permission:manage-finding-services']], function () use($PREFIX)
				{
					Route::get('/finding-services/getRecords', 'FindingServicesController@getRecords')->name('admin.finding-services.getRecords'); 
					Route::resource('finding-services', 'FindingServicesController', ['as' => $PREFIX]);
				});

				// Roster 
				Route::group(['middleware' => ['permission:manage-roster']], function () use($PREFIX)
				{ 
					Route::get('/roster/getRecords', 'RosterController@getRecords')->name('admin.roster.getRecords'); 
					Route::post('/roster/excludeDate/{encID}', 'RosterController@excludeDate')->name('admin.roster.excludeDate'); 
					Route::post('/roster/getExcludeDates/{encID}', 'RosterController@getExcludeDates')->name('admin.roster.getExcludeDates'); 
					Route::post('/roster/getDoctorDates', 'RosterController@getDoctorDates'); 
					Route::post('/roster/getDoctorDutyRoster', 'RosterController@getDoctorDutyRoster'); 
					Route::resource('roster', 'RosterController', ['as' => $PREFIX]);
				});  


				// Appointment
				Route::group(['middleware' => ['permission:manage-appointment']], function () use($PREFIX)  
				{

					//Route::get('/appointment/test', 'AppointmentController@test'); 

					Route::get('/appointment/getRecords', 'AppointmentController@getRecords')->name('admin.appointment.getRecords'); 
					Route::post('/appointment/getDoctorTimeFrames', 'AppointmentController@getDoctorTimeFrames'); 
					Route::post('/appointment/getServices', 'AppointmentController@getServices'); 

				
					Route::resource('appointment', 'AppointmentController', ['as' => $PREFIX]);   
					Route::get('/notification/getRecords', 'AppointmentHasNotificationController@getRecords')->name('admin.notification.getRecords');  
					Route::resource('notification', 'AppointmentHasNotificationController', ['as' => $PREFIX]);
					Route::get('/notification/sendnotification/{id}', 'AppointmentHasNotificationController@sendNotification')->name('admin.notification.sendnotification');

					Route::get('/appoitment/generateChecklistPDF/{id}', 'AppointmentController@generateChecklistPDF');
					Route::get('/appoitment/generateDocumentPDF/{id}', 'AppointmentController@generateDocumentPDF');

					Route::get('/appoitment/getDoctorEvents', 'AppointmentController@getEvents')->name('admin.doctor_dashboard'); 


					Route::post('/appoitment/viewPopup', 'AppointmentController@viewPopup')->name('admin.view_popup');  

					Route::post('/appoitment/storeDismissal', 'AppointmentController@storeDismissal'); 
					Route::post('/appoitment/getPatientDetails', 'AppointmentController@getPatientDetails'); 
					
					Route::post('/appoitment/sendDocumentForPatients', 'AppointmentController@sendDocumentForPatients'); 

					

					// Route::get('/appoitment/doctors', 'AppointmentController@getDoctors')->name('admin.getDoctors');  
					Route::post('/appoitment/assignExamination', 'AppointmentController@assignExamination')->name('admin.assignExamination');  
					Route::get('/appoitment/type/{id}', 'AppointmentController@fetchType')->name('admin.assignExamination');  

					Route::post('/typeupdate/{endID}',  'AppointmentController@updateType')->name('admin.typeupdate'); 
					Route::post('/appoitment/updateDocumentStatus',  'AppointmentController@updateDocumentStatus')->name('admin.appoitment.updateDocumentStatus');
					Route::post('/appoitment/exportFindings',  'AppointmentController@exportFindings')->name('admin.appointment.exportFindings'); 

					Route::post('/appoitment/updateChecklistStatus',  'AppointmentController@updateChecklistStatus')->name('admin.appoitment.updateChecklistStatus'); 
					Route::post('/appoitment/updatePrintStatus',  'AppointmentController@updatePrintStatus');
					Route::get('/doctor-dashboard',  'AppointmentController@doctorDashboard')->name($PREFIX.'.doctor-dashboard');  	
					Route::post('/doctor-dashboard/updateReminder', 'AppointmentController@updateReminder')->name('admin.doctor-dashboard.updateReminder');				
				});   

				Route::group(['middleware' => ['permission:manage-waiting-queue']], function () use($PREFIX)  
				{

				Route::get('/waiting-queue-number/getRecords', 'WaitingQueueNumberController@getRecords')->name('admin.waiting-queue-number.getRecords');   
				Route::post('/waiting-queue-number/updateCallStatus/{encID}', 'WaitingQueueNumberController@updateCallStatus')->name('admin.waiting-queue-number.updatecallstatus');   
				Route::resource('waiting-queue-number', 'WaitingQueueNumberController', ['as' => $PREFIX]); 

				Route::get('/waiting-number-symbols/getRecords', 'WaitingNumberSymbolsController@getRecords')->name('admin.waiting-number-symbols.getRecords');  
				Route::resource('waiting-number-symbols', 'WaitingNumberSymbolsController', ['as' => $PREFIX]);
			});

				// Diagnostic Finding Types 
				Route::group(['middleware' => ['permission:manage-diagnostic-finding-types']], function () use($PREFIX)
				{
					Route::get('/diagnostic-finding-types/getRecords', 'DiagnosticFindingTypesController@getRecords')->name('admin.diagnostic-finding-types.getRecords'); 
					Route::resource('diagnostic-finding-types', 'DiagnosticFindingTypesController', ['as' => $PREFIX]);
				}); 

			});    

	}); 


// Default Route
// Route::redirect('/','admin/login'); 

/*
|--------------------------------------------------------------------------
| COMMAND ROUTES
|--------------------------------------------------------------------------
*/

	//Clear Route cache:
	Route::get('/clear-cache', function() 
	{
		$exitCode = Artisan::call('cache:clear');
		return '<h1>Cache facade value cleared</h1>';
	});

	//Clear Route cache:
	Route::get('/route-clear', function() 
	{
		$exitCode = Artisan::call('route:clear');
		return '<h1>Route cache cleared</h1>';
	});

	//Clear View cache:
	Route::get('/view-clear', function() 
	{
		$exitCode = Artisan::call('view:clear');
		return '<h1>View cache cleared</h1>';
	});
