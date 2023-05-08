<?php

namespace Symbiotic\BtcPuzzle;


class SignatureGenerator
{
    private Config $config;
    /**
     * @var null |RangesLog
     */
    private $log = null;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $logPath = $config->getLogPath();
        if (!empty($logPath)) {
            $this->log = new RangesLog($logPath);
        }

        $this->config = $config;
    }

    /**
     * @param string $token
     * @param Sector $sector
     * @param int    $user_id
     *
     * @return array
     */
    public function generateSectorSignature(string $token, Sector $sector, int $user_id): array
    {
        $addressData = $this->generateSectorAddress($token, $sector, $user_id);
        $sectorHash = $this->getSectorHash($sector->getPuzzleId(), $sector->getSectorNumber());
        if ($this->log) {
            $this->log->writeSector($sectorHash, $sector->getSectorNumber(), $user_id);
        }
        return [
            'sectorHash' => $sectorHash,
            'address' => $addressData['address']
        ];
    }

    /**
     * @param Sector $sector
     * @param int    $user_id
     *
     * @return array
     */
    public function generateSectorAddress(string $token, Sector $sector, int $user_id): array
    {
        if ($token !== $this->config->getToken()) {
            throw new \InvalidArgumentException('Token is not valid!');
        }
        $range = $sector->getRange()->range()->sub(1230);
        /**
         * d90f05ffc7363798718b910ec2ede481d3578fd40a7f81cafbf6889a99f618cb
         */
        $hash = $this->getUserSectorHash($sector->getPuzzleId(), $sector->getSectorNumber(), $user_id);

        $len = strlen($range->str(16));
        // 22dd6f80000000000
        $startHex = $sector->getStart()->str(16);
        // 22dd6f8
        $startPrefix = substr($startHex, 0, strlen($startHex) - $len);
        // d90f05ffc7
        $subNum = substr($hash, 0, $len);
        // 22dd6f8d90f05ffc7
        $privateHex = $startPrefix . $subNum;

        $wallet = new BitcoinECDSA();
        $wallet->setPrivateKey($privateHex);

        return [
            'privateHex' => $privateHex,
            'address' => $wallet->getUncompressedAddress(true)
        ];
    }


    /**
     * @param int $sectorNumber
     *
     * @return string
     */
    public function getSectorHash(int $puzzleNumber, int $sectorNumber): string
    {
        return hash('sha256', $this->config->getSecret() . $puzzleNumber . $sectorNumber);
    }

    /**
     * @param int $puzzleNumber
     * @param int $sectorNumber
     * @param int $user_id
     *
     * @return string
     */
    public function getUserSectorHash(int $puzzleNumber, int $sectorNumber, int $user_id): string
    {
        return hash('sha256', $this->config->getSecret() . $puzzleNumber . $sectorNumber . $user_id);
    }

    /**
     * @param int    $puzzleNumber
     * @param string $hash
     *
     * @return bool
     */
    public function checkSectorHash(int $puzzleNumber, string $hash, int $userId, int $sectorExponent = 40): bool
    {
        if ($this->log) {
            $data = $this->log->getSectorData($hash);
            if (!empty($data)) {
                return true;
            }
        }
        $countSectors = (int)(new Gmp(2))->pow($puzzleNumber - 1)->div((new Gmp(2))->pow($sectorExponent))->str();
        //  long!!!
        for ($i = 1; $i <= $countSectors; $i++) {
            if (hash('sha256', $this->config->getSecret() . $puzzleNumber . $i) === $hash) {
                return true;
            }
        }
        return false;
    }
}