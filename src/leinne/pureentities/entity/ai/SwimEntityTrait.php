<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

trait SwimEntityTrait{

    /**
     * 타겟과의 상호작용
     *
     * @return bool
     */
    //public abstract function interactTarget() : bool;

    /**
     * @see Entity::entityBaseTick()
     *
     * @param int $tickDiff
     *
     * @return bool
     */
    /*public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->closed){
            return \false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->isMovable()){
            return $hasUpdate;
        }

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = 0.0 + $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        $needJump = \false;
        if(!$this->interactTarget() && $diff !== 0.0){
            $hasUpdate = \true;
            $needJump = $this->onGround;
            $ground = $this->onGround ? 0.12 : 0.002;
            $this->motion->x += $this->getSpeed() * $ground * $x / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z / $diff;
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90.0;
        $this->pitch = $y === 0.0 ? $y : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return $hasUpdate;
    }*/

}