<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use pocketmine\block\Block;
use pocketmine\block\Liquid;
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

        /*if(!$this->checkJump($tickDiff)){
            if($this->onGround){
                $this->motionY = 0;
            }elseif($this->motion->y > -$this->gravity * 4){
                if(!($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.9), Math::floorFloat($this->z))) instanceof Liquid)){
                    $this->motion->y -= $this->gravity * $tickDiff;
                }
            }else{
                $this->motion->y -= $this->gravity * $tickDiff;
            }
        }*/

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        if($diff !== 0.0 && !$this->interactTarget() && $this->onGround){
            $this->motion->x += $this->getSpeed() * 0.1 * $x / $diff;
            $this->motion->z += $this->getSpeed() * 0.1 * $z / $diff;
        }

        $this->yaw = \rad2deg(\atan2($z, $x)) - 90;
        $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return \true;
    }

    /*protected function checkJump(int $tickDiff) : bool{
        $dx = $this->motion->x;
        $dz = $this->motion->z;

        if($this->motion->y == $this->gravity * 2){
            return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.3), Math::floorFloat($this->z))) instanceof Liquid;
        }else{
            if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.9), Math::floorFloat($this->z))) instanceof Liquid){
                $this->motion->y = $this->gravity * 2 * $tickDiff;
                return \true;
            }
        }

        if(!$this->onGround){
            return \false;
        }

        $xWidth = $dx > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX;
        $zWidth = $dz > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ;
        $block = $this->getLevel()->getBlock(new Vector3(Math::ceilFloat($xWidth + $dx * 2), $this->y, Math::ceilFloat($zWidth + $dz * 2)));
        if(
            $block->getId() !== Block::AIR && ($aabb = $block->getBoundingBox()) !== \null
            && $aabb->maxY - $aabb->minY <= 1
            && $block->getSide(Block::SIDE_UP)->getBoundingBox() === \null
            && $block->getSide(Block::SIDE_UP, 2)->getBoundingBox() === \null
        ){
            if($this->motion->y < $this->gravity * 4 * $tickDiff){
                $this->motion->y = $this->gravity * 4 * $tickDiff;
            }else{
                $this->motion->y += $this->gravity * $tickDiff;
            }
            return \true;
        }
        return \false;
    }*/

}