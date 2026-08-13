<?php

/**
 * Spoken and typed email addresses.
 *
 * Run with:  php tests/ai-assistant/email_test.php
 *
 * The dividing line this file guards: Web Speech turns speech into text, and
 * this application turns text into an email address. It must never guess that
 * "Preethi" was meant to be "Priti" - it normalises exactly what it was given
 * and asks the patient to confirm it.
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

echo "TYPED\n";
t('"priti@yopmail.com"', $call('cleanEmail', 'priti@yopmail.com'), 'priti@yopmail.com');
t('"Priti@Yopmail.com" (case)', $call('cleanEmail', 'Priti@Yopmail.com'), 'priti@yopmail.com');

echo "\nSPOKEN\n";
foreach ([
    ['Priti at the rate of yopmail dot com', 'priti@yopmail.com'],
    ['Priti at the rate of yopmail.com', 'priti@yopmail.com'],
    ['Priti at yopmail dot com', 'priti@yopmail.com'],
    ['Priti at gmail dot com', 'priti@gmail.com'],
    ['Priti at the rate of gmail dot com', 'priti@gmail.com'],
    ['Priti at rate of yopmail dot com', 'priti@yopmail.com'],
    ['Priti at rate yopmail dot com', 'priti@yopmail.com'],
    ['Priti at yopmail period com', 'priti@yopmail.com'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}

echo "\nSAID IN A SENTENCE\n";
foreach ([
    ['My email address is Priti at the rate of yopmail dot com', 'priti@yopmail.com'],
    ['My email address is Priti at the rate of yopmail.com', 'priti@yopmail.com'],
    ['my email is priti at gmail dot com', 'priti@gmail.com'],
    ["it's priti@yopmail.com", 'priti@yopmail.com'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}

echo "\nCOMMON DOMAINS, SPOKEN\n";
foreach (['gmail', 'yopmail', 'yahoo', 'hotmail', 'outlook', 'icloud'] as $domain) {
    t('"priti at ' . $domain . ' dot com"',
        $call('cleanEmail', 'priti at ' . $domain . ' dot com'), 'priti@' . $domain . '.com');
}

echo "\nARBITRARY DOMAINS ARE NOT RESTRICTED\n";
foreach ([
    ['patient at clinic-example dot de', 'patient@clinic-example.de'],
    ['patient@clinic-example.de', 'patient@clinic-example.de'],
    ['someone at praxis-mueller dot at', 'someone@praxis-mueller.at'],
    ['first.last at university dot ac dot uk', 'first.last@university.ac.uk'],
    ['info at ordination-wien dot co dot at', 'info@ordination-wien.co.at'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}

echo "\nA MIS-HEARD NAME IS NOT SECOND-GUESSED\n";
// Web Speech heard "Preethi". The application must normalise that and ask -
// not quietly substitute the name it thinks the patient meant.
t('"Preethi at the rate of yopmail.com" -> preethi, not priti',
    $call('cleanEmail', 'Preethi at the rate of yopmail.com'), 'preethi@yopmail.com');
t('"Preethi at the rate of mail.com" -> mail.com, not yopmail.com',
    $call('cleanEmail', 'Preethi at the rate of mail.com'), 'preethi@mail.com');

echo "\nSPELLED OUT LETTER BY LETTER (what people do after a mis-hearing)\n";
foreach ([
    ['Preethi at the rate y o p m a i l.com', 'preethi@yopmail.com'],
    ['Preethi at the rate of y o p m a i l dot com', 'preethi@yopmail.com'],
    ['p r i t i at yopmail dot com', 'priti@yopmail.com'],
    ['p r i t i at the rate of y o p m a i l dot com', 'priti@yopmail.com'],
    ['priti at g m a i l dot com', 'priti@gmail.com'],
] as [$p, $expect]) {
    t('"' . $p . '"', $call('cleanEmail', $p), $expect);
}
// The old behaviour: the run was left alone, so validation kept only "i".
t('a spelled local part is never reduced to its last letter',
    $call('cleanEmail', 'p r i t i at yopmail dot com'), 'priti@yopmail.com');

echo "\nTRANSCRIPTS THE ENGINE GOT WRONG ARE NOT REPAIRED\n";
// "yopmail" heard as "male" - there is nothing left in the text to recover.
t('"Preethi at the rate of male" is rejected', $call('cleanEmail', 'Preethi at the rate of male'), null);
// A real but wrong domain must be shown, not silently corrected to yopmail.
t('"Preethi at the rate yavatmal.com" is kept as heard',
    $call('cleanEmail', 'Preethi at the rate yavatmal.com'), 'preethi@yavatmal.com');

echo "\nREJECTED\n";
foreach (['not an email', 'priti at', 'at yopmail dot com', '', 'priti yopmail com'] as $p) {
    t('"' . $p . '" rejected', $call('cleanEmail', $p), null);
}

echo "\nTHE TEN LISTED CASES\n";
$cases = [
    ['john dot smith at gmail dot com', 'john.smith@gmail.com', true],
    ['john underscore smith at gmail dot com', 'john_smith@gmail.com', true],
    ['j o h n dot s m i t h at gmail dot com', 'john.smith@gmail.com', true],
    ['john at gmail dot com', 'john@gmail.com', true],
    ['john.smith@gmail.com', 'john.smith@gmail.com', true],
    ['john at gmail', null, false],
    ['john@@gmail.com', null, false],
    ['john smith@gmail.com', 'smith@gmail.com', true],   // valid shape, but low confidence
    ['john dot smith at gmail dot co dot uk', 'john.smith@gmail.co.uk', true],
    ["I don't know", null, false],
];
foreach ($cases as $i => [$input, $expected, $valid]) {
    $r = $call('captureEmail', $input);
    t('Test ' . ($i + 1) . ': "' . mb_substr($input, 0, 38) . '"', $r['email'], $expected);
    t('  ...valid = ' . var_export($valid, true), $r['valid'], $valid);
}
// Test 8 must not be accepted even though its shape is fine: "john" was lost.
$r = $call('captureEmail', 'john smith@gmail.com');
t('Test 8 is low confidence, not silently accepted', $r['confidence'] < 0.5, true);
t('Test 8 flags the local part as uncertain', $r['uncertain'], 'local');

echo "\nSHAPE RULES BEYOND A BASIC REGEX\n";
foreach ([['john@gmail', false], ['john@@gmail.com', false], ['john smith@gmail.com', false],
    ['john..smith@gmail.com', false], ['john@gmail..com', false], ['john@.com', false],
    ['john@gmail.c', false], ['john@gmail.com', true], ['john.smith@sub.domain.co.uk', true],
    [str_repeat('a', 65) . '@gmail.com', false], ['a@gmail.com', true]] as [$email, $expect]) {
    t('"' . mb_substr($email, 0, 40) . '" shape', $call('emailShapeValid', $email), $expect);
}

echo "\nA SUSPECTED MIS-HEARD DOMAIN IS OFFERED, NEVER APPLIED\n";
foreach ([['john.smith@gamil.com', 'john.smith@gmail.com'], ['john@yahooo.com', 'john@yahoo.com'],
    ['john@hotmial.com', 'john@hotmail.com'], ['john@outlok.com', 'john@outlook.com']] as [$heard, $meant]) {
    $r = $call('captureEmail', $heard);
    t('"' . $heard . '" is kept as heard', $r['email'], $heard);
    t('  ...and ' . $meant . ' is suggested', $r['suggestion'], $meant);
    t('  ...with middling confidence', $r['confidence'] > 0.5 && $r['confidence'] < 1.0, true);
}
// A real domain that merely resembles a common one must be left alone.
foreach (['patient@clinic-example.de', 'someone@praxis-mueller.at', 'info@ordination-wien.co.at'] as $email) {
    $r = $call('captureEmail', $email);
    t('"' . $email . '" is not second-guessed', $r['suggestion'], null);
    t('  ...and is fully trusted', $r['confidence'], 1.0);
}

echo "\nLOGGING IS MASKED\n";
t('an address is masked', $call('maskEmail', 'john.smith@gmail.com'), 'j***@gmail.com');
t('a short local part too', $call('maskEmail', 'a@gmail.com'), 'a***@gmail.com');
t('nothing to mask', $call('maskEmail', null), '(none)');

echo "\nTHE CONFIRMATION FLOW\n";
$base = array_merge($call('freshState'), [
    'step' => 'email', 'goal' => 'book',
    'patient' => ['first_name' => 'Priti', 'family_name' => 'Shirode',
        'mobile_no' => '987654321', 'birth_date' => '1990-04-05'],
]);
$answer = Closure::bind(function (array $b, string $text, string $choice = '', string $source = 'voice') {
    $s = $b;
    $msgs = $this->handleAnswer($text, $choice, $source, $s,
        app(App\Services\AiAssistant\PureMedApiClient::class),
        app(App\Services\AiAssistant\PatientAuthenticator::class));
    $out = [];
    foreach ($msgs as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return [$s, implode(' | ', $out)];
}, $c, App\Http\Controllers\AiAssistant\ChatController::class);

[$heard, $m] = $answer($base, 'Preethi at the rate of yopmail.com', '', 'voice');
t('a spoken address goes to confirmation', $heard['step'], 'email_confirm');
t('it is held, not saved', $heard['patient']['email'] ?? null, null);
t('what was heard is held for the question', $heard['pending_email'], 'preethi@yopmail.com');

$prompt = $call('promptFor', $heard);
t('the question quotes what was heard', $prompt['text'], 'I heard preethi@yopmail.com - is that right?');
$titles = array_column($prompt['options']['items'] ?? [], 'title');
t('the Yes chip is unchanged', $titles[0] ?? null, "Yes, that's right");
t('the No chip is unchanged', $titles[1] ?? null, "No, I'll type it");

[$yes, ] = $answer($heard, '', 'yes');
t('Yes saves exactly what was heard', $yes['patient']['email'] ?? null, 'preethi@yopmail.com');
t('Yes moves on', $yes['step'], 'gender');

[$no, $noMsg] = $answer($heard, '', 'no');
t('No returns to the email question', $no['step'], 'email');
t('No discards what was heard', $no['pending_email'], null);
t('No asks for it typed', str_contains($noMsg, 'type it in'), true);

[$typed, $typedMsg] = $answer($no, 'priti@yopmail.com', '', 'text');
t('the typed correction is accepted', $typed['patient']['email'] ?? null, 'priti@yopmail.com');
t('typing needs no confirmation', $typed['step'], 'gender');
t('the typed value is used exactly', str_contains($typedMsg, 'priti@yopmail.com'), true);

// TEST B from the brief: a different but valid address types through too.
[$typedB, ] = $answer($no, 'preethi@yopmail.com', '', 'text');
t('"preethi@yopmail.com" typed is accepted', $typedB['patient']['email'] ?? null, 'preethi@yopmail.com');
t('and is not rewritten to priti', $typedB['patient']['email'] ?? null, 'preethi@yopmail.com');

// Typing is not put through the spoken vocabulary in a damaging way: an
// address containing "at" or "dot" as text still survives.
t('a typed address with a dotted local part',
    $call('cleanEmail', 'first.last@clinic-example.de'), 'first.last@clinic-example.de');

// Saying it again at the confirmation updates the question rather than failing.
[$again, ] = $answer($heard, 'priti at the rate of yopmail dot com', '', 'voice');
t('saying it again updates what is held', $again['pending_email'], 'priti@yopmail.com');
t('and asks again', $again['step'], 'email_confirm');

echo "\nEMAIL NEVER REACHES THE LLM\n";
t('the email step is not NLU-eligible', $call('nluEligible', 'email'), false);
t('the confirm step is not NLU-eligible', $call('nluEligible', 'email_confirm'), false);
foreach ([['ollama', new App\Services\AiAssistant\OllamaNluService()],
    ['groq', new App\Services\AiAssistant\GroqNluService()]] as [$name, $svc]) {
    t($name . ' refuses the email step',
        $svc->interpret('email', [], 'priti at the rate of yopmail dot com'), null);
    t($name . ' refuses the confirm step',
        $svc->interpret('email_confirm', [], 'yes'), null);
}
t('no NLU context is built while confirming', $call('nluContext', $heard), []);

printf("\n  %d passed, %d failed\n", $ok, $bad);
