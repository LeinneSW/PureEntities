<?php

namespace milk\pureentities\entity\projectile;

use pocketmine\entity\projectile\Throwable;

class LargeFireBall extends Throwable{
    const NETWORK_ID = self::LARGE_FIREBALL;

    public $width = 0.5;
    public $height = 0.5;

    public $fireTicks = 1200;

    protected $damage = 4;

}