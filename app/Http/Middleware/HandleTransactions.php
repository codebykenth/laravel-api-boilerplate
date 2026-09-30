<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleTransactions
{
    /**
     * HTTP methods that perform write operations.
     *
     * @var list<string>
     */
    private const array WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * Wraps write requests in a database transaction. The transaction is committed when the response is successful, or rolled back on exceptions and server-error (5xx) responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), self::WRITE_METHODS, true)) {
            return $next($request);
        }

        DB::beginTransaction();

        try {
            $response = $next($request);

            if ($response->isServerError()) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $response;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
