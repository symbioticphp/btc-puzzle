<?php


namespace Symbiotic\BtcPuzzle;


class Config
{
    private array $data;

    public function __construct(array $config)
    {
        if (empty($config['secret']) || strlen($config['secret']) < 20) {
            throw new \Exception('Secret is small!');
        }
        if (empty($config['token'])) {
            throw new \Exception('Token is required!');
        }

        $this->data = $config;
    }


    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->data['token'];
    }

    /**
     * @return string|null
     */
    public function getLogPath(): ?string
    {
        return $this->data['log'] ?? null;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->data['secret'];
    }

}