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
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        $needJump = \false;
        if(!$this->interactTarget() && $diff !== 0.0 && $this->onGround){
            $needJump = \true;
            $hasUpdate = \true;
            $this->motion->x += $this->getSpeed() * 0.12 * $x * $tickDiff / $diff;
            $this->motion->z += $this->getSpeed() * 0.12 * $z * $tickDiff / $diff;
        }

        if($needJump){
            $hasUpdate = !$hasUpdate && $this->checkJump($tickDiff) ? \true : $hasUpdate;
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90;
        $this->pitch = $y === 0.0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return $hasUpdate;
    }

    protected function checkJump(int $tickDiff) : bool{
        $xWidth = ($dx = $this->motion->x) > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX;
        $zWidth = ($dz = $this->motion->z) > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ;
        $block = $this->getLevel()->getBlock(new Vector3((int) ($xWidth + $dx * $tickDiff * 2), $this->y, (int) ($zWidth + $dz * $tickDiff * 2)));
        if(
            ($aabb = $block->getBoundingBox()) !== \null
            && $block->getSide(Facing::UP)->getBoundingBox() === \null
            && $block->getSide(Facing::UP, 2)->getBoundingBox() === \null
        ){
            if($aabb->maxY - $aabb->minY == 1){
                $this->motion->y = 0.5 * $tickDiff;
            }else if((int) $aabb->minY !== $aabb->minY && $aabb->maxY - $aabb->minY == 0.5){
                $this->motion->y = 0.24 * $tickDiff;
            }
            return \true;
        }
        return \false;
    }

}