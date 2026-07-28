<?php

return [

	/*--------------------------------------
	|	Common
	-------------------------------------------------------------------*/
		//SITE
		// 'TITLE_SITE_BEGIN' => 'Pure',
		// 'TITLE_SITE_END' => 'GYN',
		'TITLE_SITE_BEGIN' => 'Pure',
		'TITLE_SITE_END' => 'Med',
		'TITLE_SITE_HOME' => 'Home',
		'TITLE_SITE_COPYRIGHT' => 'Copyright',
		'TITLE_SITE_RIGHTS' => 'Alle Rechte vorbehalten.',
		"TITLE_NEW_WINDOW"  => 'Neues Fenster',
		"SERVICE_EVENT_TYPE_MENU" =>'Vorgeschlagene Leistungen',
		"CALENDAR_ID"=>'Calender Id',
		"TITLE_EARMING_MESSAGE_SERVICE_INACTIVE"=>'Leistungen, die als Kontrolle angezeigt werden, müssen aktiv sein',


		//Setting Options
		"IMPORT SETTING" => "EINSTELLUNGEN_IMPORTIEREN",
		"IMPORT_SETTING" => "DASHBOARD BEFUND IMPORT",
		"IS_UPDATED" => "PATIENTEN PASSWORT UPDATE",
		"NEW_WINDOW_SETTING" => "DASHBOARDFENSTER_EIGENSCHAFTEN",
		"MAX_DISTANCE" =>"SUCHRADIUS_ORDINATION",
		"REMINDER_SETTING"=> "ERINNERUNGEN_EINSTELLEN",
		"SEND_FINDING_VIA_EMAIL" => "BEFUNDE_ALS_EMAIL_VERSENDEN",
		"DISMISSAL" => "ENTLASSUNG_LAUFZETTEL_NOTIZEN",
		"FINDING_KEYWORDS" => "BEFUND_KEYWORDS",
		"EXPORT_PATH" => "BEFUNDE_EXPORT_PFAD",
		"BEACONS" => "WARTENUMMER_BLUETOOTH_BEACONS",
		"ADMINISTRATOR_EMAIL" => "ADMINISTRATOR_EMAIL",
		"AUDIO_SOUND_FILE" => "WARTEBILDSCHIRM_AUDIOSIGNAL",
		"HOSPITAL_DISTANCE" => "WARTENUMMER_GPS_UMKREIS",
		"TAB_PIN" => "TABLET_APP_PIN",
		"GDPR_CONTENT" => "APP_DSGVO_CONTENT",
		"TIME_SLOTS_DURATION" => "TERMIN_MINIMALE_DAUER",
		"ORDINATION_EMAIL_ADDRESS" => "ORDINATION_EMAIL_ADRESSE",
		"HOSPITAL_WAITING_MINUTES" => "WARTENUMMER_MAX_ZEIT_ZUM_TERMIN",
		"HOSPITAL_LONGITUDE" => "WARTENUMMER_GPS_LAENGE_ORDINATION",
		"HOSPITAL_LATITUDE" => "WARTENUMMER_GPS_BREITE_ORDINATION",
		"EMERGENCY_BUTTON_SUBJECT" => "APP_NOTFALL_BUTTON_EMAIL_BETREFF",
		"EMERGENCY_BUTTON_EMAIL_ADDRESS" => "APP_NOTFALL_BUTTON_EMAIL_ADRESSE",
		"APP_LOGGED_OUT_IMAGE_LINK" => "APP_BILD_STARTSEITE_LOGOUT",
		"APP_LOGGED_IN_IMAGE_LINK" => "APP_BILD_STARTSEITE_LOGIN",
		"APP_LOGGED_OUT_SALUTATION_TEXT" => "APP_TEXT_STARTSEITE_LOGOUT",
		"APP_LOGGED_IN_SALUTATION_TEXT" => "APP_TEXT_STARTSEITE_LOGIN",
		"APP_LOGGED_MINS" => "APP_LOGIN_ZEITRAUM",
		"TITLE_DISMISSAL_BUTTON_NAME" => 'Wollen sie den Patienten wirklich entlassen?',
		"TITLE_SETTING_WIDTH" => "Breite (px)",
		"TITLE_SETTING_HEIGHT" => "Höhe (px)",
		"TITLE_SETTING_POSITION" => "Position",
		"ERR_SETTING_NUMBER_VALIDATION" => "Der Wert sollte nur in der Nummer foramt angegeben werden.",
		"ERR_SETTING_HEIGHT_REQUIRED" => 'Feld zum Einstellen der Höhe ist erforderlich.',
		"ERR_SETTING_WIDTH_REQUIRED" => 'Feld zum Einstellen der Breite erforderlich.',
		'ERR_BIRTH_DATE_BEFORE_REQUIRED' => 'Das Geburtsdatum muss vor -13 Jahren liegen.',
		'SHOW_EXAMINATION' => 'Leistungen anzeigen',
		'HIDE_EXAMINATION' => 'Leistungen ausblenden',

		"TITLE_RECOMMEND_HOLIDAY_SETTING"  => 'Wochenenden',
		"TITLE_RECOMMEND_WEEKENDS_SETTING"  => 'Ferien',

		"TITLE_RECOMMEND_SMS_SETTING"  => 'SMS',
		"TITLE_RECOMMEND_EMAIL_SETTING"  => 'E-mail',
		"TITLE_CHOICE_OF_CHANNELS_SETTING"  => 'Auswahl der Kanäle',
		"TITLE_REMINDER_FOR_RECOMMENED_SETTING"  => 'Erinnerungen für empfohlen',
		"TITLE_DEFAULT_TEXT_SETTING"  => 'Standardtexte für Erinnerungen',
		"TITLE_DEFAULT_TEXT_PUSH_REMINDER_SETTING"  => 'Push-Benachrichtigungserinnerungstext',
		"TITLE_DEFAULT_TEXT_MAIL_REMINDER_SETTING"  => 'Mail-Erinnerungstext',
		"TITLE_DEFAULT_TEXT_SMS_REMINDER_SETTING"  => 'SMS-Erinnerungstext',
		"TITLE_GENERAL_SETTING" => 'Allgemeines',

		// "TITLE_GENERAL_REMINDER" => 'Termintypabhängige Erinnerung', //commented on 16-jan-24
		"TITLE_GENERAL_REMINDER" => 'Folge-Leistungsempfehlung', //changed on 16-jan-24


		// "TITLE_AGE_REMINDER" => 'Altersabhängige Erinnerung', //commented on 16-jan-24
		"TITLE_AGE_REMINDER" => 'Profilabhängige Leistungsempfehlung', //changed on 16-jan-24

		"TITLE_PERIOD_OF_REMINDER_CONTROL" => 'Zeitintervall der Leistungsempfehlung',


		// "TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER" => 'Zeiträume einer allgemeinen Erinnerung', //commneted on 14-feb-25 for #303 issue
		"TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER" => 'Vorlaufzeit zum Start',//Added on 14-feb-25 for  for #303 issue



		// "TITLE_CHECKUP_REMINDER" => 'Erinnerung Verlaufskontrolle',//commented on 16-jan-24
		"TITLE_CHECKUP_REMINDER" => 'Kontroll-Leistungsempfehlung', //changed on 16-jan-24

		"TITLE_PERIOD_OF_REMINDER" => 'Erinnerungsfristen',
		"TITLE_NEW_REMINDER" => 'Periode innerhalb des Intervalls',
		"TITLE_REMINDER" => 'Erinnerung',
		"TITLE_REMINDER_POPUP" => 'Leistungsempfehlung (LE)',
		"TITLE_DR_DASH_REMINDER" => 'Verlaufkontrolle',
		"TITLE_DR_DASH_SELECT_REMINDER" => 'Bitte auswählen',//'Wählen Sie Reste aus',
		"TITLE_DR_DASH_CONTROL_REMINDER" => 'Kontrolle',
		"TITLE_FIRST_REMINDER" => 'Vorankündigung Leistungsempfehlung',
		"TITLE_TIME_INTERVAL" => 'Erinnerungsfrequenz',
		"TITLE_NUMBER_OF_INTERVAL" => 'max. Anzahl Erinnerungen',

		"TITLE_SERVICES" => 'Leistung',
		"TITLE_REVERS" => 'Revers',
		"TITLE_FINDING" => 'Befunde',



		// Module Titles
		'TITLE_MANAGE_TEXT' => 'verwalten',
		'TITLE_DOWNLOAD_TEXT' => 'Herunterladen',
		'TITLE_CREATE_TEXT' => 'Neu',
		'TITLE_EDIT_TEXT' => 'Bearbeiten',
		'TITLE_INFORMATION_TEXT' => 'Information',
		'TITLE_ACTIONS_TEXT' => 'Aktionen',
		'TITLE_SELECT_TEXT' => 'Auswahl',
		'TITLE_STATUS_ACTIVE_TEXT'  => 'Aktiv',
		'TITLE_STATUS_INACTIVE_TEXT'  => 'Inaktiv',
		'TITLE_DOCTOR_AVAILABILITY'  => 'Verfügbarkeit des Arztes',
		'TITLE_DOWNLOAD_DOC'  => 'Dokument herunter laden',

		'TITLE_PAGE_SHOW'  => 'Einträge',
		'TITLE_PAGE_TO'    => 'bis',
		'TITLE_PAGE_OF'    => 'von',

		//Buttons
		'TITLE_BACK_BUTTON' => 'Zurück',
	 	'TITLE_SAVE_BUTTON' => 'Speichern',
	 	'TITLE_CLOSE_BUTTON' 	=> 'Schließen',
	 	'TITLE_RESET_BUTTON' => 'Zurücksetzen',
	 	'TITLE_ADD_BUTTON' => 'hinzufügen',
	 	'TITLE_REMINDER_SERVICE_BTN' => 'Erinnerung konfigurieren',
	 	'TITLE_OK_BUTTON' 		=> 'in Ordnung',
		'TITLE_REMOVE_BUTTON' 	=> 'Entfernen',
	 	'TITLE_VIEW_BUTTON' => 'ansehen',

	 	// 'TITLE_DELETE_BUTTON' => 'Löschen',
	 	'TITLE_DELETE_BUTTON' => 'Gelöscht',
	 	'TITLE_CANCEL_BUTTON' => 'Abbrechen',
	 	'TITLE_DELETE_SURE' => 'Sind Sie sicher?',
	 	'TITLE_CHECKLIST_BUTTON'=> 'Checklisten',
		'TITLE_DOCUMENT_BUTTON' => 'Dokumente',
		'TITLE_APPOINMENT_TYPE_BUTTON' => 'Terminarten',
	 	'TITLE_DELETE_QUESTION' => 'Wollen Sie löschen?',
	 	'TITLE_DELETE_QUESTION2' => 'Sie möchten es dauerhaft löschen?',
	 	'TITLE_REMINDER_TITLE'  => 'Dies löscht alle vorhandenen Erinnerungen !!',
	 	'TITLE_REMINDER_UPDATE'  => 'Erinnerung erfolgreich aktualisiert.',
		'TITLE_REMINDER_DEACTIVATE_TITLE' 	=> 'Möchten Sie wirklich deaktivieren?',
	 	'TITLE_OTHER_TEXT' => 'Speichern Sie die Änderungen?',
	 	'TITLE_DISMISSAL_BUTTON'=> 'Entlassung erledigt',
	 	'TITLE_EXAMINATION_BUTTON'=> 'Prüfungen durchgeführt',

	 	//Placeholder Title
	 	'TITLE_SEARCH_TEXT' => 'Suchen...',
	 	'TITLE_SEARCH_WEB_TEXT' => 'Buchen',
	 	// ERROR MESSAGES
	 	'ERR_SOMETHING_WRONG' => 'Am Server ist ein Fehler aufgetreten. Kontaktieren Sie bitte Ihren Administrator.',
 		'ERR_SESSION_TIMEOUT' => 'Eine Zeitüberschreitung ist aufgetreten. Bitte versuchen Sie es nach einer neuen Anmeldung noch einmal.',
 		'FAIL_LANGUAGE_STATUS' => 'Fehler beim Aktualisieren der Sprache am Server.',
 		'CHANGE_LANGUAGE_STATUS' => 'Die Sprache wurde erfolgreich aktualisiert.',

 		// Response status
 		'RESP_SUCCESS' 	=> 'success',
		'RESP_ERROR' 	=> 'error',
		'RESP_WARNING' 	=> 'warning',
		'RESP_INFO' 	=> 'info',
		'ERR_NOT_FOUND'  => 'Kein Eintrag gefunden.',
		'ERR_NO_NOTES_FOUND'  => 'Keine Notizen gefunden.',
		'ERR_ENTER_NOTES_FOUND'  => 'Bitte geben Sie Notizen ein.',
		'ERR_DEFAULT_TEXT_REQUIRED'=>'Das Standardtextfeld ist erforderlich',
		'ERR_SETTING_TEXT_REQUIRED'  =>'Textfeld ist erforderlich',
		'ERR_SETTING_IPHONE_REQUIRED'=>'Iphone field is required',
		'ERR_SETTING_ANDOID_REQUIRED'=>'Iphone Feld ist erforderlich',
		'ERR_SETTING_TABLET_REQUIRED'=>'Tablet-Textfeld ist erforderlich',


	/*--------------------------------------
	|	Module name Sidebar
	--------------------------------------------------------------*/
		// Module Titles
		'TITLE_DASHBOARD' => 'Kalender',
 		'TITLE_ACTIVITY' => 'Aktivitäten',

 	/*--------------------------------------
	|	Assistant Dashboard
	-------------------------------------------------------------------*/
		// Module Titles
		'TITLE_ASSISTANT_DASHBOARD' => 'Assistenz Dashboard',
		'TITLE_ASSISTANT_DASHBOARD_TODO_LIST'  => 'ToDo Liste',
		'TITLE_ASSISTANT_DASHBOARD_APPOINMENT' => 'Kalender',
		'TITLE_ASSISTANT_APPOINMENT_TITLE' => 'Termin Details',
		'TITLE_ASSISTANT_DASHBOARD_DUPLICATE' => 'Duplikat',
		'TITLE_ASSISTANT_DASHBOARD_WAITING'    => 'Warteliste',
		'TITLE_ASSISTANT_DASHBOARD_DISMISSAL'  => 'Entlassung',
		'TITLE_NEW'    => 'Neu',
		'TITLE_FINDING'=> 'Befund',
		'SEND_MAIL_TO'=>'Empfänger E-Mail',
		'TITLE_COPY'   => 'Kopieren',
		'TITLE_FINDING_DOCUMANT_NAME' => 'Dokumentname',
		'TITLE_TODO_LIST_FINDING'    => 'Befunddatum',
		'TITLE_TODO_LIST_IMPORT_FINDING' => 'Befund importieren',
		'TITLE_TODO_LIST_SEND_FINDING' => 'Mail senden',
		'TITLE_TODO_LIST_SEND'=>'Senden',
		'TITLE_TODO_LIST_SEND_ALL'=>'Sende alle',
		'TITLE_TOTO_SELECT_FINDING_TYPE'   => 'Art auswählen',
		'TITLE_TOTO_NEW_COMPLETED'   => 'Abgeschlossen',
		'TITLE_TODO_LIST_IMPORT'     => 'Importieren',
		'TITLE_TOTO_NEW_COMPLETED_NEXT'   => 'Vollständig und weiter',
		'TITLE_TOTO_ABORT'          => 'Abbrechen',
		'TITLE_TOTO_VIEW_MORE'   => 'Mehr sehen',
		'ERR_DISMISSAL_REQUIRED' 	=> "Bitte wählen Sie bei Liste eine Entlassung.",
		'DISSMISSAL_SUCCESS_MSG' 	=> "Entlassung erledigt.",
		'DISSMISSAL_ERROR_MSG' 	    => "Etwas wenden, um falsch zu sein.",
		'ERR_DISMISSAL_REQUIRED_NOT_EXIST' =>'Aktuell keine Daten vorhanden',
		'ERR_TODO_LIST_IMP_DOCUMENT_NAME'  =>'Das Feld für den Dokumentnamen ist erforderlich.',
		'ERR_TODO_LIST_IMP' =>'Datei wird hochgeladen',
		'ERR_CLEAR_TODO_LIST_WARNING_MSG'  =>'Alle Daten wurden erfolgreich kopiert / eingefügt?',
		'WARNING_TITLE' =>'Ja',
		'WARNING_TITLE_NO' =>'Nein',
		'TODO_LIST_IMPORT_CREATE' => "Datei erfolgreich importiert.",
		'FINDING_SEND_SUCCESS' => "Sende erfolgreich finden.",
		'TITLTE_TODO_WARNING' =>'Warnung',
		'TITLTE_TODO_COMPLETED_MSG' =>'Abgeschlossen.',
		'TITLTE_TODO_NOT_COMPLETED_MSG' =>'Not completed.',

		//Added by Shyam 04-01-22
		'MSG_FINDING_IMPORTED_FAILED' =>'Der Befund konnte nicht importiert werden, weil der Patientenname nicht korrekt erkannt wurde',
		'MSG_FINDING_IMPORTED_SUCCESSFULLY' =>'Die Suche wurde erfolgreich importiert.',
		'MSG_FINDING_IMPORTED_ERR_MSG' =>'Dateien enthalten keine Patientendaten.',
		'MSG_FINDING_IMPORTED_SUCCESS' =>'Nachfolgend finden Sie die Dateien, die mit den Patientendaten abgeglichen werden : ',
		'MSG_FINDING_IMPORTED_FAILURE' =>'Nachfolgend sind die Dateien aufgeführt, die nicht mit den Patientendaten abgeglichen werden : ',

		'MSG_FINDING_SEND_VIA_MAIL' =>'Are you sure you want to send findings?',
		'MSG_SEND_DOC_FOR_PATIENT' =>'E-Mail an Patienten schicken',
		'MSG_FINDING_SEND_PUSH_NOTIFICATION' =>'Push-Benachrichtigung erfolgreich gesendet.',
		'TITLE_PRINT' =>'drucken',
		'TITLE_DOCUMENT_SEND' =>'Dokument erfolgreich gesendet.',
		'TITLE_NOT_EXIST_APP' =>'Der Versand von Befunden über E-Mail ist in den Einstellungen nicht aktiviert und die Patientin nutzt keine Smartphone-App. Daher kann der Befund nicht digital zugestellt werden.',
 	/*--------------------------------------
	|	Module name LOGIN
	-------------------------------------------------------------*/
		// ERROR MESSAGES
		'ERR_FIRSTNAME_REQUIRED' => 'Der Vorname ist erforderlich.',
 		'ERR_FIRSTNAME_REGEX_REQUIRED' => 'Der Vorname darf nur Buchstaben und Zahlen enthalten.',
 		'ERR_LASTNAME_REQUIRED' => 'Der Nachname ist erforderlich.',
 		'ERR_LASTNAME_REGEX_REQUIRED' => 'Der Nachname darf nur Buchstaben und Zahlen enthalten',
	 	'ERR_USERNAME_REQUIRED' => 'Der Benutzername ist erforderlich.',
	 	'ERR_EMAIL_REQUIRED' => 'Das E-Mail-Feld muss ein gültiges Format haben.',
	 	'ERR_PASSWORD_REQUIRED' => 'Das Kennwort ist erforderlich.',
	 	'ERR_LANGUAGE_REQUIRED' => 'Die Sprache ist erforderlich.',

	 	// Response error
	 	'ERR_ACCOUNT_DEACTIVATE' => 'Dieser Account ist deaktiviert, setzen Sie sich bitte mit dem Administrator in Verbindung.',
	 	'ERR_ACCOUNT_EMAIL_MOBILE' 	=> "Falsche Handy- oder E-Mail-Details.",
		'ERR_ACCOUNT_PASSWORD_WRONG' 	=> "Falsches Passwort.",

	 	'ERR_ACCOUNT_INCORRECT' => 'Falsche Anmeldedaten.',
	 	'ACCOUNT_SUCCESS' => 'Anmeldung erfolgreich.',

	 	// Titles
	 	'TITLE_LOGIN_HEADING' => 'Bitte anmelden um die Sitzung zu starten',
	 	'TITLE_EMAIL' => 'E-Mail',
	 	'TITLE_PASSWORD' => 'Kennwort',
	 	'TITLE_SELECT_LANGUAGE' => 'Sprache wählen',
	 	'TITLE_LANGUAGE_GERMAN' => 'Deutsch',
	 	'TITLE_LANGUAGE_ENGLISH' => 'English',
	 	'TITLE_REMEMBER_ME' => 'Anmeldedaten merken',
	 	'TITLE_FORGOT_PASSWORD' => 'Kennwort vergessen',
	 	'TITLE_BUTTON_LOGIN' => 'Anmelden',
	 	'TITLE_RESEND_OTP' 	=> 'Erneut senden',
	 	'TITLE_BUTTON_LOGOUT' => 'Abmelden',
	 	'COPY_MSG_SUCCESS'=> 'Name erfolgreich kopieren.',
		'COPY_MSG_ERROR'  => 'Nachname ist keine Kopie.',

	/*--------------------------------------
	|	Module name Change Password
	--------------------------------------------------------------*/
		// Titles
		'TITLE_CHANGE_PASSWORD_MODULE' => 'Kennwort ändern',
	 	'TITLE_CHANGE_PASSWORD_HEADING' => 'Kennwort aktualisieren',
	 	'TITLE_CHANGE_PASSWORD_OLD' => 'Altes Kennwort',
	 	'TITLE_CHANGE_PASSWORD_BUTTON' => 'Aktualisierung',

	 	// Errors
	 	'ERR_CHANGE_PASSWORD_OLD' => 'Altes Kennwort-Feld ist erforderlich.',
 		'ERR_CHANGE_PASSWORD_NEW' => 'Neues Kennwort-Feld ist erforderlich.',
 		'ERR_CHANGE_PASSWORD_NEW_LENGTH' => 'Das neue Kennwort muss mindestens 6 Zeichen lang sein.',

 		// Response messages
 		'CHANGE_PASSWORD_STATUS' => 'Kennwort erfolgreich aktualisiert.',

 		// Response error
 		'FAIL_CHANGE_PASSWORD_STATUS' => 'Server-Fehler beim Aktualisiern des Kennworts.',
 		'FAIL_CHANGE_PASSWORD_MATCH' => 'Das alte Kennwort stimmt nicht mit dem Kennwort überein, das sie zum Anmelden bennutzt haben.',

 	/*--------------------------------------
	|	Module name Forgot Password
	--------------------------------------------------------------*/
		// Titles
		'TITLE_FORGOT_PASSWORD_MODULE' => 'KENNWORT VERGESSEN',
 		'TITLE_FORGOT_PASSWORD_HEADING' => 'Haben sie ihr Kennwort vergessen? Hier können sie einfach ein neues Kennwort anfordern.',
 		'TITLE_FORGOT_PASSWORD_BUTTON' => 'Bitten um neues Kennwort',

 		// Response messages
 		'FORGOT_PASSWORD_STATUS' => 'Kennwort-Rücksetzen Link erfolgreich versendet, überprüfen Sie bitte Ihre E-Mails.',

 		// Response error
 		'FAIL_FORGOT_PASSWORD_MATCH' 	=> 'Benutzername wurde nicht im System gefunden.',
		'FAIL_FORGOT_PASSWORD_DISABLED' 	=> 'Benutzer-Account ist deaktiviert. Bitte wenden sie sich an den Administrator.',

	/*--------------------------------------
	|	Module name Reset Password
	-------------------------------------------------------------*/
		// Titles
		'TITLE_RESET_PASSWORD_MODULE' => 'KENNWORT ZURÜCK SETZEN',
 		'TITLE_RESET_PASSWORD_HEADING' => 'Mit nur einem weiteren Schritt erhalten sie ein neues Kennwort.',
 		'TITLE_RESET_PASSWORD_CONFIRM_PASSWORD' => 'Kennwort bestätigen',
 		'TITLE_RESET_PASSWORD_BUTTON' => 'Kennwort ändern',

 		// Errors
 		'ERR_RESET_PASSWORD_CONFIRM_PASSWORD' 	=> 'Das Feld zur Bestätigung des Kennworts ist erforderlich.',

 		// Response messages
 		'RESET_PASSWORD_STATUS' => 'Kennwort wurde erfolgreich aktualisiert.',

 		// Response error
 		'FAIL_RESET_PASSWORD_STATUS_CHANGE' => 'Fehler beim Zurücksetzen des Kennworts (Token abgelaufen).',

 	/*--------------------------------------
	|	Module name USERS
	------------------------------------------------------------*/

		// Errors
		'ERR_FIRST_NAME' => 'Vorname-Feld ist erforderlich.',
 		'ERR_LAST_NAME' => 'Nachname-Feld ist erforderlich.',
	 	'ERR_EMAIL_NAME' => 'E-Mail Feld ist erforderlich.',
	 	'ERR_EMAIL_FORMAT' => 'E-Mail Format ist ungültig.',
	 	'ERR_EMAIL_DUP' => 'Diese E-Mail wird bereits verwendet.',
	 	'ERR_EMAIL_DUP_PATIENT' 	=> "Patient has already taken.",
	 	'ERR_PASS' => 'Kennwort-Feld ist erforderlich.',
	 	'ERR_PASS_MIN_SIZE' => 'Kennwort muss mindestens 6 Zeichen lang sein.',
	 	'ERR_CONFIRM_PASS' => 'Kennwort-bestätigen Feld ist erforderlich.',
	 	'ERR_COMPARE_PASS' => 'Die Kennwort-Bestätigung stimmt nicht mit dem Kennwort überein.',
	 	'ERR_PROFILE_IMAGE_FORMAT' 	=> "Das Profilbild muss eine Datei vom Typ jpeg, jpg, png, gif sein.",
	 	'ERR_ROLE' => 'Rolle-Feld ist erforderlich.',
	 	'ERR_COLOR' => 'Farbe-Feld ist erforderlich.',
	 	'ERR_MOBILE_NUMBER_REQUIRED' => 'Handynummer-Feld ist erforderlich',

	 	// Titles
	 	'TITLE_USERS_MODULE' => 'Benutzer',
	 	'TITLE_DOCTORS_TEXT' 	=> 'Ärzte',
	 	'TITLE_USER_MODULE' => 'Benutzer',
	 	'TITLE_LOGIN_MODULE' => 'Admin Anmeldung',
	 	'TITLE_FIRST_NAME' => 'Vorname',
	 	'TITLE_USER_NAME' => 'Name',
	 	'TITLE_LAST_NAME' => 'Nachname',
	 	'TITLE_EMAIL_ADDRESS' => 'E-Mail',
	 	'TITLE_MOBILE_NO' => 'Handynummer',
	 	'TITLE_PASS' => 'Kennwort',
	 	'TITLE_CONFIRM_PASS' => 'Kennwort bestätigen',
	 	'TITLE_ROLE' => 'Rolle',
	 	'TITLE_SELECT_ROLE' => 'Rolle wählen',
	 	'TITLE_COLOR' => 'Farbe',
	 	'TITLE_PREVIEW'			=> 'Vorschau',
	 	'TITLE_SELECT_COLOR'	=> 'Farbe auswählen',
	 	'TITLE_PROFILE_IMAGE' 	=> 'Profilbild',

	 	// Response messages
	 	'USER_CREATED' => 'Benutzer erfolgreich angelegt.',
		'USER_UPDATED' => 'Benutzer erfolgreich aktualsiert.',
		'USER_DELETED' => 'Benutzer erfolgreich gelöscht.',
		'USER_STATUS_CHANGED' => 'Benutzerstatus erfolgreich geändert.',

		// Response error
		'FAIL_USER_CREATE' => 'Server-Fehler beim Anlegen des Benutzers.',
 		'FAIL_USER_UPDATE' => 'Server-Fehler beim Aktualisieren des Benutzers.',
 		'FAIL_USER_DELETE' => 'Server-Fehler beim Löschen des Benutzers.',
 		'FAIL_USER_NOT_DELETE' => 'Der aktuell angemeldete Benutzer kann nicht gelöscht werden',
 		'FAIL_USER_STATUS_CHANGE' => 'Server-Fehler beim Ändern des Benutzer-Status.',

 		'AUTH_OTP_REQ' 		  => 'Das Einmal-Kennwort ist erforderlich.',
		'AUTH_OTP_EXPIRED' 	  => 'Das Einmal-Kennwort ist abgelaufen.',
		'AUTH_INVALID_OTP' 	  => 'Falscher Anmelde-Code.',
		'AUTH_FAILED_OTP' 	  => 'Fehler beim Senden des Anmelde-Codes.',
		'TITLE_OTP' 	      => 'OTP',
		'AUTH_USER_RESEND_OTP_SUCCESS'=> 'Das Einmal-Kennwort wurde erfolgreich versendet.',

 	/*--------------------------------------
	|	Module name Roles
	---------------------------------------------------------------*/
		// Titles
		'TITLE_ROLES_MODULE' => 'Rollen',
 		'TITLE_ROLE_NAME' => 'Name',
 		'TITLE_ROLE_IDENTIFIER' => 'Identifizieren',
 		'TITLE_ROLE_HEADING' => 'Neue Rolle hinzufügen',
 		'TITLE_DOCTOR_ROLE' => 'Arzt',
		'TITLE_ASSISTANT_ROLE' => 'Assistent',

 		// Errors
 		'ERR_ROLE_NAME' => 'Rollen-Namensfeld ist erforderlich.',
 		'ERR_ROLE_REGEX' => 'Rollen-Name darf nur Buchstaben und das Zeichen - enthalten.',
 		'ERR_ROLE_UNIQUE' => 'Rollen-Name bereits vergeben.',
 		'ERR_ROLE_IDENTIFIER' => "Bezeichnerfeld erforderlich.",

 		// Response messages
 		'ROLE_CREATED' 	=> 'Rolle erfolgreich angelegt.',
		'ROLE_UPDATED' 	=> 'Rolle erfolgreich aktualisiert.',
		'ROLE_DELETED' 	=> 'Rolle erfolgreich gelöscht.',

		// Response error
		'FAIL_ROLE_CREATE' => 'Server-Fehler beim Anlegen der Rolle.',
 		'FAIL_ROLE_UPDATE' => 'Server-Fehler beim Aktualisieren der Rolle.',
 		'FAIL_ROLE_DELETE' => 'Server-Fehler beim Löschen der Rolle.',
 		'FAIL_ROLE_DELETE_ASSIGNED' => 'Fehler beim Löschen der Rolle, da der Rolle Berechtigungen zugewiesen sind.',

 	/*--------------------------------------
	|	Module name Permissions
	-------------------------------------------------------------*/
		//Button
		'DETAIL' => 'Details',

		// Titles
		'TITLE_TOTAL_USERS' => 'Gesamte Benutzer',
		'TITLE_OFF' => 'aus',
		// 'TITLE_ON' => 'ein', //Commented by Shyam 21-02-22
		'TITLE_ON' => 'Aktiv', //Added by Shyam 21-02-22
		'TITLE_PERMISSION_MODULE' => 'Berechtigungen',
		'PERMISSION_UPDATED' => 'Berechtigungen erfolgreich aktualisiert.',

		// Response error
		'FAIL_PERMISSION_UPDATE' => 'Server-Fehler beim Aktualisieren der Berechtigungen.',

	/*--------------------------------------
	|	Module name Activity Logs
	-------------------------------------------------------------*/
		// Module Title
		'TITLE_ACTIVITY_LOG_TEXT' => 'Aktivitäten Log',

		// Titles
		'TITLE_ACTIVITY_MODULE' => 'Modul',
 		'TITLE_ACTIVITY_ACTION' => 'Aktivität',
 		'TITLE_ACTIVITY_MESSAGE' => 'Nachricht',
 		'TITLE_ACTIVITY_METHOD' => 'Methode',
 		'TITLE_ACTIVITY_URL' => 'URL',
 		'TITLE_ACTIVITY_IP' => 'IP',
 		'TITLE_ACTIVITY_AGENT' => 'Agent',
 		'TITLE_ACTIVITY_USER_ID'  	=> 'Benutzer-ID',
 		'TITLE_ACTIVITY_USER' => 'Benutzer',
 		'TITLE_CREATED_AT_TEXT'  	=> 'Erstellt bei',

 	/*--------------------------------------
	|	Module name Setting
	---------------------------------------------------------------*/
		// Module Title
		'TITLE_SETTING_TEXT' => 'Einstellungen',
		'TITLE_SETTING_TEXT_SMARTPHONE' => 'Text',
		'TITLE_SETTING_TEXT_IPHONE' => 'Iphone',
		'TITLE_SETTING_TEXT_TABLET' => 'Tablet',
		'TITLE_SETTING_TEXT_ANDOID' => 'Andoid',
		// ERROR MESSAGES
		'ERR_SETTING_KEY_REQUIRED' => 'Key-Feld ist erforderlich.',
 		'ERR_SETTING_VALUE_REQUIRED' => 'Wert-Feld ist erforderlich',
 		'ERR_SETTING_VALUE' => "Geben Sie den Einstellungswert in Minuten ein.",
 		'ERR_SETTING_DESCRIPTION_REQUIRED' => 'Beschreibungs-Feld ist erforderlich',
 		'ERR_SETTING_EXPORT_PATH_REQUIRED' 	=> "Pfadfeld ist erforderlich.",

 		// Titles
 		'TITLE_SETTING_KEY' => 'Key setzen',
 		'TITLE_SETTING_VALUE' => 'Wert setzen',
 		'TITLE_SETTING_DESCRIPTION' => 'Beschreibung setzen',
 		'TITLE_SETTING_STATUS' => 'Status setzen',
 		'TITLE_SETTING_EVENT_TYPE'	   => 'Event Type',
 		'TITLE_SETTING_STATUS_ACTIVE' => 'Aktiv',

 		// Response error
 		'FAIL_SETTING_CREATE' => 'Server-Fehler beim setzen der Einstellung.',
 		'FAIL_SETTING_DELETE' => 'Server-Fehler beim Löschen der Einstellung.',

 		// Response messages
 		'SETTING_CREATED' => 'Einstellung erfolgreich gesetzt.',
 		'SETTING_UPDATED' => 'Einstellung erfolgreich aktualisiert.',
 		'PASSWORD_SETTING_UPDATED' => "Die Einstellung für die Kennwortaktualisierung wurde erfolgreich aktualisiert.",
 		'SETTING_DELETED' => 'Einstellung erfolgreich gelöscht.',

 		'EXPORT_PATH' => 'Speicherpfad exportieren',

 	/*--------------------------------------
	|	Module name Finding services
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_FINDING_TEXT' => 'Befunddienste',
		'TITLE_FINDING_SERVICES_TEXT' => 'Befunddienste',

		// ERROR MESSAGES
		'ERR_FINDING_SERVICES_NAME_REQUIRED' 	=> "Namensfeld ist erforderlich.",
		'ERR_FINDING_SERVICES_WEB_URL_REQUIRED' => "Web-URL-Feld ist erforderlich.",

		// Titles
		'TITLE_FINDING_SERVICES_NAME' 	=> 'Name',
		'TITLE_FINDING_SERVICES_WEB_URL'=> 'Web-URL',
		'TITLE_FINDING_SERVICES_TYPE' 	=> 'Art',
		'TITLE_FINDING_SERVICES_STATUS'			=> 'Status',
		'TITLE_FINDING_SERVICES_STATUS_ACTIVE' 	=> 'Aktiv',
		'TITLE_DISMISSAL_NAME' 	        => 'Entlassung',

		// Response error
		'FAIL_FINDING_SERVICES_CREATE' => "Fehler beim Erstellen von Suchdiensten. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_FINDING_SERVICES_DELETE' => "Fehler beim Löschen der Suchdienste. Auf dem Server ist ein Fehler aufgetreten.",

		// Response messages
		'FINDING_SERVICES_CREATED' => "Suche nach erfolgreich erstellten Diensten.",
		'FINDING_SERVICES_UPDATED' => "Suche nach Diensten erfolgreich aktualisiert.",
		'FINDING_SERVICES_DELETED' => "Suche nach Diensten erfolgreich gelöscht.",

 	/*--------------------------------------
	|	Module name APPOINTMENT_TYPE
	---------------------------------------------------------------*/

		// Module Title
		'TITLE_APPOINTMENT_TYPE_TEXT' => 'Termin-Typen',
		// 'TITLE_STATUS_APPOINTMENT'	  => 'Ernennung Status', //commented for #285 issue
		'TITLE_STATUS_APPOINTMENT'	      => 'Terminstatus', //changed for #285 issue
		'TITLE_OPTIMAL_APPOINTMENT_LABEL' => 'Optimaler Termin',

		// ERROR MESSAGES
		'ERR_APPOINTMENT_TYPE_NAME_REQUIRED' => 'Namens-Feld ist erforderlich.',
		'ERR_APPOINTMENT_TYPE_NAME_UNIQUE' 	=> "Terminname sollte uique.",
 		'ERR_APPOINTMENT_TYPE_DURATION_REQUIRED' => 'Namens-Feld muss eindeutig sein',
 		'ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED' => 'Beschreibungs-Feld ist erforderlich',

 		// Titles
 		'TITLE_APPOINTMENT_TYPE_NAME' => 'Name',
 		'TITLE_APPOINTMENT_TYPE_DURATION' => 'Dauer',
 		'TITLE_APPOINTMENT_TYPE_DESCRIPTION' => 'Beschreibung',
 		'TITLE_APPOINTMENT_RECOMMEND_EXAM_STATUS' => 'Empfohlene Leistungen nicht anzeigen',
 		'TITLE_APPOINTMENT_TYPE_UPLOAD_DOC_ACTIVE' => 'Dokument für Patienten hochladen',
 		'TITLE_APPOINTMENT_TYPE_STATUS' => 'Status',
 		'TITLE_APPOINTMENT_TYPE_STATUS_ACTIVE' => 'Aktiv',
 		'TITLE_APPOINTMENT_TYPE_DASHBOARD_SETTING' 	=> 'im Ärzte-Dashboard anzeigen', //In Kalender-Übersicht zeigen or 'Am Dashboard zeigen',
 		'TITLE_APPOINTMENT_TYPE_RECOMMENDE_SETTING' => 'Als Empfehlung anzeigen',
 		'TITLE_APPOINTMENT_QR_CODE' => 'QR-Code',
		'TITLE_OPTIMAL_APPOINTMENT_YES' => 'Ja',
		'TITLE_OPTIMAL_APPOINTMENT_NO' => 'NEIN',

 		//Placeholder Title
 		'TITLE_SELECT_DURATION_TEXT' => 'Dauer wählen',

 		// Response error
 		'FAIL_APPOINTMENT_TYPE_CREATE' => 'Server-Fehler beim Anlegen des Termins.',
 		'FAIL_APPOINTMENT_TYPE_UPDATE' => 'Server-Fehler beim Aktualsieren des Termins.',
 		'FAIL_APPOINTMENT_TYPE_DELETE' => 'Server-Fehler beim Löschen des Termins.',
 		'FAIL_APPOINTMENT_TYPE_DELETE_ASSIGNED' => 'Fehler beim Löschen des Termintyps, Da der Termintyp dem Termin zugewiesen ist.',

 		// Response messages
 		'APPOINTMENT_TYPE_CREATED' => "Termin erfolgreich angelegt.",
		'APPOINTMENT_TYPE_UPDATED' => "Termin erfolgreich aktualisiert.",
		'APPOINTMENT_TYPE_DELETED' => "Termin erfolgreich gelöscht",
		'APPOINTMENT_TYPE_NOT_DELETED' => "Termin nicht gelöscht.",
		'DOCTOR_APPOINTMENT_TYPE_UPDATED' => "Terminart erfolgreich aktualisiert.",
		'DOCUMENT_STATUS_UPDATED' => "Dokumentstatus erfolgreich aktualisiert.",
		'DOCUMENT_EXPORTED' => "Ergebnisse erfolgreich exportiert.",

	/*--------------------------------------
	|	Module name Offence
	-------------------------------------------------------------------*/

		// ERROR MESSAGES
		'ERR_OFFENCE_NAME' => 'Namens-Feld ist erforderlich.',
 		'ERR_OFFENCE_DESC' => 'Beschreibungs-Feld ist erforderlich',
 		'ERR_OFFENCE_PENALTY' => 'Penalty-Feld ist erforderlich.',
 		'ERR_OFFENCE_PENALTY_FORMAT' => 'Bitte für Penalty ein gültiges Format eingeben.',

 	/*--------------------------------------
	|	Module name EXAMINATIONS
	-------------------------------------------------------------------*/

		// ERROR MESSAGES
		'ERR_EXAM_NAME_REQUIRED'       => 'Namens-Feld ist erforderlich.',
 		'ERR_EXAM_NAME_REGEX_REQUIRED' => 'Im Namen dürfen nur Buchstaben und Zahlen vorkommen.',
 		'ERR_URL_REQUIRED'             => 'URL-Feld ist erforderlich.',
 		'ERR_CHECK_LIST_REQUIRED'      => "Checklistenfeld ist erforderlich.",
 		'ERR_CHECK_LIST_TYPE_REQUIRED' => "Das Feld für den Checklistentyp ist erforderlich.",

 		// Module Title
 		'TITLE_EXAMINATIONS_TEXT' => 'Leistung',
		'TITLE_SELECT_EXAMINATIONS_TEXT' => 'Bitte auswählen',//'Wählen Sie Dienste aus',
 		'TITLE_EXAMINATIONS_EVENT_TYPE' => 'Ereignistyp',
 		'TITLE_EXAMINATIONS_EVENT_ADMIN' => 'Admin',
		'TITLE_EXAMINATIONS_EVENT_WEB' => 'Web',
		'TITLE_EXAMINATIONS_EVENT_SMARTPHONE' => 'Smart Phone',
		'TITLE_EXAMINATIONS_EVENT_TABLET' => 'Tablet',
		'TITLE_EXAMINATIONS_EVENT_STATUS_DISPLAYED' => 'Displayed',
		'TITLE_EXAMINATIONS_EVENT_STATUS_BOOKED' => 'Booked',
 		'TITLE_VIEW_EXAMINATIONS_TEXT'=>'Leistungen ansehen',
 		'TITLE_EXAMINATIONS_CHECKUP_BUTTON_TEXT' => 'Checkup hinzufügen',
 		'TITLE_DISMISSAL_TEXT'    => 'Entlassung',
 		'TITLE_SELECT_DISMISSAL_TEXT'    => 'Bitte auswählen',//'Wählen Sie Entlassungsoptionen',
 		// Titles
 		'TITLE_EXAMINATION_NAME' => 'Name',
 		'TITLE_EXAMINATION_URL' => 'URL',
 		'TITLE_EXAMINATION_STATUS' => 'Status',
 		'TITLE_EXAMINATION_STATUS_ACTIVE' => 'Aktiv',
 		'TITLE_EXAMINATION_TRIGGER' => 'Trigger',
		'TITLE_EXAM_TRIGGER' => 'Trigger-Prüfung',
		'TITLE_DOCUMENT_NAME' => 'Dokument',
		'TITLE_VIEW_DOCUMENT_NAME' => 'Dokumente ansehen',
		'TITLE_VIEW_EXAMINATIONS_TEXT' => 'Leistungen ansehen',
		'TITLE_DOCUMENT_TYPE' => 'Dokument Typ',
		'TITLE_DOCUMENT_STATUS' => 'Dokument Status',
		'TITLE_SELECT_DOCUMENT_STATUS' => 'Wählen Sie den Dokumentstatus',
		'TITLE_SELECT_DOCUMENT_UNREAD' => 'Ungelesen',
		'TITLE_SELECT_DOCUMENT_READ' => 'Lesen',
		'TITLE_SELECT_DOCUMENT_SIGN' => 'Unterzeichnen',
		'TITLE_SELECT_CHECK_LIST_SIGNDOC' =>'SignDoc',
		'TITLE_CHECK_LIST' => 'Checkliste',
		'TITLE_CHECK_LIST_TYPE' => 'Art der Checkliste',
		'TITLE_CHECK_LIST_FORMTITLE' => 'Checklisten Information',
		'TITLE_TYPE_CHECKLIST_GENERAL'=>'Allgemein',
		'TITLE_TYPE_CHECKLIST_PERFORMANCE'=>'Leistung',
		'TITLE_SELECT_CHECK_LIST' => 'Wählen Sie Checkliste',
		'CHECK_LIST_BUTTON' => 'Checkliste hinzufügen',
		'DOCUMENT_LIST_ADD_BUTTON' => 'Dokument hinzufügen',
		'TITLE_DOCUMENT_LIST' => 'Dokumentenliste',
		'TITLE_DOCUMENT_DATE_TIME' => 'Terminzeit',
		'TITLE_DOCUMENT_PRINT' => 'Drucken',
		'TITLE_DOCUMENT_EMAIL' => 'Email',


 		// Response error
 		'FAIL_EXAM_CREATE' => 'Server-Fehler beim Anlegen der Untersuchung.',
 		'FAIL_EXAM_DELETE' => 'Server-Fehler beim Löschen der Untersuchung.',

 		// Response messages
 		'EXAM_CREATED' => 'Untersuchung erfolgreich angelegt.',
 		'EXAM_UPDATED' => 'Untersuchung erfolgreich aktualisiert.',
 		'APPOITMENT_EXAM_UPDATED' => "Prüfung erfolgreich aktualisiert.",
 		'EXAM_DELETED' => 'Untersuchung erfolgreich gelöscht.',


 	/*--------------------------------------
	|	Module name CHECK LIST
	-------------------------------------------------------------------*/
	// ERROR MESSAGES
		'ERR_CHECK_LIST_NAME_REQUIRED'  => "Das Feld für den Namen der Checkliste ist erforderlich.",
		'ERR_CHECKLIST_INTRODUCER_TEXT_REQUIRED' => "Einführungs-Textfeld ist erforderlich.",
		'ERR_CHECKLIST_FINAL_TEXT_REQUIRED' 	 => "Das endgültige Textfeld ist erforderlich.",
		'ERR_CHECKLIST_QUESTION_HEADING_REQ' 	 => "Frage- und Überschriftenfeld ist erforderlich.",
		'ERR_CHECKLIST_QUESTION_REQUIRED' 	     => "Das Fragenfeld ist erforderlich.",
		'ERR_CHECKLIST_HEADING_REQUIRED' 	     => "Das Feld für den Überschriftenabschnitt ist erforderlich.",

		// Module Title
		'TITLE_CHECKLIST_TEXT' => 'Checkliste',


		// Titles
		'TITLE_CHECKLIST_NAME'  	        => 'Name der Checkliste',
		'TITLE_CHECKLIST_INTRODUCER_TEXT'  	=> 'Einführungstext',
		'TITLE_CHECKLIST_HEADING_SECTION'  	=> 'Überschrift Abschnitt',

		'TITLE_CHECKLIST_QUESTION'  	    => 'Frage',
		'TITLE_CHECKLIST_FINAL_TEXT'  	    => 'Schlusstext',
		'TITLE_EXAMINATION_STATUS'          => 'Status',
		'TITLE_EXAMINATION_STATUS_ACTIVE'   => 'Aktiv',
		'TITLE_CHECKLIST_VIEW_HEADING_SECTION'       => 'Aussicht',
		'TITLE_CHECKLIST_QUESTION'  	    => 'Frage',
		'TITLE_CHECKLIST_ACTIVE_NEW' =>'Neu aktivieren',
		'TITLE_CHECKLIST_LAST_ACTIVATION' =>'CheckList Last-Aktivierung',
		'TITLE_CHECKLIST_LAST_ACTIVATION_CONFIRMATION' =>"Sie möchten das letzte Aktivierungsdatum aktualisieren?",

		// Response error
		'FAIL_EXAM_CREATE' => "Failed to create exam, Something went wrong on server.",
		'FAIL_EXAM_DELETE' => "Failed to delete exam, Something went wrong on server.",

		// Response messages
		'CHECK_LIST_CREATED' => "Checkliste erfolgreich erstellt.",
		'CHECK_LIST_UPDATED' => "Checkliste erfolgreich aktualisiert.",
		'CHECK_LIST_DELETED' => "Checkliste erfolgreich gelöscht.",
		'CHECK_LIST_WARNING_DELETED_MSG'     => "Diese Checkliste ist der Prüfung zugeordnet! Sie möchten es löschen?",
 	/*--------------------------------------
	|	Module name PROFILES TEMPLATES
	-------------------------------------------------------------------*/

		// Module Title
		'TITLE_PROFILE_TEMPLATE_TEXT' => 'Profil-Vorlagen',

		// ERROR MESSAGES
		'ERR_PROFILE_NAME_REQUIRED' => 'Namens-Feld ist erforderlich.',
 		'ERR_PROFILE_NAME_REGEX_REQUIRED' => 'Namens-Feld darf nur Buchstaben und Zahlen enthalten.',
 		'ERR_AGE_FROM_REQUIRED' => 'Alter von-Feld ist erforderlich.',
 		'ERR_AGE_TO_REQUIRED' => 'Alter bis-Feld ist erforderlich.',
 		'ERR_EXAM_REQUIRED' => 'Untersuchungs-Feld ist erforderlich.',
 		'ERR_STATUS_REQUIRED' => 'Status-Feld ist erforderlich.',
 		'ERR_AGE_TO' => 'Alter bis-Feld muss größer sein als Altervon-Feld.',
		'ERR_ALLREADY_SELECTED_AGE' => "Dieser Altersbereich wurde bereits ausgewählt.",
		'TITLE_AGE' => 'Alter',
 		// Titles
 		'TITLE_PROFILE_NAME' => 'Name',
 		'TITLE_PROFILE_AGE_FROM' => 'Alter von',
 		'TITLE_PROFILE_AGE_TO' => 'Alter bis',
 		'TITLE_PROFILE_EXAMINATIONS' => 'Leistungen',
 		'TITLE_PROFILE_STATUS' => 'Status',
 		'TITLE_EVENT_TYPE_SERVICES'	    => 'Grund des Vorschlags',
 		'TITLE_PROFILE_STATUS_ACTIVE' => 'Aktiv',
		'TITLE_PROFILE_NON_EXAMINATIONS' => 'Nicht empfohlene Dienste',

 		//Placeholder Title
 		'TITLE_SELECT_AGE_FROM_TEXT' => 'Gewähltes Alter von',
 		'TITLE_SELECT_AGE_TO_TEXT' => 'Gewähltes Alter bis',

 		// Response error
 		'FAIL_PROFILE_CREATE' => 'Server-Fehler beim Erstellen der Profil-Vorlage.',
 		'FAIL_PROFILE_UPDATE' => 'Server-Fehler beim Aktualisieren der Profil-Vorlage.',
 		'FAIL_PROFILE_DELETE' => 'Server-Fehler beim Löschen der Profil-Vorlage.',
 		'ERR_SELECT_EXAM' => 'wählen Sie bitte die Untersuchung aus.',

 		// Response messages
 		'PROFILE_CREATED' => 'Profil-Vorlage erfolgreich angelegt.',
 		'PROFILE_UPDATED' => 'Profil-Vorlage erfolgreich aktualisiert.',
 		'PROFILE_DELETED' => 'Profil-Vorlage erfolgreich gelöscht.',

 	/*--------------------------------------
	|	Module name PATEINTS
	-------------------------------------------------------------------*/

		// Module Title
		'TITLE_PATIENT_TEXT' => 'Patienten',
		'TITLE_SELECT_GENDER'	=> 'Geschlecht wählen',
		'TITLE_IMPORT_PATIENT' 	=> 'Patientendaten importieren.',
		'TITLE_IMPORT_BOOKING' 	=> 'Import Booking Data.',
		'TITLE_SELECT_FILE' 	=> 'Wählen Sie Datei zum Hochladen.',
		'DATA_IMPORTED' 		=> 'Daten erfolgreich importiert.',
		'TITLE_SELECT_SALUTATION' => 'Wählen Sie Anrede',
		'TITLE_MR_SALUTATION' => 'Hr.',
		'TITLE_MRS_SALUTATION' => 'Fr.',
		'TITLE_REMINDER_APPOINTMENT_DATE' => 'letzter relevanter Termin',
		"TITLE_REMINDER_DATE" => 'Start der LE',
		"TITLE_REMINDER_TYPE" => 'LE Typ',
		"TITLE_REMINDER_MEDIA" => 'Medien',
		"TITLE_FUTURE_REMINDER_DATE" => 'Zukünftiges Erinnerungsdatum',
		"TITLE_REMINDER_READ_STATUS" => 'LE angezeigt',

		// ERROR MESSAGES
		'ERR_FIRST_NAME_REQUIRED' => 'Vorname-Feld ist erforderlich.',
 		'ERR_FIRST_NAME_REGEX_REQUIRED' => 'Vorname-Feld darf nur Buchstaben und Zahlen enthalten.',
 		'ERR_LAST_NAME_REQUIRED' => 'Nachname-Feld ist erforderlich.',
 		'ERR_LAST_NAME_REGEX_REQUIRED' => 'Nachname-Feld darf nur Buchstaben und Zahlen enthalten.',
 		'ERR_PATIENT_EMAIL_ADDRESS' => 'E-Mail-Adresse-Feld ist erforderlich.',
 		'ERR_COUNTRY_CODE_REQUIRED' 	=> "Ländercodefeld erforderlich ist.",
 		'ERR_COUNTRY_CODE_WRONG' 	=> "Ungültige Länder-Vorwahl eingegeben.",
 		'ERR_MOBILE_NO_REQUIRED' => 'Handynummer-Feld ist erforderlich.',
 		'ERR_FORMAT_MOBILE_USER' 		=> "Die Handynummer darf nur aus Ziffern bestehen.",
 		'ERR_MOBILE_NO_FORMAT' 			=> "Geben Sie das richtige Handynummernformat ein.",
 		'ERR_MOBILE_NOUMBERNOTSTARTWITHZERO' => "Geben Sie die gültige Mobiltelefonnummer ein und sollten nicht mit Null beginnen.",

 		//'ERR_MOBILE_UNIQUE' => "Diese Mobilnummer ist bereits einer anderen Patientin zugeordnet. Bitte geben sie eine andere Nummer ein.",  //copmmented on 15-dec-23 for duplicate patient

 		'ERR_BIRTH_DATE_UNIQUE'			=> "Zu ihrem Namen und Geburtsdatum wurde ein existierender Eintrag gefunden. Melden sie sich bitte als bestehende Patientin an oder geben sie andere Daten ein.",
 		'ERR_BIRTH_DATE_REQUIRED' => 'Geburtsdatum-Feld ist erforderlich.',
 		'ERR_PASSWORD_REQUIRED' => 'Kennwort-Feld ist erforderlich.',
 		'ERR_GANY_PATIENT_ID_REQUIRED' => 'GanyMED Patienten-ID ist erforderlich.',
 		'ERR_ROAD_REQUIRED' 			=> "Straßen-Feld ist erforderlich.",
		'ERR_PLACE_REQUIRED' 			=> "Orts-Feld ist erforderlich.",
 		'ERR_PATIENT_FAMILY_NAME_REQUIRED'=> "Familienname-Feld ist erforderlich.",
		'ERR_PATIENT_POSTAL_CODE'		=> "Postleitzahl-Feld ist erforderlich.",
		'ERR_PATIENT_POSTAL_CODE_NUMERIC_REQUIRED' => "Gehen Sie bitte in Zahl nur ein.",
		'ERR_PATIENT_GENDER'			=> "Geschlecht-Feld ist erforderlich.",
		'ERR_PATIENT_SIZE_REQUIRED'		=> "Größen-Feld ist erforderlich.",
		'ERR_PATIENT_WEIGHT_REQUIRED'	=> "Gewichts-Feld ist erforderlich.",
		'ERR_PATIENT_TITLE_REQUIRED'	=> "Titel-Feld ist erforderlich.",
		'ERR_PATIENT_SALUTATION_REQUIRED'=> "Anrede-Feld ist erforderlich.",
		'ERR_PATIENT_FAMILY_DOCTOR_REQUIRED'=> "Hausarzt-Feld ist erforderlich.",
		'ERR_FAMILY_DOCTOR_NAME'	=> 'Der Name des Hausarztes darf nur Buchstaben enthalten.',
		'ERR_PATIENT_ENSURANCE_NUMBER_REQUIRED'		=> "Versicherungsnummer-Feld ist erforderlich.",
		'ERR_PATIENT_ADDITIONAL_ENSURANCE_REQUIRED'	=> "Zusatz-Versicherungs-Feld ist erforderlich.",
		'ERR_PATIENT_ENSURANCE_NUMBER_DIGITS_REQUIRED'	=> 'Die Versicherungsnummer muss 4 Ziffern lang sein.',
		'ERR_PATIENT_FINDING_NOTE'	=> 'Sobald sie den "speichern" Button klicken, wird die Befundanfrage in der ToDo Liste des Assistenz Dashboards hinterlegt',
 		// Titles
 		'TITLE_PATIENT_NAME' => 'Name',
 		'TITLE_PATIENT_NAME_IN_MAIL' => 'Patientenname',
	 	'TITLE_PATIENT_FIRST_NAME' => 'Vorname',
	 	'TITLE_PATIENT_LAST_NAME' => 'Nachname',
	 	'TITLE_PATIENT_EMAIL' => 'Email',
	 	'TITLE_PATIENT_COUNTRY_CODE' 	=> 'Ländercode',
	 	'TITLE_PATIENT_MOBILE_NO' => 'Handynummer',
	 	'TITLE_PATIENT_BIRTH_DATE' => 'Geburtsdatum',
	 	'TITLE_PATIENT_PASS' => 'Kennwort',
	 	'TITLE_PATIENT_GANY_PATIENT_ID' => 'GanyMED Patienten-ID',
	 	'TITLE_PATIENT_IS_BLOCK' => 'Ist gesperrt',
	 	'TITLE_PATIENT_BLOCK' => 'Sperren',
	 	'TITLE_PATIENT_STATUS' => 'Status',
	 	'TITLE_PATIENT_STATUS_ACTIVE' => 'Aktiv',
	 	'TITLE_PATIENT_ROAD'			=> 'Straße',
		'TITLE_PATIENT_PLACE'			=> 'Ort',
	 	'TITLE_PATIENT_FAMILY_NAME'		=> 'Familienname',
		'TITLE_PATIENT_POSTAL_CODE'		=> 'Postleitzahl',
		'TITLE_PATIENT_GENDER'			=> 'Geschlecht',
		'TITLE_PATIENT_SIZE'			=> 'Größe',
		'TITLE_PATIENT_WEIGHT'			=> 'Gewicht',
		'TITLE_PATIENT_TITLE'			=> 'Titel',
		'TITLE_PATIENT_SALUTATION'		=> 'Anrede',
		'TITLE_PATIENT_FAMILY_DOCTOR'	=> 'Familiendoktor',
		'TITLE_PATIENT_ENSURANCE_NUMBER'=> 'Versicherungsnummer',
		'TITLE_PATIENT_TODO_ENSURANCE_NUMBER'=> 'Vers.nr.',
		'TITLE_PATIENT_TODO_MOBILE_NO'=> 'Handynr.',
		'TITLE_PATIENT_ADDITIONAL_ENSURANCE' => 'Zusätzliche Versicherung',
		'TITLE_GANY_PATIENT_ID' => 'GanyMED Id',
		'TITLE_GANY_PAT_NR' 			=> 'Pat Nr',
		'TITLE_PATIENT_NOTE' 			=> 'Notizen zur Befundanfrage',
		'TITLE_PATIENT_STREET_NO' 		=> 'Hausnummer',
		// 'TITLE_REMINDER_ACTIVE' 		=> 'Erinnerung aktiv',//commented on 30-july-25 for #340
		'TITLE_REMINDER_ACTIVE' 		=> 'Leistungsempfehlungen versenden',//added on 30-july-25

		'TITLE_PATIENT_CREATE' 		=> 'Termin für neue Patientin anlegen',
		// 'TITLE_ON' 		=> 'auf', //Commented by Shyam 21-02-22
		'TITLE_ON' 		=> 'Aktiv', //Added by Shyam 21-02-22

	 	// Response error
	 	'FAIL_PATIENT_CREATE' => 'Server-Fehler beim Erstellen des Patienten.',
 		'FAIL_PATIENT_UPDATE' => 'Server-Fehler beim Aktualisieren des Patienten.',
 		'FAIL_PATIENT_DELETE' => 'Server-Fehler beim Löschen des Patienten.',

 		// Response messages
 		'PATIENT_CREATED' => 'Patient erfolgreich angelegt.',
 		'PATIENT_UPDATED' => 'Patient erfolgreich aktualisiert.',
 		'PATIENT_DELETED' => 'Patient erfolgreich gelöscht.',

 	/*--------------------------------------
	|	Module name MENUS SETTINGS
	-------------------------------------------------------------------*/

		// Module Title
		'TITLE_MENU_SETTING_TEXT' => 'Menüeinstellungen',

		// Titles
		'TITLE_MENU_NAME' => 'Name',
 		'TITLE_MENU_URL' => 'URL',
 		'TITLE_MENU_STATUS' => 'Status',
 		'TITLE_MENU_STATUS_ACTIVE' => 'Aktiv',

 		// ERROR MESSAGES
 		'ERR_MENU_NAME_REQUIRED' => 'Namens-Feld ist erforderlich.',
 		'ERR_MENU_URL_REQUIRED' => 'URL-Feld ist erforderlich.',

 		// Response error
 		'FAIL_MENU_CREATE' => 'Server-Fehler beim Erstellen des Menüs.',
 		'FAIL_MENU_UPDATE' => 'Server-Fehler beim Aktualisieren des Menüs.',
 		'FAIL_MENU_DELETE' => 'Server-Fehler beim Löschen des Menüs.',

 		// Response messages
 		'MENU_CREATED' => 'Menü erfolgreich erstellt.',
 		'MENU_UPDATED' => 'Menü erfolgreich aktualisiert.',
 		'MENU_DELETED' => 'Menü erfolgreich gelöscht.',

 		/*--------------------------------------
	|	Module name SUPPORT SETTINGS
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SUPPORT_SETTING_TEXT' => 'Manuelle Einstellung',
		// Titles
		'TITLE_SUPPORT_NAME' => 'Name',
 		'TITLE_SUPPORT_URL' => 'URL',
 		'TITLE_SUPPORT_STATUS' => 'Status',
 		'TITLE_SUPPORT_STATUS_ACTIVE' => 'Aktiv',
 		// ERROR MESSAGES
 		'ERR_SUPPORT_NAME_REQUIRED' => 'Namens-Feld ist erforderlich.',
 		'ERR_SUPPORT_URL_REQUIRED' => 'URL-Feld ist erforderlich.',
 		// Response error
 		'FAIL_SUPPORT_CREATE' => 'Server-Fehler beim Erstellen des Manuelle Einstellungs.',
 		'FAIL_SUPPORT_UPDATE' => 'Server-Fehler beim Aktualisieren des Manuelle Einstellungs.',
 		'FAIL_SUPPORT_DELETE' => 'Server-Fehler beim Löschen des Manuelle Einstellungs.',
 		// Response messages
 		'SUPPORT_CREATED' => 'Manuelle Einstellung erfolgreich erstellt.',
 		'SUPPORT_UPDATED' => 'Manuelle Einstellung erfolgreich aktualisiert.',
 		'SUPPORT_DELETED' => 'Manuelle Einstellung erfolgreich gelöscht.',
 		/*--------------------------------------
		|	Module name MANAGE ORDINATION
		-------------------------------------------------------------------*/
		// Module Title
		'TITLE_ORDINATION_TEXT' => 'Ordinationen',

		// Titles
		'TITLE_ORDINATION_MENU_NAME' => 'Name',
		'TITLE_ORDINATION_LOGO' 	=> 'Logo',
		'TITLE_ORDINATION_LINK' 	=> 'Link',
		'TITLE_ORDINATION_TEXT_COLOR_CODE' 	=> 'Textcode',

		'TITLE_ORDINATION_BUTTONE_COLOR_CODE' 	=> 'Tastencode',
		'TITLE_ORDINATION_SCREEBBG_COLOR_CODE' 	=> 'Bildschirmhintergrundcode',
		'TITLE_ORDINATION_APP_BAR_COLOR_CODE' 	=> 'App-Barcode',
		'TITLE_ORDINATION_TAB_SELECTION_COLOR_CODE' 	=> 'Auswahl der Registerkarten Code',
		'TITLE_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE' 	=> 'Startbildschirm-Optionscode',
		'TITLE_ORDINATION_MENU_HEARDER_COLOR_CODE' 	=> 'Menükopfzeilencode',
		'TITLE_ORDINATION_DARK_TEXT_COLOR_CODE' 	=> 'Dunkler Textcode',
		'TITLE_ORDINATION_MENU_BG_COLOR_CODE' 	=> 'Menühintergrundcode',
		'TITLE_ORDINATION_LIGHT_TEXT_COLOR_CODE' 	=> 'Lichttextcode',

		'TITLE_ORDINATION_BACKGROUND_COLOR_CODE' 	=> 'Hintergrundcode',
		'TITLE_ORDINATION_LOGIN_USER' 	=> 'Benutzer anmelden',
		'TITLE_ORDINATION_STATUS'	=> 'Status',
		'TITLE_SELECT_ORDINATION_LOGING_USER' => 'Wählen Sie Ordinations User',
		'TITLE_ORDINATION_HAS_SPECIALIST'        => 'Spezialisierung',
		'TITLE_SELECT_ORDINATION_HAS_SPECIALIST' => 'Wählen Sie Spezialist',
		'TITLE_ORDINATION_ADDRESS' => 'Adresse',
		// 'TITLE_MENU_STATUS_ACTIVE' => 'Active',

		// ERROR MESSAGES
		'ERR_ORDINATION_NAME_REQUIRED' => "Namensfeld ist erforderlich." ,
		'ERR_ORDINATION_LOGO'  => "Logo-Feld ist erforderlich.",
		'ERR_ORDINATION_TEST_COLOR_CODE'  => "Testfarbcodefeld ist erforderlich.",

		'ERR_ORDINATION_BUTTONE_COLOR_CODE'  => "Das Feld für den Farbcode der Schaltfläche ist erforderlich.",
		'ERR_ORDINATION_SCREEN_COLOR_CODE'  => "Das Feld für den Farbcode des Bildschirmhintergrunds ist erforderlich.",
		'ERR_ORDINATION_APP_BAR_COLOR_CODE'  => "Das Farbcodefeld des App-Strichs ist erforderlich.",
		'ERR_ORDINATION_TABS_SELECTION_COLOR_CODE'  => "Das Farbcodefeld für die Registerkartenauswahl ist erforderlich.",
		'ERR_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE'  => "Das Farbcodefeld für die Startbildschirmoption ist erforderlich.",
		'ERR_ORDINATION_MENU_HEARDER_COLOR_CODE'  => "Das Farbcodefeld für die Menükopfzeile ist erforderlich.",
		'ERR_ORDINATION_MENU_BG_COLOR_COLOR_CODE'  => "Das Feld für den Farbcode des Menühintergrunds ist erforderlich.",
		'ERR_ORDINATION_DARK_TEXT_COLOR_CODE'  => "Ein dunkles Textfarbcodefeld ist erforderlich.",
		'ERR_ORDINATION_LIGHT_TEXT_COLOR_CODE'  => "Helles Textfarbcodefeld ist erforderlich.",


		'ERR_ORDINATION_BUTTONE_COLOR_CODE'  => "Button color code field is required.",
		'ERR_ORDINATION_SCREEN_COLOR_CODE'  => "Screen background color code field is required.",
		'ERR_ORDINATION_APP_BAR_COLOR_CODE'  => "App bar color code field is required.",
		'ERR_ORDINATION_TABS_SELECTION_COLOR_CODE'  => "Tabs selection color code field is required.",
		'ERR_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE'  => "Home Screen option color code field is required.",
		'ERR_ORDINATION_MENU_HEARDER_COLOR_CODE'  => "Menu header color code field is required.",
		'ERR_ORDINATION_MENU_BG_COLOR_COLOR_CODE'  => "Menu background color code field is required.",
		'ERR_ORDINATION_DARK_TEXT_COLOR_CODE'  => "Dark text color code field is required.",
		'ERR_ORDINATION_LIGHT_TEXT_COLOR_CODE'  => "Light text color code field is required.",
		'ERR_ORDINATION_BACKGROUND_COLOR_CODE'  => "Hintergrundfarbcodefeld ist erforderlich.",
		'ERR_ORDINATION_LOGIN_USER'  => "Login-Benutzerfeld ist erforderlich.",
		'ERR_ORDINATION_ADDRESS_REQUIRED'	=> "Adressfeld ist erforderlich.",
		'ERR_ORDINATION_CALENDAR_ID_REQUIRED'	=> "Das Kalender-ID-Feld ist erforderlich.",
		'ERR_ORDINATION_CALENDAR_ID_DUP'	=> "Kalender-ID wurde bereits übernommen.",

		// Response error
		'FAIL_ORDINATION_CREATE' => "Fehler beim Erstellen von Ordinationen. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_ORDINATION_UPDATE' => "Ordinationen konnten nicht aktualisiert werden. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_ORDINATION_DELETE' => "Ordinationen konnten nicht gelöscht werden. Auf dem Server ist ein Fehler aufgetreten.",

		// Response messages
		'ORDINATION_CREATED' => "Ordinationen erfolgreich erstellt.",
		'ORDINATION_UPDATED' => "Ordinationen erfolgreich aktualisiert.",
		'ORDINATION_DELETED' => "Ordinationen erfolgreich gelöscht.",

	/*--------------------------------------
		|	Module name Specialist MANAGE
		-------------------------------------------------------------------*/
		// Module Title

		'TITLE_SPECIALIST_TEXT' => 'Spezialistin',

		'TITLE_SPECIALIST_DOCUMENT_TEXT' => 'Dokument',

		// Titles
		'TITLE_SPECIALIST_NAME'   => 'Name',
		'TITLE_SPECIALIST_STATUS' => 'Status',
		'TITLE_SPECIALIST_ACTIVE' => 'Aktiv',

		// ERROR MESSAGES
		'ERR_SPECIALIST_NAME_REQUIRED' => "Namensfeld ist erforderlich." ,


		// Response error
		'FAIL_SPECIALIST_CREATE' => "Fehler beim Erstellen eines Spezialisten. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_SPECIALIST_UPDATE' => "Fehler beim Aktualisieren des Spezialisten. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_SPECIALIST_DELETE' => "Spezialist konnte nicht gelöscht werden. Auf dem Server ist ein Fehler aufgetreten.",

		// Response messages
		'SPECIALIST_CREATED' => "Spezialist erfolgreich erstellt.",
		'SPECIALIST_UPDATED' => "Spezialist erfolgreich aktualisiert.",
		'SPECIALIST_DELETED' => "Spezialist erfolgreich gelöscht.",
		'SPECIALIST_ASSIGNED'=> "Spezialist erfolgreich zugewiesen.",

	    /*--------------------------------------
		|	Module name Document MANAGE
		-------------------------------------------------------------------*/
		// Module Title
		'TITLE_SPECIALIST_DOCUMENT_TEXT' => 'Dokumente',
		'TITLE_SPECIALIST_SELECT_TEXT' => 'Wählen Sie Spezialist',
		'TITLE_SPECIALIST_NOT_EXIST' => 'Derzeit gibt es keinen Spezialisten.',

		// Titles
		'TITLE_SPECIALIST_DOCUMENT_NAME'   => 'Name',
		'TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT'   => 'Art des Dokuments',
		'TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT_SELECT'   => 'Wählen Sie Dokumenttyp',
		//'TITLE_SPECIALIST_DOCUMENT_HTML_TEXT'   => 'HTML-Text',//aishwarya commented on 30-5-25
		'TITLE_SPECIALIST_DOCUMENT_HTML_TEXT'   => 'Beschreibung',//Aishwarya added text on 30-5-25

		'TITLE_SPECIALIST_DOCUMENT_HEADER_IMAGE'   => 'Header-Bild',
		'TITLE_SPECIALIST_DOCUMENT_FOOTER_IMAGE'   => 'Fußzeile Bild',
		'TITLE_SPECIALIST_DOCUMENT_BACKGROUND_COLOR'   => 'Hintergrundfarbe',
		'TITLE_SPECIALIST_DOCUMENT_FREQUENCY'   => 'Frequenz',
		'TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE'   => 'Frequenztyp',
		'TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION'   => 'Datum der letzten Aktivierung',
		'TITLE_SPECIALIST_DOCUMENT_STATUS' => 'Status',
		'TITLE_SPECIALIST_DOCUMENT_ACTIVE' => 'Aktiv',
		'TITLE_GENERAL'=>'Allgemeines',
		'TITLE_SERVICE'=>'Leistung',
		'TITLE_FREQUENCY_TYPE_SELECT'  =>'Wählen Sie den Frequenztyp',
		'TITLE_FREQUENCY_DAY'  =>'Tag',
		'TITLE_FREQUENCY_MONTH'=>'Monat',
		'TITLE_FREQUENCY_YEAR' =>'Jahr',
		'TITLE_FREQUENCY_WEEK' =>'Woche',



		// ERROR MESSAGES
		'ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED' => "Namensfeld ist erforderlich." ,
		'ERR_SPECIALIST_DOCUMENT_NAME_UNIQUE_REQUIRED' => "Das Namensfeld sollte eindeutig sein.",
		'ERR_DOCUMENT_TYPE_OF_DOCUMENT'         => 'Art des eingereichten Dokuments ist erforderlich.',
		'ERR_DOCUMENT_HEADER_IMAGE'             => 'Header Image archiviert ist erforderlich.',
		'ERR_DOCUMENT_FOOTER_IMAGE'             => 'Fußzeile Bild abgelegt ist erforderlich.',
		'ERR_DOCUMENT_DATE_OF_ACTIVATION'       => 'Das Datum der Einreichung der Aktivierung ist erforderlich.',
		//'ERR_DOCUMENT_HTML_TEXT'                => 'HTML-Text abgelegt ist erforderlich.',//Aishwarya commented on 30-5-25
		'ERR_DOCUMENT_HTML_TEXT'                => 'Beschreibung abgelegt ist erforderlich.',//Aishwarya added text on 30-5-25

		'ERR_DOCUMENT_BACKGROUND_COLOR'         => 'Hintergrundfarbe abgelegt ist erforderlich.',
		'ERR_DOCUMENT_FREQUENCY'                => 'Die eingereichte Häufigkeit ist erforderlich.',
		'ERR_DOCUMENT_FREQUENCY_TYPE'           => 'Der eingereichte Frequenztyp ist erforderlich.',


		// Response error
		'FAIL_SPECIALIST_DOCUMENT_CREATE' => "Fehler beim Erstellen eines Spezialisten. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_SPECIALIST_DOCUMENT_UPDATE' => "Fehler beim Aktualisieren des Spezialisten. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_SPECIALIST_DOCUMENT_DELETE' => "Fehler beim Löschen des Spezialisten. Auf dem Server ist ein Fehler aufgetreten.",

		// Response messages
		'SPECIALIST_DOCUMENT_CREATED' => "Dokument erfolgreich erstellt.",
		'SPECIALIST_DOCUMENT_UPDATED' => "Dokument erfolgreich aktualisiert.",
		'SPECIALIST_DOCUMENT_DELETED' => "Dokument erfolgreich gelöscht.",

		'TITLE_DOCUMENTLIST_TEXT' => 'Dokumentenliste',

	/*--------------------------------------
	|	Module name ROSTER
	-------------------------------------------------------------------*/
		// Titles
		'TITLE_ROSTER_MODULE'  			=> 'Dienstpläne',
		'TITLE_ROSTER_DOCTOR' 			=> 'Arzt',
		'TITLE_ROSTER_SELECT_DOCTOR' 	=> 'Arzt wählen',
		'TITLE_DEFAULT_TIME_DURATION' 	=> 'Standardzeitdauer (in Mins)',
		'TITLE_ROSTER_APPOINTMENT_TYPE' => 'Termin-Typ',
		'TITLE_ROSTER_SELECT_APPOINTMENT_TYPE' => 'Termin-Typ wählen',
		'TITLE_SELECT_TIME_FRAME_TEXT' => 'Zeitrahmen wählen',
		'TITLE_ROSTER_DATE_HEADING'	=> 'Datums- und Zeitbereich wählen',
		'TITLE_ROSTER_DATE'	 		=> 'Datum',
		'TITLE_ROSTER_TIME_FROM'	 	=> 'Zeit von',
		'TITLE_ROSTER_TIME_TO'	 		=> 'Zeit bis',
		'TITLE_ROSTER_TIME_FRAME'	 	=> 'Zeitrahmen ',

		//new added
		'TITLE_ROSTER_WEEKDAY'	 		=> 'Wochentag',
		'TITLE_ROSTER_STARTDATE'	 	=> 'Anfangs Datum',
		'TITLE_ROSTER_ENDDATE'	 		=> 'End Termin',
		'TITLE_ROSTER_EXCLUDE'	 		=> 'Ausgeschlossenes Datum',
		'TITLE_ROSTER_INCLUDE'	 		=> 'Enthaltenes Datum',
		'TITLE_ROSTER_EXCLUDE_INCLUDE'	=> 'Ausgeschlossene und eingeschlossene Datumsreihen',
		'TITLE_ROSTER_WEEK_MISMATCH'	=> 'Der Wochentag passt nicht zum Datum',

		// ERROR MESSAGES
		'ERR_DOCTOR_ID_REQUIRED' 	=> "Arzt-Feld ist erforderlich.",
		'ERR_APPOINTMENT_TYPE_ID_REQUIRED' => "Termintyp-Feld ist erforderlich.",
		'ERR_WEEKDAY_REQUIRED' 		=> "Wochentag-Feld ist erforderlich.",
		'ERR_TIME_FROM_REQUIRED' 	=> "Zeit von-Feld ist erforderlich.",
		'ERR_TIME_TO_REQUIRED' 		=> "Zeit bis-Feld ist erforderlich.",
		'ERR_TIME_FRAME_REQUIRED' 	=> "Zeitbereich-Feld ist erforderlich..",
		'ERR_TIME_FRAME_NOT_FOUND' 	=> "Der Arzt ist zum gewählten Zeitpunkt nicht verfügbar.",
		//'ERR_TIME_FRAME_NOT_FOUND' 	=> "Bitte Dienstplan für den gewählten Arzt anlegen.",

		'ERR_TIME_FRAME_EXIST_DATE' => "Datum",
		'ERR_TIME_FRAME_EXIST' 	=> "und seine Von- und Bis-Zeit ist bereits in einem anderen Dienstplan vorhanden.",

		// Response error
		'FAIL_ROSTER_CREATE' => "Server-Fehler beim Erstellen des Dienstplans.",
		'FAIL_ROSTER_UPDATE' => "Server-Fehler beim Aktualisieren des Dienstplans.",
		'FAIL_ROSTER_DELETE' => "Server-Fehler beim Löschen des Dienstplans.",

		'FAIL_ROSTER_EXCLUDED_DATES' => "Fehler beim Ausschluss der Daten für den Dienstplan. Auf dem Server ist ein Fehler aufgetreten.",
		'FAIL_ROSTER_EXCLUDED_DATE_UPDATE' => "Fehler beim Aktualisieren des Ausschlussdatums des Dienstplans. Auf dem Server ist ein Fehler aufgetreten.",

		// Response messages
		'ROSTER_CREATED' => "Dienstplan erfolgreich angelegt.",
		'ROSTER_WEEK_DAYS_ERROR' => "Please select a day of the week.",
		'ROSTER_UPDATED' => "Dienstplan erfolgreich aktualisiert.",
		'ROSTER_DELETED' => "Dienstplan erfolgreich gelöscht.",
		'ROSTER_EXCLUDED_DATES' => "Dienstplan ausgeschlossen Daten erfolgreich gefunden.",
		'ROSTER_CANNOT_CHANGE' => "Eine Änderung der Dienstplanzeiträume ist nicht möglich, da diese bereits verbucht sind.",

	/*--------------------------------------
	|	Module name APPOINTMENT
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_APPOINTMENT_TEXT' => 'Termine',
		'TITLE_CURRENT_APPOINTMENT_TEXT' => 'Ärzte Dashboard',

		// ERROR MESSAGES
		'ERR_APPOINTMENT_PATIENT_REQUIRED' 	=> "Patientin-Feld ist erforderlich.",
		'ERR_APPOINTMENT_DOCTOR_REQUIRED' 	=> "Arzt-Feld ist erforderlich.",
		'ERR_APPOINTMENT_TYPE_REQUIRED' 	=> "Termin-Feld ist erforderlich.",
		'ERR_APPOINTMENT_DATE_REQUIRED' 	=> "Datum-Feld ist erforderlich.",
		'ERR_APPOINTMENT_TIME_FRAME_REQUIRED' 	=> "Zeit-Feld ist erforderlich.",

		// Titles
		'TITLE_APPOINTMENT_START_DATE' 	=> 'Startdatum',
		'TITLE_APPOINTMENT_END_DATE' 	=> 'Enddatum',
		'TITLE_APPOINTMENT_PATIENT' 	=> 'Patientin',
		'TITLE_APPOINTMENT_DOCTOR'  	=> 'Arzt',
		'TITLE_APPOINTMENT_TYPE' 		=> 'Termintyp',
		'TITLE_APPOINTMENT_DATE' 		=> 'Datum',
		'TITLE_APPOINTMENT_NOTE'		=> 'Notiz',
		'TITLE_DASHBOARD_NOTE'			=> 'Notizen',
		'TITLE_APPOINTMENT_STATUS'		=> 'Status',
		'TITLE_APPOINTMENT_STATUS_ACTIVE' 	=> 'Aktiv',
		'TITLE_NOTIFICATION_TEXT'	=> 'Benachrichtigung',
		'TITLE_APPOINTMENT_SERVICES'=>'Leistungen',//Aishwarya added on 27-may-25

		//Placeholder Title
		'TITLE_SELECT_PATIENT' => 'Patientin wählen',
		'TITLE_SELECT_DOCTOR'  => 'Arzt wählen',
		'TITLE_SELECT_TYPE'    => 'Termintyp wählen',
		'TITLE_APPOINTMENT_TIME_FRAME'	=> 'Zeitrahmen',

		// Response error
		'FAIL_APPOINTMENT_CREATE' => "Server-Fehler beim Erstellen des Termins.",
		'FAIL_APPOINTMENT_UPDATE' => "Server-Fehler beim Aktualisieren des Dienstplans.",
		'FAIL_APPOINTMENT_DELETE' => "Server-Fehler beim Löschen des Dienstplans.",
		'FAIL_APPOINTMENT_TIME_FRAME' => "Server-Fehler beim laden der Arzt-Dienstplans.",

		// Response messages
		'APPOINTMENT_CREATED' => "Termin erfolgreich angelegt.",
		'APPOINTMENT_UPDATED' => "Termin erfolgreich aktualisiert.",
		'APPOINTMENT_DELETED' => "Termin erfolgreich gelöscht.",
		'APPOINTMENT_SLOT_ALREADY_EXIST' => "Terminslot ist bereits gebucht.",

	/*--------------------------------------
	|	Module name Notification
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_NOTIFICATION_TEXT'	=> 'Notification',
		'TITLE_NOTIFICATION_DEFAULT'	=> 'Generelle Einstellungen für alle Notifications',

		// Titles
		'TITLE_PATIENT_NAME' 		=> 'Patientenname',
		'TITLE_NOTIFY_DATE'  		=> 'Datum der Notification',
		'TITLE_NOTIFY_TIME'  		=> 'Zeit der Notification',
		'TITLE_TITLE_TEXT'  		=> 'Titel',
		'TITLE_CONTENT_TEXT'		=> 'Text der Notification',
		'TITLE_STATUS_TEXT' 		=> 'Status',
		'TITLE_STATUS_UPDATE_ALL' 		=> 'Für alle übernehmen',
		'TITLE_SELECT_STATUS_TEXT' 		=> 'Select Status',

		// ERROR MESSAGES
		'ERR_NOTIFICATION_PATIENT_NAME_REQUIRED' 	=> "Das Feld Patientenname ist erforderlich",
		'ERR_NOTIFICATION_DATE_REQUIRED' 	=> "Das Feld Benachrichtigungsdatum ist erforderlich",
		'ERR_NOTIFICATION_TIME_REQUIRED' 	=> "Das Feld Benachrichtigungszeit ist erforderlich",
		'ERR_NOTIFICATION_TITLE_REQUIRED' 	=> "Titelfeld ist erforderlich",
		'ERR_NOTIFICATION_CONTENT_REQUIRED' 	=> "Inhaltsfeld ist erforderlich",
		'ERR_NOTIFICATION_STATUS_REQUIRED' 	=> "Statusfeld ist erforderlich",

		// Response error
		'FAIL_NOTIFICATION_UPDATE' => "Failed to update Notification, Something went wrong on server.",

		// Response messages
		'NOTIFICATION_UPDATED' => "Die Benachrichtigung wurde erfolgreich geändert.",

	/*--------------------------------------
	|	Module name WAITING QUEUE SYSTEM
	-------------------------------------------------------------------*/
		// Module Title
		'TITLE_WAITING_SCREEEN' => "Wartebildschirm",
		'TITLE_WAITING_QUEUE_TEXT' => "Wartenummern",
		'TITLE_WAITING_NUMBER_SYMBOLS_TEXT' => "Wartenummern-Symbole",
		'WAITING_NUMBER_DELETED' => 'Wartenummern erfolgreich gelöscht.',

		// Titles
		'TITLE_WAITING_PATIENT_NAME' => "Name der Patientin",
		'TITLE_WAITING_PATIENT_DOCTOR_NAME' => "Name des Arztes",
		'TITLE_WAITING_PATIENT_APPOINTMENT_TYPE_NAME' => "Termintyp",
		'TITLE_WAITING_PATIENT_APPOINTMENT_TIME' => "Beginnzeit",
		'TITLE_WAITING_PATIENT_APPOINTMENT_STATUS' => "Terminstatus",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER' => "Wartenummer",
		'TITLE_WAITING_TYPE' => "Typ",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER_TYPE' => "Typ Wartenummer",
		'TITLE_WAITING_PATIENT_SCAN_TIME' => "Scan Zeit",
		'TITLE_WAITING_PATIENT_QUEUE_NUMBER_CREATED_AT' => "Wartenummer angelegt um",
		'TITLE_WAITING_PATIENT_STATUS' => "Status",

		'TITLE_WAITING_SYMBOL_NAME' => "Namen",
		'TITLE_WAITING_SYMBOL_URL' => "Url",

 	/*--------------------------------------
	|	Module name DIAGNOSTIC FINDING TYPE
	-----------------------------------------------------------*/
		// Module Title
		'TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT' => 'Befundarten',

		// ERROR MESSAGES
		'ERR_DIAGNOSTIC_FINDING_TYPES_NAME_REQUIRED' 	=> "Namens-Feld ist erforderlich.",
		'ERR_DIAGNOSTIC_FINDING_TYPES_COLOR_REQUIRED' 	=> "Farbe-Feld ist erforderlich.",

		// Titles
		'TITLE_DIAGNOSTIC_FINDING_TYPES_NAME' 			=> 'Name',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_COLOR'  		=> 'Farbe',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_STATUS'  		=> 'Status',
		'TITLE_DIAGNOSTIC_FINDING_TYPES_STATUS_ACTIVE' 	=> 'Aktiv',

		// Response error
		'FAIL_DIAGNOSTIC_FINDING_TYPE_CREATE' => "Server-Fehler beim Erstellen des Befundarts.",
		'FAIL_DIAGNOSTIC_FINDING_TYPE_UPDATE' => "Server-Fehler beim Aktualisieren des Befundarts.",
		'FAIL_DIAGNOSTIC_FINDING_TYPE_DELETE' => "Server-Fehler beim Löschen des Befundarts.",
		// Response messages
		'DIAGNOSTIC_FINDING_TYPE_CREATED' => "Befundart erfolgreich erstellt.",
		'DIAGNOSTIC_FINDING_TYPE_UPDATED' => "Befundart erfolgreich aktualisiert.",
		'DIAGNOSTIC_FINDING_TYPE_DELETED' => "Befundart erfolgreich gelöscht.",

        'ADD_APPOINTMENT' => "Neuer Termin",
        'APPOINTMENT_DETAILS' => "Termin Details",
        'APPOINTMENT_INFORMATION'=>"Termin Information",
        'EDIT_APPOINTMENT'=>"Termin bearbeiten",
        'NO_RESULT_FOUND'=>"Keine Einträge gefunden",
		'PLEASE_WAIT'=>"Bitte warten Sie das Laden des Ergebnisses..",


		'ERR_BEACONS_UUID_REQUIRED' => "ProximityUUID ist erforderlich.",
		'ERR_BEACONS_MAJOR_REQUIRED' => "Haupt Beacons ist erforderlich.",
		'ERR_BEACONS_IDENTIFIER_REQUIRED' => "Beacons identifier ist erforderlich.",
		'ERR_BEACONS_MINOR_REQUIRED' => "Geringer Beacons ist erforderlich.",
		'ERR_KEYWORD_REQUIRED' => "Schlüsselwort ist erforderlich",

		'TITLE_IDENTIFIER' => 'Kennung',
		'TITLE_UUID' => 'ProximityUUID',
		'TITLE_MAJOR' => 'Major',
		'TITLE_MINOR' => 'Minor',
		'FINDINGS_TITLE' 				=> 'Stichwort',
		'WHITELIST_STATUS'				=> 'Weiße Liste',
		'BLACKLIST_STATUS'				=> 'Schwarze Liste',

		'ERR_DISMISSAL_REQUIRED'       => "Entlassungsfeld ist erforderlich",

		// assigned to specilist
		'TITLE_WARNING_CONFIRM' => 'Bestätigen',
		'TITLE_WARNING_MSG'     => 'Sie möchten hinzufügen.',
		'SPECIALIST_RECORD_ERROR'     => 'Bitte wählen Sie in der Liste einen Spezialisten aus.',
		'TITLE_SHOW_AS_CONTROL' 	=> 'Als Kontrolle anzeigen',
		'TITLE_SHOW_AS_REMINDER' 	=> 'Als Erinnerung anzeigen',
		'TITLE_CONTROL' 	=> 'Kontrolle',
		'TITLE_ERR_SPECIALIST' =>"Bitte wählen Sie einen Spezialisten.",



 	/*--------------------------------------
	|	Module name SUPPORT SETTINGS
	-------------------------------------------------------------------*/

		// Module Title
		'TITLE_SUPPORT_SETTING_TEXT' => 'Manuelle Einstellung',

		// Titles
		'TITLE_SUPPORT_NAME' => 'Name',
 		'TITLE_SUPPORT_URL' => 'URL',
 		'TITLE_SUPPORT_APK' 	=> 'APK',
 		'TITLE_SUPPORT_STATUS' => 'Status',
 		'TITLE_SUPPORT_STATUS_ACTIVE' => 'Aktiv',

 		// ERROR MESSAGES
 		'ERR_SUPPORT_NAME_REQUIRED' => 'Namens-Feld ist erforderlich.',
 		'ERR_SUPPORT_URL_REQUIRED' => 'URL-Feld ist erforderlich.',

 		// Response error
 		'FAIL_SUPPORT_CREATE' => 'Server-Fehler beim Erstellen des Manuelle Einstellungs.',
 		'FAIL_SUPPORT_UPDATE' => 'Server-Fehler beim Aktualisieren des Manuelle Einstellungs.',
 		'FAIL_SUPPORT_DELETE' => 'Server-Fehler beim Löschen des Manuelle Einstellungs.',

 		// Response messages
 		'SUPPORT_CREATED' => 'Manuelle Einstellung erfolgreich erstellt.',
 		'SUPPORT_UPDATED' => 'Manuelle Einstellung erfolgreich aktualisiert.',
 		'SUPPORT_DELETED' => 'Manuelle Einstellung erfolgreich gelöscht.',

 		'INTERACTION_DATE' => "Interaktionsdatum",



 	/*--------------------------------------
	|	Added By Shyam
	-------------------------------------------------------------------*/

 		'DELETE_IMAGE_MSG' => "Bild löschen",
 		'DELETE_IMAGE_ALERT' => "Wollen Sie das Bild wirklich löschen?",
		'MAIL_STATUS_SUCCESS' => "E-Mail-Status erfolgreich entsperrt.",
		'MAIL_STATUS_FAIL' => "E-Mail-Status erfolgreich blockiert.",
		'SMS_STATUS_SUCCESS' => "SMS-Status erfolgreich entsperren.",
		'SMS_STATUS_FAIL' => "SMS-Statusblockierung erfolgreich.",
		'TITLE_EXAMINATION_SORTINGORDER' => "Sortierreihenfolge",
		'ERR_TITLE_EXAMINATION_SORTINGORDER_REQUIRED'=>'Das Feld Sortierreihenfolge ist erforderlich.',
		'TITLE_SORTORDER' => 'Sortierreihenfolge',
		'SEND_MAIL' => 'E-Mail senden',
		'SEND_SMS' => 'SMS senden',
		'SEND_DOC_MAIL_CONTENT' => 'Anbei finden Sie Ihren angefragten Befund',
		'REGARDS' => 'Herzliche Grüße',
		'YOUR_PUREGYN_TEAM' => 'Ihr PureGyn Team',
		'ERR_TODO_LIST_COMMENT' =>'Notiz ist erforderlich',

		'January'=>'Januar',
		'February'=>'Februar',
		'March'=>'März',
		'April'=>'April',
		'May'=>'Mai',
		'June'=>'Juni',
		'July'=>'Juli',
		'August'=>'August',
		'September'=>'September',
		'October'=>'Oktober',
		'November'=>'November',
		'December'=>'Dezember',

		 	 /*--------------------------------------
	   |	Manage patient notification section
	   -------------------------------------------------------------------*/
	    // 'TITTLE_NOTIFICATION_PATIENT'=>'Benachrichtigungspatienten',
	    'TITTLE_NOTIFICATION_PATIENT'=>'Patienten',
		'TITTLE_PATIENT'=>'Patient auswählen',
		'PATIENT_REQUIRED' =>'Bitte Patient auswählen',
		'PATIENT_SAVED'=>'Patient erfolgreich gespeichert',
		'NOTIFICATION_SEND_SUCCESS'=>'Benachrichtigung erfolgreich gesendet',
		'ERR_PATIENT_NOT_FOUND'=>'Patient nicht gefunden',
		'SEND_NOTIFICATION_BTN'=>'Benachrichtigungen senden',
		'MANAGE_NOTIFICATION_PATIENT'=>'Benachrichtigungspatienten',


		//Smart Appoiment 15-Sep-22 Added by divya==============//
		// 'ERR_DOCTOR_NOT_AVALIABLE' =>'Der Arzt ist leider nicht erreichbar',
		'ERR_DOCTOR_NOT_AVALIABLE' =>'Dieser Arzt ist im gewählten Zeitraum nicht verfügbar',//added by roshani

		'TITLE_BOOKING_TIMEFRAME' =>'BUCHUNGSZEITRAHMEN',
		'ERR_BOOKING_TIMEFRAME_VALUE'	=>'Bitte geben Sie einen gültigen Wert ein',
		'ERR_BOOKING_TIMEFRAME_DESC'	=>'Bitte wählen Sie Woche oder Monat aus',
		'TITLE_ADD_TIMEFRAME'=>'Zeitrahmen hinzufügen',
		'TITLE_SELECT_TIMEFRAME'=>'Wählen Sie Zeitrahmen aus',
		'ERR_SELECT_TIMEFRAME'=>'Bitte Zeitraum auswählen',
		'ERR_ADD_TIMEFRAME'=>'Bitte Zeitrahmen eingeben',
        'TITLE_OPTIMAL_APPOINTMENT' =>'Optimaler Termin',
		'TITLE_OFF' =>'Aus',
		'TITLE_ON' =>'An',
		'TITTLE_FORM_DATE'=>'Datum von',
        'TITTLE_TO_DATE'=>'Datum bis',

        'ERR_DOCTOR_ROSTER_NOT_AVALIABLE' =>'An anderen Terminen ist der Arzt nicht erreichbar',
		//End Smart Appoiment 15-Sep-22 Added by divya==============//


		// 'REMINDED_SERVICE'=>'Service erinnert werden', //commented for #303 issue on 14-feb-25
		// 'REMINDED_SERVICE'=>'Folge-Leistung',//added on 14-feb-25 for #303 issue

		
		'REMINDED_SERVICE'=>'Folgeleistung auswählen',//Roshani added on 10-april-25 for #326 (c) issue

		


		'SELECT_SERVICE'=>'Dienst auswählen',


		/*--------------------------------------
        |   Check list section added on 26-dec-22
        -------------------------------------------------------------------*/
        'TITLE_CHECKLIST_DOCUMENT_HEADER_IMAGE'   => 'Header-Bild',
		'TITLE_CHECKLIST_DOCUMENT_FOOTER_IMAGE'   => 'Fußzeilenbild',
		'ERR_CHECKLIST_DOCUMENT_HEADER_IMAGE'     => 'Das eingereichte Header-Bild ist erforderlich.',
		'ERR_CHECKLIST_DOCUMENT_FOOTER_IMAGE'     => 'Das eingereichte Fußzeilenbild ist erforderlich.',


		/*--------------------------------------
       |   Patient note related added on 23-feb-22
       -------------------------------------------------------------------*/

        'ERR_NOTE_APPOINTMENT_DATE'   => 'Bitte Termindatum auswählen',
        'ERR_NOTE'   => 'Bitte Notiz eingeben',
		'TITLE_END_CYCLE' => 'Ende aller Zyklen nach',
		'IGNORE_STATUS' => 'ignoriert',

		/*-----------------------------------------
        | Reminder popup message
        --------------------------------------------------------*/

        'Reminder_success_msg' => 'Die Erinnerung wird hinzugefügt, wenn Sie auf die Schaltfläche „Speichern“ klicken.',

          /*-----------------------------------------
        | Patient duplication message
        --------------------------------------------------------*/
        // 'ERR_MOBILE_UNIQUE' => "Diese Mobilnummer ist bereits einer anderen Patientin zugeordnet. Bitte geben sie eine andere Nummer ein.",
        'ERR_MOBILE_UNIQUE' => "Dieser Patienteneintrag existiert bereits.",

        'ERR_ROLE_NOT_ALLOWED' => "Ihre Benutzerrolle ist nicht berechtigt diese Funktion zu deaktivieren.",


         // added on 15-feb-24
		'TITLE_OPTIMAL' =>'Optimaler Termin',

		// added on 23-feb-24
		'ERR_IMPORT_PATIENT' => "Es werden nicht die gesamten Daten importiert",
		'ERR_IMPORT_APPOINTMENT' => "Es werden nicht die gesamten Daten importiert",
		'ERR_PATIENT_GENDER_REQUIRED'	=> "Das Feld „Geschlecht“ ist erforderlich.",

        'TITLE_CONFIRM_WEB_APPOINTMENT' => 'Bitte bestätigen Sie Ihren Termin',
		'APPOINTMENT_BOOKED' => "Termin erfolgreich gebucht.",
		'APPOINTMENT_CANCEL' => "Termin erfolgreich abgesagt.",


		'LABEL_PROFILE'=>'Dem Dashboard zuweisen', //added on 17-apr-24
		'LABEL_PERSONAL_DATA'=>'PERSÖNLICHE DATEN', //added on 17-apr-24
		'ERR_CSV_IMPORT'=>'Die hochgeladene Datei muss eine CSV-Datei sein.',
		'ERR_CSV_VALID_DATA'=>'Bitte geben Sie gültige Daten für die Zeile ein ',

        //Roshani added error messages for 172
		'ERR_PATTERN_COLOR_CODE' => 'Der Farbcode ist ungültig',
        //Roshani added error messages for 172

        'TITLE_ORDINATION_HEADER_FOOTER_COLOR_CODE' => 'Textcode für Kopf- und Fußzeile',//added on 30-july-24
        'ERR_ORDINATION_HEADER_FOOTER_COLOR_CODE'  => "Das Feld für den Textfarbcode ist erforderlich.", //added on 30-july-24

		 "TITLE_ERR_MESSAGE_PERIOD_OF_CONTROL"=>'Der Kontrollzeitraum sollte 12 Jahre nicht überschreiten.',

		/*********** CR #102 ***********/
        'TITLE_COUNTRY' => 'Land',
        'TITLE_SELECT_COUNTRY' => 'Wählen Sie Land aus',
        'ERR_COUNTRY_REQUIRED'=> 'Das Feld „Land“ ist erforderlich.',
        'TITLE_COUNTRY_SWITZERLAND'=> 'Schweiz',
        'TITLE_COUNTRY_GERMANY'=> 'Deutschland',
        'TITLE_COUNTRY_AUSTRIA'=> 'Österreich',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY' => 'Ungültige Postleitzahl für Deutschland. Es muss 5-stellig sein.',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1' => 'Ungültige Postleitzahl für die ',
        'MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2' => ' Es muss 4-stellig sein.',
        'INVALID_POSTAL_CODE' => 'Die Postleitzahl muss aus 4 oder 5 Ziffern bestehen.',
        /*********** CR #102 ***********/

         /*********** CR #214 ***********/

        'TITLE_CYCLE_CALENDER' => 'Zykluskalender',

        /*********** CR #214 ***********/

         /*************CR #191***************************/
        'ERR_MIMIMUM_AGE'=> 'Bitte geben Sie eine gültige Eingabe ein.',
        /***************CR #191**************************/

        'HEIGHT_UPDATED' => "Erfolgreich aktualisiert.", //added on 4-nov-24
        'HEIGHT_UPDATED' => "Erfolgreich aktualisiert.", //added on 4-nov-24	

        /***************** CR #210 on 08-nov-24*****************/
        //Title
        'TITLE_APK_DOWNLOAD' => 'Tablet-Apps herunterladen',
        'TITLE_APK_VIEW' => 'Tablet-Apps anzeigen',
        'TITLE_APK_ADD' => 'Tablet-Apps hinzufügen',
        'TITLE_APKS' => 'Apks',
        'TITLE_UPLOAD_TEXT' => 'Hochladen',
        'TITLE_SIGNDOC_APP' => 'SignDoc App',
        'TITLE_MASTER_DATA_APP' => 'Stammdaten-App',
        'TITLE_WAITING_NUM_APP' => 'Wartende Nummern-App',

        //Fehler 
        'ERR_SIGNDOC_APP_REQUIRED' => 'SignDoc-App ist erforderlich.',
        'ERR_SIGNDOC_APP_APK_ONLY' => 'SignDoc-App muss eine APK-Datei sein.',
        'ERR_SIGNDOC_APP_FILE' => 'SignDoc-App muss eine gültige Datei sein.',
        'ERR_SIGNDOC_APP_MIMETYPES' => 'SignDoc-App muss eine gültige Android-APK sein.',

        'ERR_MASTER_DATA_APP_REQUIRED' => 'Stammdaten-App ist erforderlich.',
        'ERR_MASTER_DATA_APP_APK_ONLY' => 'Master-App muss eine APK-Datei sein.',
        'ERR_MASTER_DATA_APP_FILE' => 'Master-App muss eine gültige Datei sein.',
        'ERR_MASTER_DATA_APP_MIMETYPES' => 'Master-App muss eine gültige Android-APK sein.',

        'ERR_WATING_NUM_APP_REQUIRED' => 'Wartenummern-App ist erforderlich.',
        'ERR_WATING_NUM_APP_APK_ONLY' => 'Wartende Nummern-App muss eine APK-Datei sein.',
        'ERR_WATING_NUM_APP_FILE' => 'Wartende Nummern-App muss eine gültige Datei sein.',
        'ERR_WATING_NUM_APP_MIMETYPES' => 'Wartende Nummern-App muss eine gültige Android-APK sein.',

        //Titelnamen anzeigen
        'TITLE_APK_NAME' => 'Apk-Name',
        'TITLE_APK_FILE_NAME' => 'Apk-Dateiname',
        'TITLE_APK_VERSION' => 'Apk-Version',
        'TITLE_DOWNLOAD_APK' => 'Apk herunterladen',

         'APK_UPLOADED' => 'APK erfolgreich hochgeladen.',

        /***************** CR #210 on 08-nov-24 *****************/ 	

        'TITLE_DELETE_BUTTON_PATIENT' => 'Löschen', //added for #303 issue
        'ERR_MOBILE_NUMBER' => "Die Handynummer muss eine Nummer sein.",
		'ERR_CHECKLIST_ALREADY_ADDED_TO_SERVICE' => "Der Checklistenstatus kann nicht geändert werden, da er bereits mit dem Dienst verknüpft ist",
		//added on 14-nov-24 for #303 issue

	    'ERR_DEVICE_IDS' 	=> "Ungültige Geräte-IDs", //added on 10-nov-25
	    'ERR_DEVICE_ID_NOT_EXISTS' 	=> "Geräte-ID existiert nicht", //added on 10-nov-25
		'MSG_SEND_PUSH_NOTIFICATION' =>'Push-Benachrichtigung erfolgreich gesendet.',//added on 10-nov-25
		// 'TITLE_Day' =>'Tag',//added on 11-nov-25 commented on 12-dec-25
		'TITLE_Day' =>'Erinnerung',//changed on 12-dec-25
		// 'TITLE_Current_Day' =>'Selber Tag',//added on 11-nov-25 commented on 12-dec-25
		'TITLE_Current_Day' =>'Am Tag des Termins',//changed on 12-dec-25
		// 'TITLE_Previous_Day' =>'Vorheriger Tag',//added on 11-nov-25 commented on 12-dec-25
		'TITLE_Previous_Day' =>'Am Vortag',//changed on 12-dec-25
		// 'TITLE_IS_EMERGENCY_DOCTOR' => 'Emergency Doktor aktivieren',//added on 3-dec-25
		'TITLE_IS_EMERGENCY_DOCTOR' => 'Ärzt:in online buchbar (für Emergency Doctor "nein" anklicken)',//added on 3-dec-25

		'TITLE_SPECIALIST_ADD' =>'Fachrichtung auswählen',//Added on 29-jan-26 for #394

        'TITLE_ANDROID_FLAG' =>'Android-Force-Update-Status',//Added on 12-feb-26
		'TITLE_IOS_FLAG' =>'iOS-Status „Aktualisierung erzwingen“',//Added on 12-feb-26
		'ERR_MOBILE_NO_INVALID' => "Die Handynummer muss eine gültige Nummer sein.", //Added on 12-feb-26




];
