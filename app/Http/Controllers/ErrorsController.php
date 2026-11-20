<?php

namespace App\Http\Controllers;

/**
 * Utility for consistent error responses.
 *
 * Deliberately does not extend BaseController: the error path must not pull in
 * sessions, models, or a database connection that may be the source of the
 * failed request.
 */
class ErrorsController extends Controller
{
    public function notFound(?string $message = null): never
    {
        abort(404, $message ?? 'Halaman tidak ditemukan.');
    }
}
