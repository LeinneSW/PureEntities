<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\block\Water;
use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Math;
use pocketmine\math\Vector3;

class Zombie extends WalkingMonster implements Ageable{
    const NETWORK_ID = 32;

    public $width = 0.72;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.1;
        $this->setDamage([0, 3, 4, 6]);
        if($this->getDataFlag(self::DATA_FLAG_BABY, 0) === null){
            $this->setDataFlag(self::DATA_FLAG_BABY, self::DATA_TYPE_BYTE, 0);
        }
    }

    public function getName() : string{
        return "Zombie";
    }

    public function isBaby() : bool{
        return $this->getDataFlag(self::DATA_FLAG_BABY, 0);
    }

    public function setHealth(float $amount){
        parent::setHealth($amount);

        if($this->isAlive()){
            if(15 < $this->getHealth()){
                $this->setDamage([0, 2, 3, 4]);
            }else if(10 < $this->getHealth()){
                $this->setDamage([0, 3, 4, 6]);
            }else if(5 < $this->getHealth()){
                $this->setDamage([0, 3, 5, 7]);
            }else{
                $this->setDamage([0, 4, 6, 9]);
            }
        }
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
            && !($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Water)
        ){
            $this->setOnFire(1);
        }
        return $hasUpdate;
    }

    public function getDrops() : array{
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::FEATHER, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = Item::get(Item::POTATO, 0, 1);
                    break;
            }
        }
        return $drops;
    }
}
