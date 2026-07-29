<?php


use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/


$PREFIX = 'Api\v3';

Route::group(['prefix' => 'v3','middleware' => ['ApiCustomKeyToken']], function()use($PREFIX) {
    Route::get('google',  $PREFIX.'\AuthController@redirectToGoogle');
    Route::get('callback', $PREFIX.'\AuthController@handleGoogleCallback');

    Route::post('register', $PREFIX.'\AuthController@registerPatient');

    Route::post('signup-send-otp', $PREFIX.'\AuthController@signupSendOtp');
    Route::post('signup-send-otp-new', $PREFIX.'\AuthController@signupSendOtpNew'); //added on 8-jan-23

    Route::post('signup-verify-otp', $PREFIX.'\AuthController@signupVerifyOtp');
    Route::post('signup-verify-otp-purmed', $PREFIX.'\AuthController@signupVerifyOtpPuremed');

    Route::post('password-send-otp', $PREFIX.'\AuthController@passwordSendOtp');
    Route::post('password-verify-otp', $PREFIX.'\AuthController@passwordVerifyOtp');
    Route::post('forget_password', $PREFIX.'\AuthController@forgetPassword');
    Route::post('forget_password_puremed', $PREFIX.'\AuthController@forgetPasswordPuremed');

    Route::post('current-version-setting', $PREFIX . '\AuthController@smartphoneAppsSetting');
    Route::post('get-setting-salutations', $PREFIX . '\SettingsController@getSettingSalutations');
    Route::post('get-setting-logout-images', $PREFIX . '\SettingsController@getSettingLogoutImages');
    Route::get('get-week-days', $PREFIX.'\OptimalAppointmentController@getWeekDaysListing');
    Route::post('get-ordination-list-Qrcode', $PREFIX . '\AuthController@getOrdinationQrcode');
    Route::post('ordination-login-Qrcode', $PREFIX . '\AuthController@ordinationLoginQrcode');
    Route::post('get-gany-patient-data-Qrcode', $PREFIX . '\AuthController@getGanyPatientDataQRCode');
    Route::post('edit-gany-patient-data', $PREFIX . '\AuthController@editGanyPatientData');
    Route::post('generate-examination-check-list-pdf-Qrcode', $PREFIX . '\ExaminationController@generateExaminationCheckListPdfQrcode');
    Route::post('get-general-document-Qrcode', $PREFIX . '\ExaminationController@getAllGeneralDocumentQRCode');
    // general check list
    Route::post('generate-general-check-list-pdf-Qrcode', $PREFIX . '\ExaminationController@generateGeneralCheckListPdfQrcode');
    Route::post('get-documents-Qrcode', $PREFIX . '\AppointmentAgreementController@getDocumentsQRCode');
    Route::post('get-patient-trigger-examinations-Qrcode-test', $PREFIX . '\ExaminationController@getPatientOrTriggerExaminationsQRCodetest');
    Route::post('appointment/edit-Qrcode', $PREFIX . '\AppointmentAgreementController@editAppointmentQRCode');
    Route::post('get-appointment-documents-Qrcode', $PREFIX . '\AppointmentAgreementController@getAppointmentDocumentsQRCode');
    Route::post('update-document-read-Qrcode', $PREFIX . '\AppointmentAgreementController@updateDocumentReadQrcode');
    // finding services type for tablet
    Route::post('get-findings-services-qrcode', $PREFIX . '\DiagnosticFindingsController@getFindingServicesQrcode');
    Route::post('diagnastic-findings-create-Qrcode-testing', $PREFIX . '\DiagnosticFindingsController@createDiagnosticFindingsQrcodeTesting');


     Route::post('get-patient-signed-documents-Qrcode', $PREFIX.'\AuthController@getPatientSignedDocumentsByQRCode');

        Route::post('get-patient-signed-documents', $PREFIX.'\AuthController@getPatientSignedDocuments');

         Route::post('get-patient-check-lists', $PREFIX.'\ExaminationController@getPatientCheckListQrcode');
          Route::post('get-patient-has-signed-documents-Qrcode', $PREFIX.'\AuthController@getPatientSignedDocumentsQRCode');

            //Added below api for testing purpose on 13oct22
    Route::post('update-patient-document-sign-test', $PREFIX.'\AuthController@updatePatientDocumentSignTest');


    Route::post('get-patient-has-general-checkList-Qrcode', $PREFIX.'\ExaminationController@getPatientGeneralCheckListQrcode');

    
    Route::post('get-master-performance-check-lists', $PREFIX.'\ExaminationController@getSingDocPerformaceCheckListQrcode');

    Route::post('change_password', $PREFIX.'\AuthController@changePassword');//added on 11-apr-25

    Route::post('change_password_puremed', $PREFIX.'\AuthController@changePasswordPuremed');//added on 5-may-25

    Route::post('update_password', $PREFIX.'\AuthController@updatePassword');//added on 5-may-25
    Route::post('update_password_puremed', $PREFIX.'\AuthController@updatePasswordPuremed');//added on 5-may-25

     Route::post('resend-otp', $PREFIX.'\AuthController@resendOtp');//added on 20-june-25
     Route::get('/get-country', $PREFIX.'\AuthController@getCountries'); //added on 03-march-26 for country code list


}); 

