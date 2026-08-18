<?php

/**
 * Stage 2: two facts in one answer.
 *
 * Run with:  php tests/ai-assistant/combined_input_test.php
 *
 * The assistant asks for the mobile number and date of birth together, and for
 * the full name together. Nothing about how those answers are *judged* changed:
 * splitting decides which words go to which validator, and cleanMobile(),
 * parseBirthDate() and cleanName() then rule on them exactly as they always
 * have. An answer that would have been refused when the questions were asked
 * one at a time is still refused here.
 *
 * The defect this file was written around: "76643421 and 1 January 2002" used
 * to become the mobile number 7664342112002 - every digit of the date swallowed
 * into the number, and a patient registered against a phone number they never
 * gave.
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

/** The mobile and date a single answer yields, as plain strings. */
$split = function (string $text) use ($call): array {
    $r = $call('splitIdentity', $text);

    return [
        'mobile' => $r['mobile'],
        'date' => $r['birth_date'] ? $r['birth_date']->format('d.m.Y') : null,
        'tried_date' => $r['tried_date'],
    ];
};

echo "THE DEFECT ITSELF\n";
t('the date no longer fuses into the number',
    $call('cleanMobile', '76643421 and 1 January 2002'), '76643421');
t('nor in the other order',
    $call('cleanMobile', '1 January 2002 and 76643421'), '76643421');
t('nor with a written date',
    $call('cleanMobile', '76643421, 01/01/2002'), '76643421');
t('nor with dots',
    $call('cleanMobile', '76643421 01.01.2002'), '76643421');
t('nor an ISO date',
    $call('cleanMobile', '76643421 2002-01-01'), '76643421');
t('nor "January 1, 2002"',
    $call('cleanMobile', '76643421 January 1, 2002'), '76643421');
// The date is gone even when it could never be a birth date - its digits still
// are not part of a phone number.
t('a future date is removed rather than absorbed',
    $call('cleanMobile', '76643421 and 1 January 2099'), '76643421');

echo "\nEVERY COMBINED FORM ASKED FOR\n";
foreach ([
    '76643421 and 1 January 2002',
    'my mobile is 76643421 and my date of birth is 1 January 2002',
    '76643421, 01/01/2002',
    '76643421 01.01.2002',
    'mobile 76643421 born 1st January 2002',
    'my number is 76643421 and I was born on January 1, 2002',
    '76643421 and 2002-01-01',
] as $said) {
    $r = $split($said);
    t('"' . mb_strimwidth($said, 0, 46, '...') . '"',
        [$r['mobile'], $r['date']], ['76643421', '01.01.2002']);
}

echo "\nA MONTH WITH NO SPACE AROUND IT\n";
// "1april 1994" is what fast typing and speech produce. Every date pattern
// wants the day, month and year separated, so without the space the date was
// invisible - and an invisible date is not taken out of the answer before the
// phone number is read from it. The reported result was the fifteen digit
// mobile 987654345411994, one digit under the length that would have refused
// it, with the date lost as well.
foreach ([
    '9876543454 and date of birth is 1april 1994',
    '9876543454 and 1april 1994',
    '9876543454 and 1april1994',
    '9876543454, 1april1994',
    'my mobile is 9876543454 and my dob is 1april1994',
] as $said) {
    $r = $split($said);
    t('"' . mb_strimwidth($said, 0, 44, '...') . '"',
        [$r['mobile'], $r['date']], ['9876543454', '01.04.1994']);
}

// The exact assertion the defect calls for: the fused value must never be what
// cleanMobile() returns, and the date must be gone before it is asked.
t('the fused number is never produced',
    $call('cleanMobile', '9876543454 and date of birth is 1april 1994'), '9876543454');
t('and no year is left for it to read',
    str_contains($call('withoutBirthDate', '9876543454 and date of birth is 1april 1994'), '1994'), false);
