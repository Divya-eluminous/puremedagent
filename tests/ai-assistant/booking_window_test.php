<?php

/**
 * The practice's own booking window, not one the assistant invents.
 *
 * Run with:  php tests/ai-assistant/booking_window_test.php
 *
 * get-from-date is where OptimalAppointmentController applies the quarter
 * rules, the booking timeframe and the appointment type's optimal_appointment
 * flag. The assistant asks for that window and passes it to get-doctor-slots,
 * so it offers the same dates and hours as the main app - previously it asked
 * for today..+30 days at 00:00-23:59 and offered times no practice would give.
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
$callRef = function ($m, &$state, ...$a) use ($c) {
    $r = new ReflectionMethod($c, $m);
    $r->setAccessible(true);
    return $r->invokeArgs($c, array_merge([&$state], $a));
};
$ok = 0; $bad = 0;
function t($l, $g, $e) {
    global $ok, $bad;
    $p = ($g === $e); $p ? $ok++ : $bad++;
    printf("  %-62s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

/**
 * A practice that answers get-from-date with a two-week window and clinic
 * hours, and returns slots only inside whatever window it is asked for.
 */
function makePractice(array $options = []) {
    return new class($options) extends App\Services\AiAssistant\PureMedApiClient {
        public array $fromDateCalls = [];
        public array $slotCalls = [];
        public function __construct(private array $o) {}
        public function getFromDate(string $token, array $payload = []): array {
            $this->fromDateCalls[] = $payload;
            if (!empty($this->o['fromDateFails'])) {
                return ['ok' => false, 'message' => 'nope', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
            }
            if (!empty($this->o['fromDateEmpty'])) {
                return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
            }
            return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
                'data' => ['count' => 1, 'start_date' => '13.08.2026', 'end_date' => '27.08.2026',
                    'description' => 'week', 'setting_value' => 2, 'no_of_days' => 14,
                    'from_time' => '06:00', 'to_time' => '21:00']];
        }
        public function getDoctorSlots(string $token, array $payload = []): array {
            $this->slotCalls[] = $payload;
            // The practice only ever offers what was asked for: days inside the
            // window, times inside the hours.
            $from = (int) str_replace(':', '', $payload['from_time'] ?? '0000');
            $to = (int) str_replace(':', '', $payload['to_time'] ?? '2359');
            $start = DateTime::createFromFormat('d.m.Y', $payload['start_date'] ?? '01.01.2000');
            $end = DateTime::createFromFormat('d.m.Y', $payload['end_date'] ?? '01.01.2100');
            $data = [];
            foreach (['13.08.2026', '18.08.2026', '25.08.2026', '05.09.2026'] as $day) {
                $date = DateTime::createFromFormat('d.m.Y', $day);
                if ($date < $start || $date > $end) { continue; }
                $times = []; $ids = [];
                foreach (['04:00', '05:40', '06:00', '14:00', '21:00', '22:50'] as $time) {
                    $clock = (int) str_replace(':', '', $time);
                    if ($clock < $from || $clock > $to) { continue; }
                    $times[] = $time; $ids[] = $clock;
                }
                if ($times) { $data[] = ['slot_date' => $day, 'weekday' => 'Do', 'time_slots' => $times, 'time_slots_id' => $ids]; }
            }
            return ['ok' => true, 'message' => '', 'data' => $data, 'errors' => [], 'http_status' => 200, 'body' => []];
        }
    };
}

$base = array_merge($call('freshState'), [
    'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctor' => ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar'],
    'appointment_type' => ['id' => 15, 'name' => 'Baby-TV'],
]);

echo "THE PRACTICE'S WINDOW IS ASKED FOR AND USED\n";
$practice = makePractice();
$state = $base;
$slots = $callRef('fetchSlots', $state, $practice);
t('get-from-date was called', count($practice->fromDateCalls), 1);
t('  ...with the patient', $practice->fromDateCalls[0]['patient_id'] ?? null, 8);
t('  ...the doctor', $practice->fromDateCalls[0]['doctor_id'] ?? null, 3);
t('  ...and the appointment type', $practice->fromDateCalls[0]['appointment_type_id'] ?? null, 15);

$sent = $practice->slotCalls[0] ?? [];
t('get-doctor-slots used its start_date', $sent['start_date'] ?? null, '13.08.2026');
t('  ...its end_date', $sent['end_date'] ?? null, '27.08.2026');
t('  ...its from_time', $sent['from_time'] ?? null, '06:00');
t('  ...its to_time', $sent['to_time'] ?? null, '21:00');
t('  ...and NOT 00:00', ($sent['from_time'] ?? null) === '00:00', false);
t('  ...and NOT 23:59', ($sent['to_time'] ?? null) === '23:59', false);

