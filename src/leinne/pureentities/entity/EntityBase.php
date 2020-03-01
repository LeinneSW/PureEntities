<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\ai\navigator\EntityNavigator;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\player\Player;
use pocketmine\timings\Timings;
use pocketmine\world\World;

abstract class EntityBase extends Living{

    /** @var float */
    public $eyeHeight = 0.8;

    /** @var float */
    public $width = 1.0;
    /** @var float */
    public $height = 1.0;

    /** @var float */
    private $speed = 1.0;

    /** @var int */
    protected $interactDelay = 0;

    /** @var bool */
    protected $fixedTarget = false;

    /** @var EntityNavigator */
    protected $navigator = null;

    public function getNavigator() : EntityNavigator{
        return $this->navigator ?? $this->navigator = $this->getDefaultNavigator();
    }

    public function getDefaultNavigator() : EntityNavigator{
        return new EntityNavigator($this);
    }

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
        return 0.6;
    }

    /**
     * 상호작용이 가능한 거리인지 체크
     *
     * @return bool
     */
    public function canInteractTarget() : bool{
        $target = $this->getTargetEntity();
        if($target === null){
            return false;
        }

        $width = $this->getInteractDistance() + ($this->width + $target->width) / 2;
        return abs($this->location->x - $target->location->x) <= $width
            && abs($this->location->z - $target->location->z) <= $width
            && abs($this->location->y - $target->location->y) <= min(1, $this->eyeHeight);
    }

    /**
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget || ($target instanceof Living && $distanceSquare <= 10000);
    }

    /**
     * 타겟과의 상호작용
     *
     * @return bool
     */
    public function interactTarget() : bool{
        ++$this->interactDelay;
        if(!$this->canInteractTarget()){
            return false;
        }
        return true;
    }

    public function interact(Player $player, Item $item) : void{
        //TODO
    }

    public function getDefaultMaxHealth() : int{
        return 20;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setInt("MaxHealth" , $this->getMaxHealth());
        return $nbt;
    }

    public function onUpdate(int $currentTick) : bool{
        if(!$this->canSpawnPeaceful() && $this->getWorld()->getDifficulty() === World::DIFFICULTY_PEACEFUL){
            $this->flagForDespawn();
            return false;
        }

        return parent::onUpdate($currentTick);
    }

    public function isMovable() : bool{
        return true;
    }

    public function canBreakDoor() : bool{
        return false;
    }

    public function canSpawnPeaceful() : bool{
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
            $this->getNavigator()->setGoal($target->getPosition());
        }
        $this->fixedTarget = $fixed;
    }

    public function setMotion(Vector3 $motion) : bool{
        $return = parent::setMotion($motion);
        $this->getNavigator()->updateGoal();
        return $return;
    }

    public function checkBoundingBoxState(float $movX, float $movY, float $movZ, float &$dx, float &$dy, float &$dz) : AxisAlignedBB{
        $aabb = clone $this->boundingBox;

        if($this->keepMovement){
            $aabb->offset($dx, $dy, $dz);
        }else{
            $checkStep = false;
            $list = $this->getWorld()->getCollisionBoxes($this, $aabb->addCoord($dx, $dy, $dz));
            if($this->onGround && $dy <= 0 && $this->stepHeight > 0){ //스텝 기능
                $checkStep = true;
                $newAABB = $aabb->offsetCopy($dx, 0, $dz);
                foreach($list as $k => $bb){
                    $diff = $bb->maxY - $aabb->minY;
                    if($diff > 0  && $diff <= $this->stepHeight && $bb->intersectsWith($newAABB)){
                        $dy = $diff;
                        break;
                    }
                }
            }

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

            if($checkStep && ($movX != $dx || $movZ != $dz)){ //스텝 블럭 시도했으나 실패했을 시
                $aabb = clone $this->boundingBox;
                $dx = $movX;
                $dy = $movY;
                $dz = $movZ;
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
            }
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

        $this->boundingBox = $this->checkBoundingBoxState($movX, $movY, $movZ, $dx, $dy, $dz);

        $this->location->x += $dx;
        $this->location->y += $dy;
        $this->location->z += $dz;

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
