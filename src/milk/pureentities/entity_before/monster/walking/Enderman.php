<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class Enderman extends WalkingMonster{
    const NETWORK_ID = 38;

    public $width = 0.72;
    public $height = 2.8;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.21;
        $this->setDamage([0, 4, 7, 10]);
    }

    public function getName() : string{
        return 'Enderman';
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::END_STONE, 0, 1)];
        }
        return [];
    }

}
