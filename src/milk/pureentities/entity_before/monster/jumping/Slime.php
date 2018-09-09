<?php

namespace milk\pureentities\entity_before\monster\jumping;

use milk\pureentities\entity_before\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Slime extends JumpingMonster{
    const NETWORK_ID = 37;

    public $width = 1.2;
    public $height = 1.2;


    public function getName() : string{
        return 'Slime';
    }

    public function initEntity(){
        parent::initEntity();

        $this->speed = 0.8;
        $this->setMaxHealth(4);
        $this->setDamage([0, 2, 2, 3]);
    }

    public function attackEntity(Entity $player){
        // TODO
    }

    public function targetOption(Creature $creature, $distance){
        //TODO
        return \false;
    }
    
    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::SLIMEBALL, 0, \mt_rand(0, 2))];
        }
        return [];
    }
}