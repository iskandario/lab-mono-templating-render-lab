<?php

declare(strict_types=1);

namespace application\usecase\command\template;

use application\service\ClockInterface;
use application\usecase\exception\ResourceNotFoundException;
use application\usecase\support\IsoDateTime;
use domain\template\repository\TemplateRepositoryInterface;

final class UpdateTemplatePublicityUseCase implements UpdateTemplatePublicityUseCaseInterface
{
    public function __construct(
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly ClockInterface $clock
    ) {
    }

    public function execute(UpdateTemplatePublicityCommand $command): UpdateTemplatePublicityResult
    {
        $template = $this->templateRepository->getByIdForOwner($command->templateId, $command->actorId);
        if ($template === null) {
            throw new ResourceNotFoundException('template.not_found: ' . $command->templateId);
        }

        $template->updatePublicity($command->actorId, $command->isPublic, $this->clock->now());
        $this->templateRepository->save($template);

        return new UpdateTemplatePublicityResult(
            templateId: $template->templateId,
            isPublic: $template->isPublic,
            updatedAt: IsoDateTime::format($template->updatedAt)
        );
    }
}
