<?php

declare(strict_types=1);

namespace infrastructure\presentation\http\controller;

use infrastructure\presentation\http\attribute\OpenApi;
use infrastructure\presentation\http\attribute\Route;
use infrastructure\presentation\http\HttpRequest;
use infrastructure\presentation\http\HttpResponse;
use infrastructure\presentation\http\JsonResponse;
use infrastructure\presentation\http\exception\NotFoundHttpException;
use infrastructure\repository\postgres\PostgresSharedStateRepository;

#[Route('GET', '/state/{stateId}')]
#[OpenApi('Get shared sandbox state', ['State'], response: 'SaveStateRequest', security: [])]
final class GetStateController extends AbstractJsonController
{
    public function __construct(
        private readonly PostgresSharedStateRepository $repository
    ) {
        parent::__construct();
    }

    public function __invoke(HttpRequest $request): HttpResponse
    {
        $stateId = $this->requireRouteParam($request, 'stateId');
        $state = $this->repository->get($stateId);
        if ($state === null) {
            throw new NotFoundHttpException('state.not_found', ['stateId' => $stateId]);
        }

        return JsonResponse::ok($state);
    }
}
