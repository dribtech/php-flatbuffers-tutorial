<?php declare(strict_types=1);

namespace Drib;

require_once __DIR__ . '/../vendor/autoload.php';

// create test data
$spaceshipList = [];
for ($i = 0; $i < 1000; $i++) {
    $spaceshipList[] = [
        'name' => 'NCC-1701-a-' . $i,
        'color' => rand(11, 13),
        'shield' => rand(1, 255),
        'weapons' => [
            'Spatial torpedo' => rand(1, 255),
            'Photon torpedo' => rand(1, 255),
        ],
        'position' => [
            10,
            11,
            12,
        ],
        'cloakingDeviceName' => 'T\'Kuvma cloaking device',
        'cloakingDeviceStrength' => rand(1, 255),
    ];
}

// serialize
$data = json_encode($spaceshipList);

///////// unserialize
$unserializedSpaceshipList = json_decode($data, true);

/// compare
var_dump($spaceshipList === $unserializedSpaceshipList);
