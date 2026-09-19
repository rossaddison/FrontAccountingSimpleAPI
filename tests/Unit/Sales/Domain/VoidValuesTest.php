<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Domain;

use FAAPI\Sales\Domain\VoidOutcome;
use FAAPI\Sales\Domain\VoidReason;
use FAAPI\Sales\Domain\VoidStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VoidValuesTest extends TestCase
{
    public function testAReasonIsTrimmed(): void
    {
        self::assertSame('Sent to the wrong client', VoidReason::fromString("  Sent to the wrong client \n")->text);
    }

    public function testABlankReasonFallsBackToTheDefault(): void
    {
        self::assertSame(VoidReason::DEFAULT_TEXT, VoidReason::fromString('')->text);
        self::assertSame(VoidReason::DEFAULT_TEXT, VoidReason::fromString("   \t")->text);
    }

    public function testAReasonOverTheLimitIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        VoidReason::fromString(str_repeat('x', 501));
    }

    public function testAReasonAtTheLimitIsAccepted(): void
    {
        self::assertSame(500, mb_strlen(VoidReason::fromString(str_repeat('é', 500))->text));
    }

    public function testVoidedAndAlreadyVoidedBothCountAsSuccess(): void
    {
        self::assertTrue(VoidOutcome::voided()->isSuccess());
        self::assertTrue(VoidOutcome::alreadyVoided()->isSuccess());
    }

    public function testNotFoundAndRefusedAreNotSuccess(): void
    {
        self::assertFalse(VoidOutcome::notFound()->isSuccess());
        self::assertFalse(VoidOutcome::refused('already credited')->isSuccess());
    }

    public function testARefusalCarriesItsReason(): void
    {
        $outcome = VoidOutcome::refused('This invoice cannot be voided because it was already credited.');

        self::assertSame(VoidStatus::Refused, $outcome->status);
        self::assertSame('This invoice cannot be voided because it was already credited.', $outcome->message);
    }
}
