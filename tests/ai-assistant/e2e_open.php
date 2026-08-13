<?php
/**
 * Like e2e.php, but the messages start from the assistant's opening question
 * (no registration is prepended). Usage:
 *   E2E_MSGS='["...","..."]' php tests/ai-assistant/e2e_open.php
 */
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['ai-assistant.puremed_base_url' => 'http://voice.localhost/puremedagent/public/index.php']);
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
req('POST', '/ai-assistant/reset');
$source = getenv('E2E_SOURCE') ?: 'text';
foreach (json_decode(getenv('E2E_MSGS'), true) as $text) {
    $d = json_decode(req('POST', '/ai-assistant/message', ['text' => $text, 'source' => $source])->getContent(), true);
    printf("U: %s\n", $text);
    foreach (($d['messages'] ?? []) as $mm) { printf("   A: %s\n", is_array($mm) ? ($mm['text'] ?? '') : $mm); }
    printf("   [step=%s]\n\n", $d['step'] ?? '?');
}
