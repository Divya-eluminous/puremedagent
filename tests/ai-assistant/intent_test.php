<?php

/**
 * Which flow a message enters: viewing, booking or cancelling.
 *
 * Run with:  php tests/ai-assistant/intent_test.php
 *
 * The trap this file guards: wantsBooking() matches the bare word
 * "appointment", so any question ABOUT appointments that the viewing matcher
 * fails to catch is treated as a request to make a new one - and the patient
 * ends up in doctor selection.
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
    printf("  %-56s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

// The order the controller itself uses: cancel, then view, then book.
$branch = function (string $text) use ($call) {
    if ($call('wantsCancel', $text)) { return 'cancel'; }
    if ($call('wantsAppointmentList', $text)) { return 'view'; }
    if ($call('wantsBooking', $text)) { return 'book'; }
    return 'none';
};

echo "QUESTIONS ABOUT APPOINTMENTS -> VIEWING\n";
foreach ([
    'show me my appointments', 'show my appointments', 'what are my appointments?',
    'what appointments do I have?', 'how many appointments do I have?', 'how many appointments I have',
    'do I have any appointments?', 'do I have any upcoming appointments?', 'what appointments are coming up?',
    'when is my next appointment?', 'when is my next appointment with the doctor?', 'tell me my appointments',
    'list my appointments', 'my upcoming appointments', 'can you check my appointments?',
    // An adjective between "my" and "appointments" used to break this and
    // start a whole new booking instead.
    'can you show me my total appointments', 'show me my total appointments',
    'show me my all appointments', 'my total appointments',
    'can you show my booked appointments', 'I want my appointments',
    'what about my remaining appointments',
] as $p) {
    t('"' . $p . '"', $branch($p), 'view');
}

echo "\nBOOKING NEEDS A VERB - A BARE \"APPOINTMENT\" IS NOT ENOUGH\n";
// This is what stopped six different questions from starting a booking.
foreach (['book an appointment', 'I want to book an appointment', 'book another appointment',
    'schedule an appointment', 'make an appointment', 'I want an appointment',
    'I need an appointment', 'can I get an appointment tomorrow', 'I would like a new appointment',
    'I need to see a doctor', 'I want to see Dr Gunnar Gauff'] as $p) {
    t('"' . $p . '" books', $branch($p), 'book');
}
// An unfamiliar phrasing now falls through harmlessly instead of booking.
foreach (['tell me about the appointment', 'appointment', 'what about appointments',
    'something about my appointment thing'] as $p) {
    t('"' . $p . '" does not start a booking', $branch($p) === 'book', false);
}
// "show me all appointments" - the phrasing that prompted this.
foreach (['show me all appointments', 'all appointments', 'every appointment I have',
    'show me all of the appointments'] as $p) {
    t('"' . $p . '" -> view', $branch($p), 'view');
}

echo "\nBOOKING AND CANCELLING KEEP THEIR OWN WORDS\n";
foreach ([
    ['book an appointment', 'book'],
    ['I want to book an appointment', 'book'],
    ['book an appointment for tomorrow morning', 'book'],
    ['book another appointment', 'book'],
    ['I want to see Dr Gunnar Gauff', 'book'],
    ['cancel my appointment', 'cancel'],
    ['I want to cancel my appointment', 'cancel'],
    ['cancel my appointments', 'cancel'],
    ['cancel another appointment', 'cancel'],
    ['which doctor should I see?', 'none'],
] as [$p, $expect]) {
    t('"' . $p . '" -> ' . $expect, $branch($p), $expect);
}

echo "\nFROM A FINISHED BOOKING, A QUESTION IS NOT A NEW BOOKING\n";
$answer = Closure::bind(function (array $b, string $text) {
    $s = $b;
    $msgs = $this->handleAnswer($text, '', 'text', $s,
        app(App\Services\AiAssistant\PureMedApiClient::class),
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

// A finished booking, with no API reachable - so a request that WOULD list
// answers "none to cancel/list", and a request that wrongly books moves to the
// doctor step. That difference is what these assert.
$done = array_merge($call('freshState'), [
    'step' => 'done', 'patient' => ['first_name' => 'Priti'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 2, 'first_name' => 'albert', 'last_name' => 'munnar']],
    'appointment' => ['id' => 77, 'date' => '13.08.2026', 'time' => '14:00'],
]);
foreach (['can you show me my total appointments', 'show me my appointments',
    'how many appointments do I have?'] as $p) {
    [$s, $m] = $answer($done, $p);
    t('"' . $p . '" does not open doctor selection', $s['step'] === 'doctor', false);
    t('"' . $p . '" does not start a booking', str_contains($m, "let's book another"), false);
}
[$s, $m] = $answer($done, 'book another appointment');
t('"book another appointment" still books', $s['step'], 'doctor');

echo "\nTHE OPENING QUESTION SETS THE RIGHT GOAL\n";
$start = array_merge($call('freshState'), ['step' => 'intent']);
foreach (['can you show me my total appointments', 'how many appointments I have',
    'when is my next appointment?'] as $p) {
    [$s, $m] = $answer($start, $p);
    t('"' . $p . '" -> viewing goal', $s['goal'], 'list');
    t('"' . $p . '" never asks which doctor', str_contains($m, 'Which doctor'), false);
}

printf("\n  %d passed, %d failed\n", $ok, $bad);
