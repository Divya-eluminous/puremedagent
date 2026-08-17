<?php

/**
 * The gender question, as it is actually answered out loud.
 *
 * Run with:  php tests/ai-assistant/gender_test.php
 *
 * The defect this file was written around: speech recognition writes "male" as
 * "mail", and often twice - "mail mail" - because the final and interim
 * transcripts both carry the word. The old parser compared the whole answer
 * against a short list, so every one of those was refused and the patient was
 * told "Sorry, I didn't understand" while saying the right thing.
 *
 * Nothing here involves the LLM: 'gender' is deliberately absent from
 * nluEligible(), so this question is settled by the parser alone.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'ai-assistant.nlu_driver' => 'none',
    'ai-assistant.puremed_base_url' => 'http://voice.localhost/puremedagent/public/index.php',
]);

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
    printf("  %-52s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}
/** Assert what one answer is read as. */
$reads = function (string $said, ?string $expected) use ($call) {
    $shown = trim($said) === '' ? '(nothing)' : $said;
    t('"' . $shown . '"', $call('normalizeGender', $said), $expected);
};

echo "WHAT WAS ALREADY WORKING STILL WORKS\n";
foreach (['male' => 'M', 'female' => 'W', 'Male' => 'M', 'FEMALE' => 'W',
    'Female' => 'W', 'm' => 'M', 'f' => 'W', 'w' => 'W',
    'man' => 'M', 'woman' => 'W'] as $said => $want) {
    $reads((string) $said, $want);
}

echo "\nTHE CHIP BUTTONS\n";
// Tapping Male or Female posts these values, and they go through the same
// parser as the spoken answer.
$reads('M', 'M');
$reads('W', 'W');

echo "\nWHAT THE RECOGNISER ACTUALLY WRITES\n";
foreach (['mail' => 'M', 'mail mail' => 'M', 'male male' => 'M',
    // The recogniser capitalises at the start of a phrase, and some engines
    // return the whole transcript upper-cased.
    'Mail' => 'M', 'MAIL' => 'M', 'Male' => 'M', 'MALE' => 'M',
    'MAIL MAIL' => 'M', 'mails' => 'M', 'maile' => 'M',
    'mael' => 'M', 'male mail' => 'M',
    'female female' => 'W', 'femail' => 'W', 'femaile' => 'W',
    'Female Female' => 'W'] as $said => $want) {
    $reads((string) $said, $want);
}

echo "\nHOW PEOPLE ACTUALLY ANSWER\n";
foreach (['I am male' => 'M', "I'm male" => 'M', 'yes male' => 'M',
    'I am a male' => 'M', 'i am male' => 'M', 'male please' => 'M',
    'I am a man' => 'M', 'yes I am male' => 'M', 'male.' => 'M',
    'I am female' => 'W', "I'm female" => 'W', "I'm a female" => 'W',
    'I am a female' => 'W', 'I am a woman' => 'W', 'female please' => 'W',
    'yes female' => 'W', 'female,' => 'W'] as $said => $want) {
    $reads((string) $said, $want);
}

echo "\nAND THE SAME, MISHEARD\n";
foreach (['I am mail' => 'M', "I'm mail" => 'M', 'yes mail' => 'M',
    'I am a mail' => 'M', 'mail mail mail' => 'M'] as $said => $want) {
    $reads((string) $said, $want);
}

echo "\nGERMAN\n";
foreach (['mann' => 'M', 'Mann' => 'M', 'maennlich' => 'M', 'männlich' => 'M',
    'ich bin männlich' => 'M', 'frau' => 'W', 'Frau' => 'W',
    'weiblich' => 'W', 'ich bin weiblich' => 'W'] as $said => $want) {
    $reads((string) $said, $want);
}

echo "\nNOTHING ELSE BECOMES A GENDER\n";
foreach (['banana', 'yes', 'no', 'okay', 'thanks', '', '   ', '12345',
    'book an appointment', 'I do not want to say', 'manager', 'mailbox',
    'womanhood', 'humans', 'normal', 'formal'] as $said) {
    $reads($said, null);
}

echo "\nEMAIL IS NOT A GENDER\n";
// "mail" is the whole problem, so an answer that is plainly about an address
// must not be read as male.
foreach (['email', 'e-mail', 'e mail', 'my email', 'my e-mail address',
    'emails', 'john@gmail.com', 'send it to my email',
    'my email is john at gmail dot com'] as $said) {
    $reads($said, null);
}

echo "\nBOTH AT ONCE IS NOT A DECISION\n";
// The question repeated back, or a correction mid-sentence: refuse and ask
// again rather than picking one.
foreach (['male or female', 'female or male', 'are you male or female',
    'male no female', 'm or w', 'man or woman'] as $said) {
    $reads($said, null);
}

echo "\nTHE CONVERSATION ITSELF\n";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$cookies = []; $csrf = null;
$req = function ($method, $uri, $body = null) use ($kernel, &$cookies, &$csrf) {
    $h = ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    if ($csrf) { $h['HTTP_X_CSRF_TOKEN'] = $csrf; }
    if ($body !== null) { $h['CONTENT_TYPE'] = 'application/json'; }
    $r = Illuminate\Http\Request::create('http://voice.localhost' . $uri, $method, [], $cookies, [], $h,
        $body !== null ? json_encode($body) : null);
    $res = $kernel->handle($r);
    foreach ($res->headers->getCookies() as $ck) { $cookies[$ck->getName()] = $ck->getValue(); }
    return $res;
};
$html = $req('GET', '/ai-assistant')->getContent();
if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) { $csrf = $m[1]; }
$say = function (string $text) use ($req) {
    return json_decode($req('POST', '/ai-assistant/message', ['text' => $text, 'source' => 'voice'])->getContent(), true);
};
$texts = function (array $d): string {
    $out = [];
    foreach (($d['messages'] ?? []) as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return implode(' | ', $out);
};
/** Walk a new patient to the gender question. */
$atGender = function () use ($req, $say) {
    $req('POST', '/ai-assistant/reset');
    $say('I want to book an appointment');
    $say('9871' . random_int(100000, 999999) . ' and 12 March 1988');
    $say('Test Patient');
    $say('test.patient@yopmail.com');

    // The address is always read back for confirmation, so the gender question
    // comes one answer later than the email itself.
    return $say('yes');
};

$d = $atGender();
t('the question is reached', $d['step'], 'gender');
$d = $say('mail mail');
t('a misheard answer is accepted', $d['step'] !== 'gender', true);
t('and registration completes', str_contains($texts($d), "you're all set"), true);

$atGender();
$d = $say('I am female');
t('a spoken sentence is accepted', $d['step'] !== 'gender', true);

$atGender();
$d = $say('banana');
t('an unrelated word is still refused', $d['step'], 'gender');
t('with the same message as before',
    str_contains($texts($d), "Sorry, I didn't understand. Please answer male or female."), true);

$d = $say('male or female');
t('the question echoed back is refused', $d['step'], 'gender');

$d = $say('male');
t('and a real answer still finishes it', $d['step'] !== 'gender', true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
