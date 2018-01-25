<?php

namespace milk\pureentities\entity;

use milk\pureentities\entity\animal\Animal;
use milk\pureentities\entity\monster\walking\PigZombie;
use pocketmine\block\Block;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Liquid;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;

abstract class WalkingEntity extends EntityBase{

    protected function checkTarget(){
        if($this->attackTime > 0){
            return;
        }

        if($this->followTarget != null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            return;
        }

        $option = \true;
        $target = $this->target;
        if(!($target instanceof Creature) or !($option = $this->targetOption($target, $this->distanceSquared($target)))){
            if(!$option) $this->target = \null;

            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                $distance = $this->distanceSquared($creature);
                if(
                    $creature === $this
                    || !($creature instanceof Creature)
                    || $creature instanceof Animal
                    || $creature instanceof EntityBase && $creature->isFriendly() === $this->isFriendly()
                    || $distance > $near or !$this->targetOption($creature, $distance)
                ){
                    continue;
                }

                if(
                    $distance <= 100
                    && $this instanceof PigZombie && $this->isAngry()
                    && $creature instanceof PigZombie && !$creature->isAngry()
                ){
                    $creature->setAngry(1000);
                }

                $near = $distance;
                $this->target = $creature;
            }
        }

        if($this->target instanceof Creature && $this->target->isAlive()){
            return;
        }

        if($this->moveTime <= 0 or !($this->target instanceof Vector3)){
            $x = mt_rand(20, 100);
            $z = mt_rand(20, 100);
            $this->moveTime = mt_rand(300, 1200);
            $this->target = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    protected function checkJump($tickDiff, $dx, $dz){
        if($this->motionY == $this->gravity * 2){
            return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.3), Math::floorFloat($this->z))) instanceof Liquid;
        }else{
            if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.9), Math::floorFloat($this->z))) instanceof Liquid){
                $this->motionY = $this->gravity * 2 * $tickDiff;
                return \true;
            }
        }

        if(!$this->onGround || $this->stayTime > 0){
            return \false;
        }

        $xWidth = $dx > 0 ? $this->width / 2 : -($this->width / 2);
        $zWidth = $dz > 0 ? $this->width / 2 : -($this->width / 2);
        $block = $this->getLevel()->getBlock(new Vector3(Math::ceilFloat($this->x + $xWidth + $dx * 2), $this->y, Math::ceilFloat($this->z + $zWidth + $dz * 2)));
        if(
            $block->getId() !== Block::AIR && !$block->canPassThrough() && (($aabb = $block->getBoundingBox())->maxY - $aabb->minY) <= 1
            && $block->getSide(Block::SIDE_UP)->canPassThrough()
            && $block->getSide(Block::SIDE_UP, 2)->canPassThrough()
        ){
            if($block instanceof Fence || $block instanceof FenceGate){
                $this->motionY = $this->gravity* $tickDiff;
            }else if($this->motionY <= $this->gravity * 4 * $tickDiff){
                $this->motionY = $this->gravity * 4 * $tickDiff;
            }else{
                $this->motionY += $this->gravity * 0.25 * $tickDiff;
            }
            return \true;
        }
        return \false;
    }

    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return null;
        }

        if($this->attackTime > 0){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.2 * $tickDiff;
            $this->updateMovement();
            return null;
        }

        if($this->followTarget !== \null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            $x = $this->followTarget->x - $this->x;
            $y = $this->followTarget->y - $this->y;
            $z = $this->followTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
            $this->pitch = $y === 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $before = $this->target;
        $this->checkTarget();
        if($this->target instanceof Creature or $before !== $this->target){
            $x = $this->target->x - $this->x;
            $y = $this->target->y - $this->y;
            $z = $this->target->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
            $this->pitch = $y === 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $isJump = $this->checkJump($tickDiff, $dx, $dz);
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motionY * $tickDiff, 0);
        }else{
            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motionY * $tickDiff, $dz);
            $af = new Vector2($this->x, $this->z);

            if(($be->x !== $af->x || $be->y !== $af->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
            if($this->onGround){
                $this->motionY = 0;
            }elseif($this->motionY > -$this->gravity * 4){
                if(!($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.9), Math::floorFloat($this->z))) instanceof Liquid)){
                    $this->motionY -= $this->gravity * $tickDiff;
                }
            }else{
                $this->motionY -= $this->gravity * $tickDiff;
            }
        }
        $this->updateMovement();
        return $this->target;
    }

}