t('the date alone still parses without its space',
    $call('parseBirthDate', '1april1994')->format('Y-m-d'), '1994-04-01');
t('and is not mistaken for a number', $call('cleanMobile', '1april1994'), null);

// Every month, so this is not an April-only repair.
foreach (['5jan 1990' => '05.01.1990', '12feb 1988' => '12.02.1988',
    '3mar1975' => '03.03.1975', '9jun 2001' => '09.06.2001',
    '21sep1999' => '21.09.1999', '30dec 1980' => '30.12.1980'] as $said => $want) {
    $d = $call('parseBirthDate', (string) $said);
    t('"' . $said . '"', $d ? $d->format('d.m.Y') : null, $want);
}

echo "\nSPACING A MONTH CANNOT REACH ANYTHING ELSE\n";
// It only fires where a real month name meets a number, so times, phone
// numbers and addresses are left exactly as they are.
t('a time is untouched', $call('spaceAroundMonth', '06:10'), '06:10');
t('an evening time too', $call('spaceAroundMonth', '17:00'), '17:00');
t('a phone number is untouched', $call('spaceAroundMonth', '0664 123 4567'), '0664 123 4567');
t('one containing a year is untouched', $call('spaceAroundMonth', '20021234'), '20021234');
t('an email is untouched', $call('spaceAroundMonth', 'john.smith@gmail.com'), 'john.smith@gmail.com');
t('a spaced date is left as it is', $call('spaceAroundMonth', '1 april 1994'), '1 april 1994');
t('and so is a numeric one', $call('spaceAroundMonth', '01.04.1994'), '01.04.1994');


echo "\nONE VALUE ONLY\n";
$r = $split('76643421');
t('a bare mobile gives the mobile', $r['mobile'], '76643421');
t('and no date', $r['date'], null);
$r = $split('my mobile is 76643421');
t('a spoken mobile gives the mobile', $r['mobile'], '76643421');
t('and no date', $r['date'], null);
$r = $split('1 January 2002');
t('a bare date gives the date', $r['date'], '01.01.2002');
t('and no mobile', $r['mobile'], null);
$r = $split('my date of birth is 01.01.2002');
t('a spoken date gives the date', $r['date'], '01.01.2002');
t('and is not mistaken for a number', $r['mobile'], null);

echo "\nINVALID VALUES ARE REFUSED, NOT REPAIRED\n";
$r = $split('1234');
t('a too-short number is not a mobile', $r['mobile'], null);
t('and not a date either', $r['date'], null);
$r = $split('0043 664 1234567');
t('a leading 00 is still refused', $r['mobile'], null);
$r = $split('76643421 and 1 January 2099');
t('a future date is refused', $r['date'], null);
t('but the mobile beside it is kept', $r['mobile'], '76643421');
t('and the attempt is noticed', $r['tried_date'], true);
$r = $split('76643421 and 31 February 1992');
t('an impossible date is refused', $r['date'], null);
t('the mobile is still kept', $r['mobile'], '76643421');
$r = $split('76643421 and 5 April');
t('a date with no year is refused', $r['date'], null);
$r = $split('1234 and 1 January 2002');
t('a bad number does not borrow digits from a good date', $r['mobile'], null);
t('while the date is still read', $r['date'], '01.01.2002');
t('nothing was attempted as a date in "1234"', $split('1234')['tried_date'], false);

echo "\nA REAL NUMBER IS NOT MISTAKEN FOR A DATE\n";
// Only a span carrying a full 18xx/19xx/20xx year is cut out, so ordinary
// numbers - however they are spaced or punctuated - survive whole.
t('spaced digits still join up', $call('cleanMobile', '0664 123 4567'), '0664123456' . '7');
t('dashes in a phone number are not a date', $call('cleanMobile', '0664-123-4567'), '06641234567');
t('dictated digits still work', $call('cleanMobile', 'one two one two one two'), '121212');
t('a number that happens to contain 2002', $call('cleanMobile', '20021234'), '20021234');
t('and one that ends in a year', $call('cleanMobile', '6641992'), '6641992');

