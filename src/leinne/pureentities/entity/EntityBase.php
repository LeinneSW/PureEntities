<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\Node;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\AxisAlignedBB;
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

    /** @var bool */
    protected $fixedTarget = false;
    
    /** @var Vector3 */
    private $goal = null;

    /**
     * THIS IS VERY 실.험.적, so 당신의 서버 TPS Load 100% 으로 been replaced
     * @var Node[]
     */
    private $openNode = [];
    /** @var Node[] */
    private $closeNode = [];
    /** @var Node[] */
    private $finalGoal = [];

    /**
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public abstract function hasInteraction(Entity $target, float $distanceSquare) : bool;

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

    public function updateMovement(bool $teleport = false) : void{
        $send =\false;
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
    
    public function getGoal() : Vector3{
        $target = $this->getTargetEntity();
        if($target === null){
            if($this->goal === null){
                $x = mt_rand(15, 40);
                $z = mt_rand(15, 40);
                $this->moveTime = mt_rand(400, 6000);
                $this->goal = $this->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
            }
            return $this->goal;
        }
        return $target->getPosition();
    }

    public function setGoal(Vector3 $target, ?int $time = null) : void{
        $this->goal = $target->asVector3();
        $this->moveTime = $time ?? mt_rand(400, 6000);
    }

    public function setTargetEntity(?Entity $target, bool $fixed = false) : void{
        parent::setTargetEntity($target);
        $this->fixedTarget = $fixed;
    }

    protected final function updateTarget() : void{
        $pos = $this->getLocation();
        $target = $this->getTargetEntity();
        if($target === null || !$this->hasInteraction($target, $pos->distanceSquared($target->getPosition()))){
            $near = PHP_INT_MAX;
            $target = null;
            foreach($this->getWorld()->getEntities() as $k => $t){
                $distance = $pos->distanceSquared($t->getPosition());
                if(
                    $t === $this
                    || $distance > $near
                    || !($t instanceof Living)
                    || !$this->hasInteraction($t, $distance)
                ){
                    continue;
                }
                $near = $distance;
                $target = $t;
            }
            $this->setTargetEntity($target);
        }

        if(
            $this->getTargetEntityId() === null
            && (--$this->moveTime <= 0 || $pos->distanceSquared($this->getGoal()) <= 0.00025)
        ){
            $x = mt_rand(15, 40);
            $z = mt_rand(15, 40);
            $this->moveTime = mt_rand(400, 6000);
            $this->goal = $this->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }

        if(!empty($this->finalGoal)){
            return;
        }
        $index = 1;
        $goal = $this->getGoal();
        $start = Node::create($index++, $this->boundingBox, 0, abs($goal->x - $pos->x) + abs($goal->z - $pos->z));
        $this->openNode[] = $start;
        while(true){
            $nextNode = array_pop($this->openNode);
            $this->closeNode[] = $nextNode;
            for($xi = -1; $xi < 2; ++$xi){
                for($zi = -1; $zi < 2; ++$zi){
                    if($xi === 0 && $zi === 0){
                        continue;
                    }
                    $node = Node::create(
                        $index++,
                        $nextNode->boundingBox->offsetCopy($xi, 0, $zi),
                        abs($xi) === 1 && abs($zi) === 1 ? 14 : 10,
                        abs($goal->x - $pos->x) + abs($goal->z - $pos->z),
                        $nextNode->id
                    );

                    if(
                        abs($node->boundingBox->minX - $this->boundingBox->minX) < 1
                        && abs($node->boundingBox->minZ - $this->boundingBox->minZ) < 1
                    ){
                        $this->openNode[] = $node;
                        break;
                    }elseif(EntityAI::checkBlockState($this->getWorld(), $node->boundingBox) !== EntityAI::WALL){
                        $this->openNode[] = $node;
                    }

                }
            }

            $this->openNode = EntityAI::quickSort(0, count($this->openNode) - 1, $this->openNode);
            $this->closeNode[] = array_shift($this->openNode);
        }

        $index = 0;
        $this->finalGoal = [array_pop($this->closeNode)];
        while(($node = array_pop($this->closeNode)) !== null){
            if($node->parentNode === $this->finalGoal[$index]->id){
                ++$index;
                $this->finalGoal[] = $node;
            }
        }
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

        $this->blocksAround = \null;

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
