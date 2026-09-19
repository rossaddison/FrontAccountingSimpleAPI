<?php

declare(strict_types=1);

namespace FAAPI\Sales\Http;

/**
 * A status and a JSON-serialisable body, independent of the router that
 * ends up sending it.
 */
final readonly class HttpResult
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        public int $status,
        public array $body,
    ) {
    }

    public static function error(int $status, string $message): self
    {
        return new self($status, ['code' => $status, 'success' => 0, 'msg' => $message]);
    }
}
