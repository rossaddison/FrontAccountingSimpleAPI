<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

use InvalidArgumentException;

/**
 * Why a document is being voided; kept in FrontAccounting's audit trail.
 */
final readonly class VoidReason
{
    public const DEFAULT_TEXT = 'Voided by the integration.';
    private const MAX_LENGTH = 500;

    private function __construct(public string $text)
    {
    }

    public static function fromString(string $reason): self
    {
        $reason = trim($reason);
        if ($reason === '') {
            return new self(self::DEFAULT_TEXT);
        }
        if (mb_strlen($reason) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('A void reason can be at most ' . self::MAX_LENGTH . ' characters.');
        }

        return new self($reason);
    }
}
