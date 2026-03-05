<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizesInput
{
    /**
     * Fields that should never be stripped (e.g. passwords, tokens).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        '_token',
    ];

    /**
     * Strip HTML tags and formula injection characters from all string inputs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $this->clean($input);
        $request->replace($input);

        return $next($request);
    }

    /**
     * Recursively sanitize all string values in the array.
     */
    private function clean(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_string($value)) {
                // 1. Strip HTML/PHP tags — prevents stored XSS
                $value = trim(strip_tags($value));

                // 2. Strip leading formula-trigger characters — prevents CSV/Formula Injection.
                //    Excel interprets cells starting with =, +, -, @, TAB, or CR as formulas.
                //    An attacker could enter: =CMD|'/C calc'!A0  or  =HYPERLINK("http://evil.com","click")
                $value = ltrim($value, "=+-@\t\r");
            } elseif (is_array($value)) {
                $this->clean($value);
            }
        }
    }
}
