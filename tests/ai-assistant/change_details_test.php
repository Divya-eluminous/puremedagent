<?php

/**
 * Two separate things that read as if the assistant had not listened.
 *
 * Run with:  php tests/ai-assistant/change_details_test.php
 *
 * 1. "can I change my gender" at the doctor question came back as "I didn't
 *    catch which doctor you meant". The assistant registers a patient once and
 *    has no update call, so it cannot change anything - but it can say so.
 *
 * 2. Asking to cancel with nothing to cancel left the step alone, on purpose,
 *    so the booking question came straight back underneath. That is right, and
 *    it now says why rather than appearing to ignore the request.
 *
 * NOTHING HERE TOUCHES THE PRACTICE. The API is a spy that records calls and
 * returns canned data.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['ai-assistant.nlu_driver' => 'none']);

use App\Http\Controllers\AiAssistant\ChatController;
use App\Services\AiAssistant\PatientAuthenticator;
use App\Services\AiAssistant\PureMedApiClient;

/** Records every call; never opens a connection. */
class DetailsSpyClient extends PureMedApiClient
{
    /** @var array<int, string> */
    public array $calls = [];
    /** Appointments the practice will return - empty is the case under test. */
    public array $appointments = [];

    public function __construct() {}

    public function called(string $method): bool { return in_array($method, $this->calls, true); }

    public function getAppointments(string $token, array $payload): array
    {
        $this->calls[] = 'getAppointments';
        return ['ok' => true, 'data' => $this->appointments, 'errors' => []];
    }
    public function cancelAppointment(string $token, array $payload): array
    {
        $this->calls[] = 'cancelAppointment';
        return ['ok' => true, 'data' => [], 'errors' => []];
    }
    public function registerPatient(array $payload): array
    {
        $this->calls[] = 'registerPatient';
        return ['ok' => true, 'data' => ['id' => 1], 'errors' => []];
    }
    public function bookAppointment(string $token, array $payload): array
    {
        $this->calls[] = 'bookAppointment';
        return ['ok' => true, 'data' => [['id' => 1]], 'errors' => []];
    }
    public function getDoctors(string $token, array $payload = []): array
    {
        $this->calls[] = 'getDoctors';
        return ['ok' => true, 'data' => [], 'errors' => []];
    }
    /** Choosing a doctor loads the types straight away, so this has to answer. */
    public function getAppointmentTypes(string $token, array $payload = []): array
    {
        $this->calls[] = 'getAppointmentTypes';
        return ['ok' => true, 'data' => [['id' => 5, 'name' => 'Baby-TV']], 'errors' => []];
    }
    public function getAppointmentHistory(string $token, array $payload): array
    {
        $this->calls[] = 'getAppointmentHistory';
        return ['ok' => true, 'data' => [], 'errors' => []];
    }
}