echo "\nNO SLOT OUTSIDE 06:00-21:00 IS EVER OFFERED\n";
$times = array_column($slots, 'time');
sort($times);
t('the earliest offered', $times[0] ?? null, '06:00');
t('the latest offered', end($times) ?: null, '21:00');
foreach (['04:00', '05:40', '22:50'] as $outOfHours) {
    t('"' . $outOfHours . '" is not offered', in_array($outOfHours, $times, true), false);
}
t('every time is within clinic hours',
    collect($slots)->every(fn ($s) => $s['time'] >= '06:00' && $s['time'] <= '21:00'), true);

echo "\nNO DAY OUTSIDE THE CONFIGURED BOOKING WINDOW\n";
$days = array_values(array_unique(array_column($slots, 'slot_date')));
t('days offered', $days, ['13.08.2026', '18.08.2026', '25.08.2026']);
t('a day past the window is not offered', in_array('05.09.2026', $days, true), false);

echo "\nTHE WINDOW IS READ ONCE PER DOCTOR AND TYPE\n";
$practice = makePractice();
$state = $base;
$callRef('fetchSlots', $state, $practice);   // choosing a time
$callRef('fetchSlots', $state, $practice);   // holding it
$callRef('fetchSlots', $state, $practice);   // the check before booking
t('three slot fetches', count($practice->slotCalls), 3);
t('but only one get-from-date', count($practice->fromDateCalls), 1);
t('the window is remembered', $state['booking_window']['start_date'] ?? null, '13.08.2026');

echo "\nCHANGING THE DOCTOR OR THE TYPE RE-READS IT\n";
$state['appointment_type'] = ['id' => 19, 'name' => 'Vorsorge'];
$callRef('fetchSlots', $state, $practice);
t('a different type re-reads', count($practice->fromDateCalls), 2);
$state['doctor'] = ['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'];
$callRef('fetchSlots', $state, $practice);
t('a different doctor re-reads', count($practice->fromDateCalls), 3);

echo "\nWHEN THE PRACTICE'S RULES REFUSE, NOTHING IS OFFERED\n";
// get-from-date answers HTTP 200 with status=false - "Kein Eintrag gefunden" -
// which is how the quarter rule says this patient may not book this now. It is
// NOT a failure to fall back from: falling back offered dates the practice
// refuses, which is exactly what happened before this was separated out.
$refusing = new class extends App\Services\AiAssistant\PureMedApiClient {
    public array $slotCalls = [];
    public function __construct() {}
    public function getFromDate(string $token, array $payload = []): array {
        // Exactly what the live API returns in this case: no errors, and a
        // data key that is empty.
        return ['ok' => false, 'message' => 'Kein Eintrag gefunden.', 'data' => [], 'errors' => [],
            'http_status' => 200, 'body' => ['message' => 'Kein Eintrag gefunden.', 'status' => false, 'data' => [], 'errors' => []]];
    }
    public function getDoctorSlots(string $token, array $payload = []): array {
        $this->slotCalls[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['slot_date' => '13.08.2026', 'weekday' => 'Do',
                'time_slots' => ['14:00'], 'time_slots_id' => [1400]]]];
    }
};
$state = $base;
$slots = $callRef('fetchSlots', $state, $refusing);
t('no slots are offered', $slots, []);
t('get-doctor-slots is not even asked', count($refusing->slotCalls), 0);
t('the refusal is remembered', $state['booking_window']['none'] ?? null, true);

