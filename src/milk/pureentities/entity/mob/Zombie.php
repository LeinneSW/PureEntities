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
        $this->setDamages([0, 3, 4, 6]);
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->server->getDifficulty() < 1){
            $this->close();
            return \false;
        }

        if($this->closed){
            return \false;
        }

        ++$this->attackDelay;

        parent::entityBaseTick($tickDiff);

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        if($diff === 0){
            return \true;
        }

        $calX = $x / $diff;
        $calZ = $z / $diff;

        if(!$this->interactTarget() && $this->onGround){
            $this->motion->x += $this->getSpeed() * 0.12 * $calX;
            $this->motion->z += $this->getSpeed() * 0.12 * $calZ;
        }

        $this->yaw = -atan2($calX, $calZ) * 180 / M_PI;
        $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return \true;
    }

    public function getName() : string{
        return 'Zombie';
    }

    public function interactTarget() : bool{
        if(
            !($this->target instanceof Creature)
            || \abs($this->x - $this->target->x) > 0.4
            || \abs($this->z - $this->target->z) > 0.4
            || \abs($this->y - $this->target->y) > 0.001
        ){
            return \false;
        }

        if($this->attackDelay >= 15 && ($damage = $this->getResultDamage()) > 0){
            $ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $this->target->attack($ev);

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
    }

}