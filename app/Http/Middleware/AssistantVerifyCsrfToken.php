<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * The CSRF check for the AI assistant's routes. Identical to the application's own, except that it
 * never writes the XSRF-TOKEN cookie.
 *
 * That cookie has a fixed name shared by the whole host. The assistant's session is separate from
 * the admin panel's, so its token differs; if the assistant wrote the cookie it would replace the
 * admin's with a token the admin session does not hold, and the admin pages that send the cookie
 * back (axios does) would start failing with 419. The assistant page does not need the cookie: it
 * sends the token from its own page as X-CSRF-TOKEN, which Laravel checks first, and the check is
 * still made against the assistant's own session.
 */
class AssistantVerifyCsrfToken extends Middleware
{
    protected $addHttpCookie = false;

    protected $except = [
        //
    ];
}
