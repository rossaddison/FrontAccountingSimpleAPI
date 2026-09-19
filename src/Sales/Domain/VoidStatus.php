<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

enum VoidStatus
{
    case Voided;
    case AlreadyVoided;
    case NotFound;
    case Refused;
}
