<?php

namespace Symbiotic\BtcPuzzle;


class SignatureController
{

    private SignatureGenerator $generator;

    private array $queryParams;

    private Config $config;

    public function __construct(Config $config, array $queryParams)
    {
        $this->config = $config;
        $this->generator = new SignatureGenerator($config);
        $this->queryParams = $queryParams;
    }

    public function generateSignature(): string
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
            $response = $this->generator->generateSectorSignature($token, $sector, $user_id);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return json_encode($response);
    }

    public function ping(): string
    {
        $token = $this->getParam('token');
        if ($token !== $this->config->getToken()) {
            return 'Error token';
        }
        $puzzleId = (int)$this->getParam('puzzleId', '66');
        $sectorNumber = (int)$this->getParam('sectorNumber','777');
        try {
            // Для теста меняем секретный ключ на тестовый
            $generator = new SignatureGenerator(
                new Config(
                    [
                        'secret' => '________Symbiotic__________', // Меняем секретный ключ на тестовый
                        'token' => $this->config->getToken()
                    ]
                )
            );

            $response = $generator->generateSectorSignature($token, new Sector($puzzleId, $sectorNumber), 1);
        } catch (\Exception $e) {
            return 'Error: ' . htmlspecialchars($e->getMessage());
        }

        return json_encode($response);
    }

    /**
     * @return string
     * @todo check from log
     */
    public function checkSectorHash(): string
    {
        $hash = (string)$this->getParam('hash');
        $userId = (int)$this->getParam('userId');
        $puzzleId = (int)$this->getParam('puzzleId', '66');
        $sectorExponent = (int)$this->getParam('exponent', '40');
        if (empty($hash)) {
            return 'Hash param is required!';
        }
        if ($puzzleId > 74 || $puzzleId < 66) {
            return 'Puzzle ' . $puzzleId . ' is not supported!';
        }
        if ($sectorExponent < 40 || $sectorExponent > 50) {
            return 'Exponent ' . $sectorExponent . ' is not supported!';
        }
        $isSector = $this->generator->checkSectorHash($puzzleId, $hash, $userId, $sectorExponent);

        if ($isSector) {
            return 'Sector hash [' . htmlspecialchars($hash) . '] is confirmed';
        } else {
            return 'Sector hash [' . htmlspecialchars($hash) . '] is Wrong!';
        }
    }

    private function getParam(string $name, string $default = null): ?string
    {
        return $this->queryParams[$name] ?? $default;
    }


    public function dispatch(): string
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
