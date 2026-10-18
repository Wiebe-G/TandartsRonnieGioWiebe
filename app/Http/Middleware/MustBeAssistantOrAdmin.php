<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Making appointments and sending invoices: only assistants and admins.
 */
class MustBeAssistantOrAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canPlanAndInvoice(), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
