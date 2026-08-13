<?php

/**
 * "Show me more / other options" - paging a list, not choosing from it.
 *
 * Run with:  php tests/ai-assistant/more_test.php
 *
 * Two traps here. A request for more used to fall through to the matcher,
 * which found a type actually named "swati app type" inside the words
 * "appointment type" and selected it. And "another DOCTOR" must never be
 * treated as paging - it means change the doctor already chosen.
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

echo "ASKING FOR MORE\n";
foreach (['show me more appointment type', 'show me more appointment types',
    'can I see more doctors', 'do you have more options', 'more appointment types please',
    'show more', 'what else do you have'] as $p) {
    t('"' . $p . '"', $call('wantsMore', $p), true);
}

echo "\nASKING FOR OTHERS, NOT JUST \"MORE\"\n";
foreach (['can you show me other appointment types', 'can you show me other sorts slots',
    'show me other options', 'any different types', 'other appointment types',
    'different options', 'have you got other kinds'] as $p) {
    t('"' . $p . '"', $call('wantsMore', $p), true);
}

echo "\nBUT \"ANOTHER DOCTOR\" MEANS CHANGE THE DOCTOR\n";
foreach (['can I see another doctor', 'different doctor', 'I want a different physician'] as $p) {
    t('"' . $p . '" is not paging', $call('wantsMore', $p), false);
    t('  ...it is a doctor change', $call('wantsAnotherDoctor', '', $p), true);
}
foreach (['can we pick another time', 'I want another time'] as $p) {
    t('"' . $p . '" is not paging either', $call('wantsMore', $p), false);
}

echo "\nCHOICES ARE NOT REQUESTS FOR MORE\n";
foreach (['swati app type', 'Baby-TV', 'the second one', 'I want a general checkup',
    'Dr Albert Munnar', 'Vorsorge'] as $p) {
    t('"' . $p . '" is a choice', $call('wantsMore', $p), false);
}

// A practice whose type list includes one that collides with the words used to
// ask for more - the case from the original screenshot.
$types = [['id' => 25, 'name' => 'Baby-TV'], ['id' => 26, 'name' => 'swati app type'],
    ['id' => 27, 'name' => 'Vorsorge'], ['id' => 28, 'name' => 'STD-Screening']];

$client = new class extends App\Services\AiAssistant\PureMedApiClient {
    public function __construct() {}
    public function getDoctorSlots(string $token, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['slot_date' => '18.08.2026', 'weekday' => 'Do',
                'time_slots' => ['09:00'], 'time_slots_id' => [900]]]];
    }
};

$base = array_merge($call('freshState'), [
    'step' => 'appointment_type', 'chip_page' => 0, 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar']],
    'doctor' => ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar'],
    'appointment_types' => $types,
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "\nAT THE APPOINTMENT TYPE QUESTION\n";
foreach (['show me more appointment type', 'can you show me other appointment types',
    'can you show me other sorts slots'] as $p) {
    [$s, $m] = $answer($base, $p, '', $client);
    t('"' . $p . '" picks no type', $s['appointment_type'], null);
    t('  ...stays on the question', $s['step'], 'appointment_type');
    t('  ...pages the list', $s['chip_page'], 1);
    t('  ...says something out loud', $m !== '', true);
    t('  ...never "swati app type it is"', str_contains($m, 'it is'), false);
}

echo "\nCHOOSING THAT SAME TYPE BY NAME STILL WORKS\n";
$selects = function ($text) use ($answer, $base, $client) {
    [$s, $m] = $answer($base, $text, '', $client);
    return ($s['appointment_type']['id'] ?? null) ? 'selected' : 'not selected: ' . $m;
};
t('"swati app type" is selectable', $selects('swati app type'), 'selected');
t('"Baby-TV" is selectable', $selects('Baby-TV'), 'selected');
t('"the second one" is selectable', $selects('the second one'), 'selected');

echo "\nTHE Show more CHIP IS UNCHANGED (silent)\n";
[$s, $m] = $answer($base, '', '__more__', $client);
t('the chip pages the list', $s['chip_page'], 1);
t('and stays silent', $m, '');

printf("\n  %d passed, %d failed\n", $ok, $bad);
