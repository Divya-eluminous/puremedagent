<?php 

return [

	/*--------------------------------------
	|	Module name Auth & Basic message
	-------------------------------------------------------------------*/
		
		// Response messages
		'SUCCESS_MSG' 	 => 'Erfolgreich',
		'UNSUCCESS_MSG'  => 'Nicht erfolgreich',
		'AUTH_USER_VALIDATED_SUCCESS' => 'Der Benutzername wurde überprüft und ein Einmal-Kennwort verschickt.',
		'AUTH_USER_RESEND_OTP_SUCCESS'=> 'Das Einmal-Kennwort wurde erfolgreich versendet.',
		'AUTH_PATINE_POSTAL_CODE_WRONG'=> 'Nächster Standort Nicht gefunden.',
		'AUTH_VERIFY_USER_SUCCESS' 	  => 'Login erfolgreich. Bitte die Stammdsaten überprüfen.',
		//'AUTH_REGISTER_USER_SUCCESS'  => 'Danke für Ihre Registrierung. Bitte überporüfen Sie Ihre Stammdaten.', //commneted on 22-dec-25 for 368 register msg

		'AUTH_REGISTER_USER_SUCCESS'  => 'Vielen Dank. Die Registrierung war erfolgreich.',//added on 22-dec-25 for 368 register msg

		'PASSWORD_UPDATED_SUCCESSFULLY'  => 'Password updated successfully.',
		'PASSWORD_CHANGE_SUCCESSFULLY'  => 'Password Changed successfully.',
        'OLD_PASSWORD_IS_NOT_MATCH'  => 'Altes Passwort stimmt nicht überein.',
		'AUTH_ORDINATION_DATA_SUCCESS'  => 'Die Ordinationsdaten wurden erfolgreich gesendet.',
		'AUTH_ORDINATION_LOGIN_SUCCESS'  => 'Ordination-Login erfolgreich.',
		// 'AUTH_ORDINATION_DATA_NOT_EXIST'  => 'Die Ordinationsdaten existierten nicht.',
		'AUTH_ORDINATION_DATA_NOT_EXIST'  => 'Es konnte keine Praxis in der Umgebung gefunden werden.',
		'AUTH_ALLREADY_REGISTERD_SUCCESS' => 'Benutzer schon registriert.',
		'AUTH_LOGOUT'				  => 'Sie wurden erfolgreich abgemeldet.',
		'AUTH_FAIL_LOGOUT'			  => 'Abmelden nicht erfolgreich.',

	    'RECORDS_NOT_FOUND' 		  => 'Der Eintrag konnte nicht gefunden werden.',	    
	    'MSG_INVALID_REQUEST' 		  => 'Ungültige Anfrage.',
	    'MSG_DIRECT_SCRIPT_ACCESS' 	  => 'Kein direkter Zugriff erlaubt.',

		// Response error
		'AUTH_FIRSTNAME_REQ' 	=> 'Das Feld "Vorname" ist erforderlich..',
		'AUTH_FAMILYNAME_REQ' 	=> 'Das Feld "Nachname" ist erforderlich.',
		'AUTH_COUNTRY_CODE_REQ' 	=> 'Die Ländervorwahl ist erforderlich.', 
		'AUTH_MOBILENO_REQ' 	=> 'Das Feld "Handynummer" ist erforderlich.',
		'AUTH_POSTAL_CODE_REQ'   => 'Postleitzahlfeld ist erforderlich.',

		'AUTH_PASSWORD_REQ'       => 'Passwortfeld ist erforderlich.',
		'AUTH_PASSWORD_MIN_REQ'   => 'Passwort mindestens 8 Stellen.',
		'AUTH_PASSWORD_MAX_REQ'   => 'Passwort maximal 20 Stellen.',
		'AUTH_PASSWORD_REGEX_REQ' => 'Das Passwort enthält mindestens ein Großbuchstaben, ein Kleinbuchstaben, einen Zahlenwert und ein Sonderzeichen (! @ # $% ^ & *).',

		'AUTH_BIRTH_DATE_REQ'   => 'Das eingereichte Geburtsdatum ist erforderlich.',
		// 'AUTH_MOBILENO_NOTSTARTWITHZERO' 	=> 'Geben Sie die gültige Mobiltelefonnummer ein und sollten nicht mit Null beginnen.', 
		'ERR_MOBILE_NO_FORMAT' 			=> "Geben Sie das richtige Handynummernformat ein.",
		'ERR_COUNTRY_CODE_WRONG' 	=> "Ungültige Länder-Vorwahl eingegeben.",
		'AUTH_UNIQUE_MOBILE_USER' => 'Diese Handynummer ist schon vergeben.',
	    'AUTH_FORMAT_MOBILE_USER' => 'Die Handynummer darf nur aus Ziffern bestehen.',
	    'AUTH_DEVICE_TYPE_REQ' => 'Der Anmeldegerätetyp ist erforderlich.',
	    'AUTH_DEVICE_ID_REQ' => 'Die Login-Geräte-ID ist erforderlich.',
	     'AUTH_LENGTH_MOBILE_USER' => 'Die Mobilfunknummer muss zehnstellig sein.',
	    'AUTH_FORMAT_AGE_USER' => 'Das Alter darf nur aus Ziffern bestelen.',
		'AUTH_EMAIL_REQ' 		=> 'Das Feld "Email" ist erforderlich.',
		'AUTH_UNIQUE_EMAIL_USER' => 'Diese Email-Adresse ist schon vergeben.',
	    'AUTH_FORMAT_EMAIL_USER' => 'Das Format der Email-Adresse ist ungültig.',
		'AUTH_AGE_REQ' 			=> 'Das Feld "Alter" ist erforderlich.',
		'AUTH_INVALID_USER' 	=> 'Leider konnten wir die Handynummer nicht Ihrem Namen im System zuordnen. Bitte überprüfen Sie die Daten und kontaktieren Sie die Ordination, wenn nötig.',
		'AUTH_ORDINATION_POSTAL_CODE' 	=> 'Bitte aktualisieren Sie Ihre Postleitzahl.',
		'AUTH_ORDINATION_POSTAL_CODE_NOT_EXIST' 	=> 'Diese Postleitzahl existiert nicht.',
		'AUTH_INVALID_PATIENT' 	=> 'Fehlerhafte Patientendaten.',
	    'AUTH_INACTIVE_USER' 	=> 'Dieser Benutzer ist inaktiv.',
	    'AUTH_SOCIAL_SECURITY_NUMBER'=> 'Die Sozialversicherungsnummer stimmt nicht überein.',
	    'AUTH_USER_PAAWORD_WORNG' 	=> 'Passwort ist falsch.',
	    'AUTH_USER_PAAWORD_REQUEIED'=> 'Passwort ist erforderlich.',
	    'AUTH_NOT_FOUND_USER'   => 'Benutzer existiert nicht - bitte überprüfen.',
	    'ERR_SOMETHING_WRONG' 	=> "Es ist ein Systemfehler aufgetreten. Bitte kontaktieren Sie die Ordination.",
		'AUTH_INVALID_OTP' 	  => 'Falscher Anmelde-Code.',
		'AUTH_FAILED_OTP' 	  => 'Fehler beim Senden des Anmelde-Codes.',
		'AUTH_PATIENT_ID_REQ' => 'Die PatientInnen-ID ist erforderlich.',
		'AUTH_GANY_PATIENT_ID_REQ' => 'ganyMED Patienten-ID erforderlich.',
		'AUTH_OTP_REQ' 		  => 'Das Einmal-Kennwort ist erforderlich.',
		'AUTH_SYSTEM_FAIL' 	  => 'Es konnte kein Token erzeugt werden. Bitte kontaktieren Sie die Ordination.',
	    'AUTH_OTP_EXPIRED' 	  => 'Das Einmal-Kennwort ist abgelaufen.', 
	   

	/*--------------------------------------
	|	Module name MenstruationCycle
	-------------------------------------------------------------------*/

		// Response messages
	    'ERR_INVALID_DATA' 	 => 'Ungültige Daten.',
	    'DATA_INSERTED' 	 => 'Die Daten wurden erfolgreich eingefügt.', 
	    'ERR_NOT_MATCH' 	 => 'Keine Übereinstimmung.',
	    'DATA_MATCH_SUCCESS' => 'Der Eintrag wurde gefunden.',
	    'ERR_DATA_NOT_MATCH' => 'Eintrag stimmt nicht überein.',
	    'DATE_NOT_FOUND' 	 => 'Not found.',

	/*--------------------------------------
	|	Module name Appointment has notification
	-------------------------------------------------------------------*/

		// Response messages
	    'NOTIFICATION_UPDATE_SUCCESS' 	 => 'Benachrichtigungsstatusaktualisierung erfolgreich.',
	    'NOTIFICATION_UPDATE_FAIL' 	 => 'Benachrichtigungsstatus kann nicht aktualisiert werden.', 
	    // 'ERR_NOT_MATCH' 	 => 'Keine Übereinstimmung.',
	    // 'DATA_MATCH_SUCCESS' => 'Der Eintrag wurde gefunden.',
	    // 'ERR_DATA_NOT_MATCH' => 'Eintrag stimmt nicht überein.',
	    // 'DATE_NOT_FOUND' 	 => 'Not found.',

	/*--------------------------------------
	|	Module name Emaergency Request
	-------------------------------------------------------------------*/ 

		// Response error
		'ERR_PATIENT_ID_REQ'	=> 'Das Feld "PatientInnen-ID" ist erforderlich.',
		'ERR_CURRENT_COMPLAINT_REQ'			=> 'Das Feld "Aktuelle Beschwerden" ist erforderlich.',
		'ERR_PREVIOUS_TREATMENT_REQ'	=> 'Das Feld "Letzte Behandlung" ist erforderlich.',

		// Response messages
		'SUCCESS_REQUEST_SEND'	=> 'Notfall-Nachricht wurde versendet.', 


	/*--------------------------------------
	|	Module name Examinations 
	-------------------------------------------------------------------*/

		// Response error
		'AUTH_PATIENT_AGE_REQ' => 'Das Feld "Alter" ist erforderlich.',
		'AUTH_PATIENT_GENERAL_TYPE' => 'Das Typfeld ist erforderlich.',
		'ERR_NOT_FOUND'  => 'Kein Eintrag gefunden.',
		'DATA_NOT_FOUND' => 'Nicht gefunden.',  
		'ERR_EXAMS_NOT_SELECTED' => 'Prüfungen sind nicht ausgewählt.', 

		// Response messages
		'DATA_FOUND_SUCCESS' => 'Der Eintrag wurde gefunden.',


	/*--------------------------------------
	|	Module name Patients
	-------------------------------------------------------------------*/

		// Response error
		'INVALID_PATIENT_ID'  	 => 'Falsche PatientInnen-ID.',
		'PATIENT_FIRST_NAME_REQ' => 'Das Feld "Vorname" ist erforderlich.',
		'PATIENT_FAMILY_NAME_REQ'  => 'Das Feld "Familienname" ist erforderlich.',
		'PATIENT_EMAIL_REQ' 	 => 'Email Feld ist erforderlich.',
		'PATIENT_EMAIL_UNIQUE'	 =>	'Die Email-Adresse muss eindeutig sein.',
		'PATIENT_MOBILE_NO_REQ'  => 'Das Feld "Handynummer" ist erforderlich.',
		'PATIENT_MOBILE_NO_UNIQUE'=>'Diese Handynummer ist schon vergeben.',
		'PATIENT_BIRTH_DATE_REQ' => 'Das Feld "Geburtsdatum" ist erforderlich.',
		'PATIENT_AGE_REQ' => 'Age field is required',
		'PATIENT_ROAD_REQ' => 'Straßen-Feld ist erforderlich.',
		'PATIENT_STREET_NO_REQ' => 'Straße kein Feld erforderlich.',
		'PATIENT_PLACE_REQ' => 'Orts-Feld ist erforderlich.',
		'PATIENT_SOCIAL_SECURITY_NUMBER_REQ' => 'Das Feld Sozialversicherungsnummer ist erforderlich.',
		'PATIENT_POSTALCODE_REQ' => 'Das Feld für die Postleitzahl ist erforderlich.',
		'PATIENT_LOGIN_TYPE_FIELD_REQUIRED' => 'Login Typ Feld ist erforderlich.',
		'PATIENT_UPDATE_FAIL'    => 'Die PatienInnen-Daten konnten nicht geändert werden.',

		// Response messages
		'PATIENT_UPDATE_SUCCESS' => 'PatientInnen-Daten erfolgreich geändert.',
		'EXAMINATION_UPDATE_SUCCESS' => 'Dienste erfolgreich aktualisiert.',
		'PATIENT_GET_SUCCESS' 	=> 'Stammdaten erfolgreich geladen.',

		'PATIENT_GET_FAIL' 		=> 'Stammdaten konnten nicht geladen werden.',
        'IS_UPDATED_FLAG_SEND_SUCCESSFULLY'      => 'Ist das aktualisierte Flag erfolgreich gesendet.',

	/*--------------------------------------
	|	Module name Profile Templates 
	-------------------------------------------------------------------*/

		//'ERR_SOMETHING_WRONG' 		 => 'Something went wrong on the server.',
		'ERR_PROFILE_NOT_FOUND' => 'No Profile Template available for this age.', 
		'PROFILE_DATA_FOUND_SUCCESS' => 'Data found successfully',


	/*--------------------------------------
	|	Module name Diagnostic Findings
	-------------------------------------------------------------------*/

		// Response error
		'ERR_FINDINGS_PATIENT_ID_REQ' => 'Patienten-ID Feld ist erforderlich.',
		'ERR_FINDINGD_TYPE_REQ'		  => 'Das Feld "Befund-Typ" ist erforderlich.',
		'ERR_DOCUMENT_NAME_REQ'		  => 'Das Feld "Name" ist erforderlich.',
		'ERR_FINDING_FILE_REQ'		  => 'Das Feld "Datei" ist erforderlich.',
		'ERR_FINDING_DATE_REQ'		  => 'Das Feld "Datum" ist erforderlich.',
		'ERR_FINDING_COMMENT_REQ'	  => 'Das Feld "Kommentar" ist erforderlich.',
		'ERR_FINDING_STATUS_REQ'	  => 'Das Feld "Status" ist erforderlich.',
		'ERR_START_DATE_REQ'		=> 'Das Feld "Startdatum" ist erforderlich.',
		'ERR_END_DATE_REQ'			=> 'Das Feld "Enddatum" ist erforderlich.',
		'ERR_DIAGNOSTIC_FILE'		=> 'Befund nicht gefunden.',

		'FINDING_TYPE_ID_REQ'		=> 'Das Feld "Befundtyp" ist erforderlich.',
		'ERR_FINDING_ID_REQ'		=> 'Das Feld "Befund-ID" ist erforderlich.',
		'ERR_DOCUMENT_ID_REQ'		=> 'Dokumenten-ID Feld ist erforderlich.',


	/*--------------------------------------
	|	Module name Waiting Number
	-------------------------------------------------------------------*/		
		'ERR_LAT_REQ' 			=> 'Das Feld "Geografische Breite" ist erforderlich.',
		'ERR_LON_REQ' 			=> 'Das Feld "Geografische Länge" ist erforderlich.',
		'APPOINTMENT_NOT_FOUND' => 'Bitte kontaktieren Sie die Ordination.',
		'DISTANCE_INCORRECT'    => 'PatientIn ist mehr als 20km von der Ordination entfernt.',

	/*--------------------------------------
	|	Module name Menstruation Cycle(Settings)
	-------------------------------------------------------------------*/

		// Response error 
		'ERR_LATEST_DATE_REQ' => 'Das Feld "letztes Datum" ist erforderlich.',
		'ERR_LATEST_LENGTH_REQ'		 => 'Das Feld "letzte Länge" ist erforderlich.',

	/*--------------------------------------
	|	Module name Appointment Delay Report
	-------------------------------------------------------------------*/

		// Response error 
		'ERR_DELAY_REPORT_PATIENT_ID_REQ' => 'Patienten-ID Feld ist erforderlich.',
		'ERR_APPOINTMENT_REQ'		  	  => 'Das Feld "Termin-ID" ist erforderlich.',
		'AUTH_DOC_Id_REQ'		  	  => 'Das Feld "Dokument-ID" ist erforderlich.',
		'ERR_DELAY_TIME_REQ'		  	  => 'Das Feld "Zeit" ist erforderlich.',
		'ERR_MESSAGE_REQ'		  		  => 'Das Feld "Individuelle Nachricht" ist erforderlich.',
		'APPOINTMENT_TYPE_ID_REQ'	=> 'Das Feld "Termin-Typ" ist erforderlich.',
		'APPOINTMENT_START_DATE_REQ'=> 'Das Feld "Termin Beginnzeit" ist erforderlich.',
		'APPOINTMENT_END_DATE_REQ'	=> 'Das Feld "Termin Ende" ist erforderlich.',
		'APPOINTMENT_ID_REQ'	=> 'Das Feld "Termin-ID" ist erforderlich.',
		'RECORD_TYPE_REQ'	=> 'Das Feld "Datensatztyp" ist erforderlich.',
		'DOC_STATUS_REQ'	=> 'Das Feld "Dokumentstatus" ist erforderlich.',

		//Login with email address.
		'AUTH_FOUND_SUCCESS' 	=> 'Datenvergleich erfolgreich.',
		'ERR_AUTH_NOT_FOUND' 	=> 'Leider konnten unter dieser Email-Adresse keine Patientendaten gefunden werden.',

	/*--------------------------------------
	|	Module name Appointment
	-------------------------------------------------------------------*/

		// Response error 
		'ERR_APP_PATIENT_ID_REQ' 	=> 'Patienten-ID Feld ist erforderlich.',
		'ERR_APP_DOCTOR_ID_REQ'		=> 'Das Feld "Doktor" ist erforderlich.',
		'ERR_APP_TYPE_ID_REQ'		=> 'Das Feld "Termin-Typ" ist erforderlich.',
		'ERR_APP_DATE_REQ'			=> 'Das Feld "Datum des Termins" ist erforderlich.',
		'ERR_APP_DATE_FORMAT_REQ'	=> 'Das Datum des Termins muss das Datum Jahr-Monat-Tag haben.',
		'ERR_APP_VALID_DATE_REQ'			=> 'Der Termin darf nicht in der Vergangenheit liegen.',
		'ERR_APP_TIMEFRAME_REQ'		=> 'Das Feld "Zeitraum" ist erforderlich.',
		'ERR_APP_ID_REQ'			=> 'Das Feld "Termin-ID" ist erforderlich.', 


		// 'APPOINTMENT_BOOKED_SUCCESS'  => 'Der Termin wurde erfolgreich gebucht.',

		//commented below msg on 24-may-24
		// 'APPOINTMENT_BOOKED_SUCCESS'  => 'Sie haben eine E-Mail, oder SMS erhalten – bitte bestätigen Sie den Link darin.',

		//changed below msg on 24-may-24
		//'APPOINTMENT_BOOKED_SUCCESS'  => 'SIe haben eine E-Mail als Terminbestätigung erhalten.', //commented on 17-dec-25

		'APPOINTMENT_BOOKED_SUCCESS'  => 'Ihr Termin ist erfolgreich gebucht. Wir leiten Sie weiter zu zusätzlichen Angaben und Empfehlungen.', //changed on 17-dec-25


		'APPOINTMENT_UPDATED_SUCCESS' => 'Der Termin wurde erfolgreich aktualisiert.',
		'APPOINTMENT_CANCEL_SUCCESS'  => 'Der Termin wurde erfolgreich abgesagt.',
		'API_APPOINTMENT_SLOT_ALREADY_EXIST' => "Terminslot ist bereits gebucht.",	

		// Sign Pdf related translations
		'TITLE_PATIENT_NAME' => 'Patientenname',
		'TITLE_PATIENT_DATE_OF_BIRTH' => 'Patient Birthdate',	

		 //Added below code for duplicate patient
		'ERR_PATIENT_UNIQUE'=>'Dieser Patienteneintrag existiert bereits, bitte melden Sie sich mit unserem PW an.',
		'PATIENT_MOB_DOB_UNIQUE'=>'Geburtsdatum und Handynummer des Patienten bereits vergeben.',
		'ERR_PATIENT_GENDER_REQUIRED' => 'Das Feld "Geschlecht" ist erforderlich.',
		 
		 //Roshani added this msg for week days
		'WEEK_DATA_NOT_FOUND' => 'Wochentage nicht gefunden.',

		'ERR_DOCTOR_ID_REQUIRED' =>'Arztfeld ist erforderlich.',
		'ERR_APPOINTMENT_TYPE_ID_REQUIRED' =>'Das Feld "Termintyp" ist erforderlich.',
		'ERR_WEEKDAY_REQUIRED' =>'Das Feld "Wochentag" ist erforderlich.',
		'ERR_TIME_FROM_REQUIRED' =>'Zeit vom Feld ist erforderlich.',
		'ERR_TIME_TO_REQUIRED' =>'Zeit bis zum Feld ist erforderlich.',
		'ERR_TIME_FRAME_REQUIRED' =>'Zeitrahmenfeld ist erforderlich.',
		'ERR_TIME_FRAME_NOT_FOUND' =>'Der Arzt ist am ausgewählten Datum nicht verfügbar.',
		'FAIL_APPOINTMENT_TIME_FRAME' => "Es konnten keine Zeitrahmen für den Arzt abgerufen werden. Auf dem Server ist ein Fehler aufgetreten.",  

		'ERR_DOCTOR_NOT_AVALIABLE' =>'Der Arzt ist am ausgewählten Datum nicht verfügbar.',
		'ERR_WEEKDAY_INVALID' => "Die ausgewählten Tage sind ungültig.",
		'REQUIRED_FIELDS' => "Die Felder sind Pflichtfelder.",
		'ERR_APP_ID_NOT_EXIST' => 'Der Termintyp ist nicht vorhanden.',
 		'ERR_DATE_FORMAT_DD_MM_YYYY' => 'The date must be in the format dd.mm.yyyy.',
		// 'ERR_DOCTOR_NOT_AVALIABLE' =>'Dieser Arzt ist im gewählten Zeitraum nicht verfügbar',//added by roshani
		
 		/******* Roshani added below error msg for 146 ********/
 		'USER_NOT_EXIST' => 'Dieser Benutzer existiert nicht.',
		'USER_EXIST' => 'Dieser Benutzer existiert.',

        /*********** CR #102 ***********/
        'ERR_COUNTRY_REQUIRED'=> 'Das Feld „Land“ ist erforderlich.',
        'ERR_COUNTRY_IN' => 'Das Land muss entweder Österreich, Deutschland oder die Schweiz sein.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY' => 'Ungültige Postleitzahl für Deutschland. Es muss 5-stellig sein.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1' => 'Ungültige Postleitzahl für die ',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2' => ' Es muss 4-stellig sein.',
        /*********** CR #102 ***********/

        /*************CR #191***************************/
        'ERR_MIMIMUM_AGE'=> 'Das angegebene Alter liegt unter dem Mindestalter.',
        'ERR_MINIMUM_AGE_ORDINATION'=> 'Das Mindestalter für diese Ordination wurde nicht erreicht. Bitte überprüfen Sie ihr Geburtsdatum.',
        /***************CR #191**************************/

        'ERR_DOCTOR_NOT_FOUND'  =>'Für die gewählte Kombination von Leistungen stehen keine Ärzt*innen zur Verfügung. Wählen sie bitte weniger oder andere Leistungen aus', //added on 16-sept-25


        'ERR_APP_LOGIN_COUNTRY_CODE' 	=> 'Falsche Telefonnummer – bitte überprüfen Sie auch die Landesvorwahl.',  //Added on 5-nov-25  

        'ERR_PATIENT_BLOCK_ORDINATION'=>'Sie können sich bei dieser Ordination nicht anmelden.',//Added on 3-feb-26
		'COUNTRY_FOUND_SUCCESS' => 'Länderdaten erfolgreich geladen.', //added on 03-march-26 for country code list
		'ERR_ORDINATION_EMAIL_NOT_FOUND' => 'Leider konnte die Nachricht aus technischen Gründen nicht an die Ordination übermittelt werden. Wenden sie sich bitte direkt telefonisch, oder per E-Mail an die Ordination.', //added on 03-march-26 for ordination email not found error message for #325
];