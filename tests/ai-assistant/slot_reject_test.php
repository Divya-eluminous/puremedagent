<?php

/**
 * Turning down the whole list of times.
 *
 * Run with:  php tests/ai-assistant/slot_reject_test.php
 *
 * The defect: at the time question, every refusal was treated as "show me the
 * others", so "I don't need anything from this" got the same list back - an
 * answer to a question the patient had not asked.
 *
 * The distinction is between one time and all of them. "not that one" still
 * re-shows the list; "any", "anything" and "none" mean the day itself does not
 * work, so the conversation goes back to the day question - the same place
 * "another day" already went. saidNo() and saidYes() are untouched.
 *
 * NOTHING HERE TOUCHES THE PRACTICE. The API is a spy that records calls and
 * returns canned data, so "the booking API was never called" is proved rather
 * than assumed.
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
class SlotSpyClient extends PureMedApiClient
{
    /** @var array<int, string> */
    public array $calls = [];

    public function __construct() {}

    public function called(string $method): bool { return in_array($method, $this->calls, true); }

    public function bookAppointment(string $token, array $payload): array
    {
        $this->calls[] = 'bookAppointment';
        return ['ok' => true, 'data' => [['id' => 777]], 'errors' => []];
    }
    public function getFromDate(string $token, array $payload = []): array
    {
        $this->calls[] = 'getFromDate';
        return ['ok' => true, 'data' => ['from_date' => '01.09.2026'], 'errors' => []];
    }
    public function getDoctorSlots(string $token, array $payload = []): array
    {
        $this->calls[] = 'getDoctorSlots';
        return ['ok' => true, 'errors' => [], 'data' => [[
            'slot_date' => '01.09.2026', 'weekday' => 'Tuesday',
            'time_slots' => ['06:10', '06:20', '17:00'],
            'time_slots_id' => [91, 92, 93],
        ]]];
    }
    public function getDoctors(string $token, array $payload = []): array
    {
        $this->calls[] = 'getDoctors';
        return ['ok' => true, 'data' => [['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff']], 'errors' => []];
    }
    public function getAppointmentTypes(string $token, array $payload = []): array
    {
        $this->calls[] = 'getAppointmentTypes';
        return ['ok' => true, 'data' => [['id' => 5, 'name' => 'Baby-TV']], 'errors' => []];
    }
    public function getAppointments(string $token, array $payload): array
    {
        $this->calls[] = 'getAppointments';
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

$SLOTS = [
    ['slot_key' => '01.09.2026|06:10|91', 'slot_date' => '01.09.2026',
        'weekday' => 'Tuesday', 'time' => '06:10', 'time_slot_id' => 91],
    ['slot_key' => '01.09.2026|06:20|92', 'slot_date' => '01.09.2026',
        'weekday' => 'Tuesday', 'time' => '06:20', 'time_slot_id' => 92],
    ['slot_key' => '01.09.2026|17:00|93', 'slot_date' => '01.09.2026',
        'weekday' => 'Tuesday', 'time' => '17:00', 'time_slot_id' => 93],
];
$atTimes = array_merge($call('freshState'), [
    'step' => 'slot_time',
    'patient_id' => 8,
    'token' => str_repeat('t', 40),
    'patient' => ['first_name' => 'Divya'],
    'doctor' => ['id' => 1, 'first_name' => 'Gunnar', 'last_name' => 'Gauff'],
    'appointment_type' => ['id' => 5, 'name' => 'Baby-TV'],
    'slot_date' => '01.09.2026',
    'slots' => $SLOTS,
]);

/** Answer one message at the time question, with the spy in place. */
$answer = Closure::bind(function (array $state, string $text, SlotSpyClient $client, string $choice = '') {
    $s = $state;
    $auth = new PatientAuthenticator($client);
    $msgs = $this->handleAnswer($text, $choice, 'voice', $s, $client, $auth);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }

    return [$s, implode(' | ', $out)];
}, $c, ChatController::class);

$REJECTS_ALL = [
    "I don't need anything from this",
    "I don't want any of these",
    'none of these',
    "I don't want anything from these",
    "I don't need any of them",
];

echo "THE REPORTED PHRASE, AND ITS FAMILY\n";
foreach ($REJECTS_ALL as $said) {
    $spy = new SlotSpyClient();
    [$s, $out] = $answer($atTimes, $said, $spy);
    t('"' . $said . '"', $s['step'], 'slot_date');
    t('  ...no time is held', $s['slot'], null);
    t('  ...the booking API is untouched', $spy->called('bookAppointment'), false);
    t('  ...the same list is not shown again', str_contains($out, '06:10'), false);
    t('  ...the doctor is kept', $s['doctor']['id'] ?? null, 1);
    t('  ...and the appointment type', $s['appointment_type']['id'] ?? null, 5);
    t('  ...it asks about the day', str_contains($out, 'Which day would suit you better?'), true);
}