echo "\nFULL NAME IN ONE ANSWER\n";
foreach ([
    'Meera Joshi' => ['Meera', 'Joshi'],
    'meera joshi' => ['Meera', 'Joshi'],
    'my name is Meera Joshi' => ['Meera', 'Joshi'],
    "I'm Meera Joshi" => ['Meera', 'Joshi'],
    'Anne-Marie Fischer' => ['Anne-Marie', 'Fischer'],
    "Sean O'Brien" => ['Sean', "O'Brien"],
    'Jan van Berg' => ['Jan', 'Van Berg'],
    // A given name plus a surname of three parts: four words, which only
    // arrive together now that the name is asked for in one question.
    'Jan van der Berg' => ['Jan', 'Van Der Berg'],
    'my name is Jan van der Berg' => ['Jan', 'Van Der Berg'],
] as $said => $expected) {
    $p = $call('splitName', $said);
    t('"' . $said . '"', [$p['first'], $p['last']], $expected);
}

echo "\nTHE LEAD-IN PEOPLE ACTUALLY USE\n";
// The question is "May I know your full name?", so answering "my full name is
// Kritika Sen" is the natural thing to do - and it was the one phrasing the
// lead-in stripper did not know, so the whole sentence reached the word cap
// and was turned away as not a name.
foreach ([
    'my full name is Kritika Sen',
    'my complete name is Kritika Sen',
    'my whole name is Kritika Sen',
    'My Full Name Is Kritika Sen',
    'my name is Kritika Sen',
    'Kritika Sen',
] as $said) {
    $p = $call('splitName', $said);
    t('"' . $said . '"', [$p['first'], $p['last']], ['Kritika', 'Sen']);
}

// The lead-in comes off without taking anything of the name with it.
$p = $call('splitName', 'my full name is Anne-Marie Fischer');
t('a hyphen survives the lead-in', [$p['first'], $p['last']], ['Anne-Marie', 'Fischer']);
$p = $call('splitName', "my full name is Sean O'Brien");
t('an apostrophe survives it', [$p['first'], $p['last']], ['Sean', "O'Brien"]);
$p = $call('splitName', 'my full name is Jan van der Berg');
t('and a surname of three parts', [$p['first'], $p['last']], ['Jan', 'Van Der Berg']);
$p = $call('splitName', 'my full name is Kritika');
t('one name still leaves the surname outstanding', [$p['first'], $p['last']], ['Kritika', null]);

// A lead-in must not become a way to slip a command past the name check.
foreach (['my full name is book an appointment',
    'my complete name is cancel my appointment',
    'my whole name is yes'] as $said) {
    t('"' . $said . '" is still refused', $call('splitName', $said)['first'], null);
}


echo "\nONE NAME ONLY\n";
$p = $call('splitName', 'Meera');
t('a single word is the first name', $p['first'], 'Meera');
t('and leaves the surname outstanding', $p['last'], null);
$p = $call('splitName', 'my name is Meera');
t('a spoken single name works too', $p['first'], 'Meera');
t('and still leaves the surname', $p['last'], null);

echo "\nWHAT IS NOT A NAME IS STILL NOT A NAME\n";
// The four-word cases matter most: raising the limit to fit "Jan van der Berg"
// must not let a short sentence through with it. The word check is what keeps
// them out, not the length.
foreach (['book an appointment', 'I want to see a doctor tomorrow morning please',
    'book an appointment please', 'I need a doctor', 'yes please book that',
    'change the time again', 'yes', 'thanks', '76643421', ''] as $said) {
    $p = $call('splitName', $said);
    t('"' . ($said === '' ? '(nothing)' : $said) . '" is refused', $p['first'], null);
}

printf("\n  %d passed, %d failed\n", $ok, $bad);