Route::group(['prefix' => 'v3', 'middleware' => ['ApiCustomKeyToken', 'jwt.verify']], function () use ($PREFIX) {
    // Route::post('update_password', $PREFIX.'\AuthController@updatePassword'); //commented on 5-may-25
    // Route::post('update_password_puremed', $PREFIX.'\AuthController@updatePasswordPuremed'); //commented on 5-may-25


    Route::post('update_password_ordination', $PREFIX.'\AuthController@updatePasswordPuremedOrdination');

    //Route::post('change_password', $PREFIX.'\AuthController@changePassword');//commented on 11-apr-25


    // Route::post('change_password_puremed', $PREFIX.'\AuthController@changePasswordPuremed');//commented on 5-may-25

    // Route::post('resend-otp', $PREFIX.'\AuthController@resendOtp');//commented on 20-june-25

    //Route::post('temp-resend-otp', $PREFIX.'\AuthController@getPatientOrdinations');

   // Route::post('forgot-password', $PREFIX.'\AuthController@signupVerifyOtp');
    // Examinations
    Route::get('examinations', $PREFIX.'\ExaminationController@getExaminations');
    Route::post('get-examinations-check-list', $PREFIX.'\ExaminationController@getExaminationsCheckList');



    Route::post('diagnastic-findings-create-Qrcode', $PREFIX.'\DiagnosticFindingsController@createDiagnosticFindingsQrcode');

     //added below api on 13-aug-24 for testing on live





    Route::post('getPatientOrdinations', $PREFIX.'\AuthController@getPatientOrdinations');




   



    Route::get('faq', $PREFIX.'\AuthController@getFaq');
    // Route::get('gdpr', $PREFIX.'\AuthController@gdprDetails');
    Route::post('login-with-google',  $PREFIX.'\AuthController@loginWithGoogle');
    // Route::post('verify-login-with-google',  $PREFIX.'\AuthController@verifyLoginWithGoogle');


    //kiosk app
    Route::post('get-gany-patient-data', $PREFIX.'\AuthController@getGanyPatientData');

    Route::post('edit-gany-patient-data-test', $PREFIX.'\AuthController@editGanyPatientDatatest');
    Route::post('kisok-create-waiting-number', $PREFIX.'\AppointmentAgreementController@kisokCreateWaitingNumber');

    // Route::post('get-patient-signed-documents', $PREFIX.'\AuthController@getPatientSignedDocuments');
    Route::post('update-patient-sign', $PREFIX.'\AuthController@updatePatientSign');
    Route::post('update-patient-sign-pdf', $PREFIX.'\AuthController@updatePatientSignPdf');
    Route::post('update-patient-sign-pdfTest', $PREFIX.'\AuthController@updatePatientSignPdfTest');
    Route::post('update-patient-document-sign-pdf', $PREFIX.'\AuthController@updatePatientDocumentSignPdf');
    Route::post('update-patient-document-sign', $PREFIX.'\AuthController@updatePatientDocumentSign');

  
      //Added below api for testing purpose on 5-jan-23
    Route::post('update-patient-document-sign-testing', $PREFIX.'\AuthController@updatePatientDocumentSignTesting');


    // Route::post('get-patient-signed-documents-Qrcode', $PREFIX.'\AuthController@getPatientSignedDocumentsByQRCode');

    Route::post('debug_api', $PREFIX.'\AuthController@debugModeFun');

   

    Route::post('get-patient-has-signed-documents-Qrcodetest', $PREFIX.'\AuthController@getPatientSignedDocumentsQRCodetest');

    Route::post('get-patient-has-signed-documents-Qrcode-Pdf', $PREFIX.'\AuthController@getPatientSignedDocumentsQRCodePdf');//Clone


    Route::post('get-patient-trigger-examinations-Qrcode', $PREFIX.'\ExaminationController@getPatientOrTriggerExaminationsQRCode');




    Route::post('test-get-documents-Qrcode', $PREFIX.'\AppointmentAgreementController@testgetDocumentsQRCode');



    // Route::post('update-document-read-Qrcode', $PREFIX.'\AppointmentAgreementController@updateDocumentReadQRCode');



    // scan findings for tablet




    Route::post('createDiagnosticFindingsTest', $PREFIX.'\DiagnosticFindingsController@createDiagnosticFindingsTest');

    //Roshani added below routes on 2-may-24


    // Route::post('get-from-date', $PREFIX.'\OptimalAppointmentController@getFromDate');

    // Route::post('get-end-date', $PREFIX.'\OptimalAppointmentController@getEndDate');

    // Route::post('get-doctor-slots', $PREFIX.'\OptimalAppointmentController@getDoctorSlots');


    //Roshani added below routes on 2-may-24

});

