<?php

/**
 * Saying no to a confirmation.
 *
 * Run with:  php tests/ai-assistant/confirm_test.php
 *
 * The defect: "it's not right" contains the word "right", which is in the
 * agreement pattern, and no negative pattern matched it. So a patient who
 * refused was read as agreeing - and the same matcher guards three answers
 * that cannot be taken back: the email address a confirmation is sent to, the
 * booking itself, and the cancellation.
 *
 * NOTHING HERE TOUCHES THE PRACTICE. Every call that would create, book,
 * cancel or modify anything goes through SpyClient below, which records the
 * call and returns a canned response instead of a request. The whole point is
 * to prove those methods are never reached, so reaching them for real would
 * defeat the test as well as dirty the data.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['ai-assistant.nlu_driver' => 'none']);

use App\Http\Controllers\AiAssistant\ChatController;
use App\Services\AiAssistant\PatientAuthenticator;
use App\Services\AiAssistant\PureMedApiClient;

/**
 * Stands in for the API. Records every call; never opens a connection.
 */
class SpyClient extends PureMedApiClient
{
    /** @var array<int, string> */
    public array $calls = [];

    public function __construct() {}

    private function note(string $method): void { $this->calls[] = $method; }
    public function called(string $method): bool { return in_array($method, $this->calls, true); }