// And what the patient is told.
$refusing2 = new class extends App\Services\AiAssistant\PureMedApiClient {
    public function __construct() {}
    public function getFromDate(string $token, array $payload = []): array {
        return ['ok' => false, 'message' => 'Kein Eintrag gefunden.', 'data' => [], 'errors' => [],
            'http_status' => 200, 'body' => ['message' => 'Kein Eintrag gefunden.', 'status' => false, 'data' => [], 'errors' => []]];
    }
};
$loadSlots = Closure::bind(function (array $s, $client) {
    $msgs = $this->loadSlots($s, $client);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);
[$s, $m] = $loadSlots($base, $refusing2);
t('it says the practice is not offering it', str_contains($m, "isn't offering that appointment"), true);
t('  ...names the doctor', str_contains($m, 'Dr Albert Munnar'), true);
t('  ...suggests what to do', str_contains($m, 'different appointment type'), true);
t('  ...does not claim a full diary', str_contains($m, 'next 30 days'), false);
t('  ...and does not invent a reason', str_contains(mb_strtolower($m), 'quarter'), false);

echo "\nIF THE PRACTICE CANNOT SAY, AVAILABILITY IS STILL SHOWN\n";
foreach ([['the call fails', ['fromDateFails' => true]], ['it returns nothing', ['fromDateEmpty' => true]]] as [$label, $opts]) {
    $practice = makePractice($opts);
    $state = $base;
    $slots = $callRef('fetchSlots', $state, $practice);
    $sent = $practice->slotCalls[0] ?? [];
    t($label . ' -> slots are still offered', count($slots) > 0, true);
    t('  ...falling back to the configured window', $sent['start_date'] ?? null,
        Carbon\Carbon::today()->format('d.m.Y'));
    t('  ...and still within clinic hours', $sent['from_time'] ?? null, '06:00');
    t('  ...never 00:00-23:59', ($sent['to_time'] ?? null) === '23:59', false);
    $times = array_column($slots, 'time');
    t('  ...no out-of-hours slot slips through', in_array('04:00', $times, true), false);
}

echo "\nELIGIBILITY IS RE-CHECKED AFTER BOOKING, CANCELLING, AND STARTING AGAIN\n";
/**
 * A practice that allows one appointment of an optimal type per patient per
 * quarter - the rule optimalAppointmentCheck() applies, with the doctor filter
 * disabled, so it is patient-wide. Booking makes the patient ineligible;
 * cancelling makes them eligible again.
 */
$quarterRule = new class extends App\Services\AiAssistant\PureMedApiClient {
    public bool $patientHasOne = false;
    public int $fromDateCalls = 0;
    public array $slotCalls = [];
    public function __construct() {}
    public function getFromDate(string $token, array $payload = []): array {
        $this->fromDateCalls++;
        if ($this->patientHasOne) {
            // Exactly what the live API returns: no errors, empty data key.
            return ['ok' => false, 'message' => 'Kein Eintrag gefunden.', 'data' => [], 'errors' => [],
                'http_status' => 200, 'body' => ['message' => 'Kein Eintrag gefunden.', 'status' => false, 'data' => [], 'errors' => []]];
        }
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => ['start_date' => '13.08.2026', 'end_date' => '27.08.2026',
                'from_time' => '06:00', 'to_time' => '21:00']];
    }
    public function getDoctorSlots(string $token, array $payload = []): array {
        $this->slotCalls[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['slot_date' => '13.08.2026', 'weekday' => 'Do',
                'time_slots' => ['06:10', '06:20'], 'time_slots_id' => [610, 620]]]];
    }
    public function bookAppointment(string $token, array $payload): array {
        $this->patientHasOne = true;   // the practice now holds one for them
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 65, 'patient_name' => 'Sia Patil', 'doctor_name' => 'gunnar gauff',
                'appointment_type_name' => 'Kontrolluntersuchung']]];
    }
    public function cancelAppointment(string $token, array $payload): array {
        $this->patientHasOne = false;  // and no longer does
        return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
    }
    public function getDoctors(string $token, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff', 'doctor_speciality' => ''],
                ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar', 'doctor_speciality' => '']]];
    }
    public function getAppointmentTypes(string $token, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 20, 'name' => 'Kontrolluntersuchung', 'duration' => 10]]];
    }
};

