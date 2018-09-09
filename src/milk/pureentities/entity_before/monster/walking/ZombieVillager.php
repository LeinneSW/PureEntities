<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class ZombieVillager extends WalkingMonster{
    const NETWORK_ID = 44;

    public $width = 0.72;
    public $height = 1.8;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.1;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function getName() : string{
        return 'ZombieVillager';
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
            switch(\mt_rand(0, 2)){
                case 0:
                    return [Item::get(Item::FEATHER, 0, 1)];
                case 1:
                    return [Item::get(Item::CARROT, 0, 1)];
                case 2:
                    return [Item::get(Item::POTATO, 0, 1)];
            }
        }
        return [];
    }

}
