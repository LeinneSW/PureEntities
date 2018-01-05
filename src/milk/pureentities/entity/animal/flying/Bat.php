<?php

namespace milk\pureentities\entity\animal\walking;

use milk\pureentities\entity\animal\FlyingAnimal;
use pocketmine\entity\Creature;

class Bat extends FlyingAnimal{
    //TODO: This isn't implemented yet
    const NETWORK_ID = 13;

    public $width = 0.3;
    public $height = 0.3;

    public function getName() : string{
        return "Bat";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(6);
    }

    public function targetOption(Creature $creature, $distance){
        return \false;
    }

    public function getDrops() : array{
        return [];
    }

}
