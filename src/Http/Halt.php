<?php
namespace FAAPI\Http;

/**
 * Thrown by App::halt() to stop the request once a response has been set.
 */
final class Halt extends \RuntimeException
{
}
