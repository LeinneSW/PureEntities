<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\animal;

use pocketmine\math\Facing;
use pocketmine\math\Vector3;

abstract class WalkAnimal extends Animal{

    public function entityBaseTick(int $tickDiff = 1) : bool{
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
            $this->motion->x += $this->getSpeed() * $ground * $x * $tickDiff / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z * $tickDiff / $diff;
        }

        if($needJump){
            $hasUpdate = $this->checkJump($tickDiff) && !$hasUpdate ? \true : $hasUpdate;
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90.0;
        $this->pitch = $y === 0.0 ? $y : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return $hasUpdate;
    }

    protected function checkJump(int $tickDiff) : bool{
        $block = $this->getLevel()->getBlock(new Vector3(
            (int) ((($dx = $this->motion->x) > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX) + $dx * $tickDiff * 2),
            $this->y,
            (int) ((($dz = $this->motion->z) > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ) + $dz * $tickDiff * 2)
        ));
        if(($aabb = $block->getBoundingBox()) === \null || $block->getSide(Facing::UP, 2)->getBoundingBox() !== \null){
            return \false;
        }

        if(($up = $block->getSide(Facing::UP)->getBoundingBox()) === \null){
            if($aabb->maxY - $aabb->minY > 1 || $aabb->maxY === $this->y){ //울타리 or 반블럭 위
                return \false;
            }

            $this->motion->y = $aabb->maxY - $this->y === 0.5 ? 0.36 : 0.5;
            return \true;
        }elseif($up->maxY - $this->y === 1.0){ //반블럭 위에서 반블럭+한칸블럭 점프
            $this->motion->y = 0.52;
            return \true;
        }
        return \false;
    }

}