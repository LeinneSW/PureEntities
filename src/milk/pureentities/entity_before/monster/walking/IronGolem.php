<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;
use pocketmine\Player;

class IronGolem extends WalkingMonster{
    const NETWORK_ID = 20;

    public $width = 2.1;
    public $height = 2.8;
    public $eyeHeight = 2.5;

    public function initEntity(){
        $this->setMaxHealth(100);
        parent::initEntity();

        $this->speed = 0.8;
        $this->setFriendly(\true);
        $this->setDamage([0, 21, 21, 21]);
        $this->setMinDamage([0, 7, 7, 7]);
    }

    public function getName() : string{
        return 'IronGolem';
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 4){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
            $player->setMotion(new Vector3(0, 0.7, 0));
        }
    }

    public function targetOption(Creature $creature, $distance){
        if(!($creature instanceof Player)){
            return $creature->isAlive() && $distance <= 60;
        }
        return \false;
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
