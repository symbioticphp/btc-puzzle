<?php

namespace Symbiotic\BtcPuzzle;


class Range
{
    private int $puzzleId;
    private Gmp $start;
    private Gmp $end;
    private int $sectorsExponent;

    public function __construct(
        Gmp $start,
        Gmp $end,
        int $sectorsExponent = 40
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->sectorsExponent = $sectorsExponent;
        $this->puzzleId = (int)self::getPuzzleIdByNumber($this->start)->str();
    }

    /**
     * @return int
     */
    public function getPuzzleId(): int
    {
        return $this->puzzleId;
    }

    /**
     * @return Gmp
     */
    public function getStart(): Gmp
    {
        return $this->start;
    }

    /**
     * @return Gmp
     */
    public function getEnd(): Gmp
    {
        return $this->end;
    }

    /**
     * @return array|int[]
     */
    public function getSectorsIds(): array
    {
        $puzzleRangeStart = (new Gmp(2))->pow($this->puzzleId - 1);
        $amount = (new Gmp(2))->pow($this->sectorsExponent);

        $range_start = $this->start->sub($puzzleRangeStart);
        $range_end = $this->end->sub($puzzleRangeStart);

        // Add round with ffffffffff to 10000000000
        if ($range_end->div($amount)->str() !== '0') {
            $range_end = $range_end->add(1);
        }

        $sector_start = $range_start->div($amount);
        $sector_end = $range_end->div($amount);

        $sectors_ids = [];

        $sector_max = (int)$sector_end->str();

        for ($i = (int)$sector_start->str(); $i < $sector_max; $i++) {
            $sectors_ids[] = $i;
        }

        return $sectors_ids;
    }

    /**
     * @return Gmp
     */
    public function range(): Gmp
    {
        return $this->end->sub($this->start);
    }

    /**
     * @return array|Sector[]
     */
    public function getSectors(): array
    {
        $result = [];
        foreach ($this->getSectorsIds() as $id) {
            $result[$id] = new Sector($this->getPuzzleId(), $id, $this->getSectorExponent());
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getSectorExponent(): int
    {
        return $this->sectorsExponent;
    }

    /**
     * @return int
     */
    public function getRangeExponent(): int
    {
        $end = $this->end;

        if ($end->mod(2)->str() === '1') {
            $end = $end->add(1);
        }
        $exponent = 1;
        // $range = gmp_sub(gmp_add($end, 100), $this->start);
        $range = $end->add(100)->sub($this->start);

        $sqrt = $range->div(2);

        while ($sqrt->comp(1) > -1) {
            $sqrt = $sqrt->div(2);
            $exponent++;
        }

        return $exponent;
    }

    /**
     * @param Gmp|int|string|\GMP $number
     *
     * @return Gmp
     */
    public static function getPuzzleIdByNumber(Gmp|int|string|\GMP $number): Gmp
    {
        $square = new Gmp(2);
        for ($i = 64; $i < 100; $i++) {
            $puzzleRangeEnd = $square->pow($i);
            if ($puzzleRangeEnd->comp($number) > -1) {
                return new Gmp($i);
            }
        }
        throw new \InvalidArgumentException('Invalid number ' . $number . '!');
    }
}