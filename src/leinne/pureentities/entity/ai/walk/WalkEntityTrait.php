<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\EntityNavigator;
use leinne\pureentities\entity\EntityBase;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 */
trait WalkEntityTrait{

    /**
     * 문을 부수기까지의 시간을 저장합니다
     *
     * @var int
     */
    private $doorBreakTick = 0;

    /**
     * 가야할 블럭이 문인지 확인합니다
     *
     * @var bool
     */
    private $checkDoorState = false;

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

        if(!$this->checkDoorState){
            $this->doorBreakTick = 0;
        }
        $this->checkDoorState = false;
        if($hasUpdate && $this->onGround){
            switch(EntityAI::checkPassablity(new Position(
                ($this->motion->x > 0 ? $this->boundingBox->maxX : $this->boundingBox->minX) + $this->motion->x,
                $this->boundingBox->minY,
                ($this->motion->z > 0 ? $this->boundingBox->maxZ : $this->boundingBox->minZ) + $this->motion->z,
                $this->getWorld()
            ))){
                case EntityAI::BLOCK:
                    $hasUpdate = true;
                    $this->motion->y += 0.52;
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
            if($this->onGround && $movY <= 0){ //반블럭류 자동 점프 기능
                $x = ($dy > 0 ? $aabb->maxX : $aabb->minX) + $dx;
                $y = $aabb->minY;
                $z = ($dz > 0 ? $aabb->maxZ : $aabb->minZ) + $dz;
                foreach($list as $k => $bb){
                    if(
                        ($diff = $bb->maxY - $y) <= 0.5
                        && $x >= $bb->minX && $x <= $bb->maxX
                        && $y >= $bb->minY && $y < $bb->maxY
                        && $z >= $bb->minZ && $z <= $bb->maxZ
                    ){
                        $dy = $diff;
                    }
                }
            }
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
            if($delay >= 1 && $this->checkDoorState){
                $delay = -1;
                /** @var World $world */
                if(++$this->doorBreakTick >= 25){
                    $this->doorBreakTick = 0;
                    $item = $this->getInventory()->getItemInHand();
                    $this->getWorld()->useBreakOn(new Vector3(($movX > 0 ? $aabb->maxX : $aabb->minX) + $movX, $aabb->minY, ($movZ > 0 ? $aabb->maxZ : $aabb->minZ) + $movZ), $item, null, true);
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
