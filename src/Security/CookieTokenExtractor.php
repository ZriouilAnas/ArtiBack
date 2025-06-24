<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieTokenExtractor implements TokenExtractorInterface
{
    public function extract(Request $request): ?string
    {
        return $request->cookies->get('BEARER');
    }
}
