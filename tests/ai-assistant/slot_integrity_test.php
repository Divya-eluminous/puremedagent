<?php

/**
 * The booked slot is always the patient's latest explicit choice.
 *
 * Run with:  php tests/ai-assistant/slot_integrity_test.php
 *
 * The API client is stubbed so the exact payload sent to PureMed can be
 * inspected. The stub returns the SAME availability the conversation was built
 * from, so nothing about slot resolution is bypassed - only the network is.
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
    printf("  %-58s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

/** Availability in the raw PureMed shape, normalised by the controller itself. */
$raw = [];
foreach (['13.08.2026', '18.08.2026'] as $d) {
    $times = []; $ids = [];
    foreach (['09:00', '09:30', '14:00', '14:30', '15:00'] as $time) {
        $times[] = $time; $ids[] = (int) str_replace(':', '', $time);
    }
    $raw[] = ['slot_date' => $d, 'weekday' => 'Do', 'time_slots' => $times, 'time_slots_id' => $ids];
}
$slots = $call('normalizeSlots', $raw);

/** Records every call, and can be told to drop a slot from availability. */
$client = new class($raw) extends App\Services\AiAssistant\PureMedApiClient {
    public array $booked = [];
    public array $dropped = [];
    public int $slotFetches = 0;
    public function __construct(private array $fixture) {}
    public function getDoctorSlots(?string $token = null, array $payload = []): array {
        $this->slotFetches++;
        $data = [];
        foreach ($this->fixture as $group) {
            $times = []; $ids = [];
            foreach ($group['time_slots'] as $i => $time) {
                if (in_array($group['slot_date'] . ' ' . $time, $this->dropped, true)) { continue; }
                $times[] = $time; $ids[] = $group['time_slots_id'][$i];
            }
            $data[] = ['slot_date' => $group['slot_date'], 'weekday' => $group['weekday'],
                'time_slots' => $times, 'time_slots_id' => $ids];
        }
        return ['ok' => true, 'message' => '', 'data' => $data, 'errors' => [], 'http_status' => 200, 'body' => []];
    }
    public function bookAppointment(string $token, array $payload): array {
        $this->booked[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 900, 'patient_name' => 'Divya Lokhande', 'doctor_name' => 'albert munnar',
                'appointment_type_name' => 'Baby-TV']]];
    }
};

$base = array_merge($call('freshState'), [
    'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar']],
    'doctor' => ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar'],
    'appointment_types' => [['id' => 25, 'name' => 'Baby-TV']],
    'appointment_type' => ['id' => 25, 'name' => 'Baby-TV'],
    'slots' => $slots, 'slot_date' => '13.08.2026',
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "REJECT THE PROPOSED TIME -> SHOW SLOTS -> PICK ANOTHER -> CONFIRM -> BOOK\n";
// 1. A time is chosen and confirmed on screen.
[$s, ] = $answer(array_merge($base, ['step' => 'slot_time']), '09:00', '', $client);
t('1. first choice is 09:00', $s['slot']['time'] ?? null, '09:00');
t('   and it reaches the confirmation', $s['step'], 'confirm');

// 2. The patient rejects it and asks for the times again.
[$s, $m] = $answer($s, 'no I want another time', '', $client);
t('2. the old time is cleared', $s['slot'], null);
t('   the day is kept', $s['slot_date'], '13.08.2026');
t('   the times are shown again', $s['step'], 'slot_time');

// 3. They pick a different one.
[$s, ] = $answer($s, '14:30', '', $client);
t('3. the new choice is 14:30', $s['slot']['time'] ?? null, '14:30');
t('   back at the confirmation', $s['step'], 'confirm');

// 4. They confirm.
[$s, $m] = $answer($s, 'yes book it', '', $client);
t('4. the booking went through', $s['step'], 'done');
t('   exactly one booking was sent', count($client->booked), 1);

$sent = $client->booked[0] ?? [];
t('   PureMed was sent 14:30', $sent['time_frame'] ?? null, '14:30');
t('   and NOT the rejected 09:00', ($sent['time_frame'] ?? null) === '09:00', false);
t('   with the right day', $sent['appointment_date'] ?? null, '13.08.2026');
t('   the right doctor', $sent['doctor_id'] ?? null, 3);
t('   the right appointment type', $sent['appointment_type_id'] ?? null, 25);
t('   and the slot id of 14:30', $sent['time_slot_id'] ?? null, 1430);
t('   the confirmation card shows 14:30', $s['appointment']['time'] ?? null, '14:30');

