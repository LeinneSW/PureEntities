<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use leinne\pureentities\entity\EntityBase;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\Position;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 */
trait WalkEntityTrait{

    private $needSlabJump = false;

    /**
     * 타겟과의 상호작용
     *
     * @return bool
     */
    public abstract function interactTarget() : bool;

    /**
     * @see EntityBase::initEntity()
     *
     * @param CompoundTag $nbt
     */
    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->navigator = new WalkEntityNavigator($this);
    }

    /**
     * @see EntityBase::entityBaseTick()
     *
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->closed){
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->isMovable()){
            return $hasUpdate;
        }

        $this->navigator->update();

        /** @var Position $me */
        $me = $this->getPosition();
        /** @var Entity $target */
        $target = $this->getTargetEntity();
        if($target !== null){
            $goal = $target->getPosition();
        }else{
            $goal = $this->navigator->next();
            $pitch = 0.0;
        }

        if($goal === null){
            return $hasUpdate;
        }
        $x = $goal->x - $me->getX();
        $y = $goal->y - $me->getY();
        $z = $goal->z - $me->getZ();
        $diff = abs($x) + abs($z);
        if(!$this->interactTarget() && $diff !== 0.0){
            $hasUpdate = true;
            $ground = $this->onGround ? 0.125 : 0.0025;
            $this->motion->x += $this->getSpeed() * $ground * $x / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z / $diff;
        }

        $this->needSlabJump = false;
        if($hasUpdate && $this->onGround){
            switch(EntityAI::checkJumpState($this->getWorld(), $this->boundingBox, $this->motion)){
                case EntityAI::BLOCK:
                    $hasUpdate = true;
                    $this->motion->y += 0.52;
                    break;
                case EntityAI::SLAB:
                case EntityAI::STAIR:
                    $this->needSlabJump = true;
                    break;
            }
        }

        $this->setRotation(
            rad2deg(atan2($z, $x)) - 90.0,
            $pitch ?? rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)))
        );

        return $hasUpdate;
    }


    /**
     * @see EntityBase::checkBoundingBoxState()
     *
     * @param float $dx
     * @param float $dy
     * @param float $dz
     *
     * @return AxisAlignedBB
     */
    public function checkBoundingBoxState(float &$dx, float &$dy, float &$dz) : AxisAlignedBB{
        /** @var AxisAlignedBB $aabb */
        $aabb = clone $this->boundingBox;

        $movX = $dx;
        $movZ = $dz;

        if($this->keepMovement){
            $aabb->offset($dx, $dy, $dz);
        }else{
            /** @var Entity $this */
            $list = $this->getWorld()->getCollisionBoxes($this, $aabb->addCoord($dx, $dy, $dz));

            foreach($list as $k => $bb){
                $dy = $bb->calculateYOffset($aabb, $dy);
            }
            $aabb->offset(0, $dy, 0);

            foreach($list as $k => $bb){
                $dx = $bb->calculateXOffset($aabb, $dx);
                $dz = $bb->calculateZOffset($aabb, $dz);
            }
            $aabb->offset($dx, 0, $dz);

            if(
                $this->needSlabJump
                && ($movX !== $dx || $movZ !== $dz)
            ){
                $aabb = clone $this->boundingBox;

                $dx = $movX;
                $dy = 0.5;
                $dz = $movZ;
                foreach($list as $k => $bb){
                    $dy = $bb->calculateYOffset($aabb, $dy);
                }
                $aabb->offset(0, $dy, 0);

                foreach($list as $k => $bb){
                    $dx = $bb->calculateXOffset($aabb, $dx);
                    $dz = $bb->calculateZOffset($aabb, $dz);
                }
                $aabb->offset($dx, 0, $dz);
            }
        }
        return $aabb;
    }

}
