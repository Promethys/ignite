<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Http\Responses\PasswordConfirmedResponse as FortifyPasswordConfirmedResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordConfirmedResponse extends FortifyPasswordConfirmedResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $intended = $request->session()->get('url.intended');

        if ($intended && str_starts_with(parse_url($intended, PHP_URL_PATH) ?? '', '/admin')) {
            $request->session()->forget('url.intended');

            return Inertia::location($intended);
        }

        return parent::toResponse($request);
    }
}
