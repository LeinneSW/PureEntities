<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\ai\EntityNavigator;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\timings\Timings;

abstract class EntityBase extends Living{

    /** @var float */
    public $eyeHeight = 0.8;

    /** @var float */
    public $width = 1.0;
    /** @var float */
    public $height = 1.0;

    /** @var float */
    private $speed = 1.0;

    /** @var bool */
    protected $fixedTarget = false;

    /** @var EntityNavigator */
    protected $navigator = null;

    /**
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function hasInteraction(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget;
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->navigator = new EntityNavigator($this);
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
     * @return Entity
     */
    public function checkInteract() : ?Entity{
        $target = $this->getTargetEntity();
        if(
            $target !== null
            && abs($this->getLocation()->getX() - $target->getLocation()->x) <= ($width = $this->getInteractDistance() + ($this->width + $target->width) / 2)
            && abs($this->getLocation()->getZ() - $target->getLocation()->z) <= $width
            && abs($this->getLocation()->getY()- $target->getLocation()->y) <= min(1, $this->eyeHeight)
        ){
            return $target;
        }
        return null;
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
        return true;
    }

    public function updateMovement(bool $teleport = false) : void{
        $send = false;
        $pos = $this->getLocation();
        $last = $this->lastLocation;
        if(
            $last->x !== $pos->x
            || $last->y !== $pos->y
            || $last->z !== $pos->z
            || $last->yaw !== $pos->yaw
            || $last->pitch !== $pos->pitch
        ){
            $send = true;
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

    public function setTargetEntity(?Entity $target, bool $fixed = false) : void{
        parent::setTargetEntity($target);
        if($target !== null){
            $this->navigator->setEnd($target->getPosition());
        }
        $this->fixedTarget = $fixed;
    }

    public function checkBoundingBoxState(float &$dx, float &$dy, float &$dz) : AxisAlignedBB{
        $aabb = clone $this->boundingBox;

        if($this->keepMovement){
            $aabb->offset($dx, $dy, $dz);
        }else{
            $list = $this->getWorld()->getCollisionBoxes($this, $aabb->addCoord($dx, $dy, $dz));

            foreach($list as $k => $bb){
                $dy = $bb->calculateYOffset($aabb, $dy);
            }
            $aabb->offset(0, $dy, 0);

            foreach($list as $k => $bb){
                $dx = $bb->calculateXOffset($aabb, $dx);
            }
            $aabb->offset($dx, 0, 0);

            foreach($list as $k => $bb){
                $dz = $bb->calculateZOffset($aabb, $dz);
            }
            $aabb->offset(0, 0, $dz);

            $this->boundingBox = $aabb;
        }
        return $aabb;
    }

    protected function move(float $dx, float $dy, float $dz) : void{
        if(!$this->isMovable()){
            return;
        }

        $this->blocksAround = null;

        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $this->boundingBox = $this->checkBoundingBoxState($dx, $dy, $dz);

        $this->location->x += $dx;
        $this->location->y += $dy;
        $this->location->z += $dz;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        $this->navigator->addStopDelay($movX != $dx || $movZ != $dz ? 1 : -1);

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
