<?php
/**
 * Drives the real HTTP endpoints against the tenant at voice.localhost.
 * Usage: E2E_MSGS='["message","..."]' php tests/ai-assistant/e2e.php
 * Messages run after a booking intent + registration for the known test patient.
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['ai-assistant.puremed_base_url' => 'http://voice.localhost/puremedagent/public/index.php']);

class CountingNlu extends App\Services\AiAssistant\NluManager {
    public static int $calls = 0;
    public static array $last = [];
    public function interpret(string $s, array $o, string $m, ?string $p = null, array $c = []): ?array {
        self::$calls++;
        $r = parent::interpret($s, $o, $m, $p, $c);
        self::$last = ['intent' => $r['intent'] ?? '(rejected)'];
        return $r;
    }
}
$app->bind(App\Services\AiAssistant\NluManager::class, fn ($a) => new CountingNlu(
    $a->make(App\Services\AiAssistant\OllamaNluService::class),
    $a->make(App\Services\AiAssistant\GroqNluService::class)));

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$BASE = 'http://voice.localhost'; $cookies = []; $csrf = null;

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

$html = req('GET', '/ai-assistant')->getContent();
if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) { $csrf = $m[1]; }

function turn($text) {
    $t0 = microtime(true); CountingNlu::$calls = 0;
    $d = json_decode(req('POST', '/ai-assistant/message', ['text' => $text, 'source' => 'text'])->getContent(), true);
    printf("U: %s\n", $text);
    foreach (($d['messages'] ?? []) as $mm) { printf("   A: %s\n", is_array($mm) ? ($mm['text'] ?? '') : $mm); }
    $opts = [];
    foreach ((array) ($d['options']['items'] ?? []) as $it) { if (is_array($it) && isset($it['title'])) { $opts[] = $it['title']; } }
    if ($opts) { printf("   OPTIONS: %s\n", implode('  ', array_slice($opts, 0, 10))); }
    printf("   [step=%s  nlu_calls=%d  %dms]\n\n", $d['step'] ?? '?', CountingNlu::$calls, round((microtime(true) - $t0) * 1000));
    return $d;
}

$REG = ['Divya', 'Lokhande', '76643421', '01.01.2002'];
$msgs = getenv('E2E_MSGS') ? json_decode(getenv('E2E_MSGS'), true) : [];
foreach (array_merge(['I want to book an appointment'], $REG, $msgs) as $m) { turn($m); }
