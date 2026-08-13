<?php

/**
 * Session lifetime, resuming, and the "New patient" reset.
 *
 * Run with:  php tests/ai-assistant/session_test.php
 *
 * The concern this file guards: the conversation lives in one Laravel session,
 * which is per-BROWSER, not per-patient. On a shared device the reset must
 * leave nothing of the previous patient behind.
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
    $r = Illuminate\Http\Request::create($BASE . $uri, $method, [], $cookies, [],
        array_merge($h, $body !== null ? ['CONTENT_TYPE' => 'application/json'] : []),
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
function say($text) { return json_decode(req('POST', '/ai-assistant/message', ['text' => $text, 'source' => 'text'])->getContent(), true); }
function start() { return json_decode(req('POST', '/ai-assistant/message', ['start' => true])->getContent(), true); }
function reset_() { return json_decode(req('POST', '/ai-assistant/reset')->getContent(), true); }
/** The conversation state as the server holds it right now. */
function state() {
    global $app;
    return session('ai_assistant.conversation', []);
}
function texts(array $d): string {
    $out = [];
    foreach (($d['messages'] ?? []) as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return implode(' | ', $out);
}

$REG = ['Divya', 'Lokhande', '76643421', '01.01.2002'];

echo "THE SESSION COOKIE OUTLIVES THE WINDOW\n";
openPage();
$cookie = null;
foreach (req('GET', '/ai-assistant')->headers->getCookies() as $ck) {
    if ($ck->getName() === config('session.cookie')) { $cookie = $ck; }
}
t('the cookie is not cleared on browser close', config('session.expire_on_close'), false);
t('it has an expiry rather than being a session cookie', $cookie && $cookie->getExpiresTime() > time(), true);
t('the lifetime is 2 hours', (int) config('session.lifetime'), 120);
t('it is http-only', $cookie && $cookie->isHttpOnly(), true);

echo "\nA CONVERSATION IN PROGRESS RESUMES\n";
reset_();
foreach (array_merge(['I want to book an appointment'], $REG) as $m) { say($m); }
$before = state();
t('the patient was identified', $before['patient_id'] > 0, true);
t('and we are at the doctor question', $before['step'], 'doctor');

$resumed = start();
t('reopening resumes the same step', $resumed['step'], 'doctor');
t('it says so', str_contains(texts($resumed), 'pick up where we left off'), true);
t('the patient is still known', state()['patient_id'], $before['patient_id']);

echo "\nRESET LEAVES NOTHING OF THE PREVIOUS PATIENT\n";
// Get as far as a chosen doctor, type, day and time before resetting.
$doctorId = (string) ($before['doctors'][0]['id'] ?? '');
req('POST', '/ai-assistant/message', ['choice' => ['type' => 'doctor', 'value' => $doctorId]]);
$loaded = state();
$typeId = (string) ($loaded['appointment_types'][0]['id'] ?? '');
if ($typeId !== '') { req('POST', '/ai-assistant/message', ['choice' => ['type' => 'appointment_type', 'value' => $typeId]]); }
$full = state();
printf("  before reset: step=%s doctor=%s type=%s slots=%d\n", $full['step'] ?? '?',
    $full['doctor']['id'] ?? '-', $full['appointment_type']['id'] ?? '-', count($full['slots'] ?? []));

$after = reset_();
$s = state();
t('the reset says it is starting fresh', str_contains(texts($after), 'Starting fresh for a new patient'), true);
t('it opens on the first question', $after['step'], 'intent');

foreach (['patient', 'patient_id', 'token', 'pending_email', 'doctor', 'appointment_type',
    'slot', 'slot_date', 'appointment', 'cancel_target'] as $field) {
    t('reset clears ' . $field, empty($s[$field]), true);
}
foreach (['doctors', 'appointment_types', 'slots', 'cancellable', 'appointment_list',
    'appointments_context'] as $field) {
    t('reset empties ' . $field, empty($s[$field]), true);
}
t('reset clears the undo snapshot', session('ai_assistant.undo'), null);
t('nothing at all is left under the key', $s === [] || $s === null, true);

echo "\nTHE NEXT CONVERSATION CANNOT INHERIT ANY OF IT\n";
$next = say('I want to book an appointment');
// Identification comes first now: mobile and date of birth are all that is
// needed to recognise a returning patient, so nothing else is asked before it.
t('it asks the new patient to identify themselves', $next['step'], 'mobile_no');
$s = state();
t('no previous patient id', empty($s['patient_id']), true);
t('no previous token', empty($s['token']), true);
t('no previous doctor', empty($s['doctor']), true);
t('the previous name is not offered back', str_contains(texts($next), 'Divya'), false);

// A different patient registers in the same browser.
foreach (['Sia', 'Shirode', '98989292', '01.01.1992'] as $m) { say($m); }
$s = state();
t('the new patient is identified', $s['patient_id'] > 0, true);
t('and is NOT the previous one', $s['patient_id'] === $before['patient_id'], false);
t('the name on file is the new one', $s['patient']['first_name'] ?? null, 'Sia');

echo "\nEDITING CANNOT REACH ACROSS A RESET\n";
reset_();
$edit = json_decode(req('POST', '/ai-assistant/edit', ['text' => 'Divya', 'source' => 'text'])->getContent(), true);
t('an edit after reset is refused', $edit['can_edit'] ?? null, false);
t('and it does not restore anyone', empty(state()['patient_id']), true);

echo "\nA FRESH SESSION STILL RECOGNISES AN EXISTING PUREMED PATIENT\n";
// A brand new browser: no cookies at all.
$cookies = []; $csrf = null;
openPage();
$opening = start();
t('a new browser opens at the beginning', $opening['step'], 'intent');
t('with the first-time greeting', str_contains(texts($opening), "Hi, I'm your PureMed Assistant"), true);

$last = null;
foreach (array_merge(['I want to book an appointment'], $REG) as $m) { $last = say($m); }
t('mobile + date of birth identifies them', str_contains(texts($last), 'Welcome back, Divya'), true);
t('registration is skipped', $last['step'], 'doctor');
$s = state();
t('the PureMed patient id is restored', $s['patient_id'] > 0, true);
t('and a token was minted', strlen((string) $s['token']) > 20, true);
t('the email on file is used', str_contains(texts($last), '@'), true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
