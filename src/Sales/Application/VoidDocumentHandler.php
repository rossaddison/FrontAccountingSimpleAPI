<?php

declare(strict_types=1);

namespace FAAPI\Sales\Application;

use FAAPI\Sales\Domain\DocumentState;
use FAAPI\Sales\Domain\DocumentStateReader;
use FAAPI\Sales\Domain\DocumentVoider;
use FAAPI\Sales\Domain\VoidOutcome;

final readonly class VoidDocumentHandler
{
    public function __construct(
        private DocumentStateReader $states,
        private DocumentVoider $voider,
    ) {
    }

    public function __invoke(VoidDocument $command): VoidOutcome
    {
        return match ($this->states->stateOf($command->type, $command->transNo)) {
            DocumentState::Missing => VoidOutcome::notFound(),
            DocumentState::Voided => VoidOutcome::alreadyVoided(),
            DocumentState::Live => $this->void($command),
        };
    }

    private function void(VoidDocument $command): VoidOutcome
    {
        $refusal = $this->voider->void($command->type, $command->transNo, $command->reason);

        return $refusal === null ? VoidOutcome::voided() : VoidOutcome::refused($refusal);
    }
}
