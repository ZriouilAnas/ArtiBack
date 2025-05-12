<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class LoginAuthenticator extends AbstractAuthenticator
{
    private $userProvider;
    private $passwordHasher;
    private $limiterFactory;

    public function __construct(
        UserProviderInterface $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        RateLimiterFactory $limiterFactory
    ) {
        $this->userProvider = $userProvider;
        $this->passwordHasher = $passwordHasher;
        $this->limiterFactory = $limiterFactory;
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/login_check' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $ip = $request->getClientIp();
        $limiter = $this->limiterFactory->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new AuthenticationException('Too many login attempts. Please try again later.');
        }

        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [new RememberMeBadge()]
        );
    }

    public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Generate a token (JWT or custom token), here you can return success
        return new JsonResponse(['message' => 'Login successful'], 200);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentication failed',
            'error' => $exception->getMessage(),
        ], 401);
    }
}
