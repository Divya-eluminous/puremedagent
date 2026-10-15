<?php

namespace App\Services\AiAssistant;

/**
 * Turns the assistant's English wording into German.
 *
 * The conversation engine writes every message once, in English. This class is
 * the single place those messages become German, so the practice can read and
 * approve the whole German wording in one file rather than hunting through the
 * engine. Nothing here decides anything - it only changes words on their way
 * out, after the conversation has already moved on.
 *
 * Two kinds of entry:
 *  - EXACT: a message with no variable parts, matched whole.
 *  - PATTERNS: a message with something inserted into it (a name, a date, a
 *    count). The inserted part is carried across unchanged, so doctor names,
 *    appointment types and dates stay exactly as PureMed stores them.
 *
 * A message that matches neither is returned unchanged rather than guessed at.
 * That is what a PureMed error message looks like (it arrives already worded by
 * the practice's own API), and it is also how a missing entry shows up in
 * tests/ai-assistant/german_test.php.
 *
 * German uses the formal "Sie" throughout, as a medical practice would.
 */
class Translator
{
    /** @var array<string, string> */
    private const EXACT = [
        // Greetings and resuming
        "Hi, I'm your PureMed Assistant." => 'Hallo, ich bin Ihr PureMed-Assistent.',
        'Welcome back.' => 'Willkommen zurück.',
        "Welcome back. Let's pick up where we left off." => 'Willkommen zurück. Machen wir dort weiter, wo wir aufgehört haben.',
        "Starting fresh for a new patient. Hi, I'm your PureMed Assistant." => 'Wir beginnen neu für einen neuen Patienten. Hallo, ich bin Ihr PureMed-Assistent.',
        'How can I help you today?' => 'Wie kann ich Ihnen heute helfen?',
        'Anything else I can help you with?' => 'Kann ich Ihnen sonst noch helfen?',
        'Is there anything else I can help you with?' => 'Kann ich Ihnen sonst noch helfen?',
        'Sorry, something went wrong. Say "start over" to begin again.' => 'Entschuldigung, da ist etwas schiefgelaufen. Sagen Sie „neu starten“, um von vorn zu beginnen.',

        // AI unavailable
        "My AI helper is temporarily unavailable, so I couldn't understand that. Please tap one of the options, try again in a moment, or call the practice."
            => 'Mein KI-Helfer ist vorübergehend nicht erreichbar, daher konnte ich das nicht verstehen. Tippen Sie bitte auf eine der Optionen, versuchen Sie es gleich noch einmal oder rufen Sie die Praxis an.',

        // Verifying a returning patient
        'For your security, I will send you a verification code first.'
            => 'Zu Ihrer Sicherheit sende ich Ihnen zuerst einen Bestätigungscode.',
        'I cannot check verification codes right now. Please try again in a moment or call the practice.'
            => 'Ich kann Bestätigungscodes gerade nicht prüfen. Bitte versuchen Sie es gleich noch einmal oder rufen Sie die Praxis an.',
        'I cannot continue with this record. Please call the practice.'
            => 'Mit diesem Datensatz kann ich nicht fortfahren. Bitte rufen Sie die Praxis an.',
        'I could not send a verification code to the mobile number on file, so for your security I cannot continue. Please call the practice.'
            => 'Ich konnte keinen Bestätigungscode an die hinterlegte Mobilnummer senden, daher kann ich aus Sicherheitsgründen nicht fortfahren. Bitte rufen Sie die Praxis an.',
        'I could not complete the verification. Please call the practice.'
            => 'Ich konnte die Bestätigung nicht abschließen. Bitte rufen Sie die Praxis an.',

        // The conversation ends after a booking
        'Thank you - have a great day! This conversation is now over and your details have been cleared. This screen will reset shortly.'
            => 'Vielen Dank – einen schönen Tag noch! Das Gespräch ist nun beendet und Ihre Angaben wurden gelöscht. Dieser Bildschirm wird in Kürze zurückgesetzt.',
        'This conversation has ended.' => 'Das Gespräch ist beendet.',
        'This conversation is now over and your details have been cleared. This screen will reset shortly.'
            => 'Dieses Gespräch ist nun beendet und Ihre Angaben wurden gelöscht. Dieser Bildschirm wird in Kürze zurückgesetzt.',

        // Consent: asked again rather than assumed
        'I want to be sure before I book anything. Please answer yes or no, or use the buttons.'
            => 'Ich möchte sichergehen, bevor ich etwas buche. Bitte antworten Sie mit Ja oder Nein oder nutzen Sie die Schaltflächen.',
        'I want to be sure before I cancel anything. Please answer yes or no, or use the buttons.'
            => 'Ich möchte sichergehen, bevor ich etwas absage. Bitte antworten Sie mit Ja oder Nein oder nutzen Sie die Schaltflächen.',
        'I need a clear yes before I book anything. Shall I book it?'
            => 'Ich brauche ein klares Ja, bevor ich etwas buche. Soll ich den Termin buchen?',
        'I need a clear yes before I cancel anything. Shall I cancel this appointment?'
            => 'Ich brauche ein klares Ja, bevor ich etwas absage. Soll ich diesen Termin absagen?',

        // AI very busy
        "My AI helper is very busy right now, so I couldn't understand that. Please tap one of the options or try again in a moment."
            => 'Mein KI-Helfer ist gerade stark ausgelastet, daher konnte ich das nicht verstehen. Tippen Sie bitte auf eine der Optionen oder versuchen Sie es gleich noch einmal.',

        // Intent and navigation
        'Of course - here are a few more.' => 'Gerne – hier sind noch einige weitere.',
        "Of course - let's book another appointment. I still have your details." => 'Gerne – buchen wir einen weiteren Termin. Ich habe Ihre Daten noch.',
        "Sure - let's start a new booking." => 'Gerne – beginnen wir eine neue Buchung.',
        'Sure, let me show you the doctors again.' => 'Gerne, ich zeige Ihnen die Ärztinnen und Ärzte noch einmal.',
        'No problem. What do you need the appointment for?' => 'Kein Problem. Wofür benötigen Sie den Termin?',
        "Of course. I'll just need a few details to find your booking." => 'Gerne. Ich brauche nur ein paar Angaben, um Ihre Buchung zu finden.',
        "Happy to. I'll just need a few details to look them up." => 'Sehr gerne. Ich brauche nur ein paar Angaben, um sie nachzuschlagen.',
        "Sure, I'd be happy to help with that." => 'Gerne, dabei helfe ich Ihnen.',
        "Sorry, I didn't quite catch that. Tell me what you need - for example, \"I'd like to book an appointment\"."
            => 'Entschuldigung, das habe ich nicht ganz verstanden. Sagen Sie mir, was Sie brauchen – zum Beispiel „Ich möchte einen Termin buchen“.',
        "I can book a new appointment or cancel one for you - just say which." => 'Ich kann einen neuen Termin buchen oder einen bestehenden absagen – sagen Sie mir einfach, was Sie möchten.',
        "Anything else? Say 'cancel my appointment' to cancel another, or 'book another' to make a new one."
            => "Noch etwas? Sagen Sie „meinen Termin absagen“, um einen weiteren abzusagen, oder „noch einen buchen“, um einen neuen zu vereinbaren.",
        "I can't move an appointment once it is booked, but I can cancel this one and book you a new time."
            => 'Ein gebuchter Termin lässt sich nicht verschieben, aber ich kann diesen absagen und Ihnen eine neue Zeit buchen.',
        "I can't change your details here, I'm afraid - the practice can update those for you."
            => 'Ihre Daten kann ich hier leider nicht ändern – das kann die Praxis für Sie erledigen.',

        // Registration
        "That doesn't look like a valid mobile number. Please tell me the number again, digits only."
            => 'Das sieht nicht nach einer gültigen Mobilnummer aus. Bitte nennen Sie mir die Nummer noch einmal, nur Ziffern.',
        "I couldn't read that as a date of birth. Could you give me your mobile number and date of birth again? For example: 664 1234567, 27.03.1993."
            => 'Das konnte ich nicht als Geburtsdatum lesen. Können Sie mir Ihre Mobilnummer und Ihr Geburtsdatum bitte noch einmal nennen? Zum Beispiel: 664 1234567, 27.03.1993.',
        "Sorry, I didn't catch that. Could you give me your mobile number and your date of birth? For example: 664 1234567, 27.03.1993."
            => 'Entschuldigung, das habe ich nicht verstanden. Können Sie mir Ihre Mobilnummer und Ihr Geburtsdatum nennen? Zum Beispiel: 664 1234567, 27.03.1993.',
        "I couldn't read that as a date of birth, though." => 'Das Geburtsdatum konnte ich allerdings nicht lesen.',
        "I couldn't read that as a date. Could you include the year too? For example 27.03.1993 or 5 April 1990."
            => 'Das konnte ich nicht als Datum lesen. Nennen Sie bitte auch das Jahr, zum Beispiel 27.03.1993 oder 5. April 1990.',
        "Hmm, that doesn't look like a valid email address. Could you repeat it, spell it out, or type it in?"
            => 'Hm, das sieht nicht nach einer gültigen E-Mail-Adresse aus. Können Sie sie wiederholen, buchstabieren oder eintippen?',
        'Great, thank you.' => 'Sehr gut, danke.',
        'No problem - email addresses are hard to hear correctly. Could you type it in instead?'
            => 'Kein Problem – E-Mail-Adressen sind schwer richtig zu verstehen. Können Sie sie stattdessen eintippen?',
        'Could you read me the code? It is four digits.' => 'Können Sie mir den Code vorlesen? Er hat vier Ziffern.',
        'Thank you - that is verified.' => 'Danke – das ist bestätigt.',
        'That code is not right, and I have to stop there for safety. Say "send it again" and I will send you a new one.'
            => 'Dieser Code ist nicht richtig, und aus Sicherheitsgründen muss ich hier stoppen. Sagen Sie „erneut senden“, dann schicke ich Ihnen einen neuen.',
        'That code is not right. Could you check it and read it to me again?' => 'Dieser Code ist nicht richtig. Können Sie ihn prüfen und mir noch einmal vorlesen?',
        "Sorry, I didn't understand. Please answer male or female." => 'Entschuldigung, das habe ich nicht verstanden. Bitte antworten Sie mit männlich oder weiblich.',
        'Before I set you up, please read me the four digit code I sent you.' => 'Bevor ich Sie anlege, lesen Sie mir bitte den vierstelligen Code vor, den ich Ihnen geschickt habe.',
        "Thanks. I'll just need a few details to set you up." => 'Danke. Ich brauche noch ein paar Angaben, um Sie anzulegen.',
        "I couldn't find any records for that mobile number and date of birth. Could you check the number for me?"
            => 'Zu dieser Mobilnummer und diesem Geburtsdatum habe ich keine Daten gefunden. Können Sie die Nummer bitte prüfen?',
        'I could not send you a verification code just now. Please call the practice and they will book this for you.'
            => 'Ich konnte Ihnen gerade keinen Bestätigungscode senden. Bitte rufen Sie die Praxis an, dort wird der Termin für Sie gebucht.',
        'I have sent that code a few times now. Please call the practice and they will help you from here.'
            => 'Ich habe den Code jetzt mehrmals gesendet. Bitte rufen Sie die Praxis an, dort hilft man Ihnen weiter.',
        'Please read me the four digit code.' => 'Bitte lesen Sie mir den vierstelligen Code vor.',
        'Please call the practice and they will book this for you.' => 'Bitte rufen Sie die Praxis an, dort wird der Termin für Sie gebucht.',
        'May I know your full name?' => 'Darf ich Ihren vollständigen Namen erfahren?',
        'And your first name?' => 'Und Ihr Vorname?',
        'What mobile number can the practice reach you on, and your date of birth?' => 'Unter welcher Mobilnummer kann die Praxis Sie erreichen, und wie lautet Ihr Geburtsdatum?',
        'And what mobile number can the practice reach you on?' => 'Und unter welcher Mobilnummer kann die Praxis Sie erreichen?',
        'And your date of birth?' => 'Und Ihr Geburtsdatum?',
        "What's the best email address for your confirmation?" => 'Welche E-Mail-Adresse ist die beste für Ihre Bestätigung?',
        'Last one - are you male or female?' => 'Zum Schluss – sind Sie männlich oder weiblich?',

        // Doctor, type, day, time
        'Which doctor would you like to see?' => 'Welche Ärztin oder welchen Arzt möchten Sie sehen?',
        'What do you need the appointment for?' => 'Wofür benötigen Sie den Termin?',
        'Which day works best for you?' => 'Welcher Tag passt Ihnen am besten?',
        "I'll keep that in mind for the times. First, which doctor would you like to see?"
            => 'Das merke ich mir für die Uhrzeiten. Zuerst: Welche Ärztin oder welchen Arzt möchten Sie sehen?',
        "I'll remember that for the times. First, what do you need the appointment for?"
            => 'Das merke ich mir für die Uhrzeiten. Zuerst: Wofür benötigen Sie den Termin?',
        "I didn't catch which doctor you meant. Please pick one from the list." => 'Ich habe nicht verstanden, welche Ärztin oder welchen Arzt Sie meinen. Bitte wählen Sie aus der Liste.',
        "I didn't catch that one. Please choose an appointment type from the list." => 'Das habe ich nicht verstanden. Bitte wählen Sie eine Terminart aus der Liste.',
        'Of course - which of these days would suit instead?' => 'Gerne – welcher dieser Tage würde stattdessen passen?',
        "I don't have anything free on that day. Here is what I do have." => 'An diesem Tag habe ich nichts frei. Das habe ich anzubieten.',
        "I don't have anything free on that day." => 'An diesem Tag habe ich nichts frei.',
        'Which of these days works for you?' => 'Welcher dieser Tage passt Ihnen?',
        "I didn't catch that day. Which of these works for you?" => 'Diesen Tag habe ich nicht verstanden. Welcher davon passt Ihnen?',
        'Of course - which of these days would you prefer?' => 'Gerne – welchen dieser Tage bevorzugen Sie?',
        'No problem - none of those, then. Which day would suit you better?' => 'Kein Problem – dann keiner davon. Welcher Tag würde Ihnen besser passen?',
        'Of course, here are more times.' => 'Gerne, hier sind weitere Uhrzeiten.',
        "I didn't catch that time. Please pick one of the times shown." => 'Diese Uhrzeit habe ich nicht verstanden. Bitte wählen Sie eine der angezeigten Uhrzeiten.',
        "I don't have that free, I'm afraid. Here is what I do have." => 'Das habe ich leider nicht frei. Das habe ich anzubieten.',
        'Of course - which day would you prefer?' => 'Gerne – welchen Tag bevorzugen Sie?',
        "No problem. Let's pick another time." => 'Kein Problem. Suchen wir eine andere Zeit.',
        "I don't have that day for this appointment, though." => 'Diesen Tag habe ich für diesen Termin allerdings nicht.',
        "Let's get to the times - I just need a couple more details first." => 'Kommen wir zu den Uhrzeiten – ich brauche vorher nur noch ein paar Angaben.',
        'Of course - here are the days I have available. Which one works for you?' => 'Gerne – das sind die Tage, die ich anbieten kann. Welcher passt Ihnen?',
        'Yes - here are the others I have.' => 'Ja – das sind die anderen, die ich habe.',
        "I can't see the doctor list just now. Please try again in a moment." => 'Ich kann die Liste der Ärzte gerade nicht sehen. Bitte versuchen Sie es gleich noch einmal.',
        "I couldn't load the doctor list right now. Please try again in a moment." => 'Ich konnte die Liste der Ärzte gerade nicht laden. Bitte versuchen Sie es gleich noch einmal.',
        "I found your details, but I couldn't load the doctor list. Please try again in a moment."
            => 'Ich habe Ihre Daten gefunden, konnte aber die Liste der Ärzte nicht laden. Bitte versuchen Sie es gleich noch einmal.',
        "I couldn't save your details just now. Could you check your mobile number and date of birth?"
            => 'Ich konnte Ihre Daten gerade nicht speichern. Können Sie Ihre Mobilnummer und Ihr Geburtsdatum bitte prüfen?',
        'That doctor has no appointment types available. Could you pick a different doctor?'
            => 'Für diese Ärztin oder diesen Arzt sind keine Terminarten verfügbar. Können Sie eine andere wählen?',

        // Booking
        'Which appointment would you like to cancel?' => 'Welchen Termin möchten Sie absagen?',
        "Let's start that again - I want to be sure I book the right appointment." => 'Fangen wir noch einmal von vorn an – ich möchte sicher sein, den richtigen Termin zu buchen.',
        'I want to be sure of the day before I book. Which of these works for you?' => 'Ich möchte mir beim Tag sicher sein, bevor ich buche. Welcher davon passt Ihnen?',
        "I'm sorry - that time was taken while we were talking. Could you pick another one?" => 'Es tut mir leid – diese Zeit wurde vergeben, während wir gesprochen haben. Können Sie eine andere wählen?',
        "I'm sorry - that time was just taken while we were talking. Could you pick another one?" => 'Es tut mir leid – diese Zeit wurde gerade vergeben, während wir gesprochen haben. Können Sie eine andere wählen?',
        "I couldn't confirm that appointment. Please choose another time." => 'Ich konnte diesen Termin nicht bestätigen. Bitte wählen Sie eine andere Zeit.',
        'All done! Your appointment is confirmed.' => 'Fertig! Ihr Termin ist bestätigt.',
        "I couldn't find any free times for that appointment any more. You could try a different appointment type, or another doctor."
            => 'Für diesen Termin habe ich keine freien Zeiten mehr gefunden. Sie könnten eine andere Terminart oder eine andere Ärztin bzw. einen anderen Arzt versuchen.',
        "I don't have the details of that one to hand. Let's book it fresh - which doctor would you like to see?"
            => 'Die Angaben dazu habe ich nicht zur Hand. Buchen wir ihn neu – welche Ärztin oder welchen Arzt möchten Sie sehen?',
        'Sure - I still have those details.' => 'Gerne – diese Angaben habe ich noch.',

        // Viewing and cancelling
        "You don't have any upcoming appointments to cancel." => 'Sie haben keine bevorstehenden Termine, die abgesagt werden könnten.',
        'We can carry on with the new one, though.' => 'Mit dem neuen Termin können wir aber weitermachen.',
        'Of course. Here are your upcoming appointments.' => 'Gerne. Hier sind Ihre bevorstehenden Termine.',
        "You don't have any upcoming appointments at the moment." => 'Sie haben im Moment keine bevorstehenden Termine.',
        "I couldn't find any past appointments on your record." => 'In Ihrer Akte habe ich keine vergangenen Termine gefunden.',
        "I couldn't cancel that appointment. Please contact the practice." => 'Ich konnte diesen Termin nicht absagen. Bitte wenden Sie sich an die Praxis.',
        "No problem, I've left your appointments as they are." => 'Kein Problem, ich habe Ihre Termine so belassen, wie sie sind.',
        "I'm not sure which one you mean. You can tell me the time, like 5:40, or tap one below." => 'Ich bin nicht sicher, welchen Sie meinen. Nennen Sie mir die Uhrzeit, zum Beispiel 17:40, oder tippen Sie unten auf einen.',
        "I've kept that appointment. Nothing has been cancelled." => 'Ich habe diesen Termin behalten. Es wurde nichts abgesagt.',
        'Just to be sure - shall I cancel this appointment? This cannot be undone.' => 'Zur Sicherheit: Soll ich diesen Termin absagen? Das kann nicht rückgängig gemacht werden.',

        // After booking
        "I can cancel an appointment for you - just say 'cancel my appointment' and I'll show you your upcoming ones. To move an appointment, cancel it and book a new time, or contact the practice."
            => 'Ich kann einen Termin für Sie absagen – sagen Sie einfach „meinen Termin absagen“, dann zeige ich Ihnen Ihre bevorstehenden Termine. Um einen Termin zu verschieben, sagen Sie ihn ab und buchen Sie eine neue Zeit, oder wenden Sie sich an die Praxis.',
        "I can't look up your appointment history yet - I can only book new appointments. The practice can give you the full list. To book another one, say 'start over'."
            => 'Ihren Terminverlauf kann ich noch nicht abrufen – ich kann nur neue Termine buchen. Die vollständige Liste erhalten Sie in der Praxis. Um einen weiteren Termin zu buchen, sagen Sie „neu starten“.',
        "Your appointment is confirmed and the practice has it on file. Booking appointments is all I can help with for now, so please contact the practice for anything else. To book another one, say 'start over'."
            => 'Ihr Termin ist bestätigt und liegt der Praxis vor. Derzeit kann ich nur beim Buchen von Terminen helfen, bitte wenden Sie sich für alles Weitere an die Praxis. Um einen weiteren Termin zu buchen, sagen Sie „neu starten“.',

        // Buttons and placeholders
        'Book an appointment' => 'Termin buchen',
        'Cancel an appointment' => 'Termin absagen',
        'Past appointments' => 'Vergangene Termine',
        "Yes, that's right" => 'Ja, das stimmt',
        "No, I'll type it" => 'Nein, ich tippe sie ein',
        'Male' => 'Männlich',
        'Female' => 'Weiblich',
        'Different doctor' => 'Andere Ärztin / anderer Arzt',
        'Continue' => 'Weiter',
        "No problem - let's use a different number." => 'Kein Problem – nehmen wir eine andere Nummer.',
        'Which country is your mobile number from?' => 'Aus welchem Land ist Ihre Handynummer?',
        'Other country' => 'Anderes Land',
        'Please type your country code, for example +91 or 0091.' => 'Bitte geben Sie Ihre Landesvorwahl ein, zum Beispiel +91 oder 0091.',
        "I didn't catch the country. Please choose one from the list, or say \"other\"." => 'Das Land habe ich nicht verstanden. Bitte wählen Sie eines aus der Liste oder sagen Sie „anderes“.',
        "That doesn't look like a country code. It starts with + or 00, for example +91 or 0091." => 'Das sieht nicht wie eine Landesvorwahl aus. Sie beginnt mit + oder 00, zum Beispiel +91 oder 0091.',
        'Which services would you like to include? Tick the ones you want, then press Continue.' => 'Welche Leistungen möchten Sie dazu? Wählen Sie die gewünschten aus und drücken Sie auf „Weiter“.',
        'Please tick the services you would like and press Continue, or say "all", "none" or "continue".' => 'Bitte wählen Sie die gewünschten Leistungen aus und drücken Sie auf „Weiter“, oder sagen Sie „alle“, „keine“ oder „weiter“.',
        'Loading the services...' => 'Leistungen werden geladen...',
        'Yes, book it' => 'Ja, buchen',
        'Pick another time' => 'Andere Zeit wählen',
        'Keep them all' => 'Alle behalten',
        'Yes, cancel it' => 'Ja, absagen',
        'No, keep it' => 'Nein, behalten',
        'Show more' => 'Mehr anzeigen',
        'Message PureMed Assistant' => 'Nachricht an PureMed-Assistent',
        'Enter the 4-digit code' => 'Vierstelligen Code eingeben',
        "Say 'book another' to make a new appointment" => 'Sagen Sie „noch einen buchen“, um einen neuen Termin zu vereinbaren',
        "Say 'start over' to book another appointment" => 'Sagen Sie „neu starten“, um einen weiteren Termin zu buchen',

        // Narration while the server works
        'Just checking our records...' => 'Ich prüfe kurz unsere Unterlagen ...',
        'Registering your details...' => 'Ihre Daten werden registriert ...',
        'Finding available doctors...' => 'Verfügbare Ärzte werden gesucht ...',
        'Loading appointment types...' => 'Terminarten werden geladen ...',
        'Checking available slots...' => 'Freie Termine werden geprüft ...',
        'Verifying that slot is still free...' => 'Ich prüfe, ob der Termin noch frei ist ...',
        'Booking your appointment...' => 'Ihr Termin wird gebucht ...',
        'Fetching that appointment...' => 'Der Termin wird abgerufen ...',
        'Cancelling your appointment...' => 'Ihr Termin wird abgesagt ...',

        // Correcting a message
        'That is already settled with the practice, so I cannot change this message now. I can help you with something else, though.'
            => 'Das ist bereits mit der Praxis vereinbart, daher kann ich diese Nachricht jetzt nicht mehr ändern. Bei etwas anderem helfe ich Ihnen aber gern.',
        'There is nothing to change just now.' => 'Im Moment gibt es nichts zu ändern.',
        'That is already settled with the practice, so I cannot change this message now.' => 'Das ist bereits mit der Praxis vereinbart, daher kann ich diese Nachricht jetzt nicht mehr ändern.',
        'That booking is already with the practice, so I cannot change this message now.' => 'Diese Buchung liegt bereits der Praxis vor, daher kann ich diese Nachricht jetzt nicht mehr ändern.',
    ];

