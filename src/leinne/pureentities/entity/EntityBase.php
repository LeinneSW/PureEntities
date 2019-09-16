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

    /** @var Living|Vector3 */
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
            && \abs($this->getLocation()->getX() - $target->getLocation()->x) <= ($width = $this->getInteractDistance() + ($this->width + $target->width) / 2)
            && \abs($this->getLocation()->getZ() - $target->getLocation()->z) <= $width
            && \abs($this->getLocation()->getY()- $target->getLocation()->y) <= \min(1, $this->eyeHeight)
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
        $pos = $this->getLocation();
        $last = $this->lastLocation;
        if(
            $last->x !== $pos->x
            || $last->y !== $pos->y
            || $last->z !== $pos->z
            || $last->yaw !== $pos->yaw
            || $last->pitch !== $pos->pitch
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

    /**
     * @return Living|Vector3
     */
    public function getTarget(){
        return $this->target;
    }

    /**
     * @param Living|Vector3 $target
     * @param bool $fixed
     */
    public function setTarget($target, bool $fixed = \false) : void{
        if(!$fixed){
            $this->moveTime = \mt_rand(300, 2000);
        }

        $this->target = $target;
        $this->targetFixed = $fixed;
    }

    public function isTargetFixed() : bool{
        return $this->targetFixed;
    }

    public function setTargetFixed(bool $fixed = \true) : void{
        $this->targetFixed = $fixed;
    }

    /**
     * @return Living|Vector3
     */
    protected final function checkTarget(){
        if($this->isTargetFixed()){
            return $this->target;
        }

        $pos = $this->getLocation();
        if(!($this->target instanceof Living) || !($option = $this->hasInteraction($this->target, $pos->distanceSquared($this->target instanceof Living ? $this->target->getPosition() : $this->target)))){
            if(isset($option)){
                $this->target = \null;
            }

            $near = \PHP_INT_MAX;
            foreach($this->getWorld()->getEntities() as $k => $target){
                $distance = $pos->distanceSquared($target->getPosition());
                if(
                    $target === $this
                    || $distance > $near
                    || !($target instanceof Living)
                    || !$this->hasInteraction($target, $distance)
                ){
                    continue;
                }

                $near = $distance;
                $this->target = $target;
            }
        }

        if($this->target instanceof Living && $this->target->isAlive()){
            return $this->target;
        }

        if(
            $this->target === \null
            || (--$this->moveTime <= 0 || $pos->distanceSquared($this->target instanceof Living ? $this->target->getPosition() : $this->target) <= 0.00025)
        ){
            $x = \mt_rand(10, 80);
            $z = \mt_rand(10, 80);
            $this->moveTime = \mt_rand(300, 2400);
            $this->target = $pos->add(\mt_rand(0, 1) ? $x : -$x, 0, \mt_rand(0, 1) ? $z : -$z);
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
            $moveBB = clone $this->boundingBox;

            $list = $this->getWorld()->getCollisionBoxes($this, $moveBB->addCoord($dx, $dy, $dz));

            foreach($list as $k => $bb){
                $dy = $bb->calculateYOffset($moveBB, $dy);
            }
            $moveBB->offset(0, $dy, 0);

            foreach($list as $k => $bb){
                $dx = $bb->calculateXOffset($moveBB, $dx);
            }
            $moveBB->offset($dx, 0, 0);

            foreach($list as $k => $bb){
                $dz = $bb->calculateZOffset($moveBB, $dz);
            }
            $moveBB->offset(0, 0, $dz);

            $this->boundingBox = $moveBB;
        }

        $this->location->x += $dx;
        $this->location->y += $dy;
        $this->location->z += $dz;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        if($movX != $dx || $movZ != $dz){
            $this->moveTime -= 100;
        }

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