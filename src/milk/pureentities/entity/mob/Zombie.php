<?php

declare(strict_types=1);

namespace milk\pureentities\entity\mob;

use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

class Zombie extends Monster{

    const NETWORK_ID = 32;

    public $width = 0.5;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(0.9);
    }

    public function getName() : string{
        return 'Zombie';
    }

    public function interactTarget() : bool{
        ++$this->attackDelay;

        if(
            !($this->target instanceof Creature)
            || \abs($this->x - $this->target->x) > 0.4
            || \abs($this->z - $this->target->z) > 0.4
            || \abs($this->y - $this->target->y) > 0.001
        ){
            return \false;
        }

        if($this->attackDelay >= 15){
            $ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $this->target->attack($ev);

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
    }

}