<?php

/**
 * Changing your mind about booking at all, part-way through.
 *
 * Run with:  php tests/ai-assistant/abandon_test.php
 *
 * The distinction this file guards: "I don't want THIS TIME" is a change of
 * choice and belongs to the step it was said at; "I don't want to book" is a
 * change of mind about the whole thing and must stop, not re-ask.
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
    printf("  %-60s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

$raw = [['slot_date' => '18.08.2026', 'weekday' => 'Do',
    'time_slots' => ['14:00', '14:10', '14:20'], 'time_slots_id' => [1400, 1410, 1420]]];
$slots = $call('normalizeSlots', $raw);

$client = new class($raw) extends App\Services\AiAssistant\PureMedApiClient {
    public array $booked = [];
    public function __construct(private array $fixture) {}
    public function getDoctorSlots(?string $token = null, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'data' => $this->fixture, 'errors' => [], 'http_status' => 200, 'body' => []];
    }
    public function bookAppointment(string $token, array $payload): array {
        $this->booked[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [], 'data' => [[]]];
    }
};

$base = array_merge($call('freshState'), [
    'patient' => ['first_name' => 'Ashish'], 'patient_id' => 8, 'token' => 'jwt', 'goal' => 'book',
    'doctors' => [['id' => 3, 'first_name' => 'ashish', 'last_name' => 'test']],
    'doctor' => ['id' => 3, 'first_name' => 'ashish', 'last_name' => 'test'],
    'appointment_types' => [['id' => 25, 'name' => 'Testing'], ['id' => 26, 'name' => 'Vorsorge']],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s,
        $client ?: app(App\Services\AiAssistant\PureMedApiClient::class),
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

$phrases = ["I don't want to book my appointment", "I don't want to book", 'I do not want to book',
    'never mind', 'forget it', 'I changed my mind', "I don't want an appointment", 'no booking please'];

echo "THE SCREENSHOT: SAID AT THE APPOINTMENT TYPE QUESTION\n";
$atType = array_merge($base, ['step' => 'appointment_type']);
foreach ($phrases as $p) {
    [$s, $m] = $answer($atType, $p, '', $client);
    t('"' . $p . '" stops', str_contains($m, "I won't book anything"), true);
    t('"' . $p . '" does not re-ask for a type', str_contains($m, "didn't catch that one"), false);
    t('"' . $p . '" leaves the type unchosen', $s['appointment_type'], null);
}

echo "\nSAID AT ANY OTHER POINT IN THE BOOKING\n";
foreach ([
    ['doctor', array_merge($base, ['step' => 'doctor', 'doctor' => null])],
    ['slot_date', array_merge($base, ['step' => 'slot_date', 'slots' => $slots,
        'appointment_type' => ['id' => 25, 'name' => 'Testing']])],
    ['slot_time', array_merge($base, ['step' => 'slot_time', 'slots' => $slots, 'slot_date' => '18.08.2026',
        'appointment_type' => ['id' => 25, 'name' => 'Testing']])],
    ['confirm', array_merge($base, ['step' => 'confirm', 'slots' => $slots, 'slot_date' => '18.08.2026',
        'appointment_type' => ['id' => 25, 'name' => 'Testing'], 'slot' => $slots[0]])],
] as [$label, $state]) {
    [$s, $m] = $answer($state, "I don't want to book my appointment", '', $client);
    t('at ' . $label . ' it stops', str_contains($m, "I won't book anything"), true);
    t('at ' . $label . ' the doctor is cleared', $s['doctor'], null);
    t('at ' . $label . ' the time is cleared', $s['slot'], null);
    t('at ' . $label . ' the day is cleared', $s['slot_date'], null);
    t('at ' . $label . ' nothing was booked', count($client->booked), 0);
}

echo "\nWHO THEY ARE IS KEPT, SO THEY CAN DO SOMETHING ELSE\n";
[$s, $m] = $answer($atType, "I don't want to book my appointment", '', $client);
t('the patient is still known', $s['patient_id'], 8);
t('they are addressed by name', str_contains($m, 'Ashish'), true);
t('it lands somewhere with options', $s['step'], 'appointments');
[$s2, $m2] = $answer($s, 'show me my appointments', '', $client);
t('and viewing still works afterwards', str_contains($m2, 'appointment'), true);

echo "\nA CHANGE OF CHOICE IS NOT A CHANGE OF MIND\n";
$atTime = array_merge($base, ['step' => 'slot_time', 'slots' => $slots, 'slot_date' => '18.08.2026',
    'appointment_type' => ['id' => 25, 'name' => 'Testing'], 'slot' => $slots[0]]);
[$s, $m] = $answer($atTime, "I don't want this time", '', $client);
t('"I don\'t want this time" keeps booking', str_contains($m, "won't book anything"), false);
t('and keeps the day', $s['slot_date'], '18.08.2026');

$atDate = array_merge($base, ['step' => 'slot_date', 'slots' => $slots,
    'appointment_type' => ['id' => 25, 'name' => 'Testing']]);
[$s, $m] = $answer($atDate, "I don't want 18 August", '', $client);
t('"I don\'t want 18 August" keeps booking', str_contains($m, "won't book anything"), false);

[$s, $m] = $answer(array_merge($base, ['step' => 'confirm', 'slots' => $slots, 'slot_date' => '18.08.2026',
    'appointment_type' => ['id' => 25, 'name' => 'Testing'], 'slot' => $slots[0]]), 'no', '', $client);
t('a plain "no" at the confirmation still means another time',
    str_contains($m, "won't book anything"), false);

echo "\nCANCELLING AN EXISTING APPOINTMENT IS UNTOUCHED\n";
[$s, $m] = $answer($atType, 'cancel my appointment', '', $client);
t('"cancel my appointment" is not treated as abandoning',
    str_contains($m, "won't book anything"), false);

echo "\nAND IT NEVER REACHES THE MODEL\n";
[$s, ] = $answer($atType, "I don't want to book my appointment", '', $client);
t('the turn is marked handled', $s['_handled'] ?? false, true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