Route::group(['prefix' => 'v3'], function()use($PREFIX)
{
    // Route::post('get-all-appoinmatns', $PREFIX.'\DiagnosticFindingsController@getAllAppoinmant');
    // Route::post('send-required-admin-get-old-findings', $PREFIX.'\DiagnosticFindingsController@SendRequiredAdminGetOldFindings');
    Route::post('change-ordination', $PREFIX.'\AuthController@changeOrdination');
});

Route::group(['prefix' => 'v3','middleware' => ['jwt.verify']], function()use($PREFIX) {

    Route::post('get-setting-logged-in-images', $PREFIX.'\SettingsController@getSettingLoggedImages');

    Route::post('get-menu-settings', $PREFIX.'\SettingsController@getMenuSettings');
    Route::post('get-setting-beacons', $PREFIX.'\SettingsController@getSettingBeacons');
    Route::post('get-appointment-types', $PREFIX.'\SettingsController@getAppointmentTypes');
    Route::post('get-appointment-types-dynamic', $PREFIX.'\SettingsController@getAppointmentTypesForDynamic');
    Route::post('refresh-token', $PREFIX.'\AuthController@refreshToken');
    Route::post('logout', $PREFIX.'\AuthController@logout');


    //added 07 feb 2022
    Route::post('get-notification-count-for-patients', $PREFIX.'\NotificationController@getCountForRemindersAndNotification');

    // menstruation_cycle
    Route::post('menstruation-cycle', $PREFIX.'\SettingsController@menstruationCycleCreate');
    Route::post('get-menstruation-cycle', $PREFIX.'\SettingsController@getMenstruationCycle');
    Route::post('menstruation-cycle-calendar', $PREFIX.'\SettingsController@menstruationCycleCalender');

    Route::post('get-menstruation-cycle-calendar', $PREFIX.'\SettingsController@getMenstruationCycleCalender');  //added on 11-oct-23

    Route::post('menstruation-cycle-calendar-app', $PREFIX.'\SettingsController@menstruationCycleCalenderApp'); //added on 10-oct-23


    // Emargency
    Route::post('emergency', $PREFIX.'\EmergencyController@emergencyCreate');

    // Edit Master Data
    Route::post('get-master-data', $PREFIX.'\AuthController@getMasterData');
    Route::post('edit-master-data', $PREFIX.'\AuthController@editMasterData');
    Route::post('edit-master-data-new', $PREFIX.'\AuthController@editMasterDataNew');


    Route::post('get-patient-trigger-examinations', $PREFIX.'\ExaminationController@getPatientOrTriggerExaminations');
    Route::post('get-patient-selected-examinations', $PREFIX.'\ExaminationController@getPatientSelectedExaminations');
    Route::post('get-patient-selected-examinations-new', $PREFIX.'\ExaminationController@getPatientSelectedExaminationsNew');
    //Route::post('get-ordination', $PREFIX.'\AuthController@getOrdination');
    Route::post('change-ordination', $PREFIX.'\AuthController@changeOrdination');
    Route::post('search-ordination', $PREFIX.'\AuthController@searchOrdination');
    Route::post('is_updated', $PREFIX.'\AuthController@isUpdated');
    Route::post('assigned-ordination-to-patient', $PREFIX.'\AuthController@assignedOrdinationToPatient');
    Route::post('assigned-ordination-to-patient-puremed', $PREFIX.'\AuthController@assignedOrdinationToPatientPuremed');

    Route::post('assigned-ordination-to-patient-puremed-new', $PREFIX.'\AuthController@assignedOrdinationToPatientPuremedNew');

    Route::post('assigned-ordination-reminders', $PREFIX.'\AuthController@assignedOrdinationReminders');


    Route::post('get-recommended-service-reminder', $PREFIX.'\ExaminationController@getRecommendedServiceReminder');
    Route::post('generate-examination-check-list-pdf', $PREFIX.'\ExaminationController@generateExaminationCheckListPdf');

    Route::post('get-patient-has-check-lists', $PREFIX.'\ExaminationController@getPatientCheckList');

    Route::post('get-patient-has-generate-check-lists', $PREFIX.'\ExaminationController@getPatientGenerateCheckList');

     // Roshani added this code for CR #214 on 24-oct-24
        Route::get('get-cycle-calender', $PREFIX.'\SettingsController@getCycleCalenderSetting');

    // Roshani added this code for CR #214 on 24-oct-24


    // Profile Template
    //Route::post('profile-temlate', $PREFIX.'\ProfileTemplateController@getProfileTemplate');

    /*// Profile Template Examinations
    Route::post('profile-temlate-examinations', $PREFIX.'\ProfileTemplateController@getProfileExaminations');
    Route::post('create-patient-examinations', $PREFIX.'\ExaminationController@createPatientExaminations');
    Route::post('get-patient-examinations', $PREFIX.'\ExaminationController@getPatientExaminations');*/

    /*-------------------------------------
    |   Diagnastic Findings Module
    -------------------------------------------------*/
        // Diagnastic Findings Types List
        Route::get('diagnastic-findings-type', $PREFIX.'\DiagnosticFindingsController@getDiagnosticFindingsTypes');

        // Diagnastic Findings Create
        Route::post('diagnastic-findings-create', $PREFIX.'\DiagnosticFindingsController@createDiagnosticFindings');
         Route::post('get-patient-has-performance-checkList', $PREFIX.'\ExaminationController@getPatientPerformanceCheckList');

        Route::post('get-general-document', $PREFIX.'\ExaminationController@getAllGeneralDocument');
        Route::post('generate-general-check-list-pdf', $PREFIX.'\ExaminationController@generateGeneralCheckListPdf');
        Route::post('get-patient-has-general-checkList', $PREFIX.'\ExaminationController@getPatientGeneralCheckList');

        // Diagnastic Findings List
        Route::post('diagnastic-findings-list', $PREFIX.'\DiagnosticFindingsController@getDiagnosticFindings');

        // Diagnastic Findings List for android
        Route::post('diagnastic-findings-list-android', $PREFIX.'\DiagnosticFindingsController@getDiagnosticFindingsAndroid');

        // Single Diagnastic Finding Page
        Route::post('diagnastic-findings-detail-page', $PREFIX.'\DiagnosticFindingsController@getSingleDiagnosticFinding');

        // Filter by date
        Route::post('diagnastic-findings/filter-by-date', $PREFIX.'\DiagnosticFindingsController@filterByDate');

        // Filter by Diagnastic findings type
        Route::post('diagnastic-findings/filter-by-type', $PREFIX.'\DiagnosticFindingsController@filterByType');

         // Download Diagnastic finding
        Route::any('diagnastic-findings/download', $PREFIX.'\DiagnosticFindingsController@downloadFinding');

        // finding services list
        Route::post('get-findings-services', $PREFIX.'\DiagnosticFindingsController@getFindingServices');



        //Get All Old Appoinmant date
        Route::post('get-all-appoinmatns', $PREFIX.'\DiagnosticFindingsController@getAllAppoinmant');
        //send reuiest to admin for patient findins (document)
        Route::post('send-required-admin-get-old-findings', $PREFIX.'\DiagnosticFindingsController@SendRequiredAdminGetOldFindings');

    /*-----------------------------------
    |   Appointment Agreement Module
    -------------------------------------------------*/

        // Doctors List
        Route::post('get-doctors', $PREFIX.'\AppointmentAgreementController@getDoctors');

        // Appointment Types List
        Route::post('get-appointment', $PREFIX.'\AppointmentAgreementController@getAppointment');

        // Appointment Types Details
        Route::post('get-appointment-type-details', $PREFIX.'\AppointmentAgreementController@getAppointmentTypeDetail');

        // Appointment Documents
        Route::post('get-appointment-documents', $PREFIX.'\AppointmentAgreementController@getAppointmentDocuments');



        Route::post('our-services', $PREFIX.'\AuthController@ourServices');
        Route::post('manage-services-sequence', $PREFIX.'\AuthController@manageServicesSequence');

        Route::post('update-document-read', $PREFIX.'\AppointmentAgreementController@updateDocumentRead');


        // Get Doctor Timeslots

        Route::post('appointment/get-doctor-timeslots', $PREFIX.'\AppointmentAgreementController@getDoctorTimeSlots');
        Route::post('appointment/get-doctor-timeslots-new', $PREFIX.'\AppointmentAgreementController@getDoctorTimeSlotsNew');

        // Book Appointment
        Route::post('appointment/book', $PREFIX.'\AppointmentAgreementController@bookAppointment');
        Route::post('appointment/book-new', $PREFIX.'\AppointmentAgreementController@bookAppointmentNew');
        Route::post('appointment/book-newtest', $PREFIX.'\AppointmentAgreementController@bookAppointmentNewTest');

        Route::post('appointment/book-new-test', $PREFIX.'\AppointmentAgreementController@bookAppointmentNewTest');

        Route::post('appointment/edit', $PREFIX.'\AppointmentAgreementController@editAppointment');

        // Cancel Appointment
        Route::post('appointment/cancel', $PREFIX.'\AppointmentAgreementController@cancelAppointment');
        Route::post('appointment/cancel-new', $PREFIX.'\AppointmentAgreementController@cancelAppointmentNew');

        // Appointment filter bt appointment type
        Route::post('appointment/filter-by-type', $PREFIX.'\AppointmentAgreementController@getAppointmentFilterByType');
        // Appointment filter bt appointment date
        Route::post('appointment/filter-by-date', $PREFIX.'\AppointmentAgreementController@getAppointmentFilterByDate');

        // Report Delay Create
        Route::post('create-appointment-delay-report', $PREFIX.'\AppointmentAgreementController@createAppointmentDelayReport');

        // Report Delay Create
        Route::post('send_notification', $PREFIX.'\NotificationController@send_notification');

        Route::post('getnotification', $PREFIX.'\NotificationController@getNotification');

        Route::post('update-notification-status', $PREFIX.'\NotificationController@updateNotificationStatus');

        Route::post('unreadnotificationcount', $PREFIX.'\NotificationController@unreadNotificationCount');

        Route::post('create-waiting-number', $PREFIX.'\AppointmentAgreementController@createWaitingNumber');
        Route::post('get-waiting-number', $PREFIX.'\AppointmentAgreementController@getWaitingNumber');

        Route::post('appointment/history', $PREFIX.'\AppointmentAgreementController@getAppointmentHistory');

        //Roshani added below routes on 2-may-24
        // Route::get('get-week-days', $PREFIX.'\OptimalAppointmentController@getWeekDaysListing');

        Route::post('get-from-date', $PREFIX.'\OptimalAppointmentController@getFromDate');

        Route::post('get-end-date', $PREFIX.'\OptimalAppointmentController@getEndDate');

        Route::post('get-doctor-slots', $PREFIX.'\OptimalAppointmentController@getDoctorSlots');


        //Roshani added below routes on 2-may-24

        /************ Roshani added the api for 146 ****************/
        Route::post('verify-user-exist', $PREFIX.'\AuthController@VerifyUserExist');

        /************ Roshani added the api for 146 ****************/

});

