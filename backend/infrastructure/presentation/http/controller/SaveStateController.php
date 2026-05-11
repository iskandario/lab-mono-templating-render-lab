<?php

declare(strict_types=1);

namespace infrastructure\presentation\http\controller;

use infrastructure\presentation\http\attribute\OpenApi;
use infrastructure\presentation\http\attribute\Route;
use infrastructure\presentation\http\HttpRequest;
use infrastructure\presentation\http\HttpResponse;
use infrastructure\presentation\http\JsonResponse;
use infrastructure\presentation\http\exception\BadRequestHttpException;
use infrastructure\repository\postgres\PostgresSharedStateRepository;
use infrastructure\support\SystemClock;
use infrastructure\support\UuidV4Generator;

#[Route('POST', '/state')]
#[OpenApi('Save shared sandbox state', ['State'], requestBody: 'SaveStateRequest', response: 'IdResponse', responseStatus: 201)]
final class SaveStateController extends AbstractJsonController
{
    public function __construct(
        private readonly PostgresSharedStateRepository $repository,
        private readonly UuidV4Generator $idGenerator,
        private readonly SystemClock $clock
    ) {
        parent::__construct();
    }

    public function __invoke(HttpRequest $request): HttpResponse
    {
        $payload = $this->body($request);
        $this->assertState($payload);

        $stateId = $this->idGenerator->generate();
        $this->repository->save(
            stateId: $stateId,
            ownerId: $this->requireActorId($request),
            state: $payload,
            createdAt: $this->clock->now()
        );

        return JsonResponse::created(['id' => $stateId]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertState(array $payload): void
    {
        if (
            !$this->isSlot($payload['slotA'] ?? null)
            || !$this->isSlot($payload['slotB'] ?? null)
            || !is_string($payload['json'] ?? null)
        ) {
            throw new BadRequestHttpException('state.invalid');
        }
    }

    private function isSlot(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return is_string($value['engineId'] ?? null)
            && trim((string)$value['engineId']) !== ''
            && is_string($value['code'] ?? null);
    }
}
