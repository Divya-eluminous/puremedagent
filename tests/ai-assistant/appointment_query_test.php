<?php

/**
 * Asking about upcoming or past appointments, in the singular.
 *
 * Run with:  php tests/ai-assistant/appointment_query_test.php
 *
 * Every pattern used to require the plural "appointments", so "is there any
 * upcoming appointment I have" matched nothing and fell through to whichever
 * question was on screen - usually the doctor list.
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

// The order the controller uses: past before upcoming, both before booking.
$route = function (string $text) use ($call) {
    if ($call('wantsCancel', $text)) { return 'cancel'; }
    if ($call('wantsPast', $text)) { return 'past'; }
    if ($call('wantsAppointmentList', $text)) { return 'upcoming'; }
    if ($call('wantsBooking', $text)) { return 'book'; }
    return 'none';
};

echo "UPCOMING, ASKED IN THE SINGULAR\n";
foreach (['is there any upcoming appointment I have', 'do I have any appointment',
    'what appointment I have', 'any upcoming appointment', 'is there an appointment I have',
    'do I have an appointment', 'have I got any appointment', 'what is the appointment I have',
    'is there any appointment', 'are there any appointment I have'] as $p) {
    t('"' . $p . '"', $route($p), 'upcoming');
}

echo "\nSAID THE OTHER WAY ROUND\n";
foreach (['I have any appointment', 'I have an appointment', 'I have appointments',
    'do I have any appointment I have'] as $p) {
    t('"' . $p . '"', $route($p), 'upcoming');
}
// The abandon matcher runs before these in the real flow, so this phrase never
// reaches them - asserted in abandon_test. Here we only check it is not booking.
t('"I dont want any appointment" is caught by the abandon matcher',
    $call('abandonsBooking', 'I dont want any appointment'), true);

echo "\nPAST, INCLUDING WHAT SPEECH MAKES OF IT\n";
foreach (['is there any past appointment I have', 'is there any pasta appointment I have',
    'show me my past appointments', 'my previous appointments', 'show me my past visits',
    'my appointment history', 'paste appointments'] as $p) {
    t('"' . $p . '"', $route($p), 'past');
}
t('"pasta" is only repaired before "appointment"',
    $call('wantsPast', 'I would like some pasta'), false);

echo "\nBOOKING AND CANCELLING ARE UNAFFECTED\n";
foreach ([
    ['book an appointment', 'book'],
    ['I want to book an appointment', 'book'],
    ['do I have to book an appointment', 'book'],
    ['book an appointment for tomorrow', 'book'],
    ['cancel my appointment', 'cancel'],
    ['I want to cancel my appointment', 'cancel'],
] as [$p, $expect]) {
    t('"' . $p . '" -> ' . $expect, $route($p), $expect);
}

echo "\nAND A DOCTOR CHOICE IS STILL A DOCTOR CHOICE\n";
foreach (['Dr Gunnar Gauff', 'the second one', 'Gunnar', 'number 2',
    'in the past I saw Dr Gunnar'] as $p) {
    t('"' . $p . '" is not an appointment query', $route($p), 'none');
}

echo "\nFROM THE DOCTOR QUESTION IT NO LONGER PICKS A DOCTOR\n";
$client = new class extends App\Services\AiAssistant\PureMedApiClient {
    public array $calls = [];
    public function __construct() {}
    public function getAppointments(string $token, array $payload): array {
        $this->calls[] = 'upcoming';
        return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
    }
    public function getAppointmentHistory(string $token, array $payload): array {
        $this->calls[] = 'past';
        return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
    }
};

$atDoctor = array_merge($call('freshState'), [
    'step' => 'doctor', 'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'],
        ['id' => 3, 'first_name' => 'albert', 'last_name' => 'munnar']],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

foreach (['is there any upcoming appointment I have', 'do I have any appointment'] as $p) {
    $client->calls = [];
    [$s, $m] = $answer($atDoctor, $p, '', $client);
    t('"' . $p . '" asks PureMed for the upcoming list', $client->calls, ['upcoming']);
    t('  ...does not say it missed a doctor', str_contains($m, "didn't catch which doctor"), false);
    t('  ...picks no doctor', $s['doctor'], null);
}

foreach (['is there any past appointment I have', 'is there any pasta appointment I have'] as $p) {
    $client->calls = [];
    [$s, $m] = $answer($atDoctor, $p, '', $client);
    t('"' . $p . '" asks PureMed for the history', $client->calls, ['past']);
    t('  ...picks no doctor', $s['doctor'], null);
}

echo "\nCHOOSING A DOCTOR THERE STILL WORKS\n";
$withTypes = new class extends App\Services\AiAssistant\PureMedApiClient {
    public function __construct() {}
    public function getAppointmentTypes(string $token, array $payload = []): array {
        return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
            'data' => [['id' => 25, 'name' => 'Baby-TV', 'duration' => 10]]];
    }
};
[$s, ] = $answer($atDoctor, 'Dr Gunnar Gauff', '', $withTypes);
t('"Dr Gunnar Gauff" still selects', $s['doctor']['id'] ?? null, 2);
[$s, ] = $answer($atDoctor, 'the second one', '', $withTypes);
t('"the second one" still selects', $s['doctor']['id'] ?? null, 3);

printf("\n  %d passed, %d failed\n", $ok, $bad);
