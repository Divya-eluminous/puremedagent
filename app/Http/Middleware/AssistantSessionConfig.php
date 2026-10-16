<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Gives the AI assistant's routes a session of their own: their own cookie and their own folder.
 *
 * Why: Laravel's file sessions save the WHOLE session at the end of every request, with no merge
 * and no lock around read-then-write. The assistant and the admin panel used one cookie and one
 * file, so an admin page that started while the assistant was working on a turn wrote its older
 * copy back afterwards and erased the assistant's new step (and could just as well undo an admin
 * change). Separate cookie, separate file: neither can overwrite the other.
 *
 * It runs FIRST in the "assistant" group, before StartSession, and changes only two settings for
 * this request: the cookie name and the folder. Lifetime, encryption, the Secure and SameSite
 * flags and the driver stay exactly as configured for the whole application. When the request is
 * over the settings are put back, so nothing leaks into whatever the process serves next.
 *
 * It can never take the assistant down: if the folder cannot be created or written to, the
 * assistant carries on with the shared session (and a warning is logged), and
 * AI_ASSISTANT_DEDICATED_SESSION=false switches this off altogether.
 */
class AssistantSessionConfig
{
    public function handle($request, Closure $next)
    {
        if (!config('ai-assistant.dedicated_session', true)) {
            return $next($request);
        }

        $folder = $this->folder();

        if (!$this->usable($folder)) {
            $this->warn($folder);

            return $next($request);
        }

        $original = [
            'session.cookie' => config('session.cookie'),
            'session.files' => config('session.files'),
        ];

        config([
            'session.cookie' => (string) config('ai-assistant.session_cookie', 'puremed_assistant_session'),
            'session.files' => $folder,
        ]);
        $this->forgetStores();

        try {
            return $next($request);
        } finally {
            config($original);
            $this->forgetStores();
        }
    }

    /** The configured folder, or one named sessions-assistant beside the normal session folder (wherever that is). */
    private function folder(): string
    {
        $configured = trim((string) config('ai-assistant.session_path'));

        if ($configured !== '') {
            return rtrim($configured, '/\\');
        }

        return dirname(rtrim((string) config('session.files'), '/\\')) . DIRECTORY_SEPARATOR . 'sessions-assistant';
    }

    private function usable(string $folder): bool
    {
        try {
            if (!is_dir($folder)) {
                @mkdir($folder, 0700, true);
            }

            return is_dir($folder) && is_writable($folder);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * The session manager keeps the store it built first. Within one request that is what is wanted;
     * across requests in one process (a test run, a long-lived worker) it would hand this request's
     * store to the next, so it is dropped on the way in and again on the way out.
     */
    private function forgetStores(): void
    {
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
    }

    /** At most once a minute: every assistant request would otherwise repeat it. */
    private function warn(string $folder): void
    {
        try {
            if (!Cache::add('ai_assistant.session_folder_warning', 1, 60)) {
                return;
            }
        } catch (\Throwable) {
            // no cache: log anyway
        }

        Log::warning('AI assistant: the dedicated session folder cannot be created or written to, so the assistant is using the shared session', ['folder' => $folder]);
    }
}
