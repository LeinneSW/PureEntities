<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\timings\Timings;

abstract class EntityBase extends Living {

    /** @var float */
    public $eyeHeight = 0.8;

    /** @var float */
    public $width = 1.0;
    /** @var float */
    public $height = 1.0;

    /** @var float */
    private $speed = 1.0;

    /** @var int */
    protected $moveTime = 0;

    /** @var Vector3 */
    private $target = \null;
    private $targetFixed = \false;

    /**
     * $this 와 $target의 관계가 상호작용하는 관계인지 확인
     *
     * @param Living $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public abstract function hasInteraction(Living $target, float $distanceSquare) : bool;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setMaxHealth($health = $nbt->getInt("MaxHealth", $this->getDefaultMaxHealth()));
        if($nbt->hasTag("HealF", FloatTag::class)){
            $health = $nbt->getFloat("HealF");
        }elseif($nbt->hasTag("Health")){
            $healthTag = $nbt->getTag("Health");
            $health = (float) $healthTag->getValue();
        }
        $this->setHealth($health);
        $this->setImmobile();
    }

    /**
     * 상호작용을 위한 최소 거리
     *
     * @return float
     */
    public function getInteractDistance() : float{
        return 0.75;
    }

    /**
     * 상호작용이 가능한 거리인지 체크
     *
     * @return Living
     */
    public function checkInteract() : ?Living{
        $target = $this->target;
        if(
            $target instanceof Living
            && \abs($this->getLocation()->getX() - $target->x) <= ($width = $this->getInteractDistance() + ($this->width + $target->width) / 2)
            && \abs($this->getLocation()->getZ() - $target->z) <= $width
            && \abs($this->getLocation()->getY()- $target->y) <= \min(1, $this->eyeHeight)
        ){
            return $target;
        }
        return \null;
    }

    public function getDefaultMaxHealth() : int{
        return 20;
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
            $this->lastLocation->x !== $this->getLocation()->getX()
            || $this->lastLocation->y !== $this->getLocation()->getY()
            || $this->lastLocation->z !== $this->getLocation()->getZ()
            || $this->lastLocation->yaw !== $this->getLocation()->getYaw()
            || $this->lastLocation->pitch !== $this->getLocation()->getPitch()
        ){
            $send = \true;
            $this->lastLocation = $this->getLocation();
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
        if($this->target === \null || ($this->target instanceof Living && !$this->target->isAlive())){
            $this->targetFixed = \false;
        }
        return $this->targetFixed;
    }

    public function setTargetFixed(bool $fixed = \true) : void{
        $this->targetFixed = $fixed && $this->target !== \null && (!($this->target instanceof Living) || $this->target->isAlive());
    }

    protected final function checkTarget() : Vector3{
        if($this->isTargetFixed()){
            return $this->target;
        }

        if(!($this->target instanceof Living) || !($option = $this->hasInteraction($this->target, $this->getLocation()->distanceSquared($this->target)))){
            if(isset($option)){
                $this->target = \null;
            }

            $near = \PHP_INT_MAX;
            foreach($this->getWorld()->getEntities() as $k => $target){
                $distance = $this->getLocation()->distanceSquared($target->getPosition());
                if(
                    $target === $this
                    || $distance > $near
                    || !($target instanceof Living)
                    || !$this->hasInteraction($target, $distance)
                ){
                    continue;
                }

                $near = $distance;
                $this->target = $target->getPosition()->asVector3();
            }
        }

        if($this->target instanceof Living && $this->target->isAlive()){
            return $this->target;
        }

        if(
            $this->target === \null
            || (--$this->moveTime <= 0 || $this->getLocation()->distanceSquared($this->target) <= 0.00025)
        ){
            $x = \mt_rand(7, 80);
            $z = \mt_rand(7, 80);
            $this->moveTime = \mt_rand(300, 2400);
            $this->target = $this->getLocation()->add(\mt_rand(0, 1) ? $x : -$x, 0, \mt_rand(0, 1) ? $z : -$z);
        }

        return $this->target;
    }

    protected function move(float $dx, float $dy, float $dz) : void{
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
            $list = $this->getWorld()->getCollisionBoxes($this, $this->getWorld()->getTickRateTime() > 50 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));

            foreach($list as $k => $bb){
                $dy = $bb->calculateYOffset($this->boundingBox, $dy);
            }
            $this->boundingBox->offset(0, $dy, 0);

            foreach($list as $k => $bb){
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
            }
            $this->boundingBox->offset($dx, 0, 0);

            foreach($list as $k => $bb){
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset(0, 0, $dz);
        }

        $this->location->x += $dx;
        $this->location->y += $dy;
        $this->location->z += $dz;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        if($movX != $dx){
            $this->motion->x = 0;
            $this->moveTime -= 20;
        }

        if($movY != $dy){
            $this->motion->y = 0;
        }

        if($movZ != $dz){
            $this->motion->z = 0;
            $this->moveTime -= 20;
        }

        Timings::$entityMoveTimer->stopTiming();
    }

}