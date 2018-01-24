<?php

namespace milk\pureentities\entity\animal;

use milk\pureentities\entity\FlyingEntity;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class FlyingAnimal extends FlyingEntity implements Animal{

    public function initEntity(){
        parent::initEntity();

        $this->speed = 0.7;
    }

    public function isBaby() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_BABY);
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->canBreathe()){
            $hasUpdate = \true;
            $this->doAirSupplyTick($tickDiff);
        }else{
            $this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
        }
        return $hasUpdate;
    }

    public function onUpdate(int $currentTick) : bool{
        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return \false;
            }
            return \true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if($target instanceof Player){
            if($this->distance($target) <= 2){
                $this->pitch = 22;
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        }elseif(
            $target instanceof Vector3
            && $this->distanceSquared($target) <= 1
        ){
            $this->moveTime = 0;
        }
        return \true;
    }

}