    /** Words for the part of the day, as the slot prompts insert them. */
    private const PARTS_OF_DAY = [
        'morning' => 'am Vormittag',
        'afternoon' => 'am Nachmittag',
        'evening' => 'am Abend',
    ];

    /** The position words the assistant uses when it talks about one of several appointments. */
    private const ORDINALS = [
        'first' => 'erste', 'second' => 'zweite', 'third' => 'dritte', 'fourth' => 'vierte', 'fifth' => 'fünfte',
        'sixth' => 'sechste', 'seventh' => 'siebte', 'eighth' => 'achte', 'ninth' => 'neunte', 'tenth' => 'zehnte',
    ];

    /** Card headings on the confirmation and booked summaries. */
    private const SUMMARY_KEYS = [
        'patient' => 'Patient',
        'doctor' => 'Arzt',
        'appointment' => 'Termin',
        'date' => 'Datum',
        'time' => 'Uhrzeit',
        'when' => 'Zeitpunkt',
        'services' => 'Leistungen',
    ];

    /**
     * Messages with something inserted. Each pattern is matched against the
     * whole message; the callback receives the captured parts.
     *
     * @return array<string, callable>
     */
    private function patterns(): array
    {
        $n = static fn (string $count, string $one, string $many): string => $count === '1' ? $one : $many;

        return [
            '/^Too many incorrect codes\. For your security, verification is locked for (\d+) (?:minute|minutes)\. Please try again later or call the practice\.$/su'
                => fn ($m) => "Zu viele falsche Codes. Zu Ihrer Sicherheit ist die Bestätigung für {$m[1]} " . $n($m[1], 'Minute', 'Minuten') . ' gesperrt. Bitte versuchen Sie es später noch einmal oder rufen Sie die Praxis an.',
            '/^I have already sent several codes to this number\. For your security I cannot send another for (\d+) (?:minute|minutes) - please try again later or call the practice\.$/su'
                => fn ($m) => "Ich habe bereits mehrere Codes an diese Nummer gesendet. Zu Ihrer Sicherheit kann ich für {$m[1]} " . $n($m[1], 'Minute', 'Minuten') . ' keinen weiteren senden – bitte versuchen Sie es später noch einmal oder rufen Sie die Praxis an.',
            '/^Welcome back, ([^!]+)\.$/su' => fn ($m) => "Willkommen zurück, {$m[1]}.",
            '/^Welcome back, (.+)! I have your details already\.$/su' => fn ($m) => "Willkommen zurück, {$m[1]}! Ich habe Ihre Daten bereits.",
            '/^Welcome back! I have your details already\.$/su' => fn ($m) => 'Willkommen zurück! Ich habe Ihre Daten bereits.',
            "/^Of course (.+) - let's book another appointment\\. I still have your details\\.$/su" => fn ($m) => "Gerne {$m[1]} – buchen wir einen weiteren Termin. Ich habe Ihre Daten noch.",
            "/^Of course (.+) - let's book another appointment\\.$/su" => fn ($m) => "Gerne {$m[1]} – buchen wir einen weiteren Termin.",
            "/^Of course - let's book another appointment\\.$/su" => fn ($m) => 'Gerne – buchen wir einen weiteren Termin.',
            "/^No problem (.+) - I won't book anything\\.$/su" => fn ($m) => "Kein Problem {$m[1]} – ich buche nichts.",
            "/^No problem - I won't book anything\\.$/su" => fn ($m) => 'Kein Problem – ich buche nichts.',
            '/^No problem - I already have (.+) on file\.$/su' => fn ($m) => "Kein Problem – ich habe bereits {$m[1]} gespeichert.",
            "/^Thanks, I've updated your email to (.+)\\.$/su" => fn ($m) => "Danke, ich habe Ihre E-Mail-Adresse auf {$m[1]} geändert.",
            '/^Got it, (.+)\.$/su' => fn ($m) => "Verstanden, {$m[1]}.",
            '/^Thanks, I have (.+)\.$/su' => fn ($m) => "Danke, ich habe {$m[1]}.",
            '/^I only caught part of that - I heard (.+)\. Could you say the whole address again, spell it out, or type it in\?$/su'
                => fn ($m) => "Ich habe nur einen Teil verstanden – ich habe {$m[1]} gehört. Können Sie die ganze Adresse noch einmal sagen, buchstabieren oder eintippen?",
            '/^I heard (.+) - did you mean (.+)\?$/su' => fn ($m) => "Ich habe {$m[1]} gehört – meinten Sie {$m[2]}?",
            '/^I heard (.+) - is that right\?$/su' => fn ($m) => "Ich habe {$m[1]} gehört – ist das richtig?",
            "/^I'll send the confirmation to (.+)\\.$/su" => fn ($m) => "Ich schicke die Bestätigung an {$m[1]}.",
            '/^Thanks (.+), you\'re all set\.$/su' => fn ($m) => "Danke {$m[1]}, alles ist eingerichtet.",
            "/^Nice to meet you, (.*)\\. What's your last name\\?$/su" => fn ($m) => "Freut mich, {$m[1]}. Wie lautet Ihr Nachname?",
            '/^I have sent a four digit code to (your mobile and your email|your mobile|your email)\.$/su' => function ($m) {
                $where = ['your mobile and your email' => 'Ihr Handy und Ihre E-Mail-Adresse', 'your mobile' => 'Ihr Handy', 'your email' => 'Ihre E-Mail-Adresse'];

                return 'Ich habe einen vierstelligen Code an ' . $where[$m[1]] . ' gesendet.';
            },
            "/^We're already booking that - I just need your (.+) to carry on\\.$/su" => fn ($m) => "Wir buchen das bereits – ich brauche nur noch Ihren {$this->field($m[1])}, um fortzufahren.",
            "/^Sorry, I didn't catch that\\. Could you tell me your (.+) again\\?$/su" => fn ($m) => "Entschuldigung, das habe ich nicht verstanden. Können Sie mir Ihren {$this->field($m[1])} noch einmal nennen?",

            '/^Good choice - (.+)\.$/su' => fn ($m) => "Gute Wahl – {$m[1]}.",
            '/^(.+) it is\.$/su' => fn ($m) => "{$m[1]}, verstanden.",
            '/^(.+) is the only day I have free for this appointment\. I can try a different appointment type, or another doctor, if you prefer\.$/su'
                => fn ($m) => "{$m[1]} ist der einzige Tag, den ich für diesen Termin frei habe. Ich kann gern eine andere Terminart oder eine andere Ärztin bzw. einen anderen Arzt versuchen.",
            '/^The soonest I have for that is (.+)\. Here are the times - which one suits\?$/su'
                => fn ($m) => "Der früheste Termin dafür ist {$m[1]}. Hier sind die Uhrzeiten – welche passt Ihnen?",
            '/^Here is what I have on (.+)\. Which time suits\?$/su' => fn ($m) => "Das habe ich am {$m[1]} frei. Welche Uhrzeit passt Ihnen?",
            '/^Here is what I have free on (.+)\. Which time suits\?$/su' => fn ($m) => "Das habe ich am {$m[1]} frei. Welche Uhrzeit passt Ihnen?",
            '/^Here are the (morning|afternoon|evening) times on (.+)\. Which one suits\?$/su'
                => fn ($m) => 'Das sind die freien Zeiten ' . self::PARTS_OF_DAY[$m[1]] . " am {$m[2]}. Welche passt Ihnen?",
            '/^Of course - here are the (?:(morning|afternoon|evening) )?times I have on (.+)\. Which one suits\?$/su'
                => fn ($m) => 'Gerne – das sind die freien Zeiten ' . ($m[1] !== '' ? self::PARTS_OF_DAY[$m[1]] . ' ' : '') . "am {$m[2]}. Welche passt Ihnen?",
            "/^I don't have (\\d\\d:\\d\\d) free on (.+)\\. Did you mean the (.+) time in the list, or a different time\\?$/su"
                => fn ($m) => "{$m[1]} Uhr habe ich am {$m[2]} nicht frei. Meinten Sie die {$this->ordinal($m[3])} Uhrzeit in der Liste oder eine andere Zeit?",
            "/^I don't have (\\d\\d:\\d\\d) free on (.+)\\. Here are the times I do have\\.$/su"
                => fn ($m) => "{$m[1]} Uhr habe ich am {$m[2]} nicht frei. Das sind die Zeiten, die ich habe.",
            "/^The practice isn't offering that appointment with (.+) at the moment\\. You could try a different appointment type, or another doctor - the practice can tell you more\\.$/su"
                => fn ($m) => "Die Praxis bietet diesen Termin bei {$m[1]} im Moment nicht an. Sie könnten eine andere Terminart oder eine andere Ärztin bzw. einen anderen Arzt versuchen – die Praxis gibt Ihnen gern mehr Auskunft.",
            "/^I couldn't find any free times for that appointment in the next (\\d+) days\\. You can try a different appointment type, or pick another doctor\\.$/su"
                => fn ($m) => "Für diesen Termin habe ich in den nächsten {$m[1]} Tagen keine freien Zeiten gefunden. Sie können eine andere Terminart versuchen oder eine andere Ärztin bzw. einen anderen Arzt wählen.",
            '/^Currently, I only have (.+) available\. Would you like to choose one of them\?$/su'
                => fn ($m) => 'Derzeit habe ich nur ' . ($m[1] === 'no doctors' ? 'keine Ärzte' : str_replace(' and ', ' und ', $m[1])) . ' verfügbar. Möchten Sie einen davon wählen?',
            '/^(.+) is not available for booking any more\. Which doctor would you like to see\?$/su'
                => fn ($m) => "{$m[1]} ist nicht mehr buchbar. Welche Ärztin oder welchen Arzt möchten Sie sehen?",
            '/^Got it, country code (.+)\.$/su'
                => fn ($m) => "Verstanden, Landesvorwahl {$m[1]}.",
            '/^Ticked: (.+)\. Press Continue when you are happy, or tick more\.$/su'
                => fn ($m) => "Ausgewählt: {$m[1]}. Drücken Sie auf „Weiter“, wenn alles passt, oder wählen Sie weitere aus.",
            '/^Unticked: (.+)\. Press Continue when you are happy, or tick more\.$/su'
                => fn ($m) => "Abgewählt: {$m[1]}. Drücken Sie auf „Weiter“, wenn alles passt, oder wählen Sie weitere aus.",
            '/^(.+) is not offered any more\. What do you need the appointment for\?$/su'
                => fn ($m) => "{$m[1]} wird nicht mehr angeboten. Wofür benötigen Sie den Termin?",
            '/^(.+) on (.+) has been taken since\. Here is what I still have that day - which one suits\?$/su'
                => fn ($m) => "{$m[1]} Uhr am {$m[2]} wurde inzwischen vergeben. Das habe ich an diesem Tag noch frei – welche passt Ihnen?",
            '/^I no longer have anything free on (.+)\. Which of these days would suit instead\?$/su'
                => fn ($m) => "Am {$m[1]} habe ich nichts mehr frei. Welcher dieser Tage würde stattdessen passen?",
            '/^So that is (.+) with (.+) on (.+) at (.+)\. Shall I book it\?$/su'
                => fn ($m) => "Das wäre also {$m[1]} bei {$m[2]} am {$m[3]} um {$m[4]} Uhr. Soll ich ihn buchen?",

            '/^You have (\d+) upcoming (?:appointment|appointments)\.$/su'
                => fn ($m) => "Sie haben {$m[1]} bevorstehende" . $n($m[1], 'n Termin', ' Termine') . '.',
            '/^You only have (\d+) upcoming (?:appointment|appointments)\.$/su'
                => fn ($m) => "Sie haben nur {$m[1]} bevorstehende" . $n($m[1], 'n Termin', ' Termine') . '.',
            '/^You have had (\d+) (?:appointment|appointments) with us before\.$/su'
                => fn ($m) => "Sie hatten bereits {$m[1]} " . $n($m[1], 'Termin', 'Termine') . ' bei uns.',
            '/^The next one is on (.+) with (.+)\.$/su' => fn ($m) => "Der nächste ist am {$m[1]} bei {$m[2]}.",
            '/^(Your appointment|Your (\w+) appointment|Your number (\d+) appointment) is on (.+?) with (.+?)(?:, for (.+))?\.$/su' => function ($m) {
                $when = $m[4];
                $who = $m[5];
                $for = isset($m[6]) && $m[6] !== '' ? ", für {$m[6]}" : '';

                if ($m[1] === 'Your appointment') {
                    return "Ihr Termin ist am {$when} bei {$who}{$for}.";
                }

                $which = !empty($m[3]) ? $m[3] . '.' : $this->ordinal($m[2]);

                return "Ihr {$which} Termin ist am {$when} bei {$who}{$for}.";
            },
            '/^Done - your appointment on (.+) has been cancelled\. Now let me find you a new time\.$/su'
                => fn ($m) => "Erledigt – Ihr Termin am {$m[1]} wurde abgesagt. Jetzt suche ich Ihnen eine neue Zeit.",
            '/^Done - your appointment on (.+) has been cancelled\. The time is free for someone else now\.$/su'
                => fn ($m) => "Erledigt – Ihr Termin am {$m[1]} wurde abgesagt. Die Zeit ist jetzt für andere frei.",
            "/^You're welcome, (.+)\\. Have a great day!$/su" => fn ($m) => "Gern geschehen, {$m[1]}. Einen schönen Tag noch!",
            "/^You're welcome\\. Have a great day!$/su" => fn ($m) => 'Gern geschehen. Einen schönen Tag noch!',

            // Card subtitles
            '/^(\d+) more$/u' => fn ($m) => "{$m[1]} weitere",
            '/^(?:(.+) - )?(\d+) free$/u' => fn ($m) => ($m[1] !== '' ? "{$m[1]} - " : '') . "{$m[2]} frei",
            '/^(\d+) min$/u' => fn ($m) => "{$m[1]} Min.",
        ];
    }

