<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Src\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Trait HandleError
 *
 * Provides a method to log errors with context information.
 */
trait HandleError
{
    /**
     * Log an error message with optional context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function handleError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
