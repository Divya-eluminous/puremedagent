<?php

/**
 * Stale conversations from an earlier flow.
 *
 * Run with:  php tests/ai-assistant/flow_version_test.php
 *
 * A conversation lives for two hours and survives a browser close, so when the
 * order of the steps changes there are real sessions still walking the old one.
 * Before the flow stamp, reopening such a session resumed it onto whatever step
 * it held - "May I know your first name?" as the very first question, months
 * after the flow stopped asking that first.
 *
 * These run through the HTTP stack rather than calling the controller, because
 * the bug lived in the resume path, not in any single method.
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

// Read the version rather than hardcoding it: this file is about the mechanism,
// and must keep passing every time the flow legitimately changes.
$CURRENT = (new ReflectionClass(App\Http\Controllers\AiAssistant\ChatController::class))
    ->getConstant('FLOW_VERSION');
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
function start() { return j('POST', '/ai-assistant/message', ['start' => true]); }
function texts(array $d): string {
    $out = [];
    foreach (($d['messages'] ?? []) as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return implode(' | ', $out);
}

/**
 * Write a conversation into the session exactly as an older build left it, then
 * hand back the cookie that points at it - the same thing the browser sends.
 */
function plant(array $conversation, ?array $undo = null) {
    global $app, $cookies, $csrf;
    session()->flush();
    // Keep the CSRF token the page handed out, or the next POST is rejected
    // before it ever reaches the assistant.
    session(['_token' => $csrf]);
    session(['ai_assistant.conversation' => $conversation]);
    if ($undo !== null) { session(['ai_assistant.undo' => $undo]); }
    session()->save();
    $name = config('session.cookie');
    $value = Illuminate\Cookie\CookieValuePrefix::create($name, $app['encrypter']->getKey())
        . session()->getId();
    $cookies[$name] = $app['encrypter']->encrypt($value, false);
}
function stored() { return session('ai_assistant.conversation', []); }

// A booking conversation as the pre-reorder build wrote it: sent straight from
// the intent question to the patient's first name, with nothing answered yet,
// and no flow key at all because none existed.
$OLD = [
    'step' => 'first_name',
    'goal' => 'book',
    'chip_page' => 0,
    'patient' => [],
    'patient_id' => null,
    'token' => null,
    'doctors' => [],
    'appointment_types' => [],
    'slots' => [],
];

echo "THE REPORTED BUG\n";
openPage();
plant($OLD);
$d = start();
t('it does not resume the old step', $d['step'], 'intent');
t('it does not ask for a first name', str_contains(texts($d), 'first name'), false);
t('it does not claim to be picking up where it left off',
    str_contains(texts($d), 'pick up where we left off'), false);
t('it opens as a first-time conversation', str_contains(texts($d), "Hi, I'm your PureMed Assistant"), true);

echo "\nAND THE CURRENT FLOW RUNS FROM THERE\n";
$d = say('I want to book an appointment');
t('booking starts at the mobile number', $d['step'], 'mobile_no');
t('which is what it asks for', str_contains($d['messages'][1]['text'] ?? '', 'mobile number'), true);
$d = say('76643421');
t('the mobile is accepted', $d['step'], 'birth_date');
$d = say('01.01.2002');
t('mobile + date of birth still identifies the patient',
    str_contains(texts($d), 'Welcome back, Divya'), true);
t('and goes straight to the doctors', $d['step'], 'doctor');

echo "\nTHE STALE STATE IS DISCARDED, NOT CARRIED\n";
plant($OLD);
start();
$s = stored();
t('the old step is gone', $s['step'], 'intent');
t('the conversation is stamped with the current flow', $s['flow'] ?? null, $CURRENT);
t('nothing of the old goal survives', $s['goal'], 'book');   // the default, not carried

// An old conversation that had got further: a patient identified, a doctor
// chosen, an undo snapshot of the same old shape sitting behind it.
plant(array_merge($OLD, [
    'step' => 'doctor',
    'patient' => ['first_name' => 'Ghost', 'last_name' => 'Patient', 'mobile_no' => '99999999'],
    'patient_id' => 4242,
    'token' => str_repeat('x', 40),
    'doctors' => [['id' => 7, 'first_name' => 'Old', 'last_name' => 'Doctor']],
]), ['state' => $OLD, 'text' => 'Ghost']);
$d = start();
t('a further-along old conversation is dropped too', $d['step'], 'intent');
$s = stored();
t('the previous patient id does not survive', empty($s['patient_id']), true);
t('nor the token', empty($s['token']), true);
t('nor the name', empty($s['patient']), true);
t('nor the doctor list', empty($s['doctors']), true);
t('the undo snapshot goes with it', session('ai_assistant.undo'), null);
$edit = j('POST', '/ai-assistant/edit', ['text' => 'Ghost', 'source' => 'text']);
t('so the old state cannot be restored by editing', $edit['can_edit'] ?? null, false);

echo "\nA VERSION THAT IS MERELY WRONG IS ALSO REJECTED\n";
// 99 stands in for a rolled-back deploy: a conversation stamped by a build
// newer than the one now running is no more resumable than an older one.
foreach ([1 => 'an older stamp', 99 => 'a newer stamp', 0 => 'a zero stamp'] as $v => $label) {
    plant(array_merge($OLD, ['flow' => $v]));
    t($label . ' is discarded', start()['step'], 'intent');
}
plant(array_merge($OLD, ['flow' => (string) $CURRENT]));
t('the version as a string is not mistaken for the version', start()['step'], 'intent');

echo "\nCURRENT CONVERSATIONS ARE LEFT ALONE\n";
// The whole point: this must not reset anybody who is simply mid-booking.
plant(array_merge($OLD, ['flow' => $CURRENT, 'step' => 'birth_date',
    'patient' => ['mobile_no' => '76643421']]));
$d = start();
t('a current conversation resumes', $d['step'], 'birth_date');
t('it says so', str_contains(texts($d), 'pick up where we left off'), true);
t('and the answer already given is kept', stored()['patient']['mobile_no'], '76643421');
$d = say('01.01.2002');
t('it carries on from exactly where it was', $d['step'], 'doctor');

echo "\nA CONVERSATION STAMPED AS IT RUNS\n";
// A browser with no history at all. The store is flushed as well as the
// cookie: every request here shares one PHP process, and Store::start()
// merges what it reads into the attributes already in memory, so dropping
// the cookie alone would leave the previous conversation behind. A real
// browser hits a fresh process and has no such carry-over.
$cookies = []; $csrf = null;
session()->flush();
openPage();
$d = start();
t('a fresh conversation opens on intent', $d['step'], 'intent');
t('and is stamped straight away', stored()['flow'] ?? null, $CURRENT);
say('I want to book an appointment');
t('the stamp survives the next message', stored()['flow'] ?? null, $CURRENT);
t('reopening it resumes rather than resets', start()['step'], 'mobile_no');

echo "\nRESET IS UNCHANGED\n";
plant($OLD);
$d = j('POST', '/ai-assistant/reset');
t('reset still opens on intent', $d['step'], 'intent');
t('reset still says it is starting fresh',
    str_contains(texts($d), 'Starting fresh for a new patient'), true);
t('and still leaves nothing behind', stored(), []);

printf("\n  %d passed, %d failed\n", $ok, $bad);
