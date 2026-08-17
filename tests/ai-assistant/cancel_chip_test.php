<?php

/**
 * The "Cancel an appointment" button.
 *
 * Run with:  php tests/ai-assistant/cancel_chip_test.php
 *
 * Two separate defects lived here. The button was offered to a patient who had
 * just been told they have no upcoming appointments; and tapping it did
 * nothing useful, because the chip was never routed - the cancel hatch read
 * only the typed text, so the deterministic UI action was the weaker path.
 *
 * What counts as cancellable is PureMed's, not the assistant's: get-appointment
 * returns status = 1 AND start_date >= now(), and that response is what
 * 'cancellable' holds. Nothing here re-judges it with its own dates or rules -
 * the chip simply follows what the practice returned.
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
    printf("  %-56s %s\n", $l, $p ? 'OK' : 'FAIL got=' . var_export($g, true));
}

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
$json = fn ($m, $u, $b = null) => json_decode($req($m, $u, $b)->getContent(), true);
$html = $req('GET', '/ai-assistant')->getContent();
if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) { $csrf = $m[1]; }

$say = fn (string $text) => $json('POST', '/ai-assistant/message', ['text' => $text, 'source' => 'voice']);
$tap = fn (string $type, string $value) => $json('POST', '/ai-assistant/message', ['choice' => ['type' => $type, 'value' => $value]]);
$reset = fn () => $json('POST', '/ai-assistant/reset');
$st = fn () => session('ai_assistant.conversation', []);
$texts = function (array $d): string {
    $out = [];
    foreach (($d['messages'] ?? []) as $m) { $out[] = is_array($m) ? ($m['text'] ?? '') : $m; }
    return implode(' | ', $out);
};
/** The chip values on offer right now. */
$chips = function (array $d): array {
    return array_column($d['options']['items'] ?? [], 'value');
};
/** Identify as the registered test patient and land on the appointment list. */
$viewList = function () use ($reset, $say) {
    $reset();
    $say('show me my appointments');
    return $say('76643421 and 1 January 2002');
};

$PATIENT = ['76643421', '01.01.2002'];

echo "THE CHIP FOLLOWS WHAT THE PRACTICE RETURNED\n";
$d = $viewList();
t('the list step is reached', $d['step'], 'appointments');
$hasAppointments = !empty($st()['cancellable']);
printf("     (this patient currently has %d upcoming)\n", count($st()['cancellable'] ?? []));

if ($hasAppointments) {
    t('with something to cancel, the chip is offered', in_array('cancel', $chips($d), true), true);
} else {
    t('with nothing to cancel, the chip is withheld', in_array('cancel', $chips($d), true), false);
    t('and the assistant says as much',
        str_contains($texts($d), "You don't have any upcoming appointments"), true);
}
t('booking is always offered', in_array('book', $chips($d), true), true);
t('so is the history', in_array('past', $chips($d), true), true);

echo "\nWITH AN APPOINTMENT: THE CHIP APPEARS AND WORKS\n";
// Book one, so the rest of this runs against a patient who really does have
// something to cancel rather than depending on what happens to be on file.
// Not every doctor and appointment type has free time, and which ones do
// changes as the practice's roster is used up. Search for a pairing that
// actually offers a day rather than assuming the first of each does.
$d = null;
$reset();
$say('book an appointment');
$say('76643421 and 1 January 2002');

foreach ($st()['doctors'] as $doctor) {
    $tap('doctor', (string) $doctor['id']);

    foreach ($st()['appointment_types'] as $type) {
        $try = $tap('appointment_type', (string) $type['id']);
        if (($try['step'] ?? '') === 'slot_date' && !empty($try['options']['items'])) {
            $d = $try;
            break 2;
        }
    }

    // Back to the doctor list to try the next one.
    $reset();
    $say('book an appointment');
    $say('76643421 and 1 January 2002');
}
if ($d === null) {
    // Not a failure of the assistant: the practice has no bookable time left
    // for any doctor, so there is no way to reach a real appointment. Said
    // loudly rather than counted as a pass, because these checks did NOT run.
    echo "\n  ***  SKIPPED - the practice has no free days for any doctor.\n";
    echo "  ***  Everything from here needs a real appointment to exist.\n";
    echo "  ***  Add roster dates for an upcoming day and re-run.\n";
    printf("\n  %d passed, %d failed (booking-dependent checks SKIPPED)\n", $ok, $bad);

    return;
}

t('some doctor and type has free days', true, true);

$d = $tap('slot_date', (string) $d['options']['items'][0]['value']);
$d = $tap('slot_time', (string) $d['options']['items'][0]['value']);
$d = $say('yes');
t('an appointment now exists', !empty($st()['appointment']), true);

$d = $viewList();
t('the practice returns it', count($st()['cancellable'] ?? []) > 0, true);
t('so the Cancel chip is offered', in_array('cancel', $chips($d), true), true);
t('and the list is shown', str_contains($texts($d), 'upcoming'), true);

