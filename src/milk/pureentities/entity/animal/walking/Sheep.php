<?php

namespace milk\pureentities\entity\animal\walking;

use milk\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Sheep extends WalkingAnimal{
    const NETWORK_ID = 13;

    public $width = 1.45;
    public $height = 1.2;
    public $eyeHeight = 0.9;

    public function getName() : string{
        return "Sheep";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(8);
    }

    public function targetOption(Creature $creature, $distance){
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() === Item::SEEDS && $distance <= 49;
        }
        return \false;
    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::WOOL, mt_rand(0, 15), 1)];
        }
        return [];
    }

}