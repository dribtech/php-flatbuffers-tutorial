<?php declare(strict_types=1);

namespace Drib;

require_once __DIR__ . '/../vendor/autoload.php';

use Google\FlatBuffers\ByteBuffer;
use Schema\Game\CloakingDevice;
use Schema\Game\Color;
use Schema\Game\Equipment;
use Schema\Game\Spaceship;
use Google\FlatBuffers\FlatbufferBuilder;
use Schema\Game\Vector3D;
use Schema\Game\Weapon;
use Schema\Game\SpaceshipList;


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
$builder = new FlatbufferBuilder(1 * 1024 * 1024);

$fbSpaceshipListOffset = createFlatbufferSpaceshipList($builder, $spaceshipList);
$builder->finish($fbSpaceshipListOffset);

$data = $builder->dataBuffer()->data();

unset($builder);

///////// unserialize

$buffer = ByteBuffer::wrap($data);
$fbSpaceshipList = SpaceshipList::getRootAsSpaceshipList($buffer);

$unserializedSpaceshipList = readFlatbufferSpaceshipList($fbSpaceshipList);

/// compare
var_dump($spaceshipList === $unserializedSpaceshipList);

function createFlatbufferSpaceshipList(
    FlatbufferBuilder $builder,
    array $spaceshipList
): int {
    $fbSpaceshipOffsets = [];
    foreach ($spaceshipList as $spaceship) {
        $fbSpaceshipOffsets[] = createFlatbufferSpaceship(
            $builder,
            $spaceship['name'],
            $spaceship['color'],
            $spaceship['shield'],
            $spaceship['weapons'],
            $spaceship['position'],
            $spaceship['cloakingDeviceName'],
            $spaceship['cloakingDeviceStrength']
        );
    }

    $fbSpaceshipVector = SpaceshipList::createListVector($builder, $fbSpaceshipOffsets);

    SpaceshipList::startSpaceshipList($builder);
    SpaceshipList::addList($builder, $fbSpaceshipVector);

    return SpaceshipList::endSpaceshipList($builder);
}

function createFlatbufferSpaceship(
    FlatbufferBuilder $builder,
    string $name,
    int $color,
    int $shield,
    array $weapons,
    array $position,
    string $cloakingDeviceName,
    int $cloakingDeviceStrength
): int {
    // create name
    $spaceshipNameOffset = $builder->createString($name);

    // create weapons
    $individualWeaponsOffsets = [];
    foreach ($weapons as $weaponName => $weaponDamage) {

        $weaponNameOffset = $builder->createString($weaponName);

        Weapon::startWeapon($builder);
        Weapon::addName($builder, $weaponNameOffset);
        Weapon::addDamage($builder, $weaponDamage);
        $individualWeaponsOffsets[] = Weapon::endWeapon($builder);
    }

    $weaponsOffset = Spaceship::createWeaponsVector($builder, $individualWeaponsOffsets);

    // creating equipment
    $cloakingDeviceNameOffset = $builder->createString($cloakingDeviceName);
    CloakingDevice::startCloakingDevice($builder);
    CloakingDevice::addName($builder, $cloakingDeviceNameOffset);
    CloakingDevice::addStrength($builder, $cloakingDeviceStrength);
    $cloakingDeviceOffset = CloakingDevice::endCloakingDevice($builder);

    // create spaceship
    Spaceship::startSpaceship($builder);

    // create position - important, structs must be stored inline - between Spaceship::startSpaceship and Spaceship::startSpaceship
    $position = Vector3D::createVector3D(
        $builder,
        $position[0],
        $position[1],
        $position[2]
    );

    Spaceship::addPosition($builder, $position);
    Spaceship::addShield($builder, $shield);
    Spaceship::addName($builder, $spaceshipNameOffset);

    $fbColor = null;
    switch ($color) {
        case 11:
            $fbColor = Color::SpaceGray;
            break;
        case 12:
            $fbColor = Color::SpaceWhite;
            break;
        case 13:
        default:
            $fbColor = Color::SpaceBlack;
            break;
    }

    Spaceship::addColor($builder, $fbColor);
    Spaceship::addWeapons($builder, $weaponsOffset);
    Spaceship::addEquippedType($builder, Equipment::CloakingDevice);
    Spaceship::addEquipped($builder, $cloakingDeviceOffset);

    return Spaceship::endSpaceship($builder);
}

function readFlatbufferSpaceshipList(SpaceshipList $fbSpaceshipList): array
{
    $list = [];

    $len = $fbSpaceshipList->getListLength();
    for ($i = 0; $i < $len; $i++) {
        $list[] = readFlatbufferSpaceship($fbSpaceshipList->getList($i));
    }

    return $list;
}

function readFlatbufferSpaceship(Spaceship $fbSpaceship): array
{
    // read position
    $fbPosition = $fbSpaceship->getPosition();
    $position = [
        $fbPosition->GetX(),
        $fbPosition->GetY(),
        $fbPosition->GetZ(),
    ];

    // read color
    switch ($fbSpaceship->getColor()) {
        case Color::SpaceGray:
            $color = 11;
            break;
        case Color::SpaceWhite;
            $color = 12;
            break;
        case Color::SpaceBlack:
        default:
            $color = 13;
            break;
    }

    // read weapons
    $weapons = [];
    $fbWeaponsLength = $fbSpaceship->getWeaponsLength();
    for ($i = 0; $i < $fbWeaponsLength; $i++) {
        $weapons[$fbSpaceship->getWeapons($i)->getName()] = $fbSpaceship->getWeapons($i)->getDamage();
    }

    // read equipment
    $cloakingDeviceName = '';
    $cloakingDeviceStrength = 0;
    switch ($fbSpaceship->getEquippedType()) {
        case Equipment::CloakingDevice:
            /** @var CloakingDevice $cloakingDevice */
            $cloakingDevice = $fbSpaceship->getEquipped(new CloakingDevice());
            $cloakingDeviceName = $cloakingDevice->getName();
            $cloakingDeviceStrength = $cloakingDevice->getStrength();
    }

    return [
        'name' => $fbSpaceship->getName(),
        'color' => (int) $color,
        'shield' => (int) $fbSpaceship->getShield(),
        'weapons' => $weapons,
        'position' => $position,
        'cloakingDeviceName' => $cloakingDeviceName,
        'cloakingDeviceStrength' => (int) $cloakingDeviceStrength,
    ];
}
