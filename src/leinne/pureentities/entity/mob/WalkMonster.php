<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use pocketmine\block\Block;
use pocketmine\math\Math;
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

        parent::entityBaseTick($tickDiff);

        if($this->isNeedJump($tickDiff)){
            $this->motion->y = 0.6;//$this->getJumpVelocity();
        }

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        if(!$this->interactTarget() && $this->isMovable() && $diff !== 0.0 && $this->onGround){
            $this->motion->x += $this->getSpeed() * 0.14 * $x * $tickDiff / $diff;
            $this->motion->z += $this->getSpeed() * 0.14 * $z * $tickDiff / $diff;
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90;
        $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return \true;
    }

    protected function isNeedJump(int $tickDiff) : bool{
        if(!$this->onGround){
            return \false;
        }

        $xWidth = ($dx = $this->motion->x) > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX;
        $zWidth = ($dz = $this->motion->z) > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ;
        $block = $this->getLevel()->getBlock(new Vector3(Math::ceilFloat($xWidth + $dx * $tickDiff * 2), $this->y, Math::ceilFloat($zWidth + $dz * $tickDiff * 2)));
        if(
            ($aabb = $block->getBoundingBox()) !== \null
            && $aabb->maxY - $aabb->minY <= 1
            && $block->getSide(Block::SIDE_UP)->getBoundingBox() === \null
            && $block->getSide(Block::SIDE_UP, 2)->getBoundingBox() === \null
        ){
            return \true;
        }
        return \false;
    }

}