echo "\nTAPPING THE CHIP ENTERS THE EXISTING CANCELLATION FLOW\n";
$d = $tap('appointments', 'cancel');
t('it no longer falls through to the menu',
    str_contains($texts($d), 'just say which'), false);
t('it opens the cancellation list', $d['step'], 'cancel_select');
t('with the usual wording', str_contains($texts($d), 'Here are your upcoming appointments'), true);
t('and something to choose from', count($st()['cancellable'] ?? []) > 0, true);

echo "\nTAPPED AND TYPED BEHAVE THE SAME\n";
$viewList();
$typed = $say('cancel an appointment');
$viewList();
$tapped = $tap('appointments', 'cancel');
t('same step either way', $tapped['step'], $typed['step']);
t('same words either way', $texts($tapped), $texts($typed));

echo "\nTHE CANCELLATION ITSELF IS UNCHANGED\n";
if (empty($st()['cancellable'])) {
    // The appointment booked above is gone - another run of this file, or the
    // practice's own data, took it. Nothing below can be checked without one.
    echo "  ***  SKIPPED - no appointment left to cancel.\n";
    printf("\n  %d passed, %d failed (cancellation checks SKIPPED)\n", $ok, $bad);

    return;
}

$d = $tap('cancel_select', (string) $st()['cancellable'][0]['id']);
t('it asks before doing anything', $d['step'], 'cancel_confirm');
t('and warns it cannot be undone', str_contains($texts($d), 'This cannot be undone'), true);
$d = $say('yes');
t('then confirms it is done', str_contains($texts($d), 'has been cancelled'), true);
t('the held list is emptied', empty($st()['cancellable']), true);

echo "\nA FRESH EMPTY RESPONSE CLEARS A STALE CHIP\n";
// Cancel whatever else this patient holds, so the empty case can be reached.
// The count is not assumed: earlier runs of this file, and the practice's own
// data, both leave appointments behind.
for ($guard = 0; $guard < 10; $guard++) {
    $viewList();
    if (empty($st()['cancellable'])) {
        break;
    }
    $tap('appointments', 'cancel');
    $tap('cancel_select', (string) $st()['cancellable'][0]['id']);
    $say('yes');
}

// Viewing the list again must re-read from PureMed and drop the chip, rather
// than keep offering it from what was held.
$d = $viewList();
t('the practice now returns nothing', count($st()['cancellable'] ?? []), 0);
t('so the Cancel chip is gone', in_array('cancel', $chips($d), true), false);
t('and it says there is nothing',
    str_contains($texts($d), "You don't have any upcoming appointments at the moment"), true);
t('booking is still offered', in_array('book', $chips($d), true), true);

echo "\nSTALE STATE CANNOT SURVIVE AN EMPTY FETCH\n";
// Plant a cancellable list that the practice will not return, then fetch.
$s = $st();
$s['cancellable'] = [['id' => 999999, 'start_date' => '2099-01-01 09:00:00',
    'doctor_name' => 'Dr Nobody', 'appointment_type_name' => 'Ghost']];
session(['ai_assistant.conversation' => $s]);
session()->save();
t('the stale entry is in place before the fetch', count($st()['cancellable']), 1);
$d = $say('show me my appointments');
t('the empty response clears it', count($st()['cancellable'] ?? []), 0);
t('and the chip does not appear', in_array('cancel', $chips($d), true), false);

echo "\nASKING TO CANCEL WITH NOTHING TO CANCEL\n";
$viewList();
$d = $say('cancel an appointment');
t('typed: it says there is nothing to cancel',
    str_contains($texts($d), "You don't have any upcoming appointments to cancel"), true);
t('typed: and does not open a cancellation', $d['step'], 'appointments');

// The chip is withheld now, but a stale page could still post it - the server
// must answer the same way rather than trusting what was clicked.
$d = $tap('appointments', 'cancel');
t('tapped: the same answer',
    str_contains($texts($d), "You don't have any upcoming appointments to cancel"), true);
t('tapped: and no cancellation is opened', $d['step'], 'appointments');

echo "\nBOOKING STILL WORKS FROM THE SAME ROW\n";
$d = $viewList();
$d = $tap('appointments', 'book');
t('the Book chip still starts a booking', $d['step'], 'doctor');
t('with real doctors', count($st()['doctors'] ?? []) > 0, true);

echo "\nAND CANCELLING IS STILL REACHABLE MID-CONVERSATION\n";
// The hatch that answers the chip runs for an identified patient at any step,
// so this must not have changed for the typed phrase.
$reset();
$say('book an appointment');
$say('76643421 and 1 January 2002');
$d = $say('actually I want to cancel an appointment');
t('asking to cancel at the doctor question is heard',
    str_contains($texts($d), 'upcoming appointments'), true);

printf("\n  %d passed, %d failed\n", $ok, $bad);
