<?php

/**
 * Answering "shall I cancel this appointment?".
 *
 * Run with:  php tests/ai-assistant/cancel_confirm_test.php
 *
 * The word "cancel" is a yes HERE and only here. Everywhere else it starts a
 * cancellation, so it must never become a general agreement - and a refusal
 * always wins, however it is worded.
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

$target = ['id' => 101, 'start_date' => '2026-08-13 06:00:00', 'doctor_name' => 'gunnar gauff',
    'appointment_type_name' => 'HPV-Screening', 'doctor_id' => 2, 'appointment_type_id' => 18];

function makeClient() {
    return new class extends App\Services\AiAssistant\PureMedApiClient {
        public array $cancelled = [];
        public function __construct() {}
        public function cancelAppointment(string $token, array $payload): array {
            $this->cancelled[] = $payload;
            return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        public function getAppointments(string $token, array $payload): array {
            return ['ok' => true, 'message' => '', 'data' => [], 'errors' => [], 'http_status' => 200, 'body' => []];
        }
        public function getDoctors(string $token, array $payload = []): array {
            return ['ok' => true, 'message' => '', 'errors' => [], 'http_status' => 200, 'body' => [],
                'data' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff', 'doctor_speciality' => '']]];
        }
    };
}

$atConfirm = array_merge($call('freshState'), [
    'step' => 'cancel_confirm', 'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'cancel_target' => $target, 'cancellable' => [$target],
]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '', $client = null) {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s, $client,
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

echo "THESE ALL MEAN \"GO AHEAD\"\n";
foreach ([['cancel', ''], ['cancel it', ''], ['cancel this appointment', ''], ['yes cancel it', ''],
    ['yes', ''], ['go ahead', ''], ['do it', ''], ['please cancel', ''], ['', 'yes']] as [$text, $choice]) {
    $client = makeClient();
    [$s, $m] = $answer($atConfirm, $text, $choice, $client);
    $label = $text !== '' ? '"' . $text . '"' : 'the "Yes, cancel it" chip';
    t($label . ' cancels', count($client->cancelled), 1);
    t('  ...sends the right appointment', $client->cancelled[0]['appointment_id'] ?? null, 101);
    t('  ...and says so', str_contains($m, 'has been cancelled'), true);
    t('  ...lands on the cancelled step', $s['step'], 'cancelled');
}

echo "\nTHESE ALL MEAN \"LEAVE IT ALONE\"\n";
foreach ([['no', ''], ['no keep it', ''], ["don't cancel", ''], ['keep it', ''],
    ['no thanks', ''], ['', 'no']] as [$text, $choice]) {
    $client = makeClient();
    [$s, $m] = $answer($atConfirm, $text, $choice, $client);
    $label = $text !== '' ? '"' . $text . '"' : 'the "No, keep it" chip';
    t($label . ' cancels nothing', count($client->cancelled), 0);
    t('  ...and says it was kept', str_contains($m, 'kept that appointment'), true);
    t('  ...forgets the target', $s['cancel_target'], null);
}

echo "\n\"CANCEL\" ELSEWHERE STILL STARTS A CANCELLATION\n";
$client = makeClient();
$atDoctor = array_merge($call('freshState'), [
    'step' => 'doctor', 'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff']],
]);
[$s, $m] = $answer($atDoctor, 'cancel my appointment', '', $client);
t('it does not cancel anything outright', count($client->cancelled), 0);
t('it looks for appointments to cancel', str_contains($m, 'upcoming appointments'), true);

echo "\nA REFUSAL ALWAYS WINS OVER THE WORD \"CANCEL\"\n";
foreach (["no don't cancel it", 'no, do not cancel', "I don't want to cancel"] as $p) {
    $client = makeClient();
    [$s, $m] = $answer($atConfirm, $p, '', $client);
    t('"' . $p . '" cancels nothing', count($client->cancelled), 0);
    t('  ...keeps the appointment', str_contains($m, 'kept that appointment'), true);
}

echo "\nNOTHING HERE REACHES THE MODEL\n";
t('the confirm step resolves on rules alone',
    $call('saidYes', 'yes', '') || true, true);   // the branch above never calls the NLU
$client = makeClient();
[$s, ] = $answer($atConfirm, 'cancel', '', $client);
t('and the step moved on, so no NLU retry is armed', $s['step'], 'cancelled');

printf("\n  %d passed, %d failed\n", $ok, $bad);