    public function registerPatient(array $payload): array
    {
        $this->note('registerPatient');
        return ['ok' => true, 'data' => ['id' => 4242, 'token' => str_repeat('t', 40)], 'errors' => []];
    }
    public function bookAppointment(string $token, array $payload): array
    {
        $this->note('bookAppointment');
        return ['ok' => true, 'data' => ['id' => 777, 'start_date' => '2099-01-01 09:00:00'], 'errors' => []];
    }
    public function cancelAppointment(string $token, array $payload): array
    {
        $this->note('cancelAppointment');
        return ['ok' => true, 'data' => [], 'errors' => []];
    }
    public function getDoctors(string $token, array $payload = []): array
    {
        $this->note('getDoctors');
        return ['ok' => true, 'data' => [['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff']], 'errors' => []];
    }
    public function getAppointmentTypes(string $token, array $payload = []): array
    {
        $this->note('getAppointmentTypes');
        return ['ok' => true, 'data' => [['id' => 5, 'name' => 'Baby-TV']], 'errors' => []];
    }
    public function getFromDate(string $token, array $payload = []): array
    {
        $this->note('getFromDate');
        return ['ok' => true, 'data' => ['from_date' => '2099-01-01'], 'errors' => []];
    }
    /**
     * The day-grouped shape normalizeSlots() expects.
     *
     * It has to hold the slot the confirmation is about: before booking,
     * refuseUnsafeBooking() re-reads availability and refuses anything PureMed
     * no longer offers. Returning nothing here would make every booking fail
     * for that reason instead of the one under test.
     */
    public function getDoctorSlots(string $token, array $payload = []): array
    {
        $this->note('getDoctorSlots');
        return ['ok' => true, 'errors' => [], 'data' => [[
            'slot_date' => '13.08.2026',
            'weekday' => 'Thursday',
            'time_slots' => ['06:10', '06:20'],
            'time_slots_id' => [91, 92],
        ]]];
    }
    public function getAppointments(string $token, array $payload): array
    {
        $this->note('getAppointments');
        return ['ok' => true, 'data' => [], 'errors' => []];
    }
    public function getAppointmentHistory(string $token, array $payload): array
    {
        $this->note('getAppointmentHistory');
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
    printf("  %-54s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

/** Answer one message against a given state, with the spy in place. */
$answer = Closure::bind(function (array $state, string $text, SpyClient $client) {
    $s = $state;
    $auth = new PatientAuthenticator($client);
    $msgs = $this->handleAnswer($text, '', 'voice', $s, $client, $auth);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }

    return [$s, implode(' | ', $out)];
}, $c, ChatController::class);

$NEGATIVE = ["it's not right", "that's not right", 'not correct', "that's not correct",
    "that isn't right", 'not quite right', 'this is not ok', "that's not okay",
    "it's not fine", 'wrong', "that's wrong", 'incorrect', "that's incorrect", 'mistake'];
$POSITIVE = ['yes', 'yeah', 'yep', "that's right", 'correct', 'looks right',
    'ok', 'okay', 'perfect'];

echo "EVERY REFUSAL IS READ AS ONE\n";
foreach ($NEGATIVE as $said) {
    t('"' . $said . '"', [$call('saidYes', '', $said), $call('saidNo', '', $said)], [false, true]);
}

echo "\nEVERY AGREEMENT STILL AGREES\n";
foreach ($POSITIVE as $said) {
    t('"' . $said . '"', [$call('saidYes', '', $said), $call('saidNo', '', $said)], [true, false]);
}

echo "\nA BARE \"not\" IS STILL NOT A REFUSAL\n";
// The trap this fix had to avoid: "not" anywhere used to count, and a patient
// asking to see the times lost the appointment they had chosen.
foreach (["you haven't shown me the slots", "I can't see any times",
    'I haven\'t got the right day', 'show me the slots'] as $said) {
    t('"' . $said . '" is not claimed as a refusal', $call('negatesAgreement', $call('normalizeText', $said)), false);
}

echo "\nTHE SLOT CORRECTION STILL CORRECTS\n";
// Contains "wrong", but names a new time. The anchored pattern leaves it alone.
foreach (['you have taken wrong time I said 17', 'the time is wrong I said 17 40',
    'wrong day I meant 13 August'] as $said) {
    t('"' . $said . '" is not a bare refusal', $call('saidNo', '', $said), false);
}

echo "\nEMAIL: A REFUSED SUGGESTION IS NOT KEPT\n";
$base = array_merge($call('freshState'), [
    'step' => 'email_confirm',
    'patient' => ['first_name' => 'Monika', 'last_name' => 'Test',
        'mobile_no' => '76643421', 'birth_date' => '1990-01-01'],
    'pending_email' => 'monika@gmail.com',       // the suggestion
    'pending_email_heard' => 'monika@mail.com',  // what was actually said
]);
foreach (["it's not right", "that's not right", 'wrong', 'incorrect'] as $said) {
    $spy = new SpyClient();
    [$s, $out] = $answer($base, $said, $spy);
    t('"' . $said . '" does not store the suggestion', $s['patient']['email'] ?? null, null);
    t('  ...the suggestion is dropped', $s['pending_email'], null);
    t('  ...it goes back to the email question', $s['step'], 'email');
    t('  ...and asks for it again', str_contains($out, 'Could you type it in instead?'), true);
    t('  ...nothing was registered', $spy->called('registerPatient'), false);
}

echo "\nEMAIL: AGREEING STILL WORKS\n";
foreach (['yes', "that's right", 'perfect'] as $said) {
    $spy = new SpyClient();
    [$s, $out] = $answer($base, $said, $spy);
    t('"' . $said . '" keeps the address', $s['patient']['email'] ?? null, 'monika@gmail.com');
    t('  ...and moves on', $s['step'], 'gender');
}

echo "\nBOOKING: A REFUSAL DOES NOT BOOK\n";
$booking = array_merge($call('freshState'), [
    'step' => 'confirm',
    'patient_id' => 8,
    'token' => str_repeat('t', 40),
    'patient' => ['first_name' => 'Monika'],
    'doctor' => ['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff'],
    'appointment_type' => ['id' => 5, 'name' => 'Baby-TV'],
    'slot_date' => '13.08.2026',
    // The shape buildSlots() produces, so the correction path this step runs
    // on a refusal has the same data it would have in a real conversation.
    'slot' => ['slot_key' => '13.08.2026|06:10|91', 'slot_date' => '13.08.2026',
        'weekday' => 'Thursday', 'time' => '06:10', 'time_slot_id' => 91],
    'slots' => [
        ['slot_key' => '13.08.2026|06:10|91', 'slot_date' => '13.08.2026',
            'weekday' => 'Thursday', 'time' => '06:10', 'time_slot_id' => 91],
        ['slot_key' => '13.08.2026|06:20|92', 'slot_date' => '13.08.2026',
            'weekday' => 'Thursday', 'time' => '06:20', 'time_slot_id' => 92],
    ],
]);
foreach ($NEGATIVE as $said) {
    $spy = new SpyClient();
    [$s, ] = $answer($booking, $said, $spy);
    t('"' . $said . '" never reaches the booking API', $spy->called('bookAppointment'), false);
    t('  ...and no appointment is held', empty($s['appointment']), true);
}

echo "\nBOOKING: AGREEING STILL BOOKS\n";
foreach ($POSITIVE as $said) {
    $spy = new SpyClient();
    [$s, ] = $answer($booking, $said, $spy);
    t('"' . $said . '" books', $spy->called('bookAppointment'), true);
}

echo "\nCANCELLATION: A REFUSAL DOES NOT CANCEL\n";
$cancelling = array_merge($call('freshState'), [
    'step' => 'cancel_confirm',
    'patient_id' => 8,
    'token' => str_repeat('t', 40),
    'cancel_target' => ['id' => 555, 'start_date' => '2026-08-13 06:10:00',
        'doctor_name' => 'Gunnar Gauff', 'appointment_type_name' => 'Baby-TV'],
    'cancellable' => [['id' => 555, 'start_date' => '2026-08-13 06:10:00',
        'doctor_name' => 'Gunnar Gauff', 'appointment_type_name' => 'Baby-TV']],
]);
foreach ($NEGATIVE as $said) {
    $spy = new SpyClient();
    [$s, $out] = $answer($cancelling, $said, $spy);
    t('"' . $said . '" never reaches the cancel API', $spy->called('cancelAppointment'), false);
    t('  ...and says it kept the appointment',
        str_contains($out, 'Nothing has been cancelled'), true);
}

echo "\nCANCELLATION: AGREEING STILL CANCELS\n";
foreach ($POSITIVE as $said) {
    $spy = new SpyClient();
    [$s, ] = $answer($cancelling, $said, $spy);
    t('"' . $said . '" cancels', $spy->called('cancelAppointment'), true);
}

echo "\nNOTHING REACHED THE PRACTICE UNLESS IT WAS MEANT TO\n";
$spy = new SpyClient();
foreach ($NEGATIVE as $said) {
    $answer($booking, $said, $spy);
    $answer($cancelling, $said, $spy);
    $answer($base, $said, $spy);
}
t('no booking across every refusal', $spy->called('bookAppointment'), false);
t('no cancellation across every refusal', $spy->called('cancelAppointment'), false);
t('no registration across every refusal', $spy->called('registerPatient'), false);

printf("\n  %d passed, %d failed\n", $ok, $bad);
