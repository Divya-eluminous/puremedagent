<?php

/**
 * What a number means when the assistant has just asked "which time suits?".
 *
 * Run with:  php tests/ai-assistant/numeric_slot_test.php
 *
 * A bare number is read as an HOUR, because that is what people answer with.
 * A position has to be asked for in words ("the 11th one", "number 11").
 * When a number is neither a free hour nor unmistakably a position, the
 * assistant asks - it never counts down the list to an arbitrary slot.
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

// 14:00 to 17:00 in ten-minute steps - the shape from the screenshot, where
// the 11th time is 15:40 and 11:00 does not exist.
$raw = [];
$times = []; $ids = [];
for ($m = 14 * 60; $m <= 17 * 60; $m += 10) {
    $times[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60); $ids[] = $m;
}
$raw[] = ['slot_date' => '18.08.2026', 'weekday' => 'Do', 'time_slots' => $times, 'time_slots_id' => $ids];
$slots = $call('normalizeSlots', $raw);

$client = new class($raw) extends App\Services\AiAssistant\PureMedApiClient {
    public array $booked = [];
    public function __construct(private array $fixture) {}
    public function getDoctorSlots(?string $token = null, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'data' => $this->fixture, 'errors' => [], 'http_status' => 200, 'body' => []];
    }
    public function bookAppointment(string $token, array $payload): array {
        $this->booked[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 901, 'patient_name' => 'Divya', 'doctor_name' => 'albert munnar',
                'appointment_type_name' => 'Baby-TV']]];
    }
};

$base = array_merge($call('freshState'), [
    'step' => 'slot_time', 'slot_date' => '18.08.2026', 'slots' => $slots,
    'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar']],
    'doctor' => ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar'],
    'appointment_types' => [['id' => 25, 'name' => 'Baby-TV']],
    'appointment_type' => ['id' => 25, 'name' => 'Baby-TV'],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "THE SCREENSHOT: \"book for 11\" MUST NOT BECOME 15:40\n";
foreach (['can you book for 11', 'book for 11', '11', 'can we do 11'] as $p) {
    [$s, $m] = $answer($base, $p, '', $client);
    t('"' . $p . '" selects nothing', $s['slot'], null);
    t('"' . $p . '" is never 15:40', ($s['slot']['time'] ?? null) === '15:40', false);
    t('"' . $p . '" asks which was meant', str_contains($m, 'Did you mean the 11th time'), true);
    t('"' . $p . '" stays on the times', $s['step'], 'slot_time');
}

echo "\nA POSITION ASKED FOR IN WORDS IS RESOLVED AGAINST THE LIST\n";
foreach (['the 11th one', 'number 11', 'option 11'] as $p) {
    [$s, ] = $answer($base, $p, '', $client);
    t('"' . $p . '" -> the 11th time (15:40)', $s['slot']['time'] ?? null, '15:40');
    t('"' . $p . '" reaches the confirmation', $s['step'], 'confirm');
}
[$s, ] = $answer($base, 'the second one', '', $client);
t('"the second one" -> 14:10', $s['slot']['time'] ?? null, '14:10');

echo "\nAN HOUR THAT IS FREE IS TAKEN AS A TIME\n";
foreach ([['15', '15:00'], ['at 15', '15:00'], ['16', '16:00'], ['book at 16', '16:00'],
    ['15:40', '15:40'], ['16:40', '16:40'], ['4 pm', '16:00']] as [$p, $expect]) {
    [$s, ] = $answer($base, $p, '', $client);
    t('"' . $p . '" -> ' . $expect, $s['slot']['time'] ?? null, $expect);
}

echo "\nSAID AS A TIME BUT NOT FREE - said so, never counted\n";
foreach (['11 o clock', 'at 11 am'] as $p) {
    [$s, $m] = $answer($base, $p, '', $client);
    t('"' . $p . '" selects nothing', $s['slot'], null);
    t('"' . $p . '" is not offered as a position', str_contains($m, 'Did you mean'), false);
    t('"' . $p . '" says the hour is not free', str_contains($m, "don't have 11:00"), true);
}

echo "\nA NUMBER THAT IS NEITHER AN HOUR NOR A POSITION\n";
[$s, $m] = $answer($base, '40', '', $client);
t('"40" selects nothing', $s['slot'], null);
t('"40" is not offered as a position', str_contains($m, 'Did you mean'), false);

echo "\nREJECT -> ASK FOR SLOTS -> PICK ANOTHER -> BOOK ONLY THAT ONE\n";
$client->booked = [];
[$s, ] = $answer($base, '16:40', '', $client);
t('1. 16:40 chosen', $s['slot']['time'] ?? null, '16:40');
// A correction that names the hour is applied straight away - the patient
// said 17, so they get 17:00 rather than the whole list again.
[$s, $m] = $answer($s, 'you have taken wrong time I said 17', '', $client);
t('2. the correction is applied', $s['slot']['time'] ?? null, '17:00');
t('   16:40 is gone', ($s['slot']['time'] ?? null) === '16:40', false);
t('   the day is kept', $s['slot_date'], '18.08.2026');

// Then they change their mind again and ask to see the times.
[$s, $m] = $answer($s, 'no I want another time', '', $client);
t('   asking for the times clears the selection', $s['slot'], null);
t('   and shows them', $s['step'], 'slot_time');
[$s, $m] = $answer($s, 'can you book for 11', '', $client);
t('3. an ambiguous number still asks', str_contains($m, 'Did you mean the 11th time'), true);
t('   and 16:40 has NOT come back', $s['slot'], null);
[$s, ] = $answer($s, 'the 11th one', '', $client);
t('4. the named position is taken', $s['slot']['time'] ?? null, '15:40');
[$s, $m] = $answer($s, 'yes book it', '', $client);
t('5. booked', $s['step'], 'done');
t('   exactly one booking', count($client->booked), 1);
t('   PureMed got 15:40', $client->booked[0]['time_frame'] ?? null, '15:40');
t('   and never the rejected 16:40',
    collect($client->booked)->contains(fn ($b) => $b['time_frame'] === '16:40'), false);
t('   nor the intermediate 17:00',
    collect($client->booked)->contains(fn ($b) => $b['time_frame'] === '17:00'), false);

echo "\nTHE REJECTED TIME CANNOT RETURN BY ANY ROUTE\n";
$client->booked = [];
[$s, ] = $answer($base, '16:40', '', $client);
[$s, ] = $answer($s, 'no I want another time', '', $client);
t('cleared by "another time"', $s['slot'], null);
[$s, ] = $answer($s, 'show me the times', '', $client);
t('still cleared after asking for the list', $s['slot'], null);
[$s, ] = $answer($s, '17', '', $client);
t('a new explicit hour is taken', $s['slot']['time'] ?? null, '17:00');
[$s, ] = $answer($s, 'yes book it', '', $client);
t('booked the new time', $client->booked[0]['time_frame'] ?? null, '17:00');
t('never the rejected one',
    collect($client->booked)->contains(fn ($b) => $b['time_frame'] === '16:40'), false);

echo "\nTHE CLARIFYING QUESTION IS ANSWERED BY THE RULES, NOT THE MODEL\n";
[$s, ] = $answer($base, 'can you book for 11', '', $client);
t('the turn is marked as handled', $s['_handled'] ?? false, true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
