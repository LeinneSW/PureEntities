<?php

namespace milk\pureentities\entity_before\animal\walking;

use milk\pureentities\entity_before\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Rabbit extends WalkingAnimal{
    const NETWORK_ID = 18;

    public $width = 0.4;
    public $height = 0.75;

    
    public function getName() : string{
        return 'Rabbit';
    }

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.2;
        $this->setMaxHealth(3);
    }

    public function targetOption(Creature $creature, $distance){
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() === Item::SEEDS && $distance <= 49;
        }
        return \false;
    }

    public function getDrops() : array{
        return [];
    }

}