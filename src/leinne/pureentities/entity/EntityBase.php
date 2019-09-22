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
use pocketmine\world\Position;

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

    /** @var Node[] */
    private $openNode = [];
    /** @var Node[] */
    private $closeNode = [];

    /** @var Vector3 */
    public $goal = null;

    /** @var int */
    private $moveTime = 0;
    /** @var Node[] */
    private $goalNode = [];
    private $goalIndex = -1;

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

    public function getNextGoal() : Vector3{
        return $this->goalNode[$this->goalIndex]->getPosition();
    }
    
    public function getFinalGoal() : Vector3{
        if($this->goal === null){
            $x = mt_rand(15, 40);
            $z = mt_rand(15, 40);
            $this->setFinalGoal($this->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z));
        }
        return ($target = $this->getTargetEntity()) !== null ? $target->getPosition() : $this->goal;
    }

    public function setFinalGoal(Vector3 $target) : void{
        $this->goal = $target->asVector3();
        $this->moveTime = 0;
        $this->goalNode = [];
        $this->goalIndex = -1;
    }

    public function setTargetEntity(?Entity $target, bool $fixed = false) : void{
        parent::setTargetEntity($target);
        if($this->targetId !== null){
            $this->goal = null;
            $this->moveTime = 0;
            $this->goalNode = [];
            $this->goalIndex = -1;
        }
        $this->fixedTarget = $fixed;
    }

    protected final function updateTarget() : void{
        $pos = $this->getLocation();
        $target = $this->getTargetEntity();
        if($target === null || !$this->hasInteraction($target, $pos->distanceSquared($target->getPosition()))){
            $near = PHP_INT_MAX;
            $target = null;
            foreach($this->getWorld()->getEntities() as $k => $t){
                if(
                    $t === $this
                    || !($t instanceof Living)
                    || ($distance = $pos->distanceSquared($t->getPosition())) > $near
                    || !$this->hasInteraction($t, $distance)
                ){
                    continue;
                }
                $near = $distance;
                $target = $t;
            }
            $this->setTargetEntity($target);
        }

        if($this->getTargetEntity() === null){
            if(!empty($this->goalNode)){
                if($this->goalIndex < 0){
                    $x = mt_rand(15, 40);
                    $z = mt_rand(15, 40);
                    $this->setFinalGoal($this->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z));
                }elseif($pos->distanceSquared($this->getNextGoal()) < 0.2){
                    --$this->goalIndex;
                }
            }

            if($this->moveTime >= 60 || $this->goalIndex < 0){
                $x = mt_rand(15, 40);
                $z = mt_rand(15, 40);
                $this->setFinalGoal($this->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z));
            }
        }

        //TODO: 작동은 어느정도 되나 굉장히 미흡함
        if(!empty($this->goalNode) || $this->goalIndex >= 0){
            return;
        }
        $goal = $this->getFinalGoal();
        $this->openNode = [Node::create($pos, 0, $goal)];
        $finished = false;
        while(!$finished && !empty($this->openNode)){
            EntityAI::quickSort($this->openNode, 0, count($this->openNode) - 1);
            $parentNode = array_shift($this->openNode);
            $parentPos = $parentNode->getPosition();
            $this->closeNode["{$parentPos->x}:{$parentPos->y}:{$parentPos->z}"] = $parentNode;
            for($xi = -1; $xi < 2; ++$xi){
                for($zi = -1; $zi < 2; ++$zi){
                    if($xi === 0 && $zi === 0){
                        continue;
                    }

                    $node = Node::create(
                        new Position($parentPos->x + $xi, $parentPos->y, $parentPos->z + $zi, $parentPos->world),
                        abs($xi) === 1 && abs($zi) === 1 ? 14 : 10,
                        $goal,
                        $parentNode->id
                    );
                    $nodePos = $node->getPosition();
                    $key = "{$nodePos->x}:{$nodePos->y}:{$nodePos->z}";
                    if(isset($this->closeNode[$key])){
                        continue;
                    }elseif(isset($this->openNode[$key]) && $this->openNode[$key]->gscore <= $node->gscore){
                        continue;
                    }

                    if(
                        abs($nodePos->x - $goal->x) < 1
                        && abs($nodePos->z - $goal->z) < 1
                    ){
                        $finished = true;
                        $this->closeNode[$key] = $node;
                        break;
                    }elseif(($state = EntityAI::checkBlockState($nodePos)) !== EntityAI::WALL){
                        switch($state){
                            case EntityAI::STAIR:
                            case EntityAI::BLOCK:
                                //$node->position->y += 1;
                                break;
                            case EntityAI::SLAB:
                                //$node->position->y += 0.5;
                                break;
                            case EntityAI::AIR:
                                //TODO: 최소가 되는 블럭
                                /*$lastY = 1;
                                $blockPos = $nodePos->floor();
                                while(true){
                                    --$blockPos->y;
                                    $aabb = $this->getWorld()->getBlock($blockPos)->getCollisionBoxes()[0] ?? null;
                                    if($aabb === null || $aabb->maxY - $aabb->minY < 0.5){
                                        $node->position->y -= $lastY;
                                    }
                                }*/
                                break;
                        }
                        $this->openNode[$key] = $node;
                    }
                }
            }
        }

        if(!$finished){
            //TODO: 목표까지 도달이 불가능할 경우 새 목적지 추적
            return;
        }

        $index = 0;
        //$keys = array_keys($this->closeNode);
        //$closeIndex = count($this->closeNode) - 1;
        $this->goalNode = [/**$keys[$closeIndex] => */array_pop($this->closeNode)];
        while(($node = array_pop($this->closeNode)) !== null){
            //--$closeIndex;
            if($node->id === $this->goalNode[$index]->parentNode){
                ++$index;
                $this->goalNode[/**$keys[$closeIndex]*/] = $node;
            }
        }
        $this->goalIndex = count($this->goalNode) - 1;
        //foreach($this->finalGoal as $n) echo $n->getPosition() , "\n";
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

        if($movX != $dx || $movZ != $dz){
            ++$this->moveTime;
        }else{
            --$this->moveTime;
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
