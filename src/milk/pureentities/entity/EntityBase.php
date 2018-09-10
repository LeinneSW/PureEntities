<?php

declare(strict_types=1);

namespace milk\pureentities\entity;

use pocketmine\entity\Creature;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\timings\Timings;

abstract class EntityBase extends Creature{

    private $speed = 1.0;

    protected $moveTime = 0;

    /** @var Vector3 */
    private $target = \null;
    /** @var Creature */
    private $followTarget = \null;

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Creature $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public abstract function isHostility(Creature $target, float $distanceSquare) : bool;

    /**
     * 타겟과의 상호작용
     * ex) Mob & Human, Chicken & Human
     */
    public abstract function interactTarget() : bool;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setImmobile();
    }

    public function updateMovement(bool $teleport = \false) : void{
        if(
            $this->lastLocation->x !== $this->x
            || $this->lastLocation->y !== $this->y
            || $this->lastLocation->z !== $this->z
            || $this->lastLocation->yaw !== $this->yaw
            || $this->lastLocation->pitch !== $this->pitch
        ){
            $this->lastLocation = $this->asLocation();
        }

        if(
            $this->lastMotion->x !== $this->motion->x
            || $this->lastMotion->y !== $this->motion->y
            || $this->lastMotion->z !== $this->motion->z
        ){
            $this->lastMotion = clone $this->motion;
        }
        $this->broadcastMovement($teleport);
    }

    public function getSpeed() : float{
        return $this->speed;
    }

    public function setSpeed(float $speed) : void{
        $this->speed = $speed;
    }

    public function getTarget() : ?Vector3{
        return $this->target;
    }

    public function getFixedTarget() : ?Creature{
        return $this->followTarget;
    }

    public function setTarget(?Creature $target = \null) : void{
        $this->followTarget = $target;
    }

    public function checkTarget() : Vector3{
        if($this->followTarget !== null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            return $this->followTarget;
        }

        if(!($this->target instanceof Creature) or !($option = $this->isHostility($this->target, $this->distanceSquared($this->target)))){
            if(isset($option)) $this->target = \null;

            $near = \PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $target){
                $distance = $this->distanceSquared($target);
                if(
                    $target === $this
                    || $distance > $near
                    || !($target instanceof Creature)
                    || !$this->isHostility($target, $distance)
                ){
                    continue;
                }

                $near = $distance;
                $this->target = $target;
            }
        }

        if($this->target instanceof Creature && $this->target->isAlive()){
            return $this->target;
        }

        if(--$this->moveTime <= 0 or $this->target === \null){
            $x = \mt_rand(20, 100);
            $z = \mt_rand(20, 100);
            $this->moveTime = \mt_rand(300, 1200);
            $this->target = $this->add(\mt_rand(0, 1) ? $x : -$x, 0, \mt_rand(0, 1) ? $z : -$z);
        }

        return $this->target;
    }

    public function move(float $dx, float $dy, float $dz) : void{
        $this->blocksAround = \null;

        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        if($this->keepMovement){
            $this->boundingBox->offset($dx, $dy, $dz);
        }else{
            \assert(\abs($dx) <= 20 and \abs($dy) <= 20 and \abs($dz) <= 20, "Movement distance is excessive: dx=$dx, dy=$dy, dz=$dz");

            $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz), \false);

            foreach($list as $bb){
                $dy = $bb->calculateYOffset($this->boundingBox, $dy);
            }
            $this->boundingBox->offset(0, $dy, 0);

            foreach($list as $bb){
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
            }
            $this->boundingBox->offset($dx, 0, 0);

            foreach($list as $bb){
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset(0, 0, $dz);
        }

        $this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
        $this->y = $this->boundingBox->minY;
        $this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        if($movX != $dx){
            $this->motion->x = 0;
        }

        if($movY != $dy){
            $this->motion->y = 0;
        }

        if($movZ != $dz){
            $this->motion->z = 0;
        }

        Timings::$entityMoveTimer->stopTiming();
    }

}