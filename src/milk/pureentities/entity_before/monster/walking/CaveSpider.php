<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class CaveSpider extends WalkingMonster{
    const NETWORK_ID = 40;

    public $width = 0.9;
    public $height = 0.8;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.3;
        $this->setMaxHealth(12);
        $this->setDamage([0, 2, 3, 3]);
    }

    public function getName() : string{
        return 'CaveSpider';
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, \mt_rand(0, 2))] : [];
    }

}
