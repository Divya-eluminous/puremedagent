<?php

/**
 * Booking again the appointment that was just cancelled.
 *
 * Run with:  php tests/ai-assistant/rebook_test.php
 *
 * The point of this feature is that the patient does not repeat four answers
 * the assistant already has. The point of these tests is that nothing is taken
 * on trust: the doctor, the appointment type and the time are each looked up
 * again, and the patient still confirms before anything is booked.
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

echo "RECOGNISING THE REQUEST\n";
foreach (['book the same appointment again', 'can you book the same one?',
    'book it again at the same time', 'I want the same appointment again',
    'rebook the cancelled appointment', 'can you book the same appointment with the same date and time?',
    'same date and time please'] as $p) {
    t('"' . $p . '"', $call('wantsSameAgain', $p), true);
}
foreach (['book an appointment', 'cancel my appointment', 'show me my appointments',
    'no thank you', 'I want to see Dr Gunnar'] as $p) {
    t('"' . $p . '" is not a rebooking', $call('wantsSameAgain', $p), false);
}

/** A stub practice: two doctors, two types, and a day of slots. */
function makeClient(array $options = []) {
    return new class($options) extends App\Services\AiAssistant\PureMedApiClient {
        public array $booked = [];
        public function __construct(private array $o) {}
        public function getDoctors(string $token, array $payload = []): array {
            $doctors = [['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar', 'doctor_speciality' => 'doctor']];
            if (empty($this->o['doctorGone'])) { array_unshift($doctors, ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar', 'doctor_speciality' => 'doctor']); }
            if (!empty($this->o['doctorGone'])) { $doctors = [['id' => 9, 'first_name' => 'other', 'last_name' => 'doctor', 'doctor_speciality' => '']]; }
            return ['ok' => true, 'message' => '', 'data' => $doctors, 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        public function getAppointmentTypes(string $token, array $payload = []): array {
            $types = empty($this->o['typeGone'])
                ? [['id' => 25, 'name' => 'Baby-TV', 'duration' => 10], ['id' => 26, 'name' => 'Vorsorge', 'duration' => 10]]
                : [['id' => 26, 'name' => 'Vorsorge', 'duration' => 10]];
            return ['ok' => true, 'message' => '', 'data' => $types, 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        public function getDoctorSlots(string $token, array $payload = []): array {
            if (!empty($this->o['noSlots'])) {
                return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
            }
            $times = ['15:00', '15:10', '15:20', '15:30'];
            $ids = [1500, 1510, 1520, 1530];
            if (!empty($this->o['slotTaken'])) {   // 15:20 has gone
                $times = ['15:00', '15:10', '15:30']; $ids = [1500, 1510, 1530];
            }
            $data = [['slot_date' => '11.08.2026', 'weekday' => 'Di', 'time_slots' => $times, 'time_slots_id' => $ids]];
            if (!empty($this->o['dayGone'])) {     // that day has gone entirely
                $data = [['slot_date' => '19.08.2026', 'weekday' => 'Mi',
                    'time_slots' => ['09:00', '09:10'], 'time_slots_id' => [900, 910]]];
            }
            return ['ok' => true, 'message' => '', 'data' => $data, 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        public function bookAppointment(string $token, array $payload): array {
            $this->booked[] = $payload;
            return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
                'data' => [['id' => 999, 'patient_name' => 'Divya', 'doctor_name' => 'albert munnar',
                    'appointment_type_name' => 'Baby-TV']]];
        }
    };
}

/** State immediately after cancelling Baby-TV with Dr Albert on 11.08.2026 15:20. */
$justCancelled = array_merge($call('freshState'), [
    'step' => 'cancelled', 'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'last_cancelled' => [
        'doctor_id' => 3, 'doctor_name' => 'albert munnar',
        'appointment_type_id' => 25, 'appointment_type_name' => 'Baby-TV',
        'date' => '11.08.2026', 'time' => '15:20',
    ],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "\nTHE SLOT IS STILL FREE - OFFERED, NOT BOOKED\n";
$client = makeClient();
[$s, $m] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('the doctor is restored', $s['doctor']['id'] ?? null, 3);
t('the appointment type is restored', $s['appointment_type']['id'] ?? null, 25);
t('the day is restored', $s['slot_date'], '11.08.2026');
t('the time is restored', $s['slot']['time'] ?? null, '15:20');
t('it goes to the confirmation', $s['step'], 'confirm');
t('NOTHING has been booked yet', count($client->booked), 0);

$prompt = $call('promptFor', $s);
t('the card reads back the whole appointment',
    str_contains($prompt['text'], 'Baby-TV') && str_contains($prompt['text'], 'Dr Albert Munnar')
    && str_contains($prompt['text'], '11.08.2026') && str_contains($prompt['text'], '15:20'), true);

echo "\nONLY THE PATIENT'S YES BOOKS IT\n";
[$booked, $m] = $answer($s, 'yes book it', '', $client);
t('booked after confirming', $booked['step'], 'done');
t('exactly one booking', count($client->booked), 1);
t('with the original doctor', $client->booked[0]['doctor_id'] ?? null, 3);
t('the original appointment type', $client->booked[0]['appointment_type_id'] ?? null, 25);
t('the original day', $client->booked[0]['appointment_date'] ?? null, '11.08.2026');
t('the original time', $client->booked[0]['time_frame'] ?? null, '15:20');

echo "\nTHE SLOT HAS BEEN TAKEN SINCE\n";
$client = makeClient(['slotTaken' => true]);
[$s, $m] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('nothing is booked', count($client->booked), 0);
t('it does not pretend to confirm', $s['step'] === 'confirm', false);
t('it says the time has gone', str_contains($m, 'has been taken since'), true);
t('it offers that same day', $s['slot_date'], '11.08.2026');
t('and shows the times left', $s['step'], 'slot_time');
t('no time is chosen for them', $s['slot'], null);
// The patient picks one of the alternatives and books that instead.
[$s, ] = $answer($s, '15:30', '', $client);
t('an alternative can be chosen', $s['slot']['time'] ?? null, '15:30');
[$s, ] = $answer($s, 'yes book it', '', $client);
t('and booked', $client->booked[0]['time_frame'] ?? null, '15:30');

echo "\nTHE WHOLE DAY HAS GONE\n";
$client = makeClient(['dayGone' => true]);
[$s, $m] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('nothing is booked', count($client->booked), 0);
t('it says so', str_contains($m, 'no longer have anything free on 11.08.2026'), true);
t('and asks which day instead', $s['step'], 'slot_date');
t('no day is chosen for them', $s['slot_date'], null);

echo "\nTHE APPOINTMENT TYPE IS NO LONGER OFFERED\n";
$client = makeClient(['typeGone' => true]);
[$s, $m] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('nothing is booked', count($client->booked), 0);
t('it says the type has gone', str_contains($m, 'is not offered any more'), true);
t('and asks what it is for', $s['step'], 'appointment_type');
t('the doctor is still kept', $s['doctor']['id'] ?? null, 3);

echo "\nTHE DOCTOR IS NO LONGER AVAILABLE\n";
$client = makeClient(['doctorGone' => true]);
[$s, $m] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('nothing is booked', count($client->booked), 0);
t('it says the doctor has gone', str_contains($m, 'not available for booking any more'), true);
t('and asks for a doctor', $s['step'], 'doctor');

echo "\nWITH NOTHING REMEMBERED IT DOES NOT PRETEND\n";
$client = makeClient();
$noContext = array_merge($justCancelled, ['last_cancelled' => null]);
[$s, $m] = $answer($noContext, 'book the same appointment again', '', $client);
t('nothing is booked', count($client->booked), 0);
t('it does not jump to a confirmation', $s['step'] === 'confirm', false);

echo "\nTHE REQUEST IS ANSWERED BY THE RULES\n";
$client = makeClient();
[$s, ] = $answer($justCancelled, 'book the same appointment again', '', $client);
t('the turn is marked handled', $s['_handled'] ?? false, true);
t('and the remembered appointment is spent', $s['last_cancelled'], null);

printf("\n  %d passed, %d failed\n", $ok, $bad);
