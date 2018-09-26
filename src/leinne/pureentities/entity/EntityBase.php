<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use pocketmine\entity\Creature;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\timings\Timings;

abstract class EntityBase extends Creature{

    private $speed = 1.0;

    protected $moveTime = 0;

    /** @var Vector3 */
    private $target = \null;
    private $targetFixed = \false;

    /** @var float */
    protected $interactDistance = 0.5;

    /**
     * $this 와 $target의 관계가 상호작용하는 관계인지 확인
     *
     * @param Creature $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public abstract function hasInteraction(Creature $target, float $distanceSquare) : bool;

    /**
     * 타겟과의 상호작용
     *
     * @return bool
     */
    public abstract function interactTarget() : bool;

    /**
     * 상호작용이 가능한 거리인지 체크
     *
     * @return Creature
     */
    public function checkInteract() : ?Creature{
        $target = $this->target;
        if(
            $target instanceof Creature
            && \abs($this->x - $target->x) <= ($width = $this->interactDistance + $this->width / 2 + $target->width / 2)
            && \abs($this->z - $target->z) <= $width
            && \abs($this->y - $target->y) <= \min(1, $this->eyeHeight)
        ){
            return $target;
        }
        return \null;
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setImmobile();
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setInt("MaxHealth" , $this->getMaxHealth());
        return $nbt;
    }

    public function isMovable() : bool{
        return \true;
    }

    public function updateMovement(bool $teleport = \false) : void{
        $send = \false;
        if(
            $this->lastLocation->x !== $this->x
            || $this->lastLocation->y !== $this->y
            || $this->lastLocation->z !== $this->z
            || $this->lastLocation->yaw !== $this->yaw
            || $this->lastLocation->pitch !== $this->pitch
        ){
            $send = \true;
            $this->lastLocation = $this->asLocation();
        }

        if(
            $this->lastMotion->x !== $this->motion->x
            || $this->lastMotion->y !== $this->motion->y
            || $this->lastMotion->z !== $this->motion->z
        ){
            $this->lastMotion = clone $this->motion;
        }

        if($send){
            $this->broadcastMovement($teleport);
        }
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

    public function setTarget(Vector3 $target, bool $fixed = \false) : void{
        if(!$fixed){
            $this->moveTime = \mt_rand(300, 2000);
        }

        $this->target = $target;
        $this->targetFixed = $fixed;
    }

    public function isTargetFixed() : bool{
        if($this->target === \null || ($this->target instanceof Creature && !$this->target->isAlive())){
            $this->targetFixed = \false;
        }
        return $this->targetFixed;
    }

    public function setTargetFixed(bool $fixed = \true) : void{
        $this->targetFixed = $fixed && $this->target !== \null && (!($this->target instanceof Creature) || $this->target->isAlive());
    }

    protected final function checkTarget() : Vector3{
        $isFixed = $this->isTargetFixed();
        if(!$isFixed && (!($this->target instanceof Creature) || !($option = $this->hasInteraction($this->target, $this->distanceSquared($this->target))))){
            if(isset($option)) $this->target = \null;

            $near = \PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $target){
                $distance = $this->distanceSquared($target);
                if(
                    $target === $this
                    || $distance > $near
                    || !($target instanceof Creature)
                    || !$this->hasInteraction($target, $distance)
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

        if(
            $this->target === \null
            || (!$isFixed && (--$this->moveTime <= 0 || $this->distanceSquared($this->target) <= 0.008))
        ){
            $x = \mt_rand(32, 120);
            $z = \mt_rand(32, 120);
            $this->moveTime = \mt_rand(300, 2000);
            $this->target = $this->add(\mt_rand(0, 1) ? $x : -$x, 0, \mt_rand(0, 1) ? $z : -$z);
        }

        return $this->target;
    }

    public function move(float $dx, float $dy, float $dz) : void{
        if(!$this->isMovable()){
            return;
        }

        $this->blocksAround = \null;

        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        if($this->keepMovement){
            $this->boundingBox->offset($dx, $dy, $dz);
        }else{
            $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));

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

        $this->x += $dx;
        $this->y += $dy;
        $this->z += $dz;

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