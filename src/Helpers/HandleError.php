<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Src\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Trait HandleError
 * Provides a method to log errors with context.
 */
trait HandleError
{
    /**
     * Log an error message with optional context.
     *
     * @param string $message The error message
     * @param array $context Additional context for the log
     * @return void
     */
    public function handleError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}