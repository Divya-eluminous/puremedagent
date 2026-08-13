<?php

/**
 * "Do you have any other doctors?" - a question about the list, not a choice.
 *
 * Run with:  php tests/ai-assistant/other_doctors_test.php
 *
 * The answer always comes from the practice's real list. No doctor is named
 * that PureMed did not return, and asking the question must not disturb the
 * appointment type, day or time already chosen.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['ai-assistant.nlu_driver' => 'none']);

$c = new App\Http\Controllers\AiAssistant\ChatController();
$call = function ($m, ...$a) use ($c) {
    $r = new ReflectionMethod($c, $m);
    $r->setAccessible(true);
    return $r->invoke($c, ...$a);
};
$ok = 0; $bad = 0;
function t($l, $g, $e) {
    global $ok, $bad;
    $p = ($g === $e); $p ? $ok++ : $bad++;
    printf("  %-62s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

$asking = ['Do you have any other doctors other than these two doctors?',
    'Do you have any other doctors?', 'Are there any other doctors?', 'Do you have someone else?',
    'Anyone else available?', 'Can I choose another doctor?', 'Are there more doctors?',
    'What other doctors do you have besides these two?', 'is there anybody else available'];

$choosing = ['Dr Gunnar Gauff', 'Gunnar', 'the second one', 'number 2', 'Dr Albert Munnar',
    'I want the earliest', 'I want to book an appointment'];

echo "TELLING A QUESTION FROM A CHOICE\n";
foreach ($asking as $p) { t('"' . mb_substr($p, 0, 52) . '"', $call('asksForOtherDoctors', $p), true); }
foreach ($choosing as $p) { t('"' . $p . '" is a choice', $call('asksForOtherDoctors', $p), false); }

/** A practice with a controllable number of doctors. */
function makeClient(int $count) {
    return new class($count) extends App\Services\AiAssistant\PureMedApiClient {
        public int $doctorCalls = 0;
        public function __construct(private int $count) {}
        public function getDoctors(string $token, array $payload = []): array {
            $this->doctorCalls++;
            $names = [[2, 'gunnar', 'gauff'], [3, 'albert', 'munnar'], [6, 'verena', 'eckmayr'],
                [7, 'patrik', 'horak'], [27, 'swati', 'datir'], [34, 'test', 'one'], [35, 'test', 'two']];
            $data = [];
            foreach (array_slice($names, 0, $this->count) as [$id, $first, $last]) {
                $data[] = ['id' => $id, 'first_name' => $first, 'last_name' => $last, 'doctor_speciality' => 'doctor'];
            }
            return ['ok' => true, 'message' => '', 'data' => $data, 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        // Choosing a doctor loads that doctor's appointment types next.
        public function getAppointmentTypes(string $token, array $payload = []): array {
            return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
                'data' => [['id' => 25, 'name' => 'Baby-TV', 'duration' => 10]]];
        }
    };
}

$two = makeClient(2);
$state = array_merge($call('freshState'), [
    'step' => 'doctor', 'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => $call('keepFields', $two->getDoctors('jwt')['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']),
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "\nWITH ONLY THESE TWO, IT SAYS SO - AND NAMES THEM\n";
foreach ($asking as $p) {
    [$s, $m] = $answer($state, $p, '', $two);
    t('"' . mb_substr($p, 0, 46) . '" answers plainly',
        str_contains($m, 'Currently, I only have Dr Gunnar Gauff and Dr Albert Munnar available'), true);
    t('  ...and invites a choice', str_contains($m, 'Would you like to choose one of them?'), true);
    t('  ...does not talk about times', str_contains($m, 'keep that in mind for the times'), false);
    t('  ...does not claim more exist', str_contains($m, 'here are a few more'), false);
    t('  ...picks nobody', $s['doctor'], null);
    t('  ...stays on the doctor question', $s['step'], 'doctor');
}

echo "\nTHE ANSWER COMES FROM PUREMED, NOT THE SESSION\n";
$two->doctorCalls = 0;
[$s, $m] = $answer($state, 'Do you have any other doctors?', '', $two);
t('the list is read again', $two->doctorCalls, 1);
// A stale session naming a doctor the practice no longer has must not be echoed.
$stale = array_merge($state, ['doctors' => array_merge($state['doctors'],
    [['id' => 99, 'first_name' => 'ghost', 'last_name' => 'doctor', 'doctor_speciality' => '']])]);
[$s, $m] = $answer($stale, 'Do you have any other doctors?', '', $two);
t('a doctor PureMed no longer lists is not named', str_contains($m, 'Ghost'), false);
t('only the real two are named',
    str_contains($m, 'Dr Gunnar Gauff and Dr Albert Munnar available'), true);

echo "\nWHEN THERE REALLY ARE MORE, THEY ARE SHOWN\n";
$many = makeClient(7);
$manyState = array_merge($state, [
    'doctors' => $call('keepFields', $many->getDoctors('jwt')['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']),
    'chip_page' => 0,
]);
[$s, $m] = $answer($manyState, 'Do you have any other doctors?', '', $many);
t('it says there are others', str_contains($m, 'here are the others I have'), true);
t('and reveals the next page', $s['chip_page'], 1);
t('without choosing for them', $s['doctor'], null);

echo "\nASKING DOES NOT DISTURB THE REST OF THE BOOKING\n";
$midBooking = array_merge($state, [
    'appointment_type' => ['id' => 25, 'name' => 'Baby-TV'],
    'appointment_types' => [['id' => 25, 'name' => 'Baby-TV']],
    'slot_date' => '18.08.2026', 'slot_window' => 'afternoon',
    'slots' => [['slot_date' => '18.08.2026', 'weekday' => 'Do', 'time' => '15:20',
        'time_slot_id' => 1520, 'slot_key' => 'k1']],
]);
[$s, $m] = $answer($midBooking, 'Do you have any other doctors?', '', $two);
t('the appointment type is untouched', $s['appointment_type']['id'] ?? null, 25);
t('the day is untouched', $s['slot_date'], '18.08.2026');
t('the time window is untouched', $s['slot_window'], 'afternoon');
t('the slot list is untouched', count($s['slots']), 1);

echo "\nCHOOSING A DOCTOR STILL WORKS EXACTLY AS BEFORE\n";
foreach ([['Dr Gunnar Gauff', 2], ['Dr Albert Munnar', 3], ['the second one', 3], ['Gunnar', 2]] as [$p, $id]) {
    [$s, ] = $answer($state, $p, '', $two);
    t('"' . $p . '" selects doctor ' . $id, $s['doctor']['id'] ?? null, $id);
}
[$s, ] = $answer($state, '', '2', $two);
t('the chip still selects', $s['doctor']['id'] ?? null, 2);

echo "\nAND IT NEVER REACHES THE MODEL\n";
[$s, ] = $answer($state, 'Do you have any other doctors?', '', $two);
t('the turn is marked handled', $s['_handled'] ?? false, true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