echo "\nASKING FOR OTHER TIMES STILL SHOWS OTHER TIMES\n";
foreach (['show me another time', 'any other times', 'are there other slots'] as $said) {
    $spy = new SlotSpyClient();
    [$s, ] = $answer($atTimes, $said, $spy);
    t('"' . $said . '" stays on the times', $s['step'], 'slot_time');
    t('  ...and does not go to the days', $s['step'] === 'slot_date', false);
}

echo "\nA NAMED TIME IS STILL A CHOICE\n";
foreach (['06:10' => '06:10', '6:10' => '06:10', '06 10' => '06:10',
    '17:00' => '17:00', 'quarter past six' => null] as $said => $expected) {
    $spy = new SlotSpyClient();
    [$s, $out] = $answer($atTimes, (string) $said, $spy);
    if ($expected === null) {
        // Not in the list on this day - the point is only that it is read as a
        // time and never as a rejection.
        t('"' . $said . '" is not read as a rejection', $s['step'] === 'slot_date', false);
        continue;
    }
    t('"' . $said . '" selects ' . $expected, $s['slot']['time'] ?? null, $expected);
    t('  ...and asks to confirm', $s['step'], 'confirm');
    t('  ...without booking yet', $spy->called('bookAppointment'), false);
}

echo "\nTHE CORRECTION STILL CORRECTS\n";
$spy = new SlotSpyClient();
[$s, ] = $answer($atTimes, 'you have taken wrong time, I said 17:00', $spy);
t('it picks 17:00', $s['slot']['time'] ?? null, '17:00');
t('and does not go to the days', $s['step'] === 'slot_date', false);
t('and books nothing on its own', $spy->called('bookAppointment'), false);

echo "\n\"ANOTHER DAY\" IS UNCHANGED\n";
$spy = new SlotSpyClient();
[$s, $out] = $answer($atTimes, 'another day', $spy);
t('it goes to the days', $s['step'], 'slot_date');
t('with the wording it always had',
    str_contains($out, 'which of these days would you prefer?'), true);

echo "\nTHE SINGULAR PHRASES ARE LEFT ALONE ON PURPOSE\n";
// "this" may be one particular time, so re-showing the list is still right.
foreach (['not this', "I don't want this"] as $said) {
    t('"' . $said . '" is not a whole-list rejection', $call('rejectsAllSlots', $said), false);
    $spy = new SlotSpyClient();
    [$s, ] = $answer($atTimes, $said, $spy);
    t('  ...so it stays on the times', $s['step'], 'slot_time');
}

echo "\nCHIPS ARE UNTOUCHED\n";
// Every branch at this step is guarded by an empty choice value, so a tapped
// time cannot be read as a refusal however it is labelled.
// timeCards() puts the bare time on the chip, so that is what a tap posts.
$spy = new SlotSpyClient();
[$s, ] = $answer($atTimes, '', $spy, '06:10');
t('a tapped time is selected', $s['slot']['time'] ?? null, '06:10');
t('and asks to confirm', $s['step'], 'confirm');
t('and nothing is booked by the tap', $spy->called('bookAppointment'), false);

echo "\nTHE GLOBAL MATCHERS ARE NOT TOUCHED\n";
// The fix must not have leaked into the yes/no used by the booking, the
// cancellation and the email confirmation.
foreach ($REJECTS_ALL as $said) {
    t('"' . $said . '" is still not a global yes', $call('saidYes', '', $said), false);
}
t('"yes" still agrees', $call('saidYes', '', 'yes'), true);
t('"no" still refuses', $call('saidNo', '', 'no'), true);
t('"that\'s right" still agrees', $call('saidYes', '', "that's right"), true);
t('"it\'s not right" still refuses', $call('saidNo', '', "it's not right"), true);

echo "\nTHE NEW MATCHER IS SCOPED TO THE TIME QUESTION ALONE\n";
$source = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/AiAssistant/ChatController.php');
t('it is called exactly once', substr_count($source, '$this->rejectsAllSlots('), 1);

// It cannot move any other step, whatever is said to it.
foreach (['intent', 'doctor', 'appointment_type', 'slot_date', 'confirm',
    'cancel_select', 'cancel_confirm', 'appointments', 'email', 'gender'] as $step) {
    $elsewhere = array_merge($atTimes, ['step' => $step]);
    $spy = new SlotSpyClient();
    [$s, ] = $answer($elsewhere, "I don't want any of these", $spy);
    t('at "' . $step . '" nothing is rerouted to the days',
        $s['step'] === 'slot_date' && $step !== 'slot_date', false);
    t('  ...and nothing is booked', $spy->called('bookAppointment'), false);
}

printf("\n  %d passed, %d failed\n", $ok, $bad);
