<?php

declare(strict_types=1);

namespace FuzzyWuzzy;

/**
 * A collection of fuzzy string matching algorithms, based on the SeatGeek
 * python library of the same name.
 *
 * @link http://chairnerd.seatgeek.com/fuzzywuzzy-fuzzy-string-matching-in-python/
 *
 * @author Michael Crumm <mike@crumm.net>
 */
class Fuzz
{
    /**
     * Returns a basic ratio score between the two strings.
     *
     * @param string $s1
     * @param string $s2
     *
     * @return int
     */
    public function ratio(string $s1, string $s2): int
    {
        if (strlen($s1) === 0 || strlen($s2) === 0) {
            return 0;
        }

        $m = new Diff\SequenceMatcher($s1, $s2);

        return Utils::intr(100 * $m->Ratio());
    }

    /**
     *
     * @todo Skip duplicate indexes for a little more speed.
     *
     * @param string $s1
     * @param string $s2
     *
     * @return int
     */
    public function partialRatio(string $s1, string $s2): int
    {
        if (strlen($s1) === 0 || strlen($s2) === 0) {
            return 0;
        }

        if (strlen($s1) <= strlen($s2)) {
            $shorter = $s1;
            $longer  = $s2;
        } else {
            $shorter = $s2;
            $longer  = $s1;
        }

        $m = new Diff\SequenceMatcher($shorter, $longer);

        /** @var array<array{int, int, int}> $blocks */
        $blocks = $m->getMatchingBlocks();
        $scores = [];

        foreach ($blocks as $block) {
            $longStart  = $block[1] - $block[0] > 0 ? $block[1] - $block[0] : 0;
            $longEnd    = $longStart + strlen($shorter);
            $longSubstr = substr($longer, $longStart, $longEnd);

            $m2 = new Diff\SequenceMatcher($shorter, $longSubstr);
            $r  = $m2->Ratio();

            if ($r > .995) {
                return 100;
            } else {
                $scores[] = $r;
            }
        }

        if (count($scores) === 0) {
            return 0;
        }

        return Utils::intr(100 * max($scores));
    }

    /**
     * Returns a measure of the sequences' similarity between 0 and 100,
     * using different algorithms.
     *
     * @param string $s1
     * @param string $s2
     * @param boolean $forceAscii
     * @return int
     */
    public function weightedRatio(string $s1, string $s2, bool $forceAscii = true): int
    {
        $p1 = Utils::fullProcess($s1, $forceAscii);
        $p2 = Utils::fullProcess($s2, $forceAscii);

        if (!Utils::validateString($p1)) {
            return 0;
        }

        if (!Utils::validateString($p2)) {
            return 0;
        }

        # should we look at partials?
        $try_partial   = true;
        $unbase_scale  = .95;
        $partial_scale = .90;

        $base      = $this->ratio($p1, $p2);
        $len_ratio = (float)((max(strlen($p1), strlen($p2))) / min(strlen($p1), strlen($p2)));

        # if strings are similar length, don't use partials
        if ($len_ratio < 1.5) {
            $try_partial = false;
        }

        # if one string is much much shorter than the other
        if ($len_ratio > 8) {
            $partial_scale = .6;
        }

        if ($try_partial) {
            $partial = $this->partialRatio($p1, $p2) * $partial_scale;
            $ptsor = $this->tokenSortPartialRatio($p1, $p2, $forceAscii) * $unbase_scale * $partial_scale;
            $ptser = $this->tokenSetPartialRatio($p1, $p2, $forceAscii) * $unbase_scale * $partial_scale;

            return (int) max($base, $partial, $ptsor, $ptser);
        }

        $tsor = $this->tokenSortRatio($p1, $p2, $forceAscii) * $unbase_scale;
        $tser = $this->tokenSetRatio($p1, $p2, $forceAscii) * $unbase_scale;

        return (int) max($base, $tsor, $tser);
    }

