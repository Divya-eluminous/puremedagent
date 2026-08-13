<?php

/**
 * Post-action closing: answering "Is there anything else I can help you with?".
 *
 * Run with:  php tests/ai-assistant/close_test.php
 *
 * Deterministic only - the NLU driver is switched off, so a pass here proves
 * the rules handle these phrases without the LLM.
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

$appts = [['id' => 101, 'start_date' => '2026-08-13 09:00:00', 'doctor_name' => 'gunnar gauff', 'appointment_type_name' => 'Vorsorge']];
$base = [
    'patient' => ['first_name' => 'Divya'], 'patient_id' => 8, 'token' => 'jwt',
    'doctors' => [['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff']],
];
$afterCancel = array_merge($call('freshState'), $base, ['step' => 'cancelled']);
$afterBook = array_merge($call('freshState'), $base, ['step' => 'done',
    'appointment' => ['id' => 77, 'date' => '13.08.2026', 'time' => '09:00', 'doctor' => 'Dr Gunnar Gauff']]);
$afterList = array_merge($call('freshState'), $base, ['step' => 'appointments',
    'cancellable' => $appts, 'appointments_context' => $appts, 'discussed_appointment' => 1]);

$answer = Closure::bind(function (array $b, string $text, string $choice = '') {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, 'text', $s,
        app(App\Services\AiAssistant\PureMedApiClient::class),
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

$closing = ['no', 'no thanks', 'no thank you', "no, that's all", "that's all", 'nothing else',
    "I'm done", "that's it", 'nope', 'not right now', 'nothing',
    // Gratitude closes a conversation just as plainly as "no".
    'thank', 'thanks', 'thank you', 'thanks a lot', 'thank you very much',
    "no thanks that's all", 'cheers'];

foreach ([['after cancelling', $afterCancel], ['after booking', $afterBook], ['after listing', $afterList]] as [$label, $state]) {
    echo "\n" . strtoupper($label) . " - \"ANYTHING ELSE?\" ANSWERED\n";
    foreach ($closing as $p) {
        [$s, $m] = $answer($state, $p);
        t('"' . $p . '" closes the conversation', $s['step'], 'closed');
        t('"' . $p . '" says goodbye', str_contains($m, 'Have a great day'), true);
        t('"' . $p . '" no doctor selection', $s['step'] === 'doctor', false);
        t('"' . $p . '" no repeated menu', str_contains($m, "Say 'cancel my appointment'"), false);
    }
}

echo "\nSAYING NO TWICE DOES NOT LOOP THE MENU\n";
[$s, $m] = $answer($afterCancel, 'no');
[$s2, $m2] = $answer($s, 'thank you');
t('still closed', $s2['step'], 'closed');
t('still a goodbye, not a menu', str_contains($m2, 'Have a great day'), true);
t('never the fallback menu', str_contains($m2, 'book another'), false);

echo "\nPOLITENESS AROUND A REQUEST IS STILL A REQUEST\n";
foreach (['thanks, book another appointment', 'thank you, book another'] as $p) {
    [$s, $m] = $answer($afterCancel, $p);
    t('"' . $p . '" -> booking', $s['step'], 'doctor');
    t('"' . $p . '" does not close', $s['step'] === 'closed', false);
}
[$s, $m] = $answer($afterCancel, 'thanks, show my appointments');
t('"thanks, show my appointments" -> listing', str_contains($m, 'appointments'), true);
t('"thanks, show my appointments" does not close', $s['step'] === 'closed', false);

echo "\nTHE OTHER FLOWS STILL WORK FROM THE SAME PLACE\n";
foreach (['book another appointment', 'I want to book another', 'yes, book another'] as $p) {
    [$s, $m] = $answer($afterCancel, $p);
    t('"' . $p . '" -> booking', $s['step'], 'doctor');
}
[$s, $m] = $answer($afterCancel, 'cancel another appointment');
t('"cancel another appointment" -> cancel path', str_contains($m, 'to cancel'), true);
t('"cancel another appointment" not closed', $s['step'] === 'closed', false);
[$s, $m] = $answer($afterCancel, 'I want to cancel another appointment');
t('"I want to cancel another..." -> cancel path', str_contains($m, 'to cancel'), true);
foreach (['show my appointments', 'what appointments do I have?'] as $p) {
    [$s, $m] = $answer($afterCancel, $p);
    t('"' . $p . '" -> listing', str_contains($m, 'appointments'), true);
    t('"' . $p . '" not closed', $s['step'] === 'closed', false);
}

echo "\nAND FROM THE CLOSED STATE EVERYTHING IS STILL REACHABLE\n";
[$closed, ] = $answer($afterCancel, 'thank you');
[$s, $m] = $answer($closed, 'book another appointment');
t('booking works after closing', $s['step'], 'doctor');
[$s, $m] = $answer($closed, 'show my appointments');
t('listing works after closing', str_contains($m, 'appointments'), true);

echo "\nGRATITUDE ELSEWHERE IS NOT A CLOSING\n";
$slots = [['slot_date' => '13.08.2026', 'weekday' => 'Do', 'time' => '09:00', 'time_slot_id' => 1, 'slot_key' => 'k1'],
    ['slot_date' => '13.08.2026', 'weekday' => 'Do', 'time' => '09:10', 'time_slot_id' => 2, 'slot_key' => 'k2']];
$atConfirm = array_merge($call('freshState'), $base, ['step' => 'confirm', 'slots' => $slots, 'slot_date' => '13.08.2026',
    'appointment_types' => [['id' => 1, 'name' => 'V']], 'appointment_type' => ['id' => 1, 'name' => 'V'],
    'doctor' => ['id' => 2, 'first_name' => 'gunnar', 'last_name' => 'gauff'], 'slot' => $slots[0]]);
[$s, $m] = $answer($atConfirm, 'no');
t('"no" at confirm still means pick another time', $s['step'], 'slot_time');
t('"no" at confirm does NOT close', $s['step'] === 'closed', false);
[$s, $m] = $answer($atConfirm, 'thank you');
t('"thank you" at confirm does NOT close', $s['step'] === 'closed', false);
t('"thank you" at confirm does NOT book', $s['appointment'], null);

$atCancelConfirm = array_merge($call('freshState'), $base, ['step' => 'cancel_confirm', 'cancel_target' => $appts[0]]);
[$s, $m] = $answer($atCancelConfirm, 'no');
t('"no" at cancel_confirm keeps the appointment', str_contains($m, 'kept that appointment'), true);
t('"no" at cancel_confirm does NOT close', $s['step'] === 'closed', false);

printf("\n  %d passed, %d failed\n", $ok, $bad);
