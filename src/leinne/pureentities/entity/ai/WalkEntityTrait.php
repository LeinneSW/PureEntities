<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\entity\Living;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Server;
use pocketmine\world\World;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 *
 * @property bool           $keepMovement
 * @property World          $level
 * @property Server         $server
 * @property AxisAlignedBB  $boundingBox
 */
trait WalkEntityTrait{

    private $needSlabJump = \false;

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

        /** @var Entity $this */
        $me = $this->getPosition();

        $this->updateTarget();
        $x = $this->getGoal()->x - $me->getX();
        $y = $this->getGoal()->y - $me->getY();
        $z = $this->getGoal()->z - $me->getZ();

        $diff = abs($x) + abs($z);
        $needJump = false;
        if(!$this->interactTarget() && $diff !== 0.0){
            $hasUpdate = true;
            $needJump = $this->onGround;
            $ground = $this->onGround ? 0.125 : 0.0025;
            $this->motion->x += $this->getSpeed() * $ground * $x / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z / $diff;
        }

        $this->needSlabJump = \false;
        if($needJump){
            /** @var Entity $this */
            switch(EntityAI::checkBlockState($this->getWorld(), $this->boundingBox, $this->motion)){
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
            \rad2deg(\atan2($z, $x)) - 90.0,
            ($this->getTarget() !== null || $y === 0.0) ? 0.0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)))
        );

        return $hasUpdate;
    }


    /**
     * @see EntityBase::updateBoundingBoxState()
     *
     * @param float $dx
     * @param float $dy
     * @param float $dz
     *
     * @return AxisAlignedBB
     */
    public function updateBoundingBoxState(float &$dx, float &$dy, float &$dz) : AxisAlignedBB{
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
