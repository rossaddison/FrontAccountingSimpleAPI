<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * The result of asking for a document to be voided. Voiding is idempotent:
 * asking again for a document that is already void is reported as such, not
 * as a failure.
 */
final readonly class VoidOutcome
{
    private function __construct(
        public VoidStatus $status,
        public string $message = '',
    ) {
    }

    public static function voided(): self
    {
        return new self(VoidStatus::Voided);
    }

    public static function alreadyVoided(): self
    {
        return new self(VoidStatus::AlreadyVoided);
    }

    public static function notFound(): self
    {
        return new self(VoidStatus::NotFound);
    }

    public static function refused(string $reason): self
    {
        return new self(VoidStatus::Refused, $reason);
    }

    public function isSuccess(): bool
    {
        return $this->status === VoidStatus::Voided || $this->status === VoidStatus::AlreadyVoided;
    }
}
