<?php

/**
 * Stage 2 as a conversation.
 *
 * Run with:  php tests/ai-assistant/combined_flow_test.php
 *
 * combined_input_test.php proves the splitting and the validators. This proves
 * the conversation around them: that only the missing half is ever asked for,
 * that a returning patient can be recognised from a single sentence, and that
 * a bad answer is met with the question again rather than a guess.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'ai-assistant.nlu_driver' => 'none',
    'ai-assistant.puremed_base_url' => 'http://voice.localhost/puremedagent/public/index.php',
]);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$BASE = 'http://voice.localhost';
$cookies = []; $csrf = null;
$ok = 0; $bad = 0;
function t($l, $g, $e) {
    global $ok, $bad;
    $p = ($g === $e); $p ? $ok++ : $bad++;
    printf("  %-58s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

function req($method, $uri, $body = null) {
    global $kernel, $cookies, $csrf, $BASE;
    $h = ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    if ($csrf) { $h['HTTP_X_CSRF_TOKEN'] = $csrf; }
    if ($body !== null) { $h['CONTENT_TYPE'] = 'application/json'; }
    $r = Illuminate\Http\Request::create($BASE . $uri, $method, [], $cookies, [], $h,
        $body !== null ? json_encode($body) : null);
    $res = $kernel->handle($r);
    foreach ($res->headers->getCookies() as $ck) { $cookies[$ck->getName()] = $ck->getValue(); }
    return $res;
}
function openPage() {
    global $csrf;
    $html = req('GET', '/ai-assistant')->getContent();
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) { $csrf = $m[1]; }
}
function j($m, $u, $b = null) { return json_decode(req($m, $u, $b)->getContent(), true); }
function say($text) { return j('POST', '/ai-assistant/message', ['text' => $text, 'source' => 'text']); }
function reset_() { return j('POST', '/ai-assistant/reset'); }
function texts(array $d): string {
    $out = [];
    foreach (($d['messages'] ?? []) as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return implode(' | ', $out);
}
function patient() { return session('ai_assistant.conversation')['patient'] ?? []; }
/** Start a booking and return the first question asked. */
function begin() { reset_(); return say('I want to book an appointment'); }

echo "THE QUESTION ASKS FOR BOTH\n";
openPage();
$d = begin();
t('booking opens on the identity question', $d['step'], 'mobile_no');
t('which asks for the number', str_contains(texts($d), 'mobile number'), true);
t('and the date of birth, in the same breath', str_contains(texts($d), 'date of birth'), true);

echo "\nA RETURNING PATIENT IN ONE ANSWER\n";
$d = say('76643421 and 1 January 2002');
t('the practice recognises them', str_contains(texts($d), 'Welcome back, Divya'), true);
t('straight to the doctors', $d['step'], 'doctor');
t('the number stored is the one given', patient()['mobile_no'], '76643421');
t('and not one with the date fused into it', strlen(patient()['mobile_no']), 8);
t('the date stored is the one given', patient()['birth_date'], '2002-01-01');

echo "\nSAID AS A SENTENCE\n";
begin();
$d = say('my mobile is 76643421 and my date of birth is 1 January 2002');
t('recognised just the same', str_contains(texts($d), 'Welcome back, Divya'), true);
t('at the doctors', $d['step'], 'doctor');

echo "\nWRITTEN WITH A COMMA\n";
begin();
$d = say('76643421, 01/01/2002');
t('recognised', str_contains(texts($d), 'Welcome back, Divya'), true);
t('with the right number', patient()['mobile_no'], '76643421');

echo "\nONLY THE MOBILE: ONLY THE DATE IS ASKED FOR\n";
begin();
$d = say('76643421');
t('it stays on the identity question', $d['step'], 'birth_date');
t('it reads the number back', str_contains(texts($d), '76643421'), true);
t('it asks for the date', str_contains(texts($d), 'date of birth'), true);
t('and does NOT ask for the number again', substr_count(texts($d), 'mobile number'), 0);
$d = say('01.01.2002');
t('the second half completes it', str_contains(texts($d), 'Welcome back, Divya'), true);
t('at the doctors', $d['step'], 'doctor');

echo "\nONLY THE DATE: ONLY THE MOBILE IS ASKED FOR\n";
begin();
$d = say('1 January 2002');
t('it stays on the identity question', $d['step'], 'mobile_no');
t('it asks for the number', str_contains(texts($d), 'mobile number'), true);
t('and does NOT ask for the date again', substr_count(texts($d), 'date of birth'), 0);
t('the date given is kept', patient()['birth_date'], '2002-01-01');
$d = say('76643421');
t('the second half completes it', str_contains(texts($d), 'Welcome back, Divya'), true);

echo "\nA BAD NUMBER IS ASKED FOR AGAIN, NOT GUESSED AT\n";
begin();
$d = say('1234');
t('nothing is stored', patient(), []);
t('it stays put', $d['step'], 'mobile_no');
t('and says it did not understand', str_contains(texts($d), "didn't catch that"), true);
$d = say('76643421 and 1 January 2002');
t('and the real answer still works', str_contains(texts($d), 'Welcome back, Divya'), true);

