<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Result of the furniture import operation.
 */
final class ImportResult
{
    /**
     * @param int      $created   Number of successfully created furniture items.
     * @param int      $skipped   Number of skipped rows.
     * @param string[] $errors    List of error messages.
     */
    public function __construct(
        public readonly int $created,
        public readonly int $skipped,
        public readonly array $errors,
    ) {
    }// end __construct()
}// end class
