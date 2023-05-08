<?php


namespace Symbiotic\BtcPuzzle;


class Sector
{

    /**
     * Actual 66
     *
     * @var int
     */
    private int $puzzleId;

    /**
     * Sector number 1 - 33 554 432 for 66 puzzle
     *
     * @var int
     */
    private int $sectorNumber;

    /**
     * Range exponent 2^40
     *
     * @var int
     */
    private int $sectorExponent;


    /**
     * @var Gmp
     */
    private Gmp $start;


    /**
     * @var Gmp
     */
    private Gmp $end;


    /**
     * @param int $puzzleId
     * @param int $sectorNumber
     */
    public function __construct(int $puzzleId, int $sectorNumber, int $sectorExponent = 40)
    {
        if ($puzzleId < 66 || $puzzleId > 74) {
            throw new \Exception('Puzzle ' . $puzzleId . ' is not supported!');
        }
        if ($sectorExponent < 40 || $sectorExponent > 50) {
            throw new \Exception('Invalid sector exponent!');
        }
        if(empty($sectorNumber)) {
            throw new \Exception('Invalid sector number!');
        }

        $startPuzzleRange = (new Gmp(2))->pow(($puzzleId - 1));
        $amount = (new Gmp(2))->pow($sectorExponent);

        $countSectors = (int)$startPuzzleRange->div($amount)->str();

        if ($sectorNumber > $countSectors) {
            throw new \Exception('Invalid sector! Max ' . $countSectors . '!');
        }


        $this->puzzleId = $puzzleId;
        $this->sectorNumber = $sectorNumber;
        $this->sectorExponent = $sectorExponent;


        $this->start = $startPuzzleRange->add($amount->mul($sectorNumber));
        $this->end = $this->start->add($amount);
    }

    /**
     * @return int
     */
    public function getPuzzleId(): int
    {
        return $this->puzzleId;
    }

    /**
     * @return int
     */
    public function getSectorNumber(): int
    {
        return $this->sectorNumber;
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
     * @return Range
     */
    public function getRange(): Range
    {
        return new Range($this->start, $this->end, $this->sectorExponent);
    }
}