Route::group(['prefix' => 'v3'], function()use($PREFIX) {

   Route::post('diagnastic-findings-create', $PREFIX.'\DiagnosticFindingsController@createDiagnosticFindings');
    Route::post('diagnastic-findings-create-Qrcode', $PREFIX.'\DiagnosticFindingsController@createDiagnosticFindingsQrcode');
   Route::post('getnotification', $PREFIX.'\NotificationController@getNotification');
   //Route::post('temp-resend-otp', $PREFIX.'\AuthController@getPatientOrdinations');
   // Route::post('get-ordination', $PREFIX.'\AuthController@getOrdination');
   //Route::post('search-ordination', $PREFIX.'\AuthController@searchOrdination');
   // Route::post('assigned-ordination-to-patient', $PREFIX.'\AuthController@AssignedOrdinationToPatient');
   //Route::post('is_updated', $PREFIX.'\AuthController@isUpdated');
   Route::post('testTriggerExaminationsQRCode', $PREFIX.'\ExaminationController@testTriggerExaminationsQRCode');
   Route::post('test-search-ordination', $PREFIX.'\AuthController@testsearchOrdination');
    Route::post('testupdateDocumentReadQrcode', $PREFIX.'\AppointmentAgreementController@testupdateDocumentReadQrcode');
    Route::post('edit-gany-patient-data-test', $PREFIX.'\AuthController@editGanyPatientDatatest');

    //Added by Swati Mam
    Route::post('block-sms', $PREFIX.'\ExaminationController@changeSMSStatus');
    Route::post('block-mail', $PREFIX.'\ExaminationController@changeMailStatus');
    Route::post('get-patient-settings', $PREFIX.'\ExaminationController@getPateintSettings');


});
