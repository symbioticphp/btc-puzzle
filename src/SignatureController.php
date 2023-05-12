<?php

declare(strict_types=1);

namespace Symbiotic\BtcPuzzle;


use Symbiotic\BtcPuzzle\Log\FileUserSectorSignaturesLog;
use Symbiotic\BtcPuzzle\Log\UserSectorSignaturesLogInterface;

class SignatureController
{

    private SignatureGenerator $signatureGenerator;

    private array $queryParams;

    /**
     * @param Config $config
     * @param array  $queryParams
     */
    public function __construct(private Config $config, array $queryParams)
    {
        $this->signatureGenerator = new SignatureGenerator(
            $config->getToken(),
            $config->getSecret(),
            $this->initLog()
        );

        $this->queryParams = $queryParams;
    }


    /**
     * @return string
     */
    final public function generateSignature(): string
    {
        $puzzleId = (int)$this->getParam('puzzleId', '66');
        $sectorNumber = (int)$this->getParam('sectorNumber');
        $sectorExponent = (int)$this->getParam('sectorExponent', '40');
        $token = $this->getParam('token');

        $user_id = (int)$this->getParam('user_id');
        if (empty($user_id)) {
            return 'User id is required!';
        }
        try {
            $sector = new Sector($puzzleId, $sectorNumber, $sectorExponent);
            $response = $this->signatureGenerator->generateSectorSignature($token, $sector, $user_id);
        } catch (\Exception $e) {
            return \json_encode(['error' => $e->getMessage()]);
        }

        return \json_encode($response);
    }

    /**
     * @return string
     */
    final public function ping(): string
    {
        $token = $this->getParam('token');
        if ($token !== $this->config->getToken()) {
            return 'Error token';
        }
        $puzzleId = (int)$this->getParam('puzzleId', '66');
        $sectorNumber = (int)$this->getParam('sectorNumber', '777');
        try {
            // Для теста меняем секретный ключ на тестовый
            $generator = new SignatureGenerator(
                $this->config->getToken(),
                '________Symbiotic__________',
            );

            $response = $generator->generateSectorSignature($token, new Sector($puzzleId, $sectorNumber), 1);
        } catch (\Exception $e) {
            return 'Error: ' . htmlspecialchars($e->getMessage());
        }

        return json_encode($response);
    }

    /**
     * @return string
     */
    final public function checkSectorHash(): string
    {
        $userSectorHash = (string)$this->getParam('userSectorHash');
        $userId = (int)$this->getParam('userId');
        $puzzleId = (int)$this->getParam('puzzleId', '66');
        $sectorExponent = (int)$this->getParam('exponent', '40');
        if (empty($userSectorHash)) {
            return 'Hash param is required!';
        }
        if ($puzzleId > 74 || $puzzleId < 66) {
            return 'Puzzle ' . $puzzleId . ' is not supported!';
        }
        if ($sectorExponent < 40 || $sectorExponent > 50) {
            return 'Exponent ' . $sectorExponent . ' is not supported!';
        }
        $isSector = $this->signatureGenerator->checkSectorHash($puzzleId, $userSectorHash, $userId, $sectorExponent);

        if ($isSector) {
            return 'Sector hash [' . htmlspecialchars($userSectorHash) . '] is confirmed!';
        } else {
            return 'Sector hash [' . htmlspecialchars($userSectorHash) . '] is Wrong!';
        }
    }

    /**
     * @return UserSectorSignaturesLogInterface|null
     * @throws \Exception
     */
    protected function initLog(): ?UserSectorSignaturesLogInterface
    {
        $logPath = $this->config->getLogPath();
        if (!empty($logPath)) {
            return new FileUserSectorSignaturesLog($logPath);
        }

        return null;
    }

    /**
     * @param string      $name
     * @param string|null $default
     *
     * @return string|null
     */
    protected function getParam(string $name, string $default = null): ?string
    {
        return $this->queryParams[$name] ?? $default;
    }

    final public function dispatch(): string
    {
        $action = (string)$this->getParam('action');

        switch ($action) {
            case 'generateSignature':
                return $this->generateSignature();
            case 'checkSectorHash':
                return $this->checkSectorHash();
            case 'ping':
                return $this->ping();
            default:
                return 'Action ' . htmlspecialchars($action) . ' not found!';
        }
    }
}