    /** The registration fields the engine names when it re-asks. */
    private function field(string $english): string
    {
        return ['name' => 'Namen', 'first name' => 'Vornamen', 'last name' => 'Nachnamen'][$english] ?? $english;
    }

    /** "second" -> "zweite"; "7th" is left as a number with a full stop. */
    private function ordinal(string $english): string
    {
        if (isset(self::ORDINALS[$english])) {
            return self::ORDINALS[$english];
        }

        return preg_match('/^(\d+)/', $english, $m) === 1 ? $m[1] . '.' : $english;
    }

    /**
     * @param  string  $lang  'en' returns the text untouched
     */
    public function translate(string $text, string $lang): string
    {
        if ($lang !== 'de' || trim($text) === '') {
            return $text;
        }

        if (isset(self::EXACT[$text])) {
            return self::EXACT[$text];
        }

        foreach ($this->patterns() as $pattern => $build) {
            if (preg_match($pattern, $text, $matches) === 1) {
                // Optional groups that did not take part come back unset.
                $matches = array_pad($matches, 8, '');

                return $build($matches);
            }
        }

        return $text;
    }

    /** German heading for a summary card row, or the original when unknown. */
    public function summaryKey(string $key, string $lang): string
    {
        if ($lang !== 'de') {
            return $key;
        }

        return self::SUMMARY_KEYS[mb_strtolower($key)] ?? $key;
    }

    /**
     * Whether this text has a German form - used by the tests to find messages
     * the dictionary does not cover yet.
     */
    public function covers(string $text): bool
    {
        return $this->translate($text, 'de') !== $text;
    }
}