$c = new ChatController();
$call = function ($m, ...$a) use ($c) {
    $r = new ReflectionMethod($c, $m);
    $r->setAccessible(true);
    return $r->invoke($c, ...$a);
};
$ok = 0; $bad = 0;
function t($l, $g, $e) {
    global $ok, $bad;
    $p = ($g === $e); $p ? $ok++ : $bad++;
    printf("  %-56s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}
$answer = Closure::bind(function (array $state, string $text, $client, string $choice = '') {
    $s = $state;
    $auth = new PatientAuthenticator($client);
    $msgs = $this->handleAnswer($text, $choice, 'voice', $s, $client, $auth);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }

    return [$s, implode(' | ', $out)];
}, $c, ChatController::class);

$DOCTORS = [
    ['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff'],
    ['id' => 2, 'first_name' => 'Albert', 'last_name' => 'Munnar'],
];
$atDoctor = array_merge($call('freshState'), [
    'step' => 'doctor',
    'patient_id' => 8,
    'token' => str_repeat('t', 40),
    'patient' => ['first_name' => 'Divya', 'gender' => 'W', 'email' => 'divs@yopmail.com'],
    'doctors' => $DOCTORS,
]);

echo "ASKING TO CHANGE DETAILS IS RECOGNISED\n";
foreach (['can I change my gender', 'I want to change my gender', 'change my name',
    'update my email', 'can I change my details', 'change my date of birth',
    'I need to correct my mobile', 'can you update my information'] as $said) {
    t('"' . $said . '"', $call('asksToChangeDetails', $said), true);
}

echo "\nAND IS NOT CONFUSED WITH THE APPOINTMENT\n";
foreach (['change the time', 'change the day', 'another doctor', 'different doctor',
    'can I change the time', 'change my appointment to Friday', 'I want another day',
    'Gunnar Gauff', 'Albert Munnar', 'Dr Gunnar', 'the first one', 'yes', ''] as $said) {
    t('"' . ($said === '' ? '(nothing)' : $said) . '"', $call('asksToChangeDetails', $said), false);
}

echo "\nAT THE DOCTOR QUESTION IT IS ANSWERED HONESTLY\n";
foreach (['can I change my gender', 'change my name', 'update my email'] as $said) {
    $spy = new DetailsSpyClient();
    [$s, $out] = $answer($atDoctor, $said, $spy);
    t('"' . $said . '" is not read as a doctor',
        str_contains($out, "didn't catch which doctor"), false);
    t('  ...it says the practice can do it', str_contains($out, 'practice can update those'), true);
    t('  ...the step does not move', $s['step'], 'doctor');
    t('  ...the gender on file is untouched', $s['patient']['gender'] ?? null, 'W');
    t('  ...no doctor is chosen', $s['doctor'], null);
    t('  ...and PureMed is never called', $spy->calls, []);
}

echo "\nCHOOSING A DOCTOR STILL WORKS\n";
$spy = new DetailsSpyClient();
[$s, ] = $answer($atDoctor, 'Gunnar Gauff', $spy);
t('a name still selects', $s['doctor']['id'] ?? null, 1);
$spy = new DetailsSpyClient();
[$s, ] = $answer($atDoctor, '', $spy, '2');
t('a chip still selects', $s['doctor']['id'] ?? null, 2);
// A plain name that is not on the list. Not "someone else", which is a
// question about who else the practice has and has its own answer.
$spy = new DetailsSpyClient();
[$s, $out] = $answer($atDoctor, 'Zebedee', $spy);
t('an unknown name still says so', str_contains($out, "didn't catch which doctor"), true);

echo "\nNOTHING TO CANCEL: THE BOOKING IS KEPT EXACTLY AS IT WAS\n";
$midBooking = array_merge($atDoctor, [
    'step' => 'slot_time',
    'doctor' => ['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff'],
    'appointment_type' => ['id' => 5, 'name' => 'Baby-TV'],
    'slot_date' => '01.09.2026',
    'slots' => [['slot_key' => '01.09.2026|06:10|91', 'slot_date' => '01.09.2026',
        'weekday' => 'Tuesday', 'time' => '06:10', 'time_slot_id' => 91]],
]);
$spy = new DetailsSpyClient();
[$s, $out] = $answer($midBooking, 'cancel current booking', $spy);
t('it says there is nothing to cancel',
    str_contains($out, "You don't have any upcoming appointments to cancel"), true);
t('and explains why the question returns',
    str_contains($out, 'We can carry on with the new one'), true);
t('the step is kept', $s['step'], 'slot_time');
t('the doctor is kept', $s['doctor']['id'] ?? null, 1);
t('the appointment type is kept', $s['appointment_type']['id'] ?? null, 5);
t('the day is kept', $s['slot_date'], '01.09.2026');
t('the times are kept', count($s['slots']), 1);
t('cancellable stays empty', $s['cancellable'], []);
t('the cancel API is never called', $spy->called('cancelAppointment'), false);
t('only the read was made', $spy->calls, ['getAppointments']);

echo "\nTHE SAME, WHICHEVER WAY IT IS ASKED\n";
// Typed, spoken and the chip all reach the same hatch, so all three must match.
$byText = null;
foreach (['cancel current booking', 'cancel my appointment', 'I want to cancel'] as $said) {
    $spy = new DetailsSpyClient();
    [$s, $out] = $answer($midBooking, $said, $spy);
    $byText = $byText ?? $out;
    t('"' . $said . '" gives the same answer', $out, $byText);
    t('  ...and keeps the step', $s['step'], 'slot_time');
}
$spy = new DetailsSpyClient();
[$s, $out] = $answer($midBooking, '', $spy, 'cancel');
t('the chip gives the same answer too', $out, $byText);
t('  ...and keeps the step', $s['step'], 'slot_time');

echo "\nAT THE MENU THE BRIDGING LINE IS NOT PADDED IN\n";
// "Anything else I can help you with?" already follows on naturally there.
$atMenu = array_merge($atDoctor, ['step' => 'appointments', 'doctors' => $DOCTORS]);
$spy = new DetailsSpyClient();
[$s, $out] = $answer($atMenu, 'cancel current booking', $spy);
t('it still says there is nothing',
    str_contains($out, "You don't have any upcoming appointments to cancel"), true);
t('without the extra sentence', str_contains($out, 'We can carry on'), false);
t('and the step is kept', $s['step'], 'appointments');

echo "\nWITH SOMETHING TO CANCEL, NOTHING CHANGED\n";
$spy = new DetailsSpyClient();
$spy->appointments = [['id' => 555, 'start_date' => '2026-09-01 06:10:00',
    'doctor_name' => 'Gunnar Gauff', 'appointment_type_name' => 'Baby-TV']];
[$s, $out] = $answer($midBooking, 'cancel my appointment', $spy);
t('it opens the cancellation list', $s['step'], 'cancel_select');
t('with the usual wording', str_contains($out, 'Here are your upcoming appointments'), true);
t('and still cancels nothing on its own', $spy->called('cancelAppointment'), false);

printf("\n  %d passed, %d failed\n", $ok, $bad);
