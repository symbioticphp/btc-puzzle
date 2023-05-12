<?php

declare(strict_types=1);

namespace Symbiotic\BtcPuzzle;


use JetBrains\PhpStorm\ArrayShape;
use Symbiotic\BtcPuzzle\Log\UserSectorSignaturesLogInterface;

class SignatureGenerator
{

    /**
     * @param string                                $token
     * @param string                                $secret
     * @param UserSectorSignaturesLogInterface|null $log
     */
    public function __construct(
        private string $token,
        private string $secret,
        private UserSectorSignaturesLogInterface|null $log = null
    ) {}

    /**
     * @param string $token
     * @param Sector $sector
     * @param int    $user_id
     *
     * @return array
     */
    #[ArrayShape([
        'sectorHash' => "string",
        'userSectorHash' => "string",
        'signatureBlowfish' => "string",
        'address' => "mixed|String"
    ])]
    public function generateSectorSignature(string $token, Sector $sector, int $user_id): array
    {
        $addressData = $this->generateSectorAddress($token, $sector, $user_id);
        $sectorHash = $this->getSectorHash($sector->getPuzzleId(), $sector->getSectorNumber());
        $userSectorHash = $this->getUserSectorHash($sector->getPuzzleId(), $sector->getSectorNumber(), $user_id);

        $this->log?->addSector($userSectorHash, $sector->getSectorNumber(), $user_id);


        $password = $this->getPassword($sectorHash, $addressData['privateHex']);
        return [
            'sectorHash' => $sectorHash,// for decryption by the server after receiving the private key
            'userSectorHash' => $userSectorHash, // to verify that the address is issued from your server
            'signatureBlowfish' => \password_hash($password, PASSWORD_BCRYPT), // public
            'address' => $addressData['address'] // public
        ];
    }

    /**
     *
     * We deliberately complicate the calculation of the hash in order
     * to avoid brute force by the creator of the pool, which has a sectorHash param!!!
     *
     * @param string $sectorHash
     * @param string $privateHex
     *
     * @return string
     */
    private function getPassword(string $sectorHash, string $privateHex): string
    {
        $password = hash('sha256', $sectorHash . $privateHex);
        $quantity = rand(5, 50);
        for ($i = 0; $i < $quantity; $i++) {
            $password = hash('sha256', $password);
        }
        return $password;
    }

    /**
     * @param string $token
     * @param Sector $sector
     * @param int    $user_id
     *
     * @return array
     * @throws \Exception
     */
    #[ArrayShape(['privateHex' => "string", 'address' => "String"])]
    public function generateSectorAddress(string $token, Sector $sector, int $user_id): array
    {
        if ($token !== $this->token) {
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
     * @param int $puzzleNumber
     * @param int $sectorNumber
     *
     * @return string
     */
    public function getSectorHash(int $puzzleNumber, int $sectorNumber): string
    {
        return hash('sha256', $this->secret . $puzzleNumber . $sectorNumber);
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
        return hash('sha256', $this->secret . $puzzleNumber . $sectorNumber . $user_id);
    }

    /**
     * @param int    $puzzleNumber
     * @param string $hash
     * @param int    $userId
     * @param int    $sectorExponent
     *
     * @return bool
     */
    public function checkSectorHash(int $puzzleNumber, string $hash, int $userId, int $sectorExponent = 40): bool
    {
        if ($this->log) {
            $data = $this->log->getSector($hash);
            if (!empty($data)) {
                return true;
            }
        }
        $countSectors = (int)(new Gmp(2))->pow($puzzleNumber - 1)->div((new Gmp(2))->pow($sectorExponent))->str();
        //  long!!!
        for ($i = 1; $i <= $countSectors; $i++) {
            if ($this->getUserSectorHash($puzzleNumber, $i, $userId) === $hash) {
                return true;
            }
        }
        return false;
    }
}