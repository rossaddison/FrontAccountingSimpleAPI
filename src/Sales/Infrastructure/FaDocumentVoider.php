<?php

declare(strict_types=1);

namespace FAAPI\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\DocumentVoider;
use FAAPI\Sales\Domain\VoidReason;

/**
 * FrontAccounting adapter for DocumentVoider, using FrontAccounting's own
 * void_transaction(), which returns an error message when it refuses and
 * false when it voided the document.
 */
final class FaDocumentVoider implements DocumentVoider
{
    public function __construct()
    {
        if (!function_exists('void_transaction')) {
            include_once FA_ROOT . '/admin/db/voiding_db.inc';
        }
    }

    public function void(DocumentType $type, int $transNo, VoidReason $reason): ?string
    {
        /** @var string|false|null $refusal */
        $refusal = void_transaction($type->value, $transNo, Today(), $reason->text);

        return is_string($refusal) && $refusal !== '' ? $refusal : null;
    }
}
