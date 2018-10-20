<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use leinne\pureentities\entity\ai\EntityAI;

abstract class WalkMonster extends Monster{

    public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->server->getDifficulty() < 1){
            $this->close();
            return \false;
        }

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
            $ground = $this->onGround ? 0.12 : 0.008;
            $this->motion->x += $this->getSpeed() * $ground * $x / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z / $diff;
        }

        if($needJump){
            switch(EntityAI::checkJumpState(
                $this,
                (int) (($this->motion->x > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX) + $this->motion->x),
                (int) (($this->motion->z > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ) + $this->motion->z)
            )){
                case EntityAI::JUMP_BLOCK:
                    $hasUpdate = \true;
                    $this->motion->y += 0.52;
                    break;
                case EntityAI::JUMP_SLAB:
                    $hasUpdate = \true;
                    $this->motion->y += 0.36;
                    break;
            }
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90.0;
        $this->pitch = $y === 0.0 ? $y : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return $hasUpdate;
    }

}