<?php

/**
 * Stage 1: identification before registration.
 *
 * Run with:  php tests/ai-assistant/identity_order_test.php
 *
 * Mobile and date of birth are the only two facts needed to recognise a patient
 * the practice already holds - and they are the practice's own duplicate key
 * (GeneralTrait::_checkDuplicationPatient compares birth_date and mobile_no).
 * Asking them first leaves no name or email behind if someone gives up
 * part-way. Combined into one question (see combined_input_test.php), it also
 * takes a returning patient down to a single answer and a new one to four:
 * mobile and date of birth, full name, email, gender. Every field the register
 * API requires is still collected.
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

echo "THE ORDER ITSELF\n";
$order = (new ReflectionClass($c))->getConstant('REGISTRATION_STEPS');
t('identification comes first', array_slice($order, 0, 2), ['mobile_no', 'birth_date']);
t('the full set is unchanged', $order,
    ['mobile_no', 'birth_date', 'first_name', 'last_name', 'email', 'gender']);
t('nothing the register API needs was dropped',
    array_values(array_diff(['first_name', 'last_name', 'mobile_no', 'birth_date', 'email', 'gender'], $order)), []);

echo "\nEVERY ENTRY POINT ASKS TO IDENTIFY FIRST\n";
$client = app(App\Services\AiAssistant\PureMedApiClient::class);
$auth = app(App\Services\AiAssistant\PatientAuthenticator::class);
$answer = Closure::bind(function (array $b, string $text, $client, $auth) {
    $s = $b;
    $msgs = $this->handleAnswer($text, '', 'text', $s, $client, $auth);
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

$start = array_merge($call('freshState'), ['step' => 'intent']);
foreach ([['book an appointment', 'book'], ['show me my appointments', 'list'],
    ['cancel my appointment', 'cancel']] as [$phrase, $goal]) {
    [$s, ] = $answer($start, $phrase, $client, $auth);
    t('"' . $phrase . '" starts at the mobile question', $s['step'], 'mobile_no');
    t('  ...with the right goal', $s['goal'], $goal);
    t('  ...and no name has been asked for', $s['patient']['first_name'] ?? null, null);
}

echo "\nTHE FIRST QUESTION READS AS A FIRST QUESTION\n";
$atMobile = array_merge($call('freshState'), ['step' => 'mobile_no']);
$prompt = $call('promptFor', $atMobile);
t('it does not thank the patient for nothing', str_contains($prompt['text'], 'Thanks'), false);
t('it asks for the mobile number', str_contains($prompt['text'], 'mobile number'), true);

echo "\nA NEW PATIENT IS STILL ASKED FOR EVERYTHING THE API NEEDS\n";
// Order after the date of birth, when the practice does not know the patient.
t('after date of birth comes the first name', $call('nextRegistrationStep', 'birth_date'), 'first_name');
t('then the last name', $call('nextRegistrationStep', 'first_name'), 'last_name');
t('then the email', $call('nextRegistrationStep', 'last_name'), 'email');
t('then gender, and that is the last of it', $call('nextRegistrationStep', 'email'), 'gender');

echo "\nVALIDATION IS UNCHANGED BY THE REORDER\n";
t('mobile below the minimum is still refused', $call('cleanMobile', '1234'), null);
t('mobile with a leading 00 is still refused', $call('cleanMobile', '0043 664 1234567'), null);
t('a good mobile is still accepted', $call('cleanMobile', '76643421'), '76643421');
t('dictated digits still work', $call('cleanMobile', 'one two one two one two'), '121212');
$d = $call('parseBirthDate', 'my birth date is 1 January 1992');
t('a spoken date of birth still parses', $d ? $d->format('d.m.Y') : null, '01.01.1992');
t('an impossible date is still refused', $call('parseBirthDate', '31 February 1992'), null);
t('a future date is still refused', $call('parseBirthDate', '1 January 2099'), null);
t('email validation is untouched', $call('captureEmail', 'john@gmail')['valid'], false);

echo "\nAND THE ORDER STILL HOLDS NOW THE QUESTIONS ARE COMBINED\n";
// Stage 2 asks for the pair in one question. The order the answers are stored
// and asked for is unchanged - only the number of questions dropped.
t('a combined answer no longer fuses the digits',
    $call('cleanMobile', '76643421 and 1 January 2002'), '76643421');
$pair = $call('splitIdentity', '76643421 and 1 January 2002');
t('both halves come out of one answer',
    [$pair['mobile'], $pair['birth_date']->format('d.m.Y')], ['76643421', '01.01.2002']);

printf("\n  %d passed, %d failed\n", $ok, $bad);
