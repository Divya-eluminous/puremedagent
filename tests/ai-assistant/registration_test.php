<?php

/**
 * Registration input handling: mobile, date of birth, email.
 *
 * Run with:  php tests/ai-assistant/registration_test.php
 *
 * Every one of these values is patient identifying information, so the NLU
 * driver is switched off here - and the last section proves the registration
 * steps are not even eligible to reach it.
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

echo "MOBILE - below the minimum length is rejected\n";
foreach (['1', '12', '123', '1234'] as $p) {
    t('"' . $p . '" rejected', $call('cleanMobile', $p), null);
}

echo "\nMOBILE - accepted, then judged by the practice's own rule\n";
foreach ([['12345', '12345'], ['121212', '121212'], ['76643421', '76643421'],
    ['9876543210', '9876543210'], ['0664 1234567', '06641234567']] as [$p, $expect]) {
    t('"' . $p . '" -> ' . $expect, $call('cleanMobile', $p), $expect);
}

echo "\nMOBILE - the register API's regex still applies\n";
// 'mobile_no' => 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric'
t('"0043 664 1234567" (leading 00) rejected', $call('cleanMobile', '0043 664 1234567'), null);
t('"00123456" (leading 00) rejected', $call('cleanMobile', '00123456'), null);
t('16 digits rejected', $call('cleanMobile', '1234567890123456'), null);
t('"abc" rejected', $call('cleanMobile', 'abc'), null);
t('empty rejected', $call('cleanMobile', ''), null);

echo "\nMOBILE - dictated digits\n";
foreach ([['one two one two one two', '121212'], ['nine eight seven six five four', '987654'],
    ['my number is one two three four five', '12345']] as [$p, $expect]) {
    t('"' . $p . '" -> ' . $expect, $call('cleanMobile', $p), $expect);
}
t('"one two three four" is still too short', $call('cleanMobile', 'one two three four'), null);

echo "\nDATE OF BIRTH - the formats people use\n";
foreach (['1 January 1992', '1 Jan 1992', 'January 1, 1992', 'January 1 1992',
    '01/01/1992', '01-01-1992', '01.01.1992', '1.1.1992'] as $p) {
    $d = $call('parseBirthDate', $p);
    t('"' . $p . '" -> 01.01.1992', $d ? $d->format('d.m.Y') : null, '01.01.1992');
}

echo "\nDATE OF BIRTH - said in a sentence\n";
foreach (['my birth date is 1 January 1992', 'I was born on 1 January 1992',
    'My DOB is 1 January 1992', 'I was born January 1 1992',
    'my date of birth is 01.01.1992', "it's 1 January 1992"] as $p) {
    $d = $call('parseBirthDate', $p);
    t('"' . $p . '" -> 01.01.1992', $d ? $d->format('d.m.Y') : null, '01.01.1992');
}

echo "\nDATE OF BIRTH - other real dates keep working\n";
foreach ([['27.03.1993', '27.03.1993'], ['5 April 1990', '05.04.1990'],
    ['12 December 1975', '12.12.1975'], ['1992-01-01', '01.01.1992']] as [$p, $expect]) {
    $d = $call('parseBirthDate', $p);
    t('"' . $p . '" -> ' . $expect, $d ? $d->format('d.m.Y') : null, $expect);
}

echo "\nDATE OF BIRTH - rejected\n";
foreach (['31 February 1992', '30 February 1992', '32 January 1992', '1 January 2099',
    'hello there', 'next Tuesday', '1 January', ''] as $p) {
    t('"' . $p . '" rejected', $call('parseBirthDate', $p), null);
}

echo "\nEMAIL - spoken forms\n";
foreach ([
    ['divya at gmail dot com', 'divya@gmail.com'],
    ['divya at the rate of gmail dot com', 'divya@gmail.com'],
    ['divya at the rate gmail dot com', 'divya@gmail.com'],
    ['divya at rate of gmail dot com', 'divya@gmail.com'],
    ['divya at yopmail dot com', 'divya@yopmail.com'],
    ['divya at the rate of yopmail dot com', 'divya@yopmail.com'],
    ['sia at the rate of yop mail dot com', 'sia@yopmail.com'],
    ['divya at hotmail period com', 'divya@hotmail.com'],
    ['divya at out look dot com', 'divya@outlook.com'],
    ['divya at ya hoo dot com', 'divya@yahoo.com'],
    ['divya at i cloud dot com', 'divya@icloud.com'],
    ['divya at g mail dot com', 'divya@gmail.com'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}

echo "\nEMAIL - typed forms\n";
foreach ([
    ['divya@gmail.com', 'divya@gmail.com'],
    ['divya@yopmail.com', 'divya@yopmail.com'],
    ['Divya@Gmail.Com', 'divya@gmail.com'],
    ['  divya@gmail.com  ', 'divya@gmail.com'],
] as [$p, $expect]) {
    t('"' . trim($p) . '"', $call('cleanEmail', $p), $expect);
}

echo "\nEMAIL - the keyword list is speech repair, not a domain whitelist\n";
foreach ([
    ['patient at clinic-example dot de', 'patient@clinic-example.de'],
    ['patient@clinic-example.de', 'patient@clinic-example.de'],
    ['someone at praxis-mueller dot at', 'someone@praxis-mueller.at'],
    ['first.last at university dot ac dot uk', 'first.last@university.ac.uk'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}

echo "\nEMAIL - rejected\n";
foreach (['not an email', 'divya at', 'at gmail dot com', '', 'divya gmail com'] as $p) {
    t('"' . $p . '" rejected', $call('cleanEmail', $p), null);
}

echo "\nREGISTRATION VALUES NEVER REACH THE LLM\n";
// The controller's own whitelist, and each driver asserts it independently.
foreach (['first_name', 'last_name', 'mobile_no', 'birth_date', 'email', 'email_confirm', 'gender'] as $step) {
    t($step . ' is not NLU-eligible', $call('nluEligible', $step), false);
}
foreach ([['ollama', new App\Services\AiAssistant\OllamaNluService()],
    ['groq', new App\Services\AiAssistant\GroqNluService()]] as [$name, $svc]) {
    foreach (['first_name', 'mobile_no', 'birth_date', 'email', 'gender'] as $step) {
        t($name . ' refuses ' . $step, $svc->interpret($step, [], '01.01.1992'), null);
    }
}
// And nothing a patient answers at those steps is put in an NLU context.
$state = array_merge($call('freshState'), [
    'step' => 'email',
    'patient' => ['first_name' => 'Rita', 'family_name' => 'Lokhande', 'mobile_no' => '9876543210',
        'birth_date' => '1991-04-12', 'email' => 'rita@yopmail.com'],
    'patient_id' => 47426, 'token' => 'jwt-secret', 'pending_email' => 'rita@yopmail.com',
]);
$context = $call('nluContext', $state);
t('no context is built from registration state', $context, []);
foreach (['Rita', 'Lokhande', '9876543210', '1991', 'yopmail', 'jwt-secret', '47426'] as $needle) {
    t('context excludes "' . $needle . '"', str_contains(json_encode($context), $needle), false);
}

printf("\n  %d passed, %d failed\n", $ok, $bad);
