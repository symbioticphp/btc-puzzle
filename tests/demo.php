<?php

include_once dirname(__DIR__) . '/src/BitcoinECDSA.php';
include_once dirname(__DIR__) . '/src/Config.php';
include_once dirname(__DIR__) . '/src/Gmp.php';
include_once dirname(__DIR__) . '/src/Range.php';
include_once dirname(__DIR__) . '/src/Log/UserSectorSignaturesLogInterface.php';
include_once dirname(__DIR__) . '/src/Log/FileUserSectorSignaturesLog.php';
include_once dirname(__DIR__) . '/src/Sector.php';
include_once dirname(__DIR__) . '/src/SignatureGenerator.php';
include_once dirname(__DIR__) . '/src/SignatureController.php';


$config = new \Symbiotic\BtcPuzzle\Config(
    [
        'secret' => 'efjiej34f9349gj4309hg4349tfh3044f3',
        'token' => 'Aich45vbdghbds',
        'log' => __DIR__ . '/log.txt'
    ]
);

$puzzleId = 66;

$sectorNumber = 32352000;

$user_id = 1;


$generator = new \Symbiotic\BtcPuzzle\SignatureGenerator($config->getToken(),$config->getSecret());

$sector = new \Symbiotic\BtcPuzzle\Sector($puzzleId, $sectorNumber);
$address = $generator->generateSectorAddress($config->getToken(), $sector, $user_id);
$hash = $generator->getSectorHash($puzzleId, $sectorNumber);


echo 'Sector num:   ' . $sectorNumber . PHP_EOL;
echo 'Sector start: ' . $sector->getStart()->str(16) . PHP_EOL;
echo 'Sector end:   ' . $sector->getEnd()->str(16) . PHP_EOL;
echo 'Sector range: ' . $sector->getRange()->range()->str(16) . PHP_EOL;
echo 'Sector hash: ' . $generator->getSectorHash($sector->getPuzzleId(), $sector->getSectorNumber()) . PHP_EOL;
echo 'Sector user hash: ' . $generator->getUserSectorHash(
        $sector->getPuzzleId(),
        $sector->getSectorNumber(),
        $user_id
    ) . PHP_EOL;

echo 'User:    ' . $user_id . PHP_EOL;
echo 'Priv:    ' . $address['privateHex'] . PHP_EOL;
echo 'Address: ' . $address['address'] . PHP_EOL;


$sectorRange = $sector->getRange();

echo PHP_EOL . 'Sector range puzzle: ' . $sectorRange->getPuzzleId() . PHP_EOL;
echo 'Sector range start:  ' . $sectorRange->getStart()->str(16) . PHP_EOL;
echo 'Sector range end:    ' . $sectorRange->getEnd()->str(16) . PHP_EOL;

$sectorids = $sectorRange->getSectorsNumbers();

echo 'Sector range sectors: ' . implode(',', $sectorids) . PHP_EOL;

$range = new \Symbiotic\BtcPuzzle\Range(
    $sector->getStart(),
    $sector->getStart()->add($sector->getRange()->range()->mul(4))->sub(1)
);

echo PHP_EOL . 'Range puzzle x4: ' . $range->getPuzzleId() . PHP_EOL;
echo 'Range start: ' . $range->getStart()->str(16) . PHP_EOL;
echo 'Range end: ' . $range->getEnd()->str(16) . PHP_EOL;
$sectorids = $range->getSectorsNumbers();

echo 'Range sectors: ' . implode(',', $sectorids) . PHP_EOL;


$query = [
    'puzzleId' =>(string) $puzzleId,
    'user_id' => (string)$user_id,
    'sectorNumber' =>(string) $sectorNumber,
    'token' => $config->getToken(),
    'action' => 'generateSignature'
];
$controller = new \Symbiotic\BtcPuzzle\SignatureController($config, $query);

echo PHP_EOL . PHP_EOL . 'Controller:' . PHP_EOL;
echo 'Sector response:  ' . $controller->dispatch() . PHP_EOL;

$query = [
    'puzzleId' => (string)$puzzleId,
    'userId' => '1',
    'hash' => $hash,
    'action' => 'checkSectorHash',

];
$controller = new \Symbiotic\BtcPuzzle\SignatureController($config, $query);
// long
echo 'Check sector hash: ' . $controller->dispatch() . PHP_EOL;

$query = [
    'token' => $config->getToken(),
    'action' => 'ping'
];
$controller = new \Symbiotic\BtcPuzzle\SignatureController($config, $query);
// long
echo "Check ping: '" . $controller->dispatch() . "'" . PHP_EOL;

