<?php


namespace Symbiotic\BtcPuzzle;


class Gmp
{
    /**
     * @var string
     */
    private string $number;

    /**
     * @param string|int|\GMP|Gmp $number
     */
    public function __construct(string|int|\GMP|Gmp $number)
    {
        if (self::isGmp()) {
            $this->number = \gmp_strval(\gmp_init((string)$number, 10));
        } else {
            $this->number = (string)$number;
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     *
     * @return $this
     */
    public function add(int|string|\GMP|Gmp $number): Gmp
    {
        if (self::isGmp()) {
            return new static(\gmp_add($this->number, \gmp_init((string)$number, 10)));
        } else {
            return new static(\bcadd($this->number, (string)$number));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     *
     * @return $this
     */
    public function sub(int|string|\GMP|Gmp $number): Gmp
    {
        if (self::isGmp()) {
            return new static(\gmp_sub($this->number, \gmp_init((string)$number, 10)));
        } else {
            return new static(\bcsub($this->number, (string)$number));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     * @param int|null            $scale
     *
     * @return $this
     */
    public function mul(int|string|\GMP|Gmp $number, ?int $scale = null): Gmp
    {
        if (self::isGmp()) {
            return new static(\gmp_mul($this->number, \gmp_init((string)$number, 10)));
        } else {
            return new static(\bcmul($this->number, (string)$number, $scale));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     *
     * @return $this
     */
    public function div(int|string|\GMP|Gmp $number): Gmp
    {
        if (self::isGmp()) {
            return new static(
                \gmp_div_q(
                    $this->number,
                    \gmp_init((string)$number, 10)
                )
            );
        } else {
            return new static(\bcdiv($this->number, (string)$number));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $exponent
     *
     * @return $this
     */
    public function pow(int|string|\GMP|Gmp $exponent): Gmp
    {
        if (self::isGmp()) {
            return new static(\gmp_pow($this->number, (string)$exponent));
        } else {
            return new static(\bcpow($this->number, (string)$exponent));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     *
     * @return $this
     */
    public function mod(int|string|\GMP|Gmp $number): Gmp
    {
        if (self::isGmp()) {
            return new static(\gmp_div_r($this->number, \gmp_init((string)$number, 10)));
        } else {
            return new static(\bcmod($this->number, (string)$number));
        }
    }

    /**
     * @param int|string|\GMP|Gmp $number
     *
     * @return int
     */
    public function comp(int|string|\GMP|Gmp $number): int
    {
        if (self::isGmp()) {
            return \gmp_cmp(
                $this->number,
                \gmp_init((string)$number, 10)
            );
        } else {
            return \bccomp($this->number, (string)$number);
        }
    }

    /**
     * @param int $format
     *
     * @return string
     */
    public function str(int $format = 10): string
    {
        if (self::isGmp()) {
            return \gmp_strval($this->number, $format);
        } else {
            return \base_convert($this->number, 10, $format);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->number;
    }

    /**
     * @return bool
     */
    private static function isGmp(): bool
    {
        static $isGmp = null;
        if (null === $isGmp) {
            $isGmp = \function_exists('\gmp_init');
        }

        return $isGmp;
    }
}