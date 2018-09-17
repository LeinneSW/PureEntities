<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use pocketmine\math\Facing;
use pocketmine\math\Vector3;

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
        $y = $target->y - $this->y + 0.0;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        $needJump = \false;
        if(!$this->interactTarget() && $diff !== 0.0/* && $this->onGround*/){
            $needJump = \true;
            $hasUpdate = \true;
            $ground = $this->onGround ? 0.12 : 0.004;
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
            (int) $x = ((($dx = $this->motion->x) > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX) + $dx * $tickDiff * 2),
            $this->y,
            (int) $z = ((($dz = $this->motion->z) > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ) + $dz * $tickDiff * 2)
        ));
        if(
            ($aabb = $block->getBoundingBox()) !== \null
            && $block->getSide(Facing::UP)->getBoundingBox() === \null
            && $block->getSide(Facing::UP, 2)->getBoundingBox() === \null
        ){
            if($aabb->maxY - $aabb->minY == 1){
                $this->motion->y = 0.32 * $tickDiff;
            }elseif(
                ($aabb->minY !== 0.5 || $this->y - (int) $this->y === 0.5)
                && $aabb->maxY - $aabb->minY === 0.5
            ){
                $this->motion->y = 0.08 * $tickDiff;
            }
            return \true;
        }
        return \false;
    }

}