    /**
     * @param string $s1
     * @param string $s2
     * @param bool $forceAscii
     * @return int
     */
    public function tokenSetPartialRatio(string $s1, string $s2, bool $forceAscii = true): int
    {
        return $this->tokenSet($s1, $s2, true, $forceAscii);
    }

    /**
     * @param string $s1
     * @param string $s2
     * @param bool $forceAscii
     * @return int
     */
    public function tokenSetRatio(string $s1, string $s2, bool $forceAscii = true): int
    {
        return $this->tokenSet($s1, $s2, false, $forceAscii);
    }

    /**
     * @param string $s1
     * @param string $s2
     * @param bool $forceAscii
     * @return int
     */
    public function tokenSortPartialRatio(string $s1, string $s2, bool $forceAscii = true): int
    {
        return $this->tokenSort($s1, $s2, true, $forceAscii);
    }

    /**
     * @param string $s1
     * @param string $s2
     * @param bool $forceAscii
     * @return int
     */
    public function tokenSortRatio(string $s1, string $s2, bool $forceAscii = true): int
    {
        return $this->tokenSort($s1, $s2, false, $forceAscii);
    }

    /**
     * Find all alphanumeric tokens in each string...
     *
     * - treat them as a set
     * - construct two strings of the form: <sorted_intersection><sorted_remainder>
     * - take ratios of those two strings
     * - controls for unordered partial matches
     *
     * @param string $s1
     * @param string $s2
     * @param bool $partial
     * @param bool $forceAscii
     * @return int
     */
    private function tokenSet(string $s1, string $s2, bool $partial = true, bool $forceAscii = true): int
    {
        $p1 = Utils::fullProcess($s1, $forceAscii);
        $p2 = Utils::fullProcess($s2, $forceAscii);

        if (!Utils::validateString($p1)) {
            return 0;
        }

        if (!Utils::validateString($p2)) {
            return 0;
        }

        # pull tokens
        $tokens1 = StringProcessor::split(Utils::fullProcess($p1));
        $tokens2 = StringProcessor::split(Utils::fullProcess($p2));

        $intersection = $tokens1->intersection($tokens2);
        $diff1to2     = $tokens1->difference($tokens2);
        $diff2to1     = $tokens2->difference($tokens1);

        $sorted_sect = $intersection->sort()->join();
        $sorted_1to2 = $diff1to2->sort()->join();
        $sorted_2to1 = $diff2to1->sort()->join();

        $combined_1to2 = $sorted_sect . ' ' . $sorted_1to2;
        $combined_2to1 = $sorted_sect . ' ' . $sorted_2to1;

        # strip
        $sorted_sect   = trim($sorted_sect);
        $combined_1to2 = trim($combined_1to2);
        $combined_2to1 = trim($combined_2to1);

        $ratioFunc = $partial ? 'partialRatio' : 'ratio';

        $pairwise = [
            call_user_func([$this, $ratioFunc], $sorted_sect, $combined_1to2),
            call_user_func([$this, $ratioFunc], $sorted_sect, $combined_2to1),
            call_user_func([$this, $ratioFunc], $combined_1to2, $combined_2to1)
        ];

        return (int) max($pairwise);
    }

    /**
     * @param string $s1
     * @param string $s2
     * @param bool $partial
     * @param bool $forceAscii
     * @return int
     */
    private function tokenSort(string $s1, string $s2, bool $partial = true, bool $forceAscii = true): int
    {
        $sorted1 = $this->processAndSort($s1, $forceAscii);
        $sorted2 = $this->processAndSort($s2, $forceAscii);

        if ($partial) {
            return $this->partialRatio($sorted1, $sorted2);
        }

        return $this->ratio($sorted1, $sorted2);
    }

    /**
     * @param string  $str
     * @param boolean $forceAscii
     * @return string
     */
    private function processAndSort(string $str, bool $forceAscii = true): string
    {
        $tokens = StringProcessor::split(Utils::fullProcess($str, $forceAscii));

        return StringProcessor::strip($tokens->sort()->join());
    }
}
