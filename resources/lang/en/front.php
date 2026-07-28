<?php 

return [ 

/*--------------------------------------
	|	Module name APPOINTMENT
	-------------------------------------------------------------------*/
		// Module Title 
		'TITLE_APPOINTMENT_TEXT' => 'Termine',
		'TITLE_SEARCH_TEXT' => 'Suchen', 
		'TITLE_SEARCH_WEB_TEXT' => 'Buchen', 
		'TITLE_SEARCH_WEB_TEXT_CHANGE' => 'OK',
		// 'TITLE_GENERAL_CHECK_LIST' => 'Allgemein Checkliste', //commented on 19-march-25
		'TITLE_GENERAL_CHECK_LIST' => 'Allgemeine Checklisten', //added on 19-march-25


		// 'TITLE_PERFORMANCE_CHECK_LIST' => 'Leistung Checkliste',//commented on 19-march-25
		'TITLE_PERFORMANCE_CHECK_LIST' => 'Leistungsbezogene Checklisten',//added on 19-march-25


		'TITLE_DOCUMENT' => 'Unterlagen', 
		'TITLE_PATIENT_STREET_NO' 		=> 'Hausnummer',

		// ERROR MESSAGES		
		'RESP_SUCCESS' 	=> 'success',
		'RESP_ERROR' 	=> 'error',
		'ERR_SOMETHING_WRONG' 	=> "Es ist ein Problem aufgetreten. Bitte wenden sie sich an die Ordination.",

		// Delete Account
		'DELETE_ACCOUNT' 			=> 'Delete Account',
		'DELETE_ACCOUNT_CONFIRM' 	=> 'Please note that all future appointments will be cancelled.',
		'DELETE_ACCOUNT_CANCEL' 	=> 'Cancel',
		'DELETE_ACCOUNT_OK' 		=> 'OK',
		'DELETE_ACCOUNT_SUCCESS' 	=> 'Your account has been deleted.',

		'ERR_APPOINTMENT_PATIENT_REQUIRED' 	=> "Patientin-Feld ist erforderlich.",
		'ERR_APPOINTMENT_DOCTOR_REQUIRED' 	=> "Arzt-Feld ist erforderlich.",
		'ERR_APPOINTMENT_TYPE_REQUIRED' 	=> "Termin-Feld ist erforderlich.",
		'ERR_APPOINTMENT_DATE_REQUIRED' 	=> "Datum-Feld ist erforderlich.",
		'ERR_APPOINTMENT_TIME_FRAME_REQUIRED' 	=> "Zeit-Feld ist erforderlich.",
		'ERR_COUNTRY_CODE_REQUIRED' 	=> "Ländercodefeld erforderlich ist.",
 		'ERR_COUNTRY_CODE_WRONG' 	=> "Ungültige Länder-Vorwahl eingegeben.",
 		'TITLE_PATIENT_COUNTRY_CODE' 	=> 'Ländercode',

		// Titles
		'TITLE_APPOINTMENT_START_DATE' 	=> 'Startdatum', 
		'TITLE_APPOINTMENT_END_DATE' 	=> 'Enddatum',
		'TITLE_APPOINTMENT_PATIENT' 	=> 'Patientin',
		'TITLE_APPOINTMENT_DOCTOR'  	=> 'Ärzt:in', 
		'TITLE_APPOINTMENT_TYPE' 		=> 'Termintyp',
		'TITLE_ROSTER_SELECT_APPOINTMENT_TYPE' => 'Termintyp wählen',
		'TITLE_ROSTER_WEEKDAY'	 		=> 'Wochentag', 
		'TITLE_APPOINTMENT_DATE' 		=> 'Datum',
		'TITLE_APPOINTMENT_NOTE'		=> 'Notiz',
		'TITLE_APPOINTMENT_STATUS'		=> 'Status',
		'TITLE_APPOINTMENT_STATUS_ACTIVE' 	=> 'Aktiv',
		'TITLE_NOTIFICATION_TEXT'	=> 'Benachrichtigung', 

		//Placeholder Title
		'TITLE_SELECT_PATIENT' => 'Patientin wählen',
		'TITLE_SELECT_DOCTOR'  => 'Arzt wählen',
		'TITLE_SELECT_TYPE'    => 'Termintyp wählen',
		'TITLE_APPOINTMENT_TIME_FRAME'	=> 'Zeitrahmen', 
		'TITLE_ROSTER_STARTDATE'	 	=> 'Datum von', 
		'TITLE_ROSTER_ENDDATE'	 		=> 'Datum bis', 
		'TITLE_ROSTER_TIME_FROM'	 	=> 'Zeit von',
		'TITLE_ROSTER_TIME_TO'	 		=> 'Zeit bis',
		'TITLE_ROSTER_TIME_FRAME'	 	=> 'Zeitrahmen ',

		'ERR_WEEKDAY_REQUIRED' 		=> "Wochentag-Feld ist erforderlich.", 

		// Response error
		'FAIL_APPOINTMENT_CREATE' => "Server-Fehler beim Erstellen des Termins.",
		'FAIL_APPOINTMENT_UPDATE' => "Server-Fehler beim Aktualisieren des Dienstplans.",
		'FAIL_APPOINTMENT_DELETE' => "Server-Fehler beim Löschen des Dienstplans.",
		'FAIL_APPOINTMENT_TIME_FRAME' => "Server-Fehler beim laden der Arzt-Dienstplans.",

		// Response messages 
		'APPOINTMENT_CREATED' => "Termin erfolgreich angelegt.",  
		'APPOINTMENT_UPDATED' => "Termin erfolgreich aktualisiert.",		
		'APPOINTMENT_DELETED' => "Termin erfolgreich gelöscht.",	

		/*'TITLE_FIRST_NAME'	 	=> 'First Name',
		'TITLE_LAST_NAME'  	 	=> 'Last Name',
		'TITLE_EMAIL_ADDRESS' 	=> 'Email Address',
		'TITLE_MOBILE_NO' 		=> 'Mobile Number',
		'TITLE_OTP' 		=> 'OTP',
		'TITLE_SEND_OTP' 		=> 'Send Otp', 

		'ERR_FIRSTNAME_REQUIRED' 	=> 'First Name field is required.',
		'ERR_LASTNAME_REQUIRED' 		=> "Last Name field is required.",
		'ERR_EMAIL_ADDRESS_REQUIRED' 	=> "Email Address field is required.",
		'ERR_MOBILE_NO_REQUIRED' 		=> "Mobile Number field is required.",  
		'ERR_OTP_REQUIRED' 		=> "Otp field is required.", 
		'TITLE_SALUTATION_TEXT' 	=> 'Salutation',
		'TITLE_PATIENT_ROAD' 		=> 'Road',
		'TITLE_PATIENT_POSTAL_CODE' => 'Postal Code',
		'TITLE_PATIENT_PLACE' 		=> 'Place',
		'TITLE_TITLE_TEXT' 			=> 'Title',
  */   
		'TITLE_FIRST_NAME' 		=> 'Vorname',
	 	'TITLE_LAST_NAME' 		=> 'Nachname',
	 	'TITLE_EMAIL_ADDRESS' 	=> 'E-Mail',
	 	'TITLE_MOBILE_NO' 		=> 'Handynummer',
		'TITLE_OTP' 			=> 'SMS Code',
		'TITLE_SEND_OTP' 		=> 'SMS Code anfordern',
		'TITLE_PATIENT_BIRTH_DATE'		=> 'Geburtsdatum',

		'ERR_FIRSTNAME_REQUIRED' 		=> 'Der Vorname ist erforderlich.',   
		'ERR_LASTNAME_REQUIRED' 		=> 'NachnameFeld ist erforderlich.',      
		'ERR_BIRTH_DATE_REQUIRED' 		=> "Geburtsdatum-Feld ist erforderlich.",
		'ERR_EMAIL_ADDRESS_REQUIRED' 	=> "E-Mail-Adresse-Feld ist erforderlich.",
		'ERR_MOBILE_NO_REQUIRED' 		=> "HandynummerFeld ist erforderlich.",      
		'ERR_OTP_REQUIRED' 				=> "Das Opp-Feld ist erforderlich.", 
		'ERR_WRONG_OTP' 				=> "Falsches Handy oder otp eingegeben",
		'ERR_MOBILE_FORMAT' => "Ungültiges Mobiltelefonnummernformat.",      
		'ERR_EMAIL_FORMAT' => "Ungültiges E-Mail-Adressformat.",

	// Register
		'TITLE_SALUTATION_TEXT' 	=> 'Salutation',
		'TITLE_ANREDE_TEXT' 		=> 'Anrede',
		'TITLE_PATIENT_ROAD' 		=> 'Straße',
		'TITLE_PATIENT_POSTAL_CODE' => 'Postleitzahl',
		'TITLE_PATIENT_PLACE' 		=> 'Ort',
		'TITLE_TITLE_TEXT' 			=> 'Titel',
		'TITLE_SOZIAL' => 'Sozialversicherungsnumme',
		'USER_PROFILE_TITLE_SOZIAL' => 'Sozialversicherungsnr.',

		'ERR_PATIENT_SALUTATION_REQUIRED'=> "Anredefeld ist erforderlich.",
		'ERR_PATIENT_TITLE_REQUIRED'	=> "Titelfeld ist erforderlich.",
		//'ERR_FIRST_NAME_REQUIRED' 		=> "First Name field is required.",
		'ERR_PATIENT_FAMILY_NAME_REQUIRED'=> "Family Name field is required.",
		//'ERR_PATIENT_EMAIL_ADDRESS' 	=> "Email Address field is required.",
		'ERR_ROAD_REQUIRED' 			=> "Straßen-Feld ist erforderlich.",
		'ERR_PLACE_REQUIRED' 			=> "Orts-Feld ist erforderlich.",
		'ERR_PATIENT_POSTAL_CODE_REQUIRED'		=> "Das Feld für die Postleitzahl ist erforderlich.",
		'FAIL_PATIENT_CREATE_BOOK'		=> "Es ist ein Fehler aufgetreten. PaJentendaten konnten nicht angelegt werden.",

		'January' => "January",
		'February' => "February",
		'March' => "March",
		'April' => "April",
		'May' => "May",
		'June' => "June",
		'July' => "July",
		'August' => "August",
		'September' => "September",
		'October' => "October",
		'November' => "November",
		'December' => "December",
		// 'Document' => "Document",//commented on 19-march-25
		'Document' => "Dokumente",//added on 19-march-25  


		// 'Document_submit' => "Submit",//commented on 18-march-25
		'Document_submit' => "Bestätigen",//added on 18-march-25 
		'BOOK' => "Leistung buchen",
		'CONTINUE' => "Weiter ohne Buchung",
		// "SERVICES" => "Empfohlene Untersuchungen",
		"SERVICES" => "Individuelle Leistungsempfehlungen",

		'USER_REGISTERED'=>'Benutzer erfolgreich registriert',

		'ERR_MOBILE_UNIQUE' => "Dieser Patienteneintrag existiert bereits.",
		// 'TITLE_SELECT_GENDER'	=> 'Wähle Geschlecht', //commented on 7-may-24
		'TITLE_SELECT_GENDER'	=> 'Geschlecht', //changed on 7-may-24
		'USER_PROFILE_TITLE_SELECT_GENDER' => 'Geschlecht',  
		'ERR_PATIENT_GENDER_REQUIRED' => 'Das Feld „Geschlecht“ ist erforderlich',
	 	'ERR_PASSWORD_REQUIRED' => 'Das Kennwort ist erforderlich.',
	 	'TITLE_PASSWORD' => 'Kennwort',
 		// 'TITLE_FORGOT_PASSWORD_BUTTON' => 'Bitten um neues Kennwort',//commented on 18-march-25
 		'TITLE_FORGOT_PASSWORD_BUTTON' => 'Neues Kennwort anfordern',//Added on 18-march-25 


	 	// 'TITLE_FORGOT_PASSWORD' => 'Kennwort vergessen',//Hidden for CR #228
	 	// 'TITLE_FORGOT_PASSWORD' => 'Passwort anfordern',
	 	'TITLE_FORGOT_PASSWORD' => 'Kennwort vergessen',


		// 'TITLE_CON_PASSWORD' =>'Bestätigen Kennwort',//commented on 7-may-24
	 	'TITLE_CON_PASSWORD' =>'Kennwort bestätigen', //changed on 7-may-24

	 	'ERR_CONFIRM_PASSWORD' 	=> 'Das Feld „Passwort bestätigen“ ist erforderlich.',
		// 'PASSWORD_NOT_MATCHED' => 'Ihre Passwörter stimmen nicht überein. Bitte nutzen Sie die Option „Passwort vergessen“.',
		'PASSWORD_NOT_MATCHED' => 'Das eingegebene Kennwort ist falsch. Bitte versuchen Sie es erneut oder gehen Sie auf Kennwort vergessen.',
		'ERR_DOCTOR_NOT_AVALIABLE' =>'Dieser Arzt ist im gewählten Zeitraum nicht verfügbar',//added by roshani
		'APPOINTMENT_BOOKED' => "Termin erfolgreich gebucht.",  

		'TITLE_CHOOSE_GENDER'	=> 'Geschlecht wählen', //added on 7-may-24	

		/****************added for web flow otp cr*********************************/
		'OTP_NOT_MATCHED' => 'Der OTP-Code ist falsch.', //added on 14-may-24 
		'ERR_VALID_EMAIL_ADDRESS' => "Bitte geben Sie eine gültige Email Adresse an.",//added on 15-may-24
		'ERR_ALL_FIELDS' => "Bitte füllen Sie die erforderlichen Felder aus.",//added on 16-may-24

		'TITLE_WEB_OTP' => 'E-mail Code anfordern',//added on 20-may-24
		'TITLE_WEB_OTP_CODE' => 'E-mail Code',//added on 20-may-24
		/****************added for web flow otp cr*********************************/
		/*********** CR #102 ***********/
        'TITLE_COUNTRY' => 'Land',
        'TITLE_SELECT_COUNTRY' => 'Wählen Sie Land aus',
        'ERR_COUNTRY_REQUIRED'=> 'Das Feld „Land“ ist erforderlich.',
        'TITLE_COUNTRY_SWITZERLAND'=> 'Schweiz',
        'TITLE_COUNTRY_GERMANY'=> 'Deutschland',
        'TITLE_COUNTRY_AUSTRIA'=> 'Österreich',
        /*********** CR #102 ***********/

        /*********** CR #230 ***********/

        'TITLE_DAY' => 'Tag',
        'TITLE_MONTH' => 'Monat',
        'TITLE_YEAR' => 'Jahr',

        'TITLE_SELECT_DAY' => 'Wählen Tag',
        'TITLE_SELECT_MONTH' => 'Wählen Monat',
        'TITLE_SELECT_YEAR' => 'Wählen Jahr',

        'ERR_DAY_REQUIRED' => 'Tag ist erforderlich',

        /*********** CR #230 ***********/

         /*************CR #191***************************/
        'ERR_MIMIMUM_AGE'=> 'Das angegebene Alter liegt unter dem Mindestalter.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY' => 'Ungültige Postleitzahl für Deutschland. Es muss 5-stellig sein.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1' => 'Ungültige Postleitzahl für die ',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2' => ' Es muss 4-stellig sein.',
        /***************CR #191**************************/



        'TITLE_USER_GENERAL_CHECK_LIST' => 'Allgemeine Checklisten und Dokumente',  //added for #275 issue

		'RESET_PASSWORD_SUCCESS' => 'Passwort erfolgreich geändert.', //added on 18-march-25 

		'ERR_PATIENT_POSTAL_CODE_REQUIRED_WEB'	=> "Postleitzahl ist erforderlich",//added on 18-march-25 

		'ERR_LASTNAME_REQUIRED_USER_PROFILE' => 'Der Nachname ist erforderlich.',//added on 18-march-25     

		'ERR_MOBILE_NO_REQUIRED_USER_PROFILE' => "Die Handynummer ist erforderlich",//added on 18-march-25     

		'TITLE_CANCEL_BUTTON' 	=> 'Abbrechen',//added on 19-march-25   
		'FAIL_FORGOT_PASSWORD_DISABLED'   => 'Benutzer-Account ist deaktiviert. Bitte wenden sie sich an den Administrator.',
		'ERR_DOUBLE_BOOKING'=> 'Sie haben in diesem Quartal bereits einen Termin gebucht.',// added on 8-aug-25
  
		'ERR_PATIENT_DELETED'=>'Dieser Link ist leider nicht mehr gültig',
		'APPOINTMENT_DATE_BOOKED_SUCCESS'=>'Ihr Termin ist erfolgreich gebucht. Wir leiten Sie weiter zu zusätzlichen Angaben und Empfehlungen.',
		
		'APPOINTMENT_SECOND_SUCCESS'=>'Vielen Dank für Ihre Buchung. Sie erhalten eine E-Mail zur Terminbestätigung.',//added on 2-jan-25 for #347

		'FORGOT_PASSWORD_WEB_EMAIL_ERR'=>'Ihr Passwort kann leider nicht zurückgesetzt werden, da in Ihrem Profil keine E-Mail Adresse hinterlegt ist. Wenden Sie sich bitte an das Ordinationsteam um die E-Mail nachzutragen.', //added on 30-jan-26 for #417
		'ERR_MOBILE_NO_INVALID' => "Die Handynummer muss eine gültige Nummer sein.", //Added on 12-feb-26


]; 