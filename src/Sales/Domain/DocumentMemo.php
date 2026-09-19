<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

use InvalidArgumentException;

/**
 * The free-text memo stored with a document. An integration puts its own
 * reference here, which makes it the key for "did I already post this?".
 */
final readonly class DocumentMemo
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $memo): self
    {
        $memo = trim($memo);
        if ($memo === '') {
            throw new InvalidArgumentException('A document memo cannot be empty.');
        }

        return new self($memo);
    }
}
