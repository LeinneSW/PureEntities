<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\entity\Living;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\world\World;

/**
 * This trait override most methods in the {@link Entity} abstract class.
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
     * @see Entity::entityBaseTick()
     *
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->closed){
            return \false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->isMovable()){
            return $hasUpdate;
        }

        $target = $this->checkTarget();

        $x = $target->x - $this->getLocation()->getX();
        $y = 0.0 + $target->y - $this->getLocation()->getY();
        $z = $target->z - $this->getLocation()->getZ();

        $diff = \abs($x) + \abs($z);
        $needJump = \false;
        if(!$this->interactTarget() && $diff !== 0.0){
            $hasUpdate = \true;
            $needJump = $this->onGround;
            $ground = $this->onGround ? 0.125 : 0.0025;
            $this->motion->x += $this->getSpeed() * $ground * $x / $diff;
            $this->motion->z += $this->getSpeed() * $ground * $z / $diff;
        }

        $this->needSlabJump = \false;
        if($needJump){
            /** @var Entity $this */
            switch(EntityAI::checkJumpState($this)){
                case EntityAI::JUMP_BLOCK:
                    $hasUpdate = \true;
                    $this->motion->y += 0.52;
                    break;
                case EntityAI::JUMP_SLAB:
                case EntityAI::JUMP_STAIR:
                    $this->needSlabJump = \true;
                    break;
            }
        }

        $this->setRotation(
                \rad2deg(\atan2($z, $x)) - 90.0,
                (!$target instanceof Living || $y === 0.0) ? 0.0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)))
        );

        return $hasUpdate;
    }

    /**
     * @see Entity::move()
     *
     * @param float $dx
     * @param float $dy
     * @param float $dz
     */
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
            /** @var Entity $this */
            $list = $this->getWorld()->getCollisionBoxes($this, $this->getWorld()->getTickRateTime() > 50 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));

            foreach($list as $k => $bb){
                $dy = $bb->calculateYOffset($this->boundingBox, $dy);
            }
            $this->boundingBox->offset(0, $dy, 0);

            foreach($list as $k => $bb){
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset($dx, 0, $dz);

            if(
                $this->needSlabJump
                && ($movX !== $dx || $movZ !== $dz)
            ){
                $this->boundingBox->offset(-$dx, -$dy, -$dz);

                $dx = $movX;
                $dy = 0.5;
                $dz = $movZ;
                foreach($list as $k => $bb){
                    $dy = $bb->calculateYOffset($this->boundingBox, $dy);
                }
                $this->boundingBox->offset(0, $dy, 0);

                foreach($list as $k => $bb){
                    $dx = $bb->calculateXOffset($this->boundingBox, $dx);
                    $dz = $bb->calculateZOffset($this->boundingBox, $dz);
                }
                $this->boundingBox->offset($dx, 0, $dz);
            }
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