echo "\nA DATE THAT CANNOT BE RIGHT IS ASKED FOR AGAIN\n";
begin();
$d = say('76643421 and 1 January 2099');
t('the number is kept', patient()['mobile_no'], '76643421');
t('the impossible date is not', empty(patient()['birth_date']), true);
t('it says the date could not be read', str_contains(texts($d), "couldn't read that as a date"), true);
t('and asks only for the date', $d['step'], 'birth_date');
$d = say('01.01.2002');
t('a real date finishes it', str_contains(texts($d), 'Welcome back, Divya'), true);

begin();
$d = say('76643421 and 31 February 1992');
t('an impossible day is refused too', empty(patient()['birth_date']), true);
t('and only the date is asked for', $d['step'], 'birth_date');

echo "\nA DATE WITH NO NUMBER AND NO YEAR\n";
begin();
$d = say('5 April');
t('nothing is stored', patient(), []);
t('and it asks again', $d['step'], 'mobile_no');

echo "\nA NEW PATIENT GIVES THEIR NAME IN ONE ANSWER\n";
begin();
$unknown = '9876500' . random_int(100, 999);
$d = say($unknown . ' and 12 March 1988');
t('an unknown pair moves on to registration', $d['step'], 'first_name');
t('it says so', str_contains(texts($d), 'just need a few details'), true);
t('and asks for the whole name', str_contains(texts($d), 'full name'), true);
$d = say('Meera Joshi');
t('both names are taken from one answer', $d['step'], 'email');
t('the first name is right', patient()['first_name'], 'Meera');
t('and so is the surname', patient()['last_name'], 'Joshi');
t('the surname question is skipped', str_contains(texts($d), 'last name'), false);

echo "\nONE NAME: ONLY THE SURNAME IS ASKED FOR\n";
begin();
$d = say($unknown . ' and 12 March 1988');
$d = say('Meera');
t('it asks for the surname', $d['step'], 'last_name');
t('by name', str_contains(texts($d), 'Nice to meet you, Meera'), true);
t('and does not ask for the first name again', str_contains(texts($d), 'full name'), false);
$d = say('Joshi');
t('the surname completes the name', $d['step'], 'email');
t('and is stored whole', patient()['last_name'], 'Joshi');

echo "\nA SURNAME OF SEVERAL WORDS SURVIVES\n";
begin();
say($unknown . ' and 12 March 1988');
say('Jan');
say('van der Berg');
t('the surname is not cut up', patient()['last_name'], 'Van Der Berg');

echo "\nSTILL NOT A NAME\n";
begin();
say($unknown . ' and 12 March 1988');
$d = say('book an appointment');
t('a repeated request is not taken as a name', empty(patient()['first_name']), true);
t('and is answered as what it is', str_contains(texts($d), 'already booking that'), true);
t('the conversation has not moved', $d['step'], 'first_name');

echo "\nCANCEL AND VIEW ASK THE SAME ONE QUESTION\n";
foreach (['cancel my appointment' => 'cancel', 'show me my appointments' => 'list'] as $said => $goal) {
    reset_();
    $d = say($said);
    t('"' . $said . '" asks for both at once',
        str_contains(texts($d), 'mobile number') && str_contains(texts($d), 'date of birth'), true);
    $d = say('76643421 and 1 January 2002');
    t('  ...and one answer identifies them', str_contains(texts($d), 'Welcome back, Divya'), true);
}

echo "\nAN UNKNOWN PATIENT CANNOT CANCEL\n";
reset_();
say('cancel my appointment');
$d = say('9876511122 and 1 January 1975');
t('it says there are no records', str_contains(texts($d), "couldn't find any records"), true);
t('and asks for both again', $d['step'], 'mobile_no');
t('the pair is cleared so the question is whole',
    str_contains(texts($d), 'mobile number') && str_contains(texts($d), 'date of birth'), true);

echo "\nOLD STAGE 1 SESSIONS CANNOT RESUME INTO THIS\n";
session()->flush();
session(['_token' => $csrf, 'ai_assistant.conversation' => [
    'flow' => 2, 'step' => 'birth_date', 'goal' => 'book',
    'patient' => ['mobile_no' => '76643421'], 'patient_id' => null,
]]);
session()->save();
$name = config('session.cookie');
$cookies[$name] = $app['encrypter']->encrypt(
    Illuminate\Cookie\CookieValuePrefix::create($name, $app['encrypter']->getKey()) . session()->getId(), false);
$d = j('POST', '/ai-assistant/message', ['start' => true]);
t('a Stage 1 conversation is discarded', $d['step'], 'intent');
t('rather than resumed mid-identity', str_contains(texts($d), 'pick up where we left off'), false);
$d = say('I want to book an appointment');
t('and the Stage 2 question is asked from the top',
    str_contains(texts($d), 'mobile number') && str_contains(texts($d), 'date of birth'), true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
