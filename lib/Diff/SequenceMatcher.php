<?php

declare(strict_types=1);

namespace FuzzyWuzzy\Diff;

use Closure;
use Diff_SequenceMatcher;

/**
 * Overloaded SequenceMatcher for constructor convenience.
 *
 * @author Michael Crumm <mike@crumm.net>
 */
class SequenceMatcher extends Diff_SequenceMatcher
{
    /**
     * SequenceMatcher Constructor.
     *
     * @param array|string $a
     * @param array|string $b
     * @param Closure|null $junkCallback
     * @param array $options
     */
    public function __construct(array|string $a, array|string $b, ?Closure $junkCallback = null, array $options = [])
    {
        parent::__construct($a, $b, $junkCallback, $options);
    }
}