$sia = array_merge($call('freshState'), [
    'patient' => ['first_name' => 'Sia'], 'patient_id' => 17, 'token' => 'jwt', 'goal' => 'book',
    'doctors' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'],
        ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar']],
    'doctor' => ['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'],
    'appointment_type' => ['id' => 20, 'name' => 'Kontrolluntersuchung'],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);
$loadSlots2 = Closure::bind(function (array $s, $client) {
    $msgs = $this->loadSlots($s, $client);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

// 1. The first booking, while still eligible.
$state = $sia;
$slots = $callRef('fetchSlots', $state, $quarterRule);
t('1. eligible, so days are offered', count($slots) > 0, true);
t('   the window was cached', $state['booking_window']['start_date'] ?? null, '13.08.2026');
$firstCalls = $quarterRule->fromDateCalls;

$state['slot_date'] = '13.08.2026';
$state['slots'] = $slots;
$state['slot'] = $slots[0];
$state['step'] = 'confirm';
[$booked, $m] = $answer($state, 'yes book it', '', $quarterRule);
t('2. the appointment is booked', $booked['step'], 'done');
t('   the cached window is thrown away', $booked['booking_window'], null);

// 3. "book another appointment" - the screenshot's next step.
[$again, ] = $answer($booked, 'book another appointment', '', $quarterRule);
t('3. a new booking starts', $again['step'], 'doctor');
t('   still no cached window', $again['booking_window'], null);

// 4. Same doctor, same type - the case that used to reuse the stale window.
$before = $quarterRule->fromDateCalls;
$sameAgain = array_merge($again, [
    'doctor' => ['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'],
    'appointment_type' => ['id' => 20, 'name' => 'Kontrolluntersuchung'],
]);
[$s4, $m4] = $loadSlots2($sameAgain, $quarterRule);
t('4. get-from-date is asked again', $quarterRule->fromDateCalls > $before, true);
t('   no days are offered', count($s4['slots'] ?? []), 0);
t('   the practice refusal is recorded', $s4['booking_window']['none'] ?? null, true);
t('   and the patient is told', str_contains($m4, "isn't offering that appointment"), true);
t('   NOT the stale 13.08.2026', str_contains($m4, '13.08.2026'), false);

// 5. A different doctor, same type - the rule is patient-wide.
$otherDoctor = array_merge($again, [
    'doctor' => ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar'],
    'appointment_type' => ['id' => 20, 'name' => 'Kontrolluntersuchung'],
]);
[$s5, $m5] = $loadSlots2($otherDoctor, $quarterRule);
t('5. another doctor is refused too', count($s5['slots'] ?? []), 0);
t('   and named in the message', str_contains($m5, 'Dr Albert Munnar'), true);

// 6. Cancelling makes them eligible again, and the window is recalculated.
$toCancel = array_merge($sia, ['step' => 'cancel_confirm',
    'cancel_target' => ['id' => 65, 'start_date' => '2026-08-13 06:10:00',
        'doctor_name' => 'gunnar gauff', 'appointment_type_name' => 'Kontrolluntersuchung',
        'doctor_id' => 2, 'appointment_type_id' => 20],
    'booking_window' => ['key' => '2|20', 'none' => true, 'start_date' => '', 'end_date' => '',
        'from_time' => '', 'to_time' => '']]);
[$cancelled, ] = $answer($toCancel, '', 'yes', $quarterRule);
t('6. the appointment is cancelled', $cancelled['step'], 'cancelled');
t('   the refusal is not kept', $cancelled['booking_window'], null);

$before = $quarterRule->fromDateCalls;
$afterCancel = array_merge($cancelled, [
    'doctor' => ['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'],
    'appointment_type' => ['id' => 20, 'name' => 'Kontrolluntersuchung'],
]);
[$s6, ] = $loadSlots2($afterCancel, $quarterRule);
t('   get-from-date is asked again', $quarterRule->fromDateCalls > $before, true);
t('   and days are offered once more', count($s6['slots'] ?? []) > 0, true);

echo "\nAN EXPIRED TOKEN IS NOT MISTAKEN FOR A REFUSAL\n";
// The live API answers HTTP 200 for this too, but with errors and no data key.
// Treating it as "the practice isn't offering that" would be a lie to the
// patient about the practice.
$badToken = new class extends App\Services\AiAssistant\PureMedApiClient {
    public array $slotCalls = [];
    public function __construct() {}
    public function getFromDate(string $token, array $payload = []): array {
        return ['ok' => false, 'message' => 'Token is Invalid', 'data' => [],
            'errors' => ['authenticate' => 'Token is Invalid'], 'http_status' => 200,
            'body' => ['message' => 'Token is Invalid', 'status' => false, 'errors' => ['authenticate' => 'Token is Invalid']]];
    }
    public function getDoctorSlots(string $token, array $payload = []): array {
        $this->slotCalls[] = $payload;
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['slot_date' => '13.08.2026', 'weekday' => 'Do',
                'time_slots' => ['14:00'], 'time_slots_id' => [1400]]]];
    }
};
$state = $base;
$slots = $callRef('fetchSlots', $state, $badToken);
t('it is not treated as a refusal', $state['booking_window']['none'] ?? false, false);
t('  ...the fallback window is used', count($badToken->slotCalls), 1);
t('  ...within clinic hours all the same', $badToken->slotCalls[0]['from_time'] ?? null, '06:00');

echo "\nTHE WINDOW IS NOT ASKED FOR BEFORE IT CAN BE ANSWERED\n";
$practice = makePractice();
$partial = array_merge($base, ['appointment_type' => null]);
$window = $callRef('bookingWindow', $partial, $practice);
t('no call without an appointment type', count($practice->fromDateCalls), 0);
t('  ...and the fallback is used', $window['from_time'], '06:00');
$partial = array_merge($base, ['patient_id' => null]);
$practice = makePractice();
$callRef('bookingWindow', $partial, $practice);
t('no call without a patient', count($practice->fromDateCalls), 0);

printf("\n  %d passed, %d failed\n", $ok, $bad);