echo "\nCHANGING THE DAY TOO - the old day must not survive either\n";
$client->booked = [];
[$s, ] = $answer(array_merge($base, ['step' => 'slot_time']), '09:00', '', $client);
[$s, ] = $answer($s, 'can we do 18 August instead', '', $client);
t('the day changed', $s['slot_date'], '18.08.2026');
t('the old time was dropped', $s['slot'], null);
[$s, ] = $answer($s, '15:00', '', $client);
[$s, ] = $answer($s, 'yes book it', '', $client);
$sent = $client->booked[0] ?? [];
t('booked on the new day', $sent['appointment_date'] ?? null, '18.08.2026');
t('booked at the new time', $sent['time_frame'] ?? null, '15:00');

echo "\nTHE FINAL CHECK REFUSES ANYTHING THAT IS NOT CURRENT\n";
$client->booked = [];
// The slot is taken by someone else between the choice and the "yes".
[$s, ] = $answer(array_merge($base, ['step' => 'slot_time']), '14:00', '', $client);
$client->dropped = ['13.08.2026 14:00'];
[$s, $m] = $answer($s, 'yes book it', '', $client);
t('nothing was booked', count($client->booked), 0);
t('it says the time was taken', str_contains($m, 'taken while we were talking'), true);
t('the selection was cleared', $s['slot'], null);
t('and it is not left on "done"', $s['step'] === 'done', false);
$client->dropped = [];

// A slot that disagrees with the chosen day cannot be booked.
$client->booked = [];
$stale = array_merge($base, ['step' => 'confirm', 'slot_date' => '18.08.2026',
    'slot' => collect($slots)->firstWhere('slot_key', '13.08.2026|09:00|900')]);
[$s, $m] = $answer($stale, 'yes book it', '', $client);
t('a slot from another day is refused', count($client->booked), 0);
t('and the day is asked for again', $s['step'], 'slot_date');

// A slot that PureMed never offered - an invented one - cannot be booked.
$client->booked = [];
$invented = array_merge($base, ['step' => 'confirm',
    'slot' => ['slot_date' => '13.08.2026', 'time' => '23:45', 'time_slot_id' => 2345,
        'slot_key' => '13.08.2026|23:45|2345', 'weekday' => 'Do']]);
[$s, $m] = $answer($invented, 'yes book it', '', $client);
t('an invented slot is refused', count($client->booked), 0);
t('nothing was confirmed', $s['step'] === 'done', false);

// Half a selection is refused rather than sent.
$client->booked = [];
$partial = array_merge($base, ['step' => 'confirm', 'slot' => ['slot_date' => '13.08.2026', 'time' => '09:00']]);
[$s, $m] = $answer($partial, 'yes book it', '', $client);
t('an incomplete slot is refused', count($client->booked), 0);

echo "\nAVAILABILITY IS RE-READ FROM PUREMED AT THE MOMENT OF BOOKING\n";
$client->booked = []; $client->slotFetches = 0;
[$s, ] = $answer(array_merge($base, ['step' => 'slot_time']), '14:00', '', $client);
$afterPick = $client->slotFetches;
[$s, ] = $answer($s, 'yes book it', '', $client);
t('picking re-checks availability', $afterPick >= 1, true);
t('confirming re-checks it again', $client->slotFetches > $afterPick, true);
t('and the booking used the fresh row', $client->booked[0]['time_slot_id'] ?? null, 1400);

echo "\nTHE LANGUAGE MODEL IS NOT CONSULTED ANYWHERE IN THIS\n";
t('slot_time resolves without it', $call('nluEligible', 'slot_time'), true);   // eligible, but...
$before = $client->booked;
[$s, ] = $answer(array_merge($base, ['step' => 'slot_time']), '14:00', '', $client);
t('...a plain time is matched by the rules', $s['slot']['time'] ?? null, '14:00');
t('and every booked slot came from the PureMed list',
    collect($client->booked)->every(fn ($b) => in_array($b['time_frame'], ['09:00', '09:30', '14:00', '14:30', '15:00'], true)), true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
