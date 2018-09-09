<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class Silverfish extends WalkingMonster{
    const NETWORK_ID = 39;

    public $width = 0.4;
    public $height = 0.2;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.4;
        $this->setMaxDamage(8);
        $this->setDamage([0, 1, 1, 1]);
    }

    public function getName() : string{
        return 'Silverfish';
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        return [];
    }

}
