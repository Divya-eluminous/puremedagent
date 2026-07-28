<?php

return [

	/*--------------------------------------
	|	Common
	-------------------------------------------------------------------*/
		//SITE
		// 'TITLE_SITE_BEGIN' 	=> 'Pure',
		// 'TITLE_SITE_END' 	=> 'GYN',
		'TITLE_SITE_BEGIN' 	=> 'Pure',
		'TITLE_SITE_END' 	=> 'Med',
		'TITLE_SITE_HOME' 	=> 'Home',
		'TITLE_SITE_COPYRIGHT' => 'Copyright',
		'TITLE_SITE_RIGHTS' => 'All rights reserved.',
		"TITLE_NEW_WINDOW"  => 'New Window',
		"TITLE_RECOMMEND_HOLIDAY_SETTING"  => 'Weekends',
		"TITLE_RECOMMEND_WEEKENDS_SETTING"  => 'Holidays',
		"SERVICE_EVENT_TYPE_MENU" =>'Suggested Services',
		"CALENDAR_ID"=>'Calender Id',
		"TITLE_EARMING_MESSAGE_SERVICE_INACTIVE"=>'Services that are displayed as controls must be active',

		"TITLE_RECOMMEND_SMS_SETTING"  => 'SMS',
		"TITLE_RECOMMEND_EMAIL_SETTING"  => 'E-mail',
		"TITLE_CHOICE_OF_CHANNELS_SETTING"  => 'Choice Of Channels',
		"TITLE_REMINDER_FOR_RECOMMENED_SETTING"  => 'Reminders For Recommended',
		'TITLE_PATIENT_ENSURANCE_NUMBER'=> 'Versicherungsnummer',
		'TITLE_PATIENT_TODO_ENSURANCE_NUMBER'=> 'Insurance Number',
		"TITLE_DEFAULT_TEXT_PUSH_REMINDER_SETTING"  => 'Push Notification Remindes Text',
		"TITLE_DEFAULT_TEXT_SETTING"  => 'Default Texts For Reminders',
		"TITLE_DEFAULT_TEXT_MAIL_REMINDER_SETTING"  => 'Mail Reminder Text',
		"TITLE_DEFAULT_TEXT_SMS_REMINDER_SETTING"  => 'SMS Reminder Text',
		"TITLE_GENERAL_SETTING" => 'General',

		//"TITLE_GENERAL_REMINDER" => 'General Reminder',   //commented on 16-jan-24
		"TITLE_GENERAL_REMINDER" => 'Follow-up service recommendation', //changed on 16-jan-24

		// "TITLE_AGE_REMINDER" => 'Age Reminder',  //commented on 16-jan-24
		"TITLE_AGE_REMINDER" => 'Profile-dependent service recommendation', //changed on 16-jan-24

		"TITLE_AGE" => 'Age',

		// "TITLE_CHECKUP_REMINDER" => 'Checkup Reminder', //commented on 16-jan-24
		"TITLE_CHECKUP_REMINDER" => 'Checkup service recommendation',//changed on 16-jan-24

		"TITLE_PERIOD_OF_REMINDER_CONTROL" => 'Periods of Control reminder',


		// "TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER" => 'Periods of a general reminder', //commented on 14-feb-25 for #303 issue
		"TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER" => 'Lead time to start',//added on 14-feb-25 for #303 issue


		"TITLE_NEW_REMINDER" => 'New reminder',
		"TITLE_FIRST_REMINDER" => 'First reminder',
		"TITLE_REMINDER" => 'Reminder',
		"TITLE_REMINDER_POPUP" => 'Reminder',
		"TITLE_DR_DASH_REMINDER" => 'Reminder',
		"TITLE_DR_DASH_SELECT_REMINDER" => 'Select Remainders',
		"TITLE_DR_DASH_CONTROL_REMINDER" => 'Control',
		"TITLE_TIME_INTERVAL" => 'Time Interval',
		"TITLE_NUMBER_OF_INTERVAL" => 'Number of interval',

		"TITLE_SERVICES" => 'Services',
		"TITLE_REVERS" => 'Reverse',
		"TITLE_FINDING" => 'Findings',


		// Module Titles
		'TITLE_MANAGE_TEXT' => 'Manage',
		'TITLE_DOWNLOAD_TEXT' => 'Download',
		'TITLE_CREATE_TEXT' => 'Create',
		'TITLE_EDIT_TEXT'   => 'Edit',
		'TITLE_INFORMATION_TEXT' => 'Information',
		'TITLE_ACTIONS_TEXT' => 'Actions',
		'TITLE_SELECT_TEXT'  => 'Select',
		'TITLE_STATUS_ACTIVE_TEXT'  => 'Active',
		'TITLE_STATUS_INACTIVE_TEXT'  => 'Inactive',
		'TITLE_DOCTOR_AVAILABILITY'  => 'Doctor Availability',
		'TITLE_DOWNLOAD_DOC'  => 'Download Document',

		'TITLE_PAGE_SHOW'  => 'Showing',
		'TITLE_PAGE_TO'    => 'to',
		'TITLE_PAGE_OF'    => 'of',


		//Buttons
		'TITLE_BACK_BUTTON' 	=> 'Back',
		'TITLE_SAVE_BUTTON' 	=> 'Save',
		'TITLE_CLOSE_BUTTON' 	=> 'Close',
		'TITLE_RESET_BUTTON' 	=> 'Reset',
		'TITLE_ADD_BUTTON' 		=> 'Add',
		'TITLE_REMOVE_BUTTON' 	=> 'Remove',
		'TITLE_VIEW_BUTTON' 	=> 'View',

		'TITLE_DELETE_BUTTON' 	=> 'Delete',
		'TITLE_CHECKLIST_BUTTON'=> 'Checklists',
		'TITLE_DOCUMENT_BUTTON' => 'Documents',
		'TITLE_APPOINMENT_TYPE_BUTTON' => 'Appointment types',
		'TITLE_CANCEL_BUTTON' 	=> 'Cancel',
		'TITLE_OK_BUTTON' 		=> 'Ok',
		'TITLE_DELETE_SURE' 	=> 'Are you sure?',
		'TITLE_DELETE_QUESTION' => 'You want to delete ?',
		'TITLE_DELETE_QUESTION2' => 'You want to delete it permanently ?',
		'TITLE_REMINDER_TITLE'  => 'This deletes all existing reminders!!',
		'TITLE_REMINDER_UPDATE'  => 'Reminder updated successfully.',
		'TITLE_REMINDER_DEACTIVATE_TITLE' 	=> 'Are you sure want to deactivate?',
		'TITLE_OTHER_TEXT' 		=> 'save the changes?',


		//Placeholder Title
		'TITLE_SEARCH_TEXT' => 'Search...',
		'TITLE_SEARCH_WEB_TEXT' => 'Book',

		// ERROR MESSAGES
		'ERR_SOMETHING_WRONG' 	=> "Something went wrong on server.Please contact to Server",
		'ERR_SESSION_TIMEOUT' 	=> "Session timeout, Please try again after login.",
		'FAIL_LANGUAGE_STATUS' 	=> "Failed to update language, Something went wrong on server.",
		'CHANGE_LANGUAGE_STATUS' 	=> "Language updated successfully.",

		// Response status
		'RESP_SUCCESS' 	=> 'success',
		'RESP_ERROR' 	=> 'error',
		'RESP_WARNING' 	=> 'warning',
		'RESP_INFO' 	=> 'info',
		'ERR_NOT_FOUND'  => 'No entry found.',
		'ERR_NO_NOTES_FOUND'  => 'No notes found.',
		'ERR_ENTER_NOTES_FOUND'  => 'Please enter notes.',
		'ERR_DEFAULT_TEXT_REQUIRED'=>'Default text field is required',
		'ERR_EMAIL_DUP_PATIENT' 	=> "Patient has already taken.",
		'ERR_SETTING_TEXT_REQUIRED'  =>'Text field is required',
		'ERR_SETTING_IPHONE_REQUIRED'=>'Iphone field is required',
		'ERR_SETTING_ANDOID_REQUIRED'=>'Andoid text field is required',
		'ERR_SETTING_TABLET_REQUIRED'=>'Tablet text field is required',
		'TITLE_REMINDER_SERVICE_BTN' => 'Erinnerung konfigurieren',


	/*--------------------------------------
	|	Module name Sidebar
	-------------------------------------------------------------------*/
		// Module Titles
		'TITLE_DASHBOARD' => 'Dashboard',
		'TITLE_ACTIVITY' => 'Activity Logs',

	/*--------------------------------------
	|	Assistant Dashboard
	-------------------------------------------------------------------*/
		// Module Titles
		'TITLE_ASSISTANT_DASHBOARD' => 'Assistant Dashboard',
		'TITLE_ASSISTANT_DASHBOARD_TODO_LIST'  => 'ToDo List',
		'TITLE_ASSISTANT_DASHBOARD_APPOINMENT' => 'Appointments',
		'TITLE_ASSISTANT_APPOINMENT_TITLE' => 'Appointment Details',
		'TITLE_ASSISTANT_DASHBOARD_DUPLICATE' => 'Duplicate',
		'TITLE_ASSISTANT_DASHBOARD_WAITING'    => 'Waiting list',
		'TITLE_ASSISTANT_DASHBOARD_DISMISSAL'  => 'Dismissal',
		'TITLE_NEW'    => 'New',
		'TITLE_FINDING'=> 'Finding',
		'SEND_MAIL_TO'=>'To',
		'TITLE_COPY'   => 'Copy',
		'TITLE_TODO_LIST_FINDING'   => 'Diagnosis date',
		'TITLE_TODO_LIST_IMPORT_FINDING' => 'Import Finding',
		'TITLE_TODO_LIST_SEND_FINDING' => 'Send Mail',
		'TITLE_TODO_LIST_SEND'=>'Send',
		'TITLE_TODO_LIST_SEND_ALL'=>'Send All',
		'TITLE_TODO_LIST_IMPORT'   => 'Import',
		'TITLE_TOTO_NEW_COMPLETED'   => 'Completed',
		'TITLE_TOTO_NEW_COMPLETED_NEXT'   => 'Complete And Next',
		'TITLE_TOTO_ABORT'   => 'Abort',
		'TITLE_TOTO_VIEW_MORE'   => 'View More',
		'TITLE_FINDING_DOCUMANT_NAME' => 'Document Name',
		'ERR_TODO_LIST_IMP_DOCUMENT_NAME' =>'Document name field is required',
		'TITLE_TOTO_SELECT_FINDING_TYPE'   => 'Select Type',
		'ERR_DISMISSAL_REQUIRED' 	=> "Please select At list one dismissal.",
		'TODO_LIST_IMPORT_CREATE' => "Import file successfully.",
		'FINDING_SEND_SUCCESS' => "Finding send successfully.",
		'DISSMISSAL_SUCCESS_MSG' 	=> "Dismissal Done.",
		'DISSMISSAL_ERROR_MSG' 	=> "Something wenden to be wrong.",
		'ERR_DISMISSAL_REQUIRED_NOT_EXIST' =>'Record not exist.',
		'ERR_CLEAR_TODO_LIST_WARNING_MSG' =>'All data has been successfully copy/paste?',
		'ERR_TODO_LIST_IMP_TYPE' => 'Type field is required',

		'ERR_TODO_LIST_IMP' =>'Import file is required',
		'WARNING_TITLE' =>'YES',
		'WARNING_TITLE_NO' =>'NO',
		'TITLTE_TODO_WARNING' =>'Warning',
		'TITLTE_TODO_COMPLETED_MSG' =>'Completed.',
		'TITLTE_TODO_NOT_COMPLETED_MSG' =>'Not completed.',

		//Added by Shyam 04-01-22
		'MSG_FINDING_IMPORTED_FAILED' =>'Patient Name did not match. Finding is failed to import.',
		'MSG_FINDING_IMPORTED_SUCCESSFULLY' =>'Finding has imported successfully.',
		'MSG_FINDING_IMPORTED_ERR_MSG' =>'Files does not contains patient data.',
		'MSG_FINDING_IMPORTED_SUCCESS' =>'Below are the files which are matched with the patient data : ',
		'MSG_FINDING_IMPORTED_FAILURE' =>'Below are the files which are not matched with the patient data : ',

		'MSG_FINDING_SEND_VIA_MAIL' =>'Are you sure you want to send findings?',
		'MSG_SEND_DOC_FOR_PATIENT' =>'Are you sure you want to send the document?',
		'MSG_FINDING_SEND_PUSH_NOTIFICATION' =>'Push notification sent successfully.',
		'TITLE_PRINT' =>'print',
		'TITLE_DOCUMENT_SEND' =>'Document send successfully.',
		'TITLE_NOT_EXIST_APP' =>'Sending findings via email is not activated in the settings and the patient is not using a smartphone app. Thus the finding cannot be sent digitally',


	/*--------------------------------------
	|	Module name LOGIN
	-------------------------------------------------------------------*/
		// ERROR MESSAGES
		'ERR_FIRSTNAME_REQUIRED' 	=> 'First Name field is required.',
		'ERR_FIRSTNAME_REGEX_REQUIRED'=> 'First Name field should be in letter\'s and number\'s only.',
		'ERR_LASTNAME_REQUIRED' 	=> 'Last Name field is required.',
		'ERR_LASTNAME_REGEX_REQUIRED' => 'Last Name field should be in letter\'s and number\'s only.',
		'ERR_USERNAME_REQUIRED' 	=> "Username field is required.",
		'ERR_EMAIL_REQUIRED' 		=> "Email field is required with valid format.",
		'ERR_LOGIN_PASSWORD_REQUIRED' 	=> "Password field is required.",
		// 'ERR_EMAIL_REQUIRED' 		=> "E-Mail-Feld ist erforderlich.",
		// 'ERR_LOGIN_PASSWORD_REQUIRED' 	=> "Das Kennwortfeld ist erforderlich.",
		'ERR_LANGUAGE_REQUIRED' 	=> "Language field is required.",


		// Response error
		'ERR_ACCOUNT_DEACTIVATE' 	=> "This account has deactivated, Please contact website administrator.",
		'ERR_ACCOUNT_EMAIL_MOBILE' 	=> "Incorrect mobile or Email Details.",
		'ERR_ACCOUNT_PASSWORD_WRONG' 	=> "Incorrect Password.",
		'ERR_ACCOUNT_INCORRECT' 	=> "Incorrect login details.",
		'ACCOUNT_SUCCESS' 			=> "Login successfull.",

		// Titles
		'TITLE_LOGIN_HEADING' 	=> 'Sign in to start your session',
		// 'TITLE_LOGIN_HEADING' 	=> 'Melden Sie sich an, um Ihre Sitzung zu starten',
		'TITLE_EMAIL' 			=> 'Email',
		// 'TITLE_EMAIL' 			=> 'E-Mail',
		'TITLE_PASSWORD' 		=> 'Password',
		// 'TITLE_PASSWORD' 		=> 'Passwort',
		'TITLE_SELECT_LANGUAGE' => 'Select Language',
		// 'TITLE_SELECT_LANGUAGE' => 'Sprache auswählen',
		'TITLE_LANGUAGE_GERMAN' => 'German',
		'TITLE_LANGUAGE_ENGLISH'=> 'English',
		// 'TITLE_LANGUAGE_ENGLISH'=> 'Englisch',
		'TITLE_REMEMBER_ME' 	=> 'Remember me',
		'TITLE_FORGOT_PASSWORD' => 'Forgot Password',
		// 'TITLE_FORGOT_PASSWORD' => 'Passwort vergessen',
		'TITLE_BUTTON_LOGIN' 	=> 'Sign In',
		'TITLE_RESEND_OTP' 	=> 'Resend OTP',
		// 'TITLE_BUTTON_LOGIN' 	=> 'Anmelden',
		'TITLE_BUTTON_LOGOUT' 	=> 'Sign Out',
		// 'TITLE_BUTTON_LOGOUT' 	=> 'Abmelden',
		'TITLE_DISMISSAL_BUTTON'=> 'Dismissal Done',
		'TITLE_EXAMINATION_BUTTON'=> 'Services Done',
		'COPY_MSG_SUCCESS'=> 'Copy successfully.',
		'COPY_MSG_ERROR'  => 'Text is not copy.',


	/*--------------------------------------
	|	Module name Change Password
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_CHANGE_PASSWORD_MODULE'  => 'Change Password',
		'TITLE_CHANGE_PASSWORD_HEADING' => 'Update User Password',
		'TITLE_CHANGE_PASSWORD_OLD'		=> 'Old Password',
		'TITLE_CHANGE_PASSWORD_BUTTON'  => 'Update',

		// Errors
		'ERR_CHANGE_PASSWORD_OLD' 	=> 'Old password field is required.',
		'ERR_CHANGE_PASSWORD_NEW' 	=> 'New password field is required.',
		'ERR_CHANGE_PASSWORD_NEW_LENGTH' => 'New password field should be minimun 6 characters long.',

		// Response messages
		'CHANGE_PASSWORD_STATUS' 	=> 'Password updated successfully.',

		// Response error
		'FAIL_CHANGE_PASSWORD_STATUS'   => 'Failed to update password, Something went wrong on server.',
		'FAIL_CHANGE_PASSWORD_MATCH' 	=> 'Old password does not match with the password you logged in.',


	/*--------------------------------------
	|	Module name Forgot Password
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_FORGOT_PASSWORD_MODULE'  => 'FORGOT PASSWORD',
		'TITLE_FORGOT_PASSWORD_HEADING'	=> 'You forgot your password? Here you can easily retrieve a new password.',
		// 'TITLE_FORGOT_PASSWORD_HEADING'	=> 'Sie haben Ihr Passwort vergessen? Hier können Sie ganz einfach ein neues Passwort abrufen.',
		'TITLE_FORGOT_PASSWORD_BUTTON'  => 'Request new password',
		// 'TITLE_FORGOT_PASSWORD_BUTTON'  => 'Neues Kennwort anfordern',

		// Response messages
		'FORGOT_PASSWORD_STATUS' 	=> 'Password reset link send successfully, Please check your email acount.',
		// 'FORGOT_PASSWORD_STATUS' 	=> 'Passwort-Reset-Link erfolgreich senden, Bitte überprüfen Sie Ihre E-Mail-Anzeige.',

		// Response error
		'FAIL_FORGOT_PASSWORD_MATCH' 		=> 'User does not match our records.',
		'FAIL_FORGOT_PASSWORD_DISABLED' 	=> 'User account has disabled. Please contact website administrator.',
		// 'FAIL_FORGOT_PASSWORD_MATCH' 		=> 'Benutzer stimmt nicht mit unseren Datensätzen überein.',
		// 'FAIL_FORGOT_PASSWORD_DISABLED' 	=> 'Benutzerkonto deaktiviert. Wenden Sie sich an den Website-Administrator.',

	/*--------------------------------------
	|	Module name Reset Password
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_RESET_PASSWORD_MODULE'  	=> 'RESET PASSWORD',
		'TITLE_RESET_PASSWORD_HEADING'  => 'You are only one step a way from your new password, recover your password now.',
		'TITLE_RESET_PASSWORD_CONFIRM_PASSWORD' => 'Confirm Password',
		'TITLE_RESET_PASSWORD_BUTTON'  => 'Change password',

		// Errors
		'ERR_RESET_PASSWORD_CONFIRM_PASSWORD' 	=> 'Confirm password field is required.',

		// Response messages
		'RESET_PASSWORD_STATUS' 	=> 'Password updated successfully.',

		// Response error
		'FAIL_RESET_PASSWORD_STATUS_CHANGE' => 'Failed to reset password, Token expired.',

	/*--------------------------------------
	|	Module name USERS
	-------------------------------------------------------------------*/
		// Errors
		'ERR_DOCTOR_REQUIRED'	=> "Doctor field is required.",
		'ERR_FIRST_NAME'	=> "First name field is required.",
		'ERR_LAST_NAME' 	=> "Last name field is required.",
		'ERR_EMAIL_NAME' 	=> "Email field is required.",
		'ERR_EMAIL_FORMAT' 	=> "Email format is invalid.",
		'ERR_EMAIL_DUP' 	=> "Email has already taken.",
		'ERR_PASS' 			=> "Password field is required.",
		'ERR_PASS_MIN_SIZE' => "Password should be minimum 6 character long.",
		'ERR_CONFIRM_PASS' 	=> "Confirm password field is required.",
		'ERR_COMPARE_PASS' 	=> "Confirm password not match with password.",
		'ERR_PROFILE_IMAGE_FORMAT' 	=> "The profile image must be a file of type: jpeg, jpg, png, gif.",
		'ERR_ROLE' 			=> "Role field is required.",
		'ERR_COLOR' 		=> "Color field is required." ,
		'ERR_MOBILE_NUMBER_REQUIRED' => "Mobile Number field is required.",
		'ERR_PROFILE_IMAGE' => "Profile Image field is required.",
		'ERR_DOCTOR_SPECIALITY_REQUIRED' => "Doctor Speciality field is required.",

		// Titles
		'TITLE_USERS_MODULE' 	=> 'Users',
		'TITLE_DOCTORS_TEXT' 	=> 'Doctors',
		'TITLE_USER_MODULE'  	=> 'User',
		'TITLE_LOGIN_MODULE' 	=> 'Admin Login',
		'TITLE_FIRST_NAME'	 	=> 'First Name',
		'TITLE_USER_NAME'	 	=> 'Name',
		'TITLE_LAST_NAME'  	 	=> 'Last Name',
		'TITLE_EMAIL_ADDRESS' 	=> 'Email Address',
		'TITLE_MOBILE_NO' 		=> 'Mobile Number',
		'TITLE_PASS'  			=> 'Password',
		'TITLE_CONFIRM_PASS'	=> 'Confirm Password',
		'TITLE_ROLE'			=> 'Role',
		'TITLE_SELECT_ROLE'		=> 'Select Role',
		'TITLE_COLOR'			=> 'Color',
		'TITLE_PREVIEW'			=> 'Preview',
		'TITLE_SELECT_COLOR'	=> 'Select Color',
		'TITLE_PROFILE_IMAGE' 	=> 'Profile Image',
		'TITLE_DOCTOR_SPECIALITY'=>'Doctor Speciality',

		// Response messages
		'USER_CREATED' 	=> 'User created successfully.',
		'USER_UPDATED' 	=> 'User updated successfully.',
		'USER_DELETED' 	=> 'User deleted successfully.',
		'USER_STATUS_CHANGED' => 'User status changed successfully.',

		// Response error
		'FAIL_USER_CREATE' 	=> 'Failed to create user, Something went wrong on server.',
		'FAIL_USER_UPDATE' 	=> 'Failed to update user, Something went wrong on server.',
		'FAIL_USER_DELETE' 	=> 'Failed to delete user, Something went wrong on server.',
		'FAIL_USER_NOT_DELETE' 	  => "Cant delete current logged user",
		'FAIL_USER_STATUS_CHANGE' => 'Failed to change user status, Something went wrong on server.',

		'AUTH_OTP_REQ' 		  => 'Das Einmal-Kennwort ist erforderlich.',
		'AUTH_OTP_EXPIRED' 	  => 'Das Einmal-Kennwort ist abgelaufen.',
		'AUTH_INVALID_OTP' 	  => 'Falscher Anmelde-Code.',
		'AUTH_FAILED_OTP' 	  => 'Fehler beim Senden des Anmelde-Codes.',
		'TITLE_OTP' 	      => 'OTP',
		'AUTH_USER_RESEND_OTP_SUCCESS'=> 'Das Einmal-Kennwort wurde erfolgreich versendet.',


	/*--------------------------------------
	|	Module name Roles
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_ROLES_MODULE' => 'Roles',
		'TITLE_ROLE_NAME' 	 => 'Name',
		'TITLE_ROLE_IDENTIFIER' => 'Identifier',
		'TITLE_ROLE_HEADING' => 'Add New Role',
		'TITLE_DOCTOR_ROLE' => 'Doctor',
		'TITLE_ASSISTANT_ROLE' => 'Assistant',

		// Errors
		'ERR_ROLE_NAME'		=> "Role name field is required.",
		'ERR_ROLE_REGEX'	=> "Role name should accept letters and - only.",
		'ERR_ROLE_UNIQUE'	=> "Role name has already taken.",
		'ERR_ROLE_IDENTIFIER' => "Identifier field is required.",
		'ERR_ROLE_IDENTIFIER_REGEX' => "Identifier name should accept letters and - only.",
		'ERR_ROLE_IDENTIFIER_UNIQUE' => "Identifier name has already taken.",

		// Response messages
		'ROLE_CREATED' 	=> 'Role created successfully.',
		'ROLE_UPDATED' 	=> 'Role updated successfully.',
		'ROLE_DELETED' 	=> 'Role deleted successfully.',

		// Response error
		'FAIL_ROLE_CREATE' 	=> 'Failed to create role, Something went wrong on server.',
		'FAIL_ROLE_UPDATE' 	=> 'Failed to update role, Something went wrong on server.',
		'FAIL_ROLE_DELETE' 	=> 'Failed to delete role, Something went wrong on server.',
		'FAIL_ROLE_DELETE_ASSIGNED' => 'Failed to delete role, As permissions are assigned to this Role.',

	/*--------------------------------------
	|	Module name Permissions
	-------------------------------------------------------------------*/
		//Button
		'DETAIL' => 'details',

		// Titles
		'TITLE_TOTAL_USERS' 	  => 'Total Users',
		'TITLE_OFF' 			  => 'off',
		// 'TITLE_ON' 				  => 'on', //Commented by Shyam 21-02-22
		'TITLE_ON' 				  => 'Active', //Added by Shyam 21-02-22
		'TITLE_PERMISSION_MODULE' => 'Permissions',
		'PERMISSION_UPDATED' 	  => 'Permission updated successfully.',

		// Response error
		'FAIL_PERMISSION_UPDATE' 	=> 'Failed to update permissions, Something went wrong on server.',

	/*--------------------------------------
	|	Module name Activity Logs
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_ACTIVITY_LOG_TEXT' => 'Activity Logs',

		// Titles
		'TITLE_ACTIVITY_MODULE' => 'Module',
		'TITLE_ACTIVITY_ACTION' => 'Action',
		'TITLE_ACTIVITY_MESSAGE'=> 'Message',
		'TITLE_ACTIVITY_METHOD'	=> 'Method',
		'TITLE_ACTIVITY_URL'  	=> 'URL',
		'TITLE_ACTIVITY_IP' 	=> 'IP',
		'TITLE_ACTIVITY_AGENT' 	=> 'Agent',
		'TITLE_ACTIVITY_USER_ID'  	=> 'User Id',
		'TITLE_ACTIVITY_USER'  	=> 'User Name',
		'TITLE_CREATED_AT_TEXT'  	=> 'Created At',


	/*--------------------------------------
	|	Module name Setting
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SETTING_TEXT' => 'Settings',
		'TITLE_SETTING_TEXT_SMARTPHONE' => 'Text',
		'TITLE_SETTING_TEXT_IPHONE' => 'Iphone',
		'TITLE_SETTING_TEXT_MASTER_TABLET' => 'Master Data Tablet',
		'TITLE_SETTING_TEXT_WAITING_NO_TABLET' => 'Waiting No Tablet',
		'TITLE_SETTING_TEXT_SINGDOC_TABLET' => 'SingDoc Tablet',
		'TITLE_SETTING_TEXT_ANDOID' => 'Andoid',

		// ERROR MESSAGES
		'ERR_SETTING_KEY_REQUIRED' 	=> "Setting key field is required.",
		'ERR_SETTING_VALUE_REQUIRED' 	=> "Setting value field is required",
		'ERR_SETTING_VALUE' => "Please enter setting value in minutes.",
		'ERR_SETTING_DESCRIPTION_REQUIRED' 	=> "Description field is required",
		'ERR_SETTING_EXPORT_PATH_REQUIRED' 	=> "Path field is required.",

		// Titles
		'TITLE_SETTING_KEY' 			=> 'Setting Key',
		'TITLE_SETTING_VALUE'  			=> 'Setting Value',
		'TITLE_SETTING_DESCRIPTION' 	=> 'Description',
		'TITLE_SETTING_STATUS'			=> 'Status',
		'TITLE_SETTING_EVENT_TYPE'	   => 'Event Type',
		'TITLE_SETTING_STATUS_ACTIVE' 	=> 'Active',
		'TITLE_DISMISSAL_NAME' 	        => 'Dismissal',

		// Response error
		'FAIL_SETTING_CREATE' => "Failed to create setting, Something went wrong on server.",
		'FAIL_SETTING_DELETE' => "Failed to delete setting, Something went wrong on server.",

		// Response messages
		'SETTING_CREATED' => "Setting created successfully.",
		'SETTING_UPDATED' => "Setting updated successfully.",
		'PASSWORD_SETTING_UPDATED' => "Password update setting is updated successfully.",

		'SETTING_DELETED' => "Setting deleted successfully.",
		'EXPORT_PATH' => 'Export storage path',

	/*--------------------------------------
	|	Module name Finding services
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_FINDING_TEXT' => 'Finding',
		'TITLE_FINDING_SERVICES_TEXT' => 'Finding Services',

		// ERROR MESSAGES
		'ERR_FINDING_SERVICES_NAME_REQUIRED' 	=> "Name field is required.",
		'ERR_FINDING_SERVICES_WEB_URL_REQUIRED' => "Web url field is required",

		// Titles
		'TITLE_FINDING_SERVICES_NAME' 	=> 'Name',
		'TITLE_FINDING_SERVICES_WEB_URL'=> 'Web URL',
		'TITLE_FINDING_SERVICES_TYPE' 	=> 'Type',
		'TITLE_FINDING_SERVICES_STATUS'			=> 'Status',
		'TITLE_FINDING_SERVICES_STATUS_ACTIVE' 	=> 'Active',

		// Response error
		'FAIL_FINDING_SERVICES_CREATE' => "Failed to create finding services, Something went wrong on server.",
		'FAIL_FINDING_SERVICES_DELETE' => "Failed to delete finding services, Something went wrong on server.",

		// Response messages
		'FINDING_SERVICES_CREATED' => "Finding services created successfully.",
		'FINDING_SERVICES_UPDATED' => "Finding services updated successfully.",
		'FINDING_SERVICES_DELETED' => "Finding services deleted successfully.",


	/*--------------------------------------
	|	Module name APPOINTMENT_TYPE
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_APPOINTMENT_TYPE_TEXT' => 'Appointment Types',
		'TITLE_STATUS_APPOINTMENT' => 'Appointment Status',
		'TITLE_OPTIMAL_APPOINTMENT_LABEL' => 'Optimal Appointment',

		// ERROR MESSAGES
		'ERR_APPOINTMENT_TYPE_NAME_REQUIRED' 		=> "Name field is required.",
		'ERR_APPOINTMENT_TYPE_NAME_UNIQUE' 			=> "Name field should unique.",
		'ERR_APPOINTMENT_TYPE_DURATION_REQUIRED' 	=> "Duration field is required",
		'ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED' => "Description field is required",

		// Titles
		'TITLE_APPOINTMENT_TYPE_NAME' 			=> 'Name',
		'TITLE_APPOINTMENT_TYPE_DURATION'  		=> 'Duration',
		'TITLE_APPOINTMENT_TYPE_DESCRIPTION' 	=> 'Description',
		'TITLE_APPOINTMENT_RECOMMEND_EXAM_STATUS' => 'Do not show recommended services',
		'TITLE_APPOINTMENT_TYPE_UPLOAD_DOC_ACTIVE' => 'Upload document for patient',
		'TITLE_APPOINTMENT_TYPE_STATUS'			=> 'Status',
		'TITLE_APPOINTMENT_TYPE_STATUS_ACTIVE' 	=> 'Active',
		'TITLE_APPOINTMENT_TYPE_DASHBOARD_SETTING' 	=> 'Show on Dashboard',
		'TITLE_APPOINTMENT_TYPE_RECOMMENDE_SETTING' 	=> 'Show As Recommended',
		'TITLE_APPOINTMENT_QR_CODE' => 'QR Code',
		'TITLE_OPTIMAL_APPOINTMENT_YES' => 'Yes',
		'TITLE_OPTIMAL_APPOINTMENT_NO' => 'No',

		//Placeholder Title
		'TITLE_SELECT_DURATION_TEXT' => 'Select Duration',

		// Response error
		'FAIL_APPOINTMENT_TYPE_CREATE' => "Failed to create Appointment, Something went wrong on server.",
		'FAIL_APPOINTMENT_TYPE_UPDATE' => "Failed to update Appointment, Something went wrong on server.",
		'FAIL_APPOINTMENT_TYPE_DELETE' => "Failed to delete Appointment, Something went wrong on server.",

		'FAIL_APPOINTMENT_TYPE_DELETE_ASSIGNED' => 'Failed to delete appointment type, As appointment type are assigned to the Appointment.',

		// Response messages
		'APPOINTMENT_TYPE_CREATED' => "Appointment created successfully.",
		'APPOINTMENT_TYPE_UPDATED' => "Appointment updated successfully.",
		'APPOINTMENT_TYPE_UPDATED' => "Appointment type updated successfully.",
		'DOCTOR_APPOINTMENT_TYPE_UPDATED' => "Appointment type updated successfully.",
		'DOCUMENT_STATUS_UPDATED' => "Document status updated successfully.",
		'DOCUMENT_EXPORTED' => "Findings exported successfully.",
		'APPOINTMENT_TYPE_DELETED'=> "Appointment deleted successfully",
		'APPOINTMENT_TYPE_NOT_DELETED'=> "Appointment Not Deleted.",
	/*--------------------------------------
	|	Module name Offence
	-------------------------------------------------------------------*/

		// ERROR MESSAGES

		'ERR_OFFENCE_NAME' 		=> "Name field is required.",
		'ERR_OFFENCE_DESC' 		=> "Description field is required",
		'ERR_OFFENCE_PENALTY' 	=> "Penalty field is required.",
		'ERR_OFFENCE_PENALTY_FORMAT' => "Please enter penalty in valid format.",

	/*--------------------------------------
	|	Module name services
	-------------------------------------------------------------------*/
		// ERROR MESSAGES
		'ERR_EXAM_NAME_REQUIRED' 		=> "Name field is required.",
		'ERR_EXAM_NAME_REGEX_REQUIRED'  => "Name field should be in letter\'s and number\'s only.",
		'ERR_URL_REQUIRED' 		=> "URL field is required.",
		'ERR_DOCUMENT_REQUIRED' => "Document field is required",
		'ERR_DOCUMENT_STATUS_REQUIRED' => "Document Status field is required",
		'ERR_DOCUMENT_FORMAT' => "Please select correct format of document.",
		'ERR_CHECK_LIST_REQUIRED' 		=> "Check list field is required.",
		'ERR_CHECK_LIST_TYPE_REQUIRED' => "Check list type field is required.",

		// Module Title
		'TITLE_EXAMINATIONS_TEXT' => 'Services',
		'TITLE_SELECT_EXAMINATIONS_TEXT' => 'Select Services',
		'TITLE_EXAMINATIONS_EVENT_TYPE' => 'Event Type',
		'TITLE_EXAMINATIONS_EVENT_ADMIN' => 'Admin',
		'TITLE_EXAMINATIONS_EVENT_WEB' => 'Web',
		'TITLE_EXAMINATIONS_EVENT_SMARTPHONE' => 'Smart Phone',
		'TITLE_EXAMINATIONS_EVENT_TABLET' => 'Tablet',
		'TITLE_EXAMINATIONS_EVENT_STATUS_DISPLAYED' => 'Displayed',
		'TITLE_EXAMINATIONS_EVENT_STATUS_BOOKED' => 'Booked',
		'TITLE_VIEW_EXAMINATIONS_TEXT'=>'View investigations',
		'TITLE_EXAMINATIONS_CHECKUP_BUTTON_TEXT' => 'Add checkup',
		'TITLE_DISMISSAL_TEXT'    => 'Dismissal',
		'TITLE_SELECT_DISMISSAL_TEXT'    => 'Select Dismissal Options',

		// Titles
		'TITLE_EXAMINATION_NAME'  	=> 'Name',
		'TITLE_EXAMINATION_URL'  	=> 'URL',
		'TITLE_EXAMINATION_STATUS'  => 'Status',
		'TITLE_EXAMINATION_STATUS_ACTIVE' => 'Active',
		'TITLE_EXAMINATION_TRIGGER' => 'Trigger',
		'TITLE_EXAM_TRIGGER' => 'Trigger Exam',
		'TITLE_DOCUMENT_NAME' => 'Document',
		'TITLE_VIEW_DOCUMENT_NAME' => 'View Documents',
		'TITLE_VIEW_services_TEXT' => 'View services',
		'TITLE_DOCUMENT_TYPE' => 'Document Type',
		'TITLE_DOCUMENT_STATUS' => 'Document Status',
		'TITLE_SELECT_DOCUMENT_STATUS' => 'Select Document Status',
		'TITLE_SELECT_DOCUMENT_UNREAD' => 'Unread',
		'TITLE_SELECT_DOCUMENT_READ' => 'Read',
		'TITLE_SELECT_DOCUMENT_SIGN' => 'Sign',
		'TITLE_SELECT_CHECK_LIST_SIGNDOC' =>'SignDoc',
		'TITLE_CHECK_LIST' => 'Check List',
		'TITLE_CHECK_LIST_TYPE' => 'Type Of Checklist',
		'TITLE_CHECK_LIST_FORMTITLE' => 'Checklist information',
		'TITLE_TYPE_CHECKLIST_GENERAL'=>'General',
		'TITLE_TYPE_CHECKLIST_PERFORMANCE'=>'Service',//Performance
		'TITLE_SELECT_CHECK_LIST' => 'Select Check List',
		'CHECK_LIST_BUTTON' => 'add checklist',
		'DOCUMENT_LIST_ADD_BUTTON' => 'add Document',
		'TITLE_DOCUMENT_LIST' => 'Documents List',
		'TITLE_DOCUMENT_DATE_TIME' => 'Date/Time',
		'TITLE_DOCUMENT_PRINT' => 'Print',
		'TITLE_DOCUMENT_EMAIL' => 'Email',

		// Response error
		'FAIL_EXAM_CREATE' => "Failed to create exam, Something went wrong on server.",
		'FAIL_EXAM_DELETE' => "Failed to delete exam, Something went wrong on server.",

		// Response messages
		'EXAM_CREATED' => "Exam created successfully.",
		'EXAM_UPDATED' => "Exam updated successfully.",
		'APPOITMENT_EXAM_UPDATED' => "Examination updated successfully.",
		'EXAM_DELETED' => "Exam deleted successfully.",



	/*--------------------------------------
	|	Module name CHECK LIST
	-------------------------------------------------------------------*/
		// ERROR MESSAGES
		'ERR_CHECK_LIST_NAME_REQUIRED'  => "Checklist name field is required.",
		'ERR_CHECKLIST_INTRODUCER_TEXT_REQUIRED' => "Introducer text field is required.",
		'ERR_CHECKLIST_FINAL_TEXT_REQUIRED' 	 => "Final text field is required.",
		'ERR_CHECKLIST_QUESTION_REQUIRED' 	     => "Question field is required.",
		'ERR_CHECKLIST_HEADING_REQUIRED' 	     => "Heading section field is required.",

		'ERR_CHECKLIST_QUESTION_HEADING_REQ' 	 => "Question and heading field is required.",

		// Module Title
		'TITLE_CHECKLIST_TEXT' => 'Check List',
		'TITLE_CCHECKLIST_TEXT' => 'check-list',

		// Titles
		'TITLE_CHECKLIST_NAME'  	        => 'Checklist name',
		'TITLE_CHECKLIST_INTRODUCER_TEXT'  	=> 'Introduction text',
		'TITLE_CHECKLIST_HEADING_SECTION'  	=> 'Heading Section',
		'TITLE_CHECKLIST_FINAL_TEXT'  	    => 'Final text',
		'TITLE_CHECKLIST_QUESTION'  	    => 'Question',
		'TITLE_CHECKLIST_EXAMINATION_STATUS'          => 'Status',
		'TITLE_CHECKLIST_EXAMINATION_STATUS_ACTIVE'   => 'Active',
		'TITLE_CHECKLIST_ADD_MORE'          => 'Add More',
		'TITLE_CHECKLIST_REMOVE_MORE'       => 'Remove More',
		'TITLE_CHECKLIST_VIEW_HEADING_SECTION'       => 'View',
		'TITLE_CHECKLIST_ACTIVE_NEW' =>'Activate New',
		'TITLE_CHECKLIST_LAST_ACTIVATION' =>'CheckList Last Activation',
		'TITLE_CHECKLIST_LAST_ACTIVATION_CONFIRMATION' =>"You want to update last activation date?",


		// Response error
		'FAIL_EXAM_CREATE' => "Failed to create exam, Something went wrong on server.",
		'FAIL_EXAM_DELETE' => "Failed to delete exam, Something went wrong on server.",

		// Response messages
		'CHECK_LIST_CREATED' => "Check List created successfully.",
		'CHECK_LIST_UPDATED' => "Check List updated successfully.",
		'CHECK_LIST_DELETED' => "Check List deleted successfully.",
		'CHECK_LIST_HEADING_SECTION_DELETED' => "Check List heading section deleted successfully.",
		'CHECK_LIST_QUESTION_DELETED'        => "Question deleted successfully.",

		'CHECK_LIST_WARNING_DELETED_MSG'     => "This checklist assigned to the services! You Want to delete it?",


	/*--------------------------------------
	|	Module name PROFILES TEMPLATES
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_PROFILE_TEMPLATE_TEXT' => 'Profile Templates',

		// ERROR MESSAGES
		'ERR_PROFILE_NAME_REQUIRED' 	  => "Name field is required.",
		'ERR_PROFILE_NAME_REGEX_REQUIRED' => "Name field should be in letter\'s and number\'s only.",
		'ERR_AGE_FROM_REQUIRED' 		=> "From age field is required",
		'ERR_AGE_TO_REQUIRED' 			=> "To age field is required.",
		'ERR_EXAM_REQUIRED' 	=> "services field is required.",
		'ERR_STATUS_REQUIRED' 	=> "Status field is required.",
		'ERR_AGE_TO' => "Please select to age greater than from age.",
		'ERR_ALLREADY_SELECTED_AGE' => "This age range is allready selected.",

		// Titles
		'TITLE_PROFILE_NAME'  			=> 'Name',
		'TITLE_PROFILE_AGE_FROM'  		=> 'Age From',
		'TITLE_PROFILE_AGE_TO'  		=> 'Age To',
		'TITLE_PROFILE_EXAMINATIONS'	=> 'services',
		'TITLE_EVENT_TYPE_SERVICES'	    => 'Services Type',
		'TITLE_PROFILE_STATUS'  		=> 'Status',
		'TITLE_PROFILE_STATUS_ACTIVE' 	=> 'Active',
		'TITLE_PROFILE_NON_EXAMINATIONS' => 'Non recommended services',

		//Placeholder Title
		'TITLE_SELECT_AGE_FROM_TEXT' => 'Select Age From',
		'TITLE_SELECT_AGE_TO_TEXT' 	 => 'Select Age To',

		// Response error
		'FAIL_PROFILE_CREATE' => "Failed to create Profile Template, Something went wrong on server.",
		'FAIL_PROFILE_UPDATE' => "Failed to update Profile Template, Something went wrong on server.",
		'FAIL_PROFILE_DELETE' => "Failed to delete profile template, Something went wrong on server.",
		'ERR_SELECT_EXAM' 	  => "Please select examination.",

		// Response messages
		'PROFILE_CREATED' => "Profile Template created successfully.",
		'PROFILE_UPDATED' => "Profile Template updated successfully.",
		'PROFILE_DELETED' => "Profile Template deleted successfully",

	/*--------------------------------------
	|	Module name PATEINTS
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_PATIENT_TEXT' 	=> 'Patients',
		'TITLE_IMPORT_PATIENT' 	=> 'Import Patients Data.',
		'TITLE_IMPORT_BOOKING' 	=> 'Import Booking Data.',
		'TITLE_SELECT_FILE' 	=> 'Select File for Upload.',
		'TITLE_SELECT_GENDER'	=> 'Select Gender',
		'DATA_IMPORTED' 		=> 'Data imported successfully.',
		'TITLE_SELECT_SALUTATION' => 'Select Salutation',
		'TITLE_MR_SALUTATION'    => 'Mr.',
		'TITLE_MRS_SALUTATION'   => 'Mrs.',
		'TITLE_REMINDER_APPOINTMENT_DATE' => 'Appointment Date',
		"TITLE_REMINDER_DATE"    => 'Reminder Date',
		"TITLE_REMINDER_TYPE"    => 'Reminder Type',
		"TITLE_REMINDER_MEDIA"   => 'Media',
		"TITLE_FUTURE_REMINDER_DATE" => 'Future Reminder Date',
		"TITLE_REMINDER_READ_STATUS" => 'Read Status',

		// ERROR MESSAGES
		'ERR_FIRST_NAME_REQUIRED' 		=> "First Name field is required.",
		'ERR_FIRST_NAME_REGEX_REQUIRED'	=> 'First Name field should be in letter\'s only.',
		'ERR_FAMILY_NAME_REQUIRED' 		=> "Last Name field is required.",
		'ERR_FAMILY_NAME_REGEX_REQUIRED' 	=> 'Family Name field should be in letter\'s only.',
		'ERR_PATIENT_EMAIL_ADDRESS' 	=> "Email Address field is required.",
		'ERR_MOBILE_NO_REQUIRED' 		=> "Mobile Number field is required.",
		'ERR_FORMAT_MOBILE_USER' 		=> "The mobile number must be a number.",
		'ERR_COUNTRY_CODE_REQUIRED' 	=> "Country Code field is required.",
		'ERR_COUNTRY_CODE_WRONG' 	=> "Invalid Country Code Entered.",
		'ERR_MOBILE_NO_FORMAT' 			=> "Enter correct mobile number format.",
		'ERR_MOBILE_NOUMBERNOTSTARTWITHZERO' => "Enter valid mobile number and should not start with zero.",

		//'ERR_MOBILE_UNIQUE' 			=> "This mobile number is already assigned to another patient. Please enter another number.",  //commented on 15-dec-23 - for duplicate patient

		'ERR_BIRTH_DATE_UNIQUE'			=> "Data is occupied already. Please login as existing patient or change your data.",
		'ERR_BIRTH_DATE_REQUIRED' 		=> "Birth Date field is required.",
		'ERR_ROAD_REQUIRED' 			=> "Street field is required.",
		'ERR_PLACE_REQUIRED' 			=> "City field is required.",
		'ERR_PASSWORD_REQUIRED' 		=> "Password field is required.",
		'ERR_GANY_PATIENT_ID_REQUIRED' 	=> "Gany Patient Id field is required.",
		'ERR_PATIENT_FAMILY_NAME_REQUIRED'=> "Family Name field is required.",
		'ERR_PATIENT_POSTAL_CODE_REQUIRED'		=> "Postal Code field is required.",
		'ERR_PATIENT_POSTAL_CODE_NUMERIC_REQUIRED' => "Please enter number only.",
		'ERR_PATIENT_GENDER_REQUIRED'	=> "Gender field is required.",
		'ERR_PATIENT_SIZE_REQUIRED'		=> "Size field is required.",
		'ERR_PATIENT_WEIGHT_REQUIRED'	=> "Weight field is required.",
		'ERR_PATIENT_TITLE_REQUIRED'	=> "Title field is required.",
		'ERR_PATIENT_SALUTATION_REQUIRED'=> "Salutation field is required.",
		'ERR_PATIENT_FAMILY_DOCTOR_REQUIRED'=> "Family Doctor field is required.",
		'ERR_FAMILY_DOCTOR_NAME'	=> 'Family Doctor name should be in letter\'s only.',

		'ERR_PATIENT_ENSURANCE_NUMBER_REQUIRED'		=> "Insurance Number field is required.",
		'ERR_PATIENT_ADDITIONAL_ENSURANCE_REQUIRED'	=> "Additional Insurance field is required.",
		'ERR_PATIENT_ENSURANCE_NUMBER_DIGITS_REQUIRED'	=> 'Insurance Number field should accept only four digit\'s.',
		'ERR_PATIENT_FINDING_NOTE'	=> 'As soon as the "save" button is clicked, the request is displayed in the todo list of assistant dashboard',

		// Titles
		'TITLE_PATIENT_NAME' 			=> 'Name',
		'TITLE_PATIENT_NAME_IN_MAIL' 	=> 'Patient name',
		'TITLE_PATIENT_FIRST_NAME'  	=> 'First Name',
		'TITLE_PATIENT_LAST_NAME'  		=> 'Last Name',
		'TITLE_PATIENT_EMAIL'  			=> 'Email Address',
		'TITLE_PATIENT_MOBILE_NO'  		=> 'Mobile Number',
		'TITLE_PATIENT_COUNTRY_CODE' 	=> 'Country Code',
		'TITLE_PATIENT_BIRTH_DATE'		=> 'Birth Date',
		'TITLE_PATIENT_ROAD'			=> 'Road',
		'TITLE_PATIENT_PLACE'			=> 'Place',
		'TITLE_PATIENT_PASS'  			=> 'Password',
		'TITLE_PATIENT_GANY_PATIENT_ID'	=> 'Gany Patient Id',
		'TITLE_PATIENT_IS_BLOCK'		=> 'Is Blocked',
		'TITLE_PATIENT_BLOCK'			=> 'Block',
		'TITLE_PATIENT_STATUS'			=> 'Status',
		'TITLE_PATIENT_STATUS_ACTIVE' 	=> 'Active',
		'TITLE_PATIENT_FAMILY_NAME'		=> 'Family Name',
		'TITLE_PATIENT_POSTAL_CODE'		=> 'Postal Code',
		'TITLE_PATIENT_GENDER'			=> 'Gender',
		'TITLE_PATIENT_SIZE'			=> 'Size',
		'TITLE_PATIENT_WEIGHT'			=> 'Weight',
		'TITLE_PATIENT_TITLE'			=> 'Title',
		'TITLE_PATIENT_SALUTATION'		=> 'Salutation',
		'TITLE_PATIENT_FAMILY_DOCTOR'	=> 'Family Doctor',
		'TITLE_PATIENT_ENSURANCE_NUMBER'=> 'Insurance Number',
		'TITLE_PATIENT_TODO_ENSURANCE_NUMBER'=> 'Insurance Number',
		'TITLE_PATIENT_TODO_MOBILE_NO'=> 'Handynr.',
		'TITLE_PATIENT_ADDITIONAL_ENSURANCE' => 'Additional Insurance',
		'TITLE_GANY_PATIENT_ID' => 'GanyMED Id',
		'TITLE_GANY_PAT_NR' 			=> 'Pat Nr',
		'TITLE_PATIENT_NOTE' 			=> 'Notes on the report request',
		'TITLE_PATIENT_STREET_NO' 		=> 'Street No',
		// 'TITLE_REMINDER_ACTIVE' 		=> 'Reminder Active',//commented on 30-july-25 for #340
		'TITLE_REMINDER_ACTIVE' 		=> 'Send performance recommendations',//added on 30-july-25


		'TITLE_PATIENT_CREATE' => 'Create an appointment for a new patient',
		// 'TITLE_ON' 		=> 'On', //Commented by Shyam 21-02-22
		'TITLE_ON' 		=> 'Active', //Added by Shyam 21-02-22


		// Response error
		'FAIL_PATIENT_CREATE' => "Failed to create Patient, Something went wrong on server.",
		'FAIL_PATIENT_UPDATE' => "Failed to update Patient, Something went wrong on server.",
		'FAIL_PATIENT_DELETE' => "Failed to delete Patient, Something went wrong on server.",

		// Response messages
		'PATIENT_CREATED' => "Patient created successfully.",
		'PATIENT_UPDATED' => "Patient updated successfully.",
		'PATIENT_DELETED' => "Patient deleted successfully.",

	/*--------------------------------------
	|	Module name MENUS SETTINGS
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_MENU_SETTING_TEXT' => 'Menus Settings',

		// Titles
		'TITLE_MENU_NAME' 	=> 'Name',
		'TITLE_MENU_URL' 	=> 'URL',
		'TITLE_MENU_STATUS'	=> 'Status',
		'TITLE_MENU_STATUS_ACTIVE' => 'Active',

		// ERROR MESSAGES
		'ERR_MENU_NAME_REQUIRED' => "Name field is required." ,
		'ERR_MENU_URL_REQUIRED'  => "URL field is required.",

		// Response error
		'FAIL_MENU_CREATE' => "Failed to create Menu, Something went wrong on server.",
		'FAIL_MENU_UPDATE' => "Failed to update Menu, Something went wrong on server.",
		'FAIL_MENU_DELETE' => "Failed to delete Menu, Something went wrong on server.",

		// Response messages
		'MENU_CREATED' => "Menu created successfully.",
		'MENU_UPDATED' => "Menu updated successfully.",
		'MENU_DELETED' => "Menu deleted successfully.",

		/*--------------------------------------
	|	Module name SUPPORT SETTINGS
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SUPPORT_SETTING_TEXT' => 'Manual Setting',
		// Titles
		'TITLE_SUPPORT_NAME' 	=> 'Name',
		'TITLE_SUPPORT_URL' 	=> 'URL',
		'TITLE_SUPPORT_STATUS'	=> 'Status',
		'TITLE_SUPPORT_STATUS_ACTIVE' => 'Active',
		// ERROR MESSAGES
		'ERR_SUPPORT_NAME_REQUIRED' => "Name field is required." ,
		'ERR_SUPPORT_URL_REQUIRED'  => "URL field is required.",
		// Response error
		'FAIL_SUPPORT_CREATE' => "Failed to create manual setting, Something went wrong on server.",
		'FAIL_SUPPORT_UPDATE' => "Failed to update manual setting, Something went wrong on server.",
		'FAIL_SUPPORT_DELETE' => "Failed to delete manual setting, Something went wrong on server.",
		// Response messages
		'SUPPORT_CREATED' => "Manual setting created successfully.",
		'SUPPORT_UPDATED' => "Manual setting updated successfully.",
		'SUPPORT_DELETED' => "Manual setting deleted successfully.",
		/*--------------------------------------
		|	Module name MANAGE ORDINATION
		-------------------------------------------------------------------*/
		// Module Title
		'TITLE_ORDINATION_TEXT' => 'Ordinations',

		// Titles
		'TITLE_ORDINATION_NAME' => 'Name',
		'TITLE_ORDINATION_LOGO' 	=> 'Logo',
		'TITLE_ORDINATION_LINK' 	=> 'Verknüpfung',
		'TITLE_ORDINATION_TEXT_COLOR_CODE' 	=> 'Text Code',

		'TITLE_ORDINATION_BUTTONE_COLOR_CODE' 	=> 'Button Code',
		'TITLE_ORDINATION_SCREEBBG_COLOR_CODE' 	=> 'Screen Background Code',
		'TITLE_ORDINATION_APP_BAR_COLOR_CODE' 	=> 'App bar Code',
		'TITLE_ORDINATION_TAB_SELECTION_COLOR_CODE' 	=> 'Tabs selection Code',
		'TITLE_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE' 	=> 'Home Screen Option Code',
		'TITLE_ORDINATION_MENU_HEARDER_COLOR_CODE' 	=> 'Menu Header Code',
		'TITLE_ORDINATION_DARK_TEXT_COLOR_CODE' 	=> 'Dark Text Code',
		'TITLE_ORDINATION_MENU_BG_COLOR_CODE' 	=> 'Menu Background Code',
		'TITLE_ORDINATION_LIGHT_TEXT_COLOR_CODE' 	=> 'Light Text Code',

		'TITLE_ORDINATION_BACKGROUND_COLOR_CODE' 	=> 'Background Code',
		'TITLE_ORDINATION_LOGIN_USER' 	=> 'Login User',
		'TITLE_ORDINATION_STATUS'	             => 'Status',
		'TITLE_SELECT_ORDINATION_LOGING_USER'    => 'Select Ordinations User',
		'TITLE_ORDINATION_HAS_SPECIALIST'        => 'Specialist',
		'TITLE_SELECT_ORDINATION_HAS_SPECIALIST' => 'Select Specialist',
		'TITLE_ORDINATION_ADDRESS' => 'Address',
		// 'TITLE_MENU_STATUS_ACTIVE' => 'Active',

		// ERROR MESSAGES
		'ERR_ORDINATION_NAME_REQUIRED' => "Name field is required." ,
		'ERR_ORDINATION_LOGO'  => "Logo field is required.",
		'ERR_ORDINATION_TEST_COLOR_CODE'  => "Test color code field is required.",

		'ERR_ORDINATION_BUTTONE_COLOR_CODE'  => "Button color code field is required.",
		'ERR_ORDINATION_SCREEN_COLOR_CODE'  => "Screen background color code field is required.",
		'ERR_ORDINATION_APP_BAR_COLOR_CODE'  => "App bar color code field is required.",
		'ERR_ORDINATION_TABS_SELECTION_COLOR_CODE'  => "Tabs selection color code field is required.",
		'ERR_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE'  => "Home Screen option color code field is required.",
		'ERR_ORDINATION_MENU_HEARDER_COLOR_CODE'  => "Menu header color code field is required.",
		'ERR_ORDINATION_MENU_BG_COLOR_COLOR_CODE'  => "Menu background color code field is required.",
		'ERR_ORDINATION_DARK_TEXT_COLOR_CODE'  => "Dark text color code field is required.",
		'ERR_ORDINATION_LIGHT_TEXT_COLOR_CODE'  => "Light text color code field is required.",

		'ERR_ORDINATION_BACKGROUND_COLOR_CODE'  => "Background color code field is required.",
		'ERR_ORDINATION_LOGIN_USER'  => "Login user field is required.",
		'ERR_ORDINATION_NAME_UNIQUE_REQUIRED'	=> "Name has already taken.",
		'ERR_ORDINATION_ADDRESS_REQUIRED'	=> "Address field is required.",
		'ERR_ORDINATION_CALENDAR_ID_REQUIRED'	=> "Calender id field is required.",
		'ERR_ORDINATION_CALENDAR_ID_DUP'	=> "Calender id has already taken.",


		// Response error
		'FAIL_ORDINATION_CREATE' => "Failed to create Menu, Something went wrong on server.",
		'FAIL_ORDINATION_UPDATE' => "Failed to update Menu, Something went wrong on server.",
		'FAIL_ORDINATION_DELETE' => "Failed to delete Menu, Something went wrong on server.",

		// Response messages
		'ORDINATION_CREATED' => "Ordinations created successfully.",
		'ORDINATION_UPDATED' => "Ordinations updated successfully.",
		'ORDINATION_DELETED' => "Ordinations deleted successfully.",

		/*--------------------------------------
		|	Module name Specialist MANAGE
		-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SPECIALIST_TEXT' => 'Specialist',
		'TITLE_SPECIALIST_DOCUMENT_TEXT' => 'Document',
		'TITLE_SPECIALIST_SELECT_TEXT' => 'Select Specialist',
		'TITLE_SPECIALIST_NOT_EXIST' => 'Currently Specialist is not exist.',


		// Titles
		'TITLE_SPECIALIST_NAME'   => 'Name',
		'TITLE_SPECIALIST_STATUS' => 'Status',
		'TITLE_SPECIALIST_ACTIVE' => 'Active',

		// ERROR MESSAGES
		'ERR_SPECIALIST_NAME_REQUIRED' => "Name field is required." ,


		// Response error
		'FAIL_SPECIALIST_CREATE' => "Failed to create specialist, Something went wrong on server.",
		'FAIL_SPECIALIST_UPDATE' => "Failed to update specialist, Something went wrong on server.",
		'FAIL_SPECIALIST_DELETE' => "Failed to delete specialist, Something went wrong on server.",

		// Response messages
		'SPECIALIST_CREATED' => "Specialist created successfully.",
		'SPECIALIST_UPDATED' => "Specialist updated successfully.",
		'SPECIALIST_DELETED' => "Specialist deleted successfully.",
		'SPECIALIST_ASSIGNED'=> "Specialist assigned successfully.",

		/*--------------------------------------
		|	Module name Document MANAGE
		-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SPECIALIST_DOCUMENT_TEXT' => 'Documents',

		// Titles
		'TITLE_SPECIALIST_DOCUMENT_NAME'   => 'Name',
		'TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT'   => 'Type Of Document',
		'TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT_SELECT'   => 'Select Type Of Document',
		//'TITLE_SPECIALIST_DOCUMENT_HTML_TEXT'   => 'Html Text', //aishwarya commented on 30-5-25
		'TITLE_SPECIALIST_DOCUMENT_HTML_TEXT'   => 'Description',//Aishwarya added text on 30-5-25

		'TITLE_SPECIALIST_DOCUMENT_HEADER_IMAGE'   => 'Header Image',
		'TITLE_SPECIALIST_DOCUMENT_FOOTER_IMAGE'   => 'Footer Image',
		'TITLE_SPECIALIST_DOCUMENT_BACKGROUND_COLOR'   => 'Background Color',
		'TITLE_SPECIALIST_DOCUMENT_FREQUENCY'   => 'Frequency',
		'TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE'   => 'Frequency Type',
		'TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION'   => 'Date Of Last Activation',
		'TITLE_SPECIALIST_DOCUMENT_STATUS' => 'Status',
		'TITLE_SPECIALIST_DOCUMENT_ACTIVE' => 'Active',
		'TITLE_GENERAL'=>'General',
		'TITLE_SERVICE'=>'Service',
		'TITLE_FREQUENCY_TYPE_SELECT'  =>'Select Frequency type',
		'TITLE_FREQUENCY_DAY'  =>'Day',
		'TITLE_FREQUENCY_MONTH'=>'Month',
		'TITLE_FREQUENCY_YEAR' =>'Year',
		'TITLE_FREQUENCY_WEEK' =>'Week',



		// ERROR MESSAGES
		'ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED' => "Name field is required." ,
		'ERR_SPECIALIST_DOCUMENT_NAME_UNIQUE_REQUIRED' => "Name field should unique.",
		'ERR_DOCUMENT_TYPE_OF_DOCUMENT'         => 'Type Of document filed is required.',
		'ERR_DOCUMENT_HEADER_IMAGE'             => 'Header Image filed is required.',
		'ERR_DOCUMENT_FOOTER_IMAGE'             => 'Footer Image filed is required.',
		'ERR_DOCUMENT_DATE_OF_ACTIVATION'       => 'Date of activation filed is required.',
		//'ERR_DOCUMENT_HTML_TEXT'                => 'HTML text filed is required.',//Aishwarya commented on 30-5-25
		'ERR_DOCUMENT_HTML_TEXT'                => 'Description filed is required.',//Aishwarya added on 30-5-25

		'ERR_DOCUMENT_BACKGROUND_COLOR'         => 'Background color filed is required.',
		'ERR_DOCUMENT_FREQUENCY'         => 'Frequency filed is required.',
		'ERR_DOCUMENT_FREQUENCY_TYPE'         => 'Frequency type filed is required.',


		// Response error
		'FAIL_SPECIALIST_DOCUMENT_CREATE' => "Failed to create specialist, Something went wrong on server.",
		'FAIL_SPECIALIST_DOCUMENT_UPDATE' => "Failed to update specialist, Something went wrong on server.",
		'FAIL_SPECIALIST_DOCUMENT_DELETE' => "Failed to delete specialist, Something went wrong on server.",

		// Response messages
		'SPECIALIST_DOCUMENT_CREATED' => "Document created successfully.",
		'SPECIALIST_DOCUMENT_UPDATED' => "Document updated successfully.",
		'SPECIALIST_DOCUMENT_DELETED' => "Document deleted successfully.",

		'TITLE_DOCUMENTLIST_TEXT' => 'Document List',

	/*--------------------------------------
	|	Module name ROSTER
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_ROSTER_MODULE'  			=> 'Duty Roster',
		'TITLE_ROSTER_DOCTOR' 			=> 'Doctor',
		'TITLE_ROSTER_SELECT_DOCTOR' 	=> 'Select Doctor',
		// 'TITLE_ROSTER_DATE' 	=> 'Date',
		// 'TITLE_ROSTER_TIME_FROM' 	=> 'Select Doctor',
		// 'TITLE_ROSTER_TIME_TO' 	=> 'Select Doctor',
		'TITLE_DEFAULT_TIME_DURATION' 	=> 'Default Time duration (In Mins)',
		'TITLE_ROSTER_APPOINTMENT_TYPE' => 'Appointment Type',
		'TITLE_ROSTER_SELECT_APPOINTMENT_TYPE' => 'Select Appointment Type',
		'TITLE_SELECT_TIME_FRAME_TEXT' => 'Select Time Frame',

		'TITLE_ROSTER_DATE_HEADING'	=> 'Weekwise Time Frames',
		'TITLE_ROSTER_DATE'	 		=> 'Date',
		'TITLE_ROSTER_TIME_FROM'	 	=> 'Time From',
		'TITLE_ROSTER_TIME_TO'	 		=> 'Time To',
		'TITLE_ROSTER_TIME_FRAME'	 	=> 'Time Frames',

		//new added
		'TITLE_ROSTER_WEEKDAY'	 		=> 'Weekday',
		'TITLE_ROSTER_STARTDATE'	 	=> 'Start Date',
		'TITLE_ROSTER_ENDDATE'	 		=> 'End Date',
		'TITLE_ROSTER_EXCLUDE'	 		=> 'Excluded Date',
		'TITLE_ROSTER_INCLUDE'	 		=> 'Included Date',
		'TITLE_ROSTER_EXCLUDE_INCLUDE'	=> 'Excluded and Included Date Series',
		'TITLE_ROSTER_WEEK_MISMATCH'	=> 'Weekday does not match date',

		// ERROR MESSAGES
		'ERR_DOCTOR_ID_REQUIRED' 	=> "Doctor field is required.",
		'ERR_APPOINTMENT_TYPE_ID_REQUIRED' => "Appointment type field is required.",
		'ERR_WEEKDAY_REQUIRED' 		=> "Weekday field is required.",
		'ERR_TIME_FROM_REQUIRED' 	=> "Time from field is required.",
		'ERR_TIME_TO_REQUIRED' 		=> "Time to field is required.",
		'ERR_TIME_FRAME_REQUIRED' 	=> "Time frame field is required.",
		'ERR_TIME_FRAME_NOT_FOUND' 	=> "Doctor is not available on selected date.",
		//'ERR_TIME_FRAME_NOT_FOUND' 	=> "Please add duty roster for selected doctor.",

		//new added
		'ERR_TIME_FRAME_EXIST_DATE' => "Date",
		'ERR_TIME_FRAME_EXIST' 	=> "and its From and To time is already exist in another roster.",

		// Response error
		'FAIL_ROSTER_CREATE' => "Failed to create Roster, Something went wrong on server.",
		'FAIL_ROSTER_UPDATE' => "Failed to update Roster, Something went wrong on server.",
		'FAIL_ROSTER_DELETE' => "Failed to delete roster, Something went wrong on server.",

		//new added
		'FAIL_ROSTER_EXCLUDED_DATES' => "Failed to get roster excluded dates, Something went wrong on server.",
		'FAIL_ROSTER_EXCLUDED_DATE_UPDATE' => "Failed to update roster excluded date, Something went wrong on server.",

		// Response messages
		'ROSTER_CREATED' => "Roster created successfully.",
		'ROSTER_WEEK_DAYS_ERROR' => "Please select a day of the week.",
		'ROSTER_UPDATED' => "Roster updated successfully.",
		'ROSTER_DELETED' => "Roster deleted successfully.",
		//new added
		'ROSTER_EXCLUDED_DATES' => "Roster excluded dates found successfully.",
		'ROSTER_CANNOT_CHANGE' => "The duty roster time frames cannot be changed because it has already been booked.",

	/*--------------------------------------
	|	Module name APPOINTMENT
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_APPOINTMENT_TEXT' => 'Appointment',
		'TITLE_CURRENT_APPOINTMENT_TEXT' => "Doctor's dashboard",

		// ERROR MESSAGES
		'ERR_APPOINTMENT_PATIENT_REQUIRED' 	=> "Patient field is required.",
		'ERR_APPOINTMENT_DOCTOR_REQUIRED' 	=> "Doctor field is required",
		'ERR_APPOINTMENT_TYPE_REQUIRED' 	=> "Appointment type field is required",
		'ERR_APPOINTMENT_DATE_REQUIRED' 	=> "Date field is required",
		'ERR_APPOINTMENT_TIME_FRAME_REQUIRED' 	=> "Time Frame field is required.",

		// Titles
		'TITLE_APPOINTMENT_START_DATE' 	=> 'Start Date',
		'TITLE_APPOINTMENT_END_DATE' 	=> 'End Date',
		'TITLE_APPOINTMENT_PATIENT' 	=> 'Patient',
		'TITLE_APPOINTMENT_DOCTOR'  	=> 'Doctor',
		'TITLE_APPOINTMENT_TYPE' 		=> 'Appointment type',
		'TITLE_APPOINTMENT_DATE' 		=> 'Date',
		'TITLE_APPOINTMENT_NOTE'		=> 'Notes',
		'TITLE_DASHBOARD_NOTE'			=> 'Notes',
		'TITLE_APPOINTMENT_STATUS'		=> 'Status',
		'TITLE_APPOINTMENT_STATUS_ACTIVE' 	=> 'Active',
		'TITLE_APPOINTMENT_SERVICES'=>'Services',
		// 'TITLE_NOTIFICATION_TEXT'	=> 'Notification',

		//Placeholder Title
		'TITLE_SELECT_PATIENT' => 'Select Patient',
		'TITLE_SELECT_DOCTOR'  => 'Select Doctor',
		'TITLE_SELECT_TYPE'    => 'Select Appointment Type',
		'TITLE_APPOINTMENT_TIME_FRAME'	=> 'Time Frame',

		// Response error
		'FAIL_APPOINTMENT_CREATE' => "Failed to create Appointment, Something went wrong on server.",
		'FAIL_APPOINTMENT_UPDATE' => "Failed to update Appointment, Something went wrong on server.",
		'FAIL_APPOINTMENT_DELETE' => "Failed to delete Appointment, Something went wrong on server.",
		'FAIL_APPOINTMENT_TIME_FRAME' => "Failed to get doctor time frames, Something went wrong on server.",

		// Response messages
		'APPOINTMENT_CREATED' => "Appointment created successfully.",
		'APPOINTMENT_UPDATED' => "Appointment updated successfully.",
		'APPOINTMENT_DELETED' => "Appointment deleted successfully.",
		'APPOINTMENT_SLOT_ALREADY_EXIST' => "Appointment slot is already booked.",

	/*--------------------------------------
	|	Module name Notification
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_NOTIFICATION_TEXT'	=> 'Notification',
		'TITLE_NOTIFICATION_DEFAULT'	=> 'Default Notification for all Patients',

		// Titles
		'TITLE_PATIENT_NAME' 		=> 'Patient Name',
		'TITLE_NOTIFY_DATE'  		=> 'Notify Date',
		'TITLE_NOTIFY_TIME'  		=> 'Notify Time',
		'TITLE_TITLE_TEXT'  		=> 'Title',
		'TITLE_CONTENT_TEXT'		=> 'Content',
		'TITLE_STATUS_TEXT' 		=> 'Status',
		'TITLE_STATUS_UPDATE_ALL' 		=> 'Update for All',
		'TITLE_SELECT_STATUS_TEXT' 		=> 'Select Status',

		// ERROR MESSAGES
		'ERR_NOTIFICATION_PATIENT_NAME_REQUIRED' 	=> "Patient Name field is required.",
		'ERR_NOTIFICATION_DATE_REQUIRED' 	=> "Notify Date field is required",
		'ERR_NOTIFICATION_TIME_REQUIRED' 	=> "Notify Time field is required",
		'ERR_NOTIFICATION_TITLE_REQUIRED' 	=> "Title field is required",
		'ERR_NOTIFICATION_CONTENT_REQUIRED' 	=> "Content field is required",
		'ERR_NOTIFICATION_STATUS_REQUIRED' 	=> "Status field is required",

		// Response error
		'FAIL_NOTIFICATION_UPDATE' => "Failed to update Notification, Something went wrong on server.",

		// Response messages
		'NOTIFICATION_UPDATED' => "Notification updated successfully.",


	/*--------------------------------------
	|	Module name WAITING QUEUE SYSTEM
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_WAITING_SCREEEN' => "Waiting Screen",
		'TITLE_WAITING_QUEUE_TEXT' => "Waiting Queue Numbers",
		'TITLE_WAITING_NUMBER_SYMBOLS_TEXT' => "Waiting Number Symbols",
		'WAITING_NUMBER_DELETED' => 'Waiting Queue Number deleted successfully.',

		// Titles
		'TITLE_WAITING_PATIENT_NAME' => "Patient Name",
		'TITLE_WAITING_PATIENT_DOCTOR_NAME' => "Doctor Name",
		'TITLE_WAITING_PATIENT_APPOINTMENT_TYPE_NAME' => "Appointment Type",
		'TITLE_WAITING_PATIENT_APPOINTMENT_TIME' => "Appointment Time",
		'TITLE_WAITING_PATIENT_APPOINTMENT_STATUS' => "Appointment Status",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER' => "Queue Number",
		'TITLE_WAITING_TYPE' => "Typ",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER_TYPE' => "Queue Number Type",
		'TITLE_WAITING_PATIENT_SCAN_TIME' => "Scan Time",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER_CREATED_AT' => "Queue Number Created at",
		'TITLE_WAITING_PATIENT_STATUS' => "Status",

		'TITLE_WAITING_SYMBOL_NAME' => "Name",
		'TITLE_WAITING_SYMBOL_URL' => "URL",



	/*--------------------------------------
	|	Module name DIAGNOSTIC FINDING TYPE
	-----------------------------------------------------------*/
		// Module Title
		'TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT' => 'Diagnostic Finding Types',

		// ERROR MESSAGES
		'ERR_DIAGNOSTIC_FINDING_TYPES_NAME_REQUIRED' 	=> "Name field is required.",
		'ERR_DIAGNOSTIC_FINDING_TYPES_COLOR_REQUIRED' 	=> "colour field is required.",

		// Titles
		'TITLE_DIAGNOSTIC_FINDING_TYPES_NAME' 			=> 'Name',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_COLOR'  		=> 'colour',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_STATUS'  		=> 'Status',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_STATUS_ACTIVE' 	=> 'Active',

		// Response error
		'FAIL_DIAGNOSTIC_FINDING_TYPE_CREATE' => "Failed to create Diagnostic Finding Type, Something went wrong on server.",
		'FAIL_DIAGNOSTIC_FINDING_TYPE_UPDATE' => "Failed to update Diagnostic Finding Type, Something went wrong on server.",
		'FAIL_DIAGNOSTIC_FINDING_TYPE_DELETE' => "Failed to delete Diagnostic Finding Type, Something went wrong on server.",

		// Response messages
		'DIAGNOSTIC_FINDING_TYPE_CREATED' => "Diagnostic Finding Type created successfully.",
		'DIAGNOSTIC_FINDING_TYPE_UPDATED' => "Diagnostic Finding Type updated successfully.",
		'DIAGNOSTIC_FINDING_TYPE_DELETED' => "Diagnostic Finding Type deleted successfully.",

		'ADD_APPOINTMENT' => "Add Appointment",
		'APPOINTMENT_DETAILS' => "Appointment Details",
		'APPOINTMENT_INFORMATION'=>"Appointment Information",
		'EDIT_APPOINTMENT'=>"Edit Appointment",
		'NO_RESULT_FOUND'=>"No Result Found",
		'PLEASE_WAIT'=>"Please wait result loading..",


		'ERR_BEACONS_UUID_REQUIRED' => "ProximityUUID id is required",
		'ERR_BEACONS_MAJOR_REQUIRED' => "Major Beacons is required",
		'ERR_BEACONS_IDENTIFIER_REQUIRED' => "Beacons identifier is required",
		'ERR_BEACONS_MINOR_REQUIRED' => "Minor Beacons is required",
		'ERR_KEYWORD_REQUIRED' => "Keyword is required",

		'TITLE_IDENTIFIER' => 'Identifier',
		'TITLE_UUID' => 'ProximityUUID',
		'TITLE_MAJOR' => 'Major',
		'TITLE_MINOR' => 'Minor',
		'FINDINGS_TITLE' 				=> 'Keyword',
		'WHITELIST_STATUS'				=> 'Whitelist',
		'BLACKLIST_STATUS'				=> 'Blacklist',


		'ERR_DISMISSAL_REQUIRED'       => "Dismissal field is required",
		"TITLE_NEW_WINDOW"  => 'New Window',
		//Setting Options
		"IMPORT SETTING" => "IMPORT_SETTING",
		"SEND_FINDING_VIA_EMAIL" => "SEND_FINDING_VIA_EMAIL",
		"DISMISSAL" => "DISMISSAL",
		"FINDING_KEYWORDS" => "FINDING_KEYWORDS",
		"EXPORT_PATH" => "EXPORT_PATH",
		"BEACONS" => "BEACONS",
		"ADMINISTRATOR_EMAIL" => "ADMINISTRATOR_EMAIL",
		"AUDIO_SOUND_FILE" => "AUDIO_SOUND_FILE",
		"HOSPITAL_DISTANCE" => "HOSPITAL_DISTANCE",
		"TAB_PIN" => "TAB_PIN",
		"GDPR_CONTENT" => "GDPR_CONTENT",
		"TIME_SLOTS_DURATION" => "TIME_SLOT_DURATION",
		"ORDINATION_EMAIL_ADDRESS" => "ORDINATION_EMAIL_ADDRESS",
		"HOSPITAL_WAITING_MINUTES" => "HOSPITAL_WAITING_MINUTES",
		"HOSPITAL_LONGITUDE" => "HOSPITAL_LONGITUDE",
		"HOSPITAL_LATITUDE" => "HOSPITAL_LATITUDE",
		"EMERGENCY_BUTTON_SUBJECT" => "EMERGENCY_BUTTON_SUBJECT",
		"EMERGENCY_BUTTON_EMAIL_ADDRESS" => "EMERGENCY_BUTTON_EMAIL_ADDRESS",
		"APP_LOGGED_OUT_IMAGE_LINK" => "APP_LOGGED_OUT_IMAGE_LINK",
		"APP_LOGGED_IN_IMAGE_LINK" => "APP_LOGGED_IN_IMAGE_LINK",
		"APP_LOGGED_OUT_SALUTATION_TEXT" => "APP_LOGGED_OUT_SALUTATION_TEXT",
		"APP_LOGGED_IN_SALUTATION_TEXT" => "APP_LOGGED_IN_SALUTATION_TEXT",
		"APP_LOGGED_MINS" => "APP_LOGGED_MINS",
		"TITLE_DISMISSAL_BUTTON_NAME" => 'Do you really want to dismiss the patient?',
		"TITLE_SETTING_WIDTH" => "Width (px)",
		"TITLE_SETTING_HEIGHT" => "Height (px)",
		"TITLE_SETTING_POSITION" => "Position",
		"ERR_SETTING_NUMBER_VALIDATION" => "Value should be in number foramt only.",
		"ERR_SETTING_HEIGHT_REQUIRED" => 'Setting height field is required.',
		"ERR_SETTING_WIDTH_REQUIRED" => 'Setting width field is required.',

		// assigned to specilist
		'TITLE_WARNING_CONFIRM' => 'Confirm',
		'TITLE_WARNING_MSG'     => 'You want to add.',
		'SPECIALIST_RECORD_ERROR'     => 'Please select at list one specialist.',
		'TITLE_SHOW_AS_CONTROL' 	=> 'Show as control',
		'TITLE_SHOW_AS_REMINDER' 	=> 'Show as Reminder',
		'TITLE_CONTROL' 	=> 'Control',
		'TITLE_ERR_SPECIALIST' =>"Please select specialist",
		'SHOW_EXAMINATION' => 'Show Service',
		'HIDE_EXAMINATION' => 'Hide Service',

/*--------------------------------------
	|	Module name SUPPORT SETTINGS
	-------------------------------------------------------------------*/

		// Module Title
		'TITLE_SUPPORT_SETTING_TEXT' => 'Manual Setting',

		// Titles
		'TITLE_SUPPORT_NAME' 	=> 'Name',
		'TITLE_SUPPORT_URL' 	=> 'URL',
		'TITLE_SUPPORT_APK' 	=> 'APK',
		'TITLE_SUPPORT_STATUS'	=> 'Status',
		'TITLE_SUPPORT_STATUS_ACTIVE' => 'Active',

		// ERROR MESSAGES
		'ERR_SUPPORT_NAME_REQUIRED' => "Name field is required." ,
		'ERR_SUPPORT_URL_REQUIRED'  => "URL field is required.",

		// Response error
		'FAIL_SUPPORT_CREATE' => "Failed to create manual setting, Something went wrong on server.",
		'FAIL_SUPPORT_UPDATE' => "Failed to update manual setting, Something went wrong on server.",
		'FAIL_SUPPORT_DELETE' => "Failed to delete manual setting, Something went wrong on server.",

		// Response messages
		'SUPPORT_CREATED' => "Manual setting created successfully.",
		'SUPPORT_UPDATED' => "Manual setting updated successfully.",
		'SUPPORT_DELETED' => "Manual setting deleted successfully.",

		'INTERACTION_DATE' => "Interaction Date",


 	/*--------------------------------------
	|	Added By Shyam
	-------------------------------------------------------------------*/

 		'DELETE_IMAGE_MSG' => "Delete Image",
 		'DELETE_IMAGE_ALERT' => "You want to delete this image?",
		'MAIL_STATUS_SUCCESS' => "Email Status Unblock Successfully.",
		'MAIL_STATUS_FAIL' => "Email Status Block Successfully.",
		'SMS_STATUS_SUCCESS' => "SMS Status Unblock Successfully.",
		'SMS_STATUS_FAIL' => "SMS Status Block Successfully.",
		'TITLE_EXAMINATION_SORTINGORDER' => "Sorting Order",
		'ERR_TITLE_EXAMINATION_SORTINGORDER_REQUIRED'=>'Sorting Order field is required.',
		'TITLE_SORTORDER' => 'Sort Order',
		'SEND_MAIL' => 'Send Mail',
		'SEND_SMS' => 'Send SMS',
		'SEND_DOC_MAIL_CONTENT' => 'Please kindly check below finding attachment',
		'REGARDS' => 'Regards',
		'YOUR_PUREGYN_TEAM' => 'Your PureGyn Team',
		'ERR_TODO_LIST_COMMENT' =>'Note is required',
		'January'=>'January',
		'February'=>'February',
		'March'=>'March',
		'April'=>'April',
		'May'=>'May',
		'June'=>'June',
		'July'=>'July',
		'August'=>'August',
		'September'=>'September',
		'October'=>'October',
		'November'=>'November',
		'December'=>'December',


	  /*--------------------------------------
	   |	Manage patient notification section
	   -------------------------------------------------------------------*/
	   // 'TITTLE_NOTIFICATION_PATIENT'=>'Notification Patients',
	    'TITTLE_NOTIFICATION_PATIENT'=>'Patient',
		'TITTLE_PATIENT'=>'Select Patient',
		'PATIENT_REQUIRED' =>'Please select patient',
		'PATIENT_SAVED'=>'Patient saved successfully',
		'NOTIFICATION_SEND_SUCCESS'=>'Notification send successfully',
		'ERR_PATIENT_NOT_FOUND'=>'Patient Not Found',
		'SEND_NOTIFICATION_BTN'=>'Send Notifications',
		'MANAGE_NOTIFICATION_PATIENT'=>'Notification Patients',

	    //Smart Appoiment 15-Sep-22 Added by divya==============//
		'ERR_DOCTOR_NOT_AVALIABLE' =>'The doctor is unfortunately not available',
		'TITLE_BOOKING_TIMEFRAME' =>'BOOKING TIMEFRAME',
		'ERR_BOOKING_TIMEFRAME_VALUE'	=>'Please enter valid value',
		'ERR_BOOKING_TIMEFRAME_DESC'	=>'Please select week or month',
		'TITLE_ADD_TIMEFRAME'=>'Add Timeframe',
		'TITLE_SELECT_TIMEFRAME'=>'Select Timeframe',
		'ERR_SELECT_TIMEFRAME'=>'Please select timeframe',
		'ERR_ADD_TIMEFRAME'=>'Please enter timeframe',
		'TITLE_OPTIMAL_APPOINTMENT' =>'Optimal Date',
		'TITLE_OFF' =>'Off',
		'TITLE_ON' =>'On',
		'TITTLE_FORM_DATE'=>'From Date',
        'TITTLE_TO_DATE'=>'To Date',

         'ERR_DOCTOR_ROSTER_NOT_AVALIABLE' =>'The doctor is not available for other dates',
		//End Smart Appoiment 15-Sep-22 Added by divya==============//

        // 'REMINDED_SERVICE'=>'Service to be reminded',//commented on 14-feb-25 for #303 issue
         //'REMINDED_SERVICE'=>'Follow-up service',//added on 14-feb-25 for #303 issue

          
         'REMINDED_SERVICE'=>'Select follow-up service',// added on 30-may-25 for #326 (c) issue

		'SELECT_SERVICE'=>'Select Servcie',



        /*--------------------------------------
       |   Check list section added on 26-dec-22
       -------------------------------------------------------------------*/
        'TITLE_CHECKLIST_DOCUMENT_HEADER_IMAGE'   => 'Header Image',
		'TITLE_CHECKLIST_DOCUMENT_FOOTER_IMAGE'   => 'Footer Image',
		'ERR_CHECKLIST_DOCUMENT_HEADER_IMAGE'     => 'Header Image filed is required.',
		'ERR_CHECKLIST_DOCUMENT_FOOTER_IMAGE'     => 'Footer Image filed is required.',

		'TITLE_SITE_PUREMADE' 	=> 'Puremed',

	  /*--------------------------------------
       |   Patient note related added on 23-feb-22
       -------------------------------------------------------------------*/

        'ERR_NOTE_APPOINTMENT_DATE'   => 'Please select appointment date',
        'ERR_NOTE'   => 'Please enter note',
        'TITLE_END_CYCLE' => 'End of cycles after',
        'IGNORE_STATUS' => 'ignored',

        /*-----------------------------------------
        | Reminder popup message
        --------------------------------------------------------*/

        'Reminder_success_msg' => 'Reminder will be added when you click the save button.',

        /*-----------------------------------------
        | Patient duplication message
        --------------------------------------------------------*/
		// 'ERR_MOBILE_UNIQUE' 	=> "This mobile number is already assigned to another patient. Please enter another number.",
        'ERR_MOBILE_UNIQUE' => "This patient entry already exists.",

        'ERR_ROLE_NOT_ALLOWED' => "Your role is not permitted to untick this checkbox.",

        	 // added on 15-feb-24
		'TITLE_OPTIMAL' =>'Optimal Appointment',

		//added on 23-feb-24
		'ERR_IMPORT_PATIENT' => "Whole data is not imported",
		'ERR_IMPORT_APPOINTMENT' => "Whole data is not imported",

		'ERR_ROLE_NOT_ALLOWED' => "Your role is not permitted to untick this checkbox.",
        'ERR_APP_TYPE_RESTRICTED' => "At least one appointment type needs to be kept without restriction.",
        'TITLE_CONFIRM_WEB_APPOINTMENT' => 'Please Confirm Your Appointment',
		'APPOINTMENT_BOOKED' => "Appointment booked successfully.",
		'APPOINTMENT_CANCEL' => "Appointment cancel successfully.",

        'LABEL_PROFILE'=>'Assign to dashboard', //added on 17-apr-24
        'LABEL_PERSONAL_DATA'=>'PERSONAL DATA', //added on 17-apr-24
        'ERR_CSV_IMPORT'=>'The uploaded file must be a CSV.',
        'ERR_CSV_VALID_DATA'=>'Please enter valid data for Row ',

        //Roshani added error messages for 171
        'ERR_PATTERN_COLOR_CODE' => 'The color code is invalid',
        //Roshani added error messages for 171

        'TITLE_ORDINATION_HEADER_FOOTER_COLOR_CODE' => 'Header Footer Text Code',//added on 30-july-24
        'ERR_ORDINATION_HEADER_FOOTER_COLOR_CODE'  => "Text color code field is required.", //added on 30-july-24


        "TITLE_ERR_MESSAGE_PERIOD_OF_CONTROL"=>'Period of control should not exceed 12 year.',

        /*********** CR #102 ***********/
        'TITLE_COUNTRY' => 'Country',
        'TITLE_SELECT_COUNTRY' => 'Select Country',
        'ERR_COUNTRY_REQUIRED'=> 'The Country field is required.',
        'TITLE_COUNTRY_SWITZERLAND'=> 'Switzerland',
        'TITLE_COUNTRY_GERMANY'=> 'Germany',
        'TITLE_COUNTRY_AUSTRIA'=> 'Austria',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY' => 'Invalid postal code for Germany. It must be 5 digits.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1' => 'Invalid postal code for ',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2' => ' It must be 4 digits.',
         'INVALID_POSTAL_CODE' => 'The postal code must be 4 or 5 digits.',
        /*********** CR #102 ***********/
        /*********** CR #214 ***********/

        'TITLE_CYCLE_CALENDER' => 'Cycle Calender',

        /*********** CR #214 ***********/


         /*************CR #191***************************/
        'ERR_MIMIMUM_AGE'=> 'Please enter valid input.',
        /***************CR #191**************************/

        'HEIGHT_UPDATED' => "Updated successfully.", //added on 4-nov-24

        'TITLE_DELETE_BUTTON_PATIENT' 	=> 'Delete',  //added for #303 issue
        /***************** CR #210 on 08-nov-24*****************/
        //Title
        'TITLE_APK_DOWNLOAD' => "Download tablet apps",
        'TITLE_APK_VIEW' => "View Tablet Apps",
        'TITLE_APK_ADD' => "Add Tablet Apps",
        'TITLE_APKS' => 'Apks',
        'TITLE_UPLOAD_TEXT' => 'Upload',
        'TITLE_SIGNDOC_APP' => 'SignDoc App',
        'TITLE_MASTER_DATA_APP' => 'Master Data App',
        'TITLE_WAITING_NUM_APP' => 'Waiting Number App',

        //Error 
        'ERR_SIGNDOC_APP_REQUIRED' => 'SignDoc app is required.',
        'ERR_SIGNDOC_APP_APK_ONLY' => 'SignDoc app must be an APK file.',
        'ERR_SIGNDOC_APP_FILE' => 'SignDoc app must be a valid file.',
        'ERR_SIGNDOC_APP_MIMETYPES' => 'SignDoc app must be a valid Android APK.',

        'ERR_MASTER_DATA_APP_REQUIRED' => 'Master data App is required.',
        'ERR_MASTER_DATA_APP_APK_ONLY' => 'Master app must be an APK file.',
        'ERR_MASTER_DATA_APP_FILE' => 'Master app must be a valid file.',
        'ERR_MASTER_DATA_APP_MIMETYPES' => 'Master app must be a valid Android APK.',

        'ERR_WATING_NUM_APP_REQUIRED' => 'Waiting number app is required.',
        'ERR_WATING_NUM_APP_APK_ONLY' => 'Waiting number app must be an APK file.',
        'ERR_WATING_NUM_APP_FILE' => 'Waiting number app must be a valid file.',
        'ERR_WATING_NUM_APP_MIMETYPES' => 'Waiting number app must be a valid Android APK.',

        //View Title name
        'TITLE_APK_NAME' => 'Apk Name',
        'TITLE_APK_FILE_NAME' => 'Apk File Name',
        'TITLE_APK_VERSION' => 'Apk Version',
        'TITLE_DOWNLOAD_APK' => 'Download Apk',

        'APK_UPLOADED' => 'APK Uploaded Successfully.',
        
        /***************** CR #210 on 08-nov-24*****************/


        'ERR_MOBILE_NUMBER' => "mobile number must be a number.",
		'ERR_CHECKLIST_ALREADY_ADDED_TO_SERVICE' => "Checklist status can't be changed as it's already linked to the service",

	    'ERR_DEVICE_IDS' 	=> "Invalid device ids", //added on 10-nov-25
	    'ERR_DEVICE_ID_NOT_EXISTS' 	=> "Device id not exists", //added on 10-nov-25
		'MSG_SEND_PUSH_NOTIFICATION' =>'Push notification sent successfully.',//added on 10-nov-25
		 // 'TITLE_Day' =>'Day',//added on 11-nov-25 commented on 16-dec-25
		 'TITLE_Day' =>'Reminder',//changed on 16-dec-25
		 'TITLE_Current_Day' =>'Current day',//added on 11-nov-25 
		 'TITLE_Previous_Day' =>'Previous day',//added on 11-nov-25 
		//  'TITLE_IS_EMERGENCY_DOCTOR' => 'Activate Emergency Doctor',//added on 3-dec-25		
		 'TITLE_IS_EMERGENCY_DOCTOR' => 'Doctor bookable online (click "no" for Emergency Doctor)',//added on 3-dec-25		

		'TITLE_SPECIALIST_ADD' =>'Select Specialization',//Added on 29-jan-26

		'TITLE_ANDROID_FLAG' =>'Android Force Update Status',//Added on 12-feb-26
		'TITLE_IOS_FLAG' =>'Ios Force Update Status',//Added on 12-feb-26
		'ERR_MOBILE_NO_INVALID' => "Mobile number must be a valid number.", //Added on 12-feb-26

];
