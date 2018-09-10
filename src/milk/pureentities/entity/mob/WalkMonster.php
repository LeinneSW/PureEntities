<?php

declare(strict_types=1);

namespace milk\pureentities\entity\mob;

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

        $hasUpdate = parent::entityBaseTick($tickDiff);

        /*if($this->checkJump()){
            $this->jump();
        }*/

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        if($diff === 0){
            return $hasUpdate;
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

    /*protected function checkJump($tickDiff, $dx, $dz){
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

        $xWidth = $dx > 0 ? $this->width / 2 : -($this->width / 2);
        $zWidth = $dz > 0 ? $this->width / 2 : -($this->width / 2);
        $block = $this->getLevel()->getBlock(new Vector3(Math::ceilFloat($this->x + $xWidth + $dx * 2), $this->y, Math::ceilFloat($this->z + $zWidth + $dz * 2)));
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