<?php

declare(strict_types=1);

namespace Symbiotic\BtcPuzzle\Log;


class FileUserSectorSignaturesLog implements UserSectorSignaturesLogInterface
{

    private string $filePath;

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!\file_exists($filePath)) {
            if (!\is_dir(\dirname($filePath))) {
                \mkdir(\dirname($filePath), 0777, true);
            }
            \file_put_contents($filePath, '');
            \clearstatcache(true, $filePath);
        }
        if (!\is_writable($filePath)) {
            throw new \Exception('Log is not Writeable!');
        }
    }

    /**
     * @param string $userSectorHash
     *
     * @return array|null
     * @throws \Exception
     */
    public function getSector(string $userSectorHash):? array
    {
        $fp = @fopen($this->filePath, "r");
        if (!\is_resource($fp)) {
            throw new \Exception('Log is not readable!');
        }
        while (($data = \fgetcsv($fp, 4000, ";")) !== false) {
            if (\strpos($data[0], $userSectorHash) !== false) {
                return [
                    'userSectorHash' => $data[0],
                    'sectorHash' => $data[1],
                    'sectorNumber' => $data[2],
                    'userId' => $data[3],
                ];
            }
        }
        if (!\feof($fp)) {
            throw new \Exception('fgets() error!');
        }
        \fclose($fp);

        return null;
    }


    /***
     * @param string $userSectorHash
     * @param int    $sectorNumber
     * @param int    $user_id
     *
     * @return bool
     */
    public function addSector(string $userSectorHash,  int $sectorNumber, int $user_id): bool
    {
        return !empty(
        \file_put_contents(
            $this->filePath,
            $userSectorHash . ';' . $sectorNumber . ';' . $user_id . ';' . PHP_EOL,
            FILE_APPEND
        )
        );
    }
}