<?php

declare(strict_types=1);

namespace application\usecase\command\benchmark_run;

use application\service\ClockInterface;
use application\service\IdGeneratorInterface;
use application\usecase\exception\ResourceNotFoundException;
use application\usecase\support\IsoDateTime;
use domain\benchmark_run\model\BenchmarkRun;
use domain\benchmark_run\repository\BenchmarkRunRepositoryInterface;
use domain\common\exception\ValidationException;
use domain\template\exception\TemplateInactiveException;
use domain\template\repository\TemplateRepositoryInterface;

final class StartBenchmarkRunUseCase implements StartBenchmarkRunUseCaseInterface
{
    public function __construct(
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly BenchmarkRunRepositoryInterface $benchmarkRunRepository,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly ClockInterface $clock
    ) {
    }

    public function execute(StartBenchmarkRunCommand $command): StartBenchmarkRunResult
    {
        $templateId = $command->templateId !== null ? trim($command->templateId) : '';
        if ($templateId !== '') {
            $template = $this->templateRepository->getByIdForOwner($templateId, $command->actorId);
            if ($template === null) {
                throw new ResourceNotFoundException('template.not_found: ' . $templateId);
            }

            if (!$template->isActive) {
                throw new TemplateInactiveException($template->templateId);
            }

            $ownerId = $template->ownerId;
            $engineType = $template->engineType;
            $templateBodySnapshot = $template->templateBody;
            $templateId = $template->templateId;
        } else {
            $ownerId = $command->actorId;
            $engineType = trim((string)$command->engineType);
            $templateBodySnapshot = trim((string)$command->templateBody);
            $templateId = null;
            if ($engineType === '' || $templateBodySnapshot === '') {
                throw new ValidationException('benchmark_run.snapshot.invalid', 4620);
            }
        }

        $benchmarkRun = BenchmarkRun::start(
            benchmarkRunId: $this->idGenerator->generate(),
            ownerId: $ownerId,
            templateId: $templateId,
            engineType: $engineType,
            templateBodySnapshot: $templateBodySnapshot,
            contextJson: $command->contextJson,
            iterationsN: $command->iterationsN,
            startedAt: $this->clock->now()
        );

        $this->benchmarkRunRepository->save($benchmarkRun);

        return new StartBenchmarkRunResult(
            benchmarkRunId: $benchmarkRun->benchmarkRunId,
            templateId: $benchmarkRun->templateId,
            ownerId: $benchmarkRun->ownerId,
            status: $benchmarkRun->status,
            iterationsN: $benchmarkRun->iterationsN,
            startedAt: IsoDateTime::format($benchmarkRun->startedAt)
        );
    }
}
