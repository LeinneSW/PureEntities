<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\EntityNavigator;
use leinne\pureentities\entity\EntityBase;

use pocketmine\block\Door;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\Position;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 */
trait WalkEntityTrait{

    private $needSlabJump = false;

    private $checkDoorState = false;

    private $doorBreakTick = 0;

    /** @var EntityNavigator */
    protected $navigator = null;

    /**
     * 타겟과의 상호작용
     *
     * @return bool
     */
    public abstract function interactTarget() : bool;

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

        $this->getNavigator()->update();

        /** @var Position $me */
        $me = $this->getPosition();
        /** @var Entity $target */
        $target = $this->getTargetEntity();

        $goal = $this->getNavigator()->next();
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
        $this->checkDoorState = false;
        if($hasUpdate && $this->onGround){
            switch(EntityAI::checkPassablity($pos = new Position(
                ($this->motion->x > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX) + $this->motion->x,
                $this->boundingBox->minY,
                ($this->motion->z > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ) + $this->motion->z,
                $this->getWorld()
            ))){
                case EntityAI::BLOCK:
                    $hasUpdate = true;
                    $this->motion->y += 0.52;
                    break;
                case EntityAI::SLAB:
                case EntityAI::STAIR:
                    $this->needSlabJump = true;
                    break;
                case EntityAI::DOOR:
                    $this->checkDoorState = true;
                    break;
            }
        }

        $this->setRotation(
            rad2deg(atan2($z, $x)) - 90.0,
            $target === null ? 0.0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)))
        );

        return $hasUpdate;
    }


    /**
     * @see EntityBase::checkBoundingBoxState()
     *
     * @param float $movX
     * @param float $movY
     * @param float $movZ
     * @param float $dx
     * @param float $dy
     * @param float $dz
     *
     * @return AxisAlignedBB
     */
    public function checkBoundingBoxState(float $movX, float $movY, float $movZ, float &$dx, float &$dy, float &$dz) : AxisAlignedBB{
        /** @var AxisAlignedBB $aabb */
        $aabb = clone $this->boundingBox;

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

            $delay = ($movX != $dx) + ($movZ != $dz);
            if($delay >= 1){
                if($this->needSlabJump){
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
                }elseif($this->checkDoorState){
                    $delay = -1;
                    if($this->getWorld()->getBlock($this->getPosition()) instanceof Door && ++$this->doorBreakTick >= 20){
                        $item = $this->getInventory()->getItemInHand();
                        $this->getWorld()->useBreakOn($this->getPosition(), $item, null, true);
                    }
                }
            }

            $this->getNavigator()->addStopDelay($delay);
        }

        return $aabb;
    }

    public function getNavigator() : EntityNavigator{
        return $this->navigator ?? $this->navigator = new WalkEntityNavigator($this);
    }

}
