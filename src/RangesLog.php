<?php


namespace Symbiotic\BtcPuzzle;


class RangesLog
{

    private string $filePath;

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!\file_exists($filePath)) {
            \mkdir(\dirname($filePath), 0777, true);
            \file_put_contents($filePath, '');
            \clearstatcache(true, $filePath);
        }
        if (!\is_writable($filePath)) {
            throw new \Exception('Log is not Writeable!');
        }
    }

    /**
     * @param string $hash
     *
     * @return bool
     * @throws \Exception
     */
    public function getSectorData(string $hash): ?array
    {
        $fp = @fopen($this->filePath, "r");
        if (!\is_resource($fp)) {
            throw new \Exception('Log is not readable!');
        }
        while (($data = \fgetcsv($fp, 1000, ";")) !== false) {
            if (\strpos($data[0], $hash) !== false) {
                return $data;
            }
        }
        if (!\feof($fp)) {
            throw new \Exception('fgets() error!');
        }
        \fclose($fp);

        return null;
    }

    /**
     * @param string $sectorHash
     * @param int    $sectorNumber
     * @param int    $user_id
     *
     * @return bool
     */
    public function writeSector(string $sectorHash, int $sectorNumber, int $user_id): bool
    {
        return !empty(\file_put_contents($this->filePath, $sectorHash . ';' . $sectorNumber . ';' . $user_id . ';' . PHP_EOL, FILE_APPEND));
    }
}