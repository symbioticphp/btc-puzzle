<?php

declare(strict_types=1);

namespace Symbiotic\BtcPuzzle\Log;


interface UserSectorSignaturesLogInterface
{
    /**
     * @param string $userSectorHash
     * @param int    $sectorNumber
     * @param int    $user_id
     *
     * @return bool
     */
    public function addSector(
        string $userSectorHash,
        int $sectorNumber,
        int $user_id
    ): bool;

    /**
     * @param string $userSectorHash
     *
     * @return array|null
     */
    public function getSector(string $userSectorHash): ?array;
}