<?php

declare(strict_types=1);

namespace infrastructure\presentation\http;

use application\service\ClockInterface;
use domain\account\repository\AuthSessionRepositoryInterface;
use infrastructure\presentation\http\attribute\OpenApi;
use infrastructure\presentation\http\exception\UnauthorizedHttpException;
use ReflectionClass;

final class SessionAuthenticator
{
    public function __construct(
        private readonly AuthSessionRepositoryInterface $authSessionRepository,
        private readonly ClockInterface $clock,
        private readonly JwtSessionTokenProcessor $tokenProcessor,
        private readonly string $cookieName = 'auth_token'
    ) {
    }

    public function authenticate(HttpRequest $request, object $controller): HttpRequest
    {
        if (!$this->requiresSession($controller)) {
            return $request;
        }

        $token = $request->cookie($this->cookieName);
        if ($token === null || trim($token) === '') {
            throw new UnauthorizedHttpException('auth.token.required');
        }

        $sessionId = $this->tokenProcessor->decodeSessionId(trim($token));
        if ($sessionId === null) {
            throw new UnauthorizedHttpException('auth.token.invalid');
        }

        $session = $this->authSessionRepository->getById($sessionId);
        if ($session === null) {
            throw new UnauthorizedHttpException('auth.session.not_found');
        }

        $session->assertActive($this->clock->now());

        return new HttpRequest(
            method: $request->method,
            path: $request->path,
            headers: $request->headers,
            queryParams: $request->queryParams,
            cookies: $request->cookies,
            routeParams: $request->routeParams,
            attributes: [...$request->attributes, 'actorId' => $session->userId, 'sessionId' => $session->sessionId],
            body: $request->body
        );
    }

    private function requiresSession(object $controller): bool
    {
        $attributes = (new ReflectionClass($controller))->getAttributes(OpenApi::class);
        if ($attributes === []) {
            return false;
        }

        $openApi = $attributes[0]->newInstance();
        if (!$openApi instanceof OpenApi) {
            return false;
        }

        return in_array('sessionCookie', $openApi->security, true);
    }
}
