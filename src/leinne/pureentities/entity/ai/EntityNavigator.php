<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EntityNavigator{

    /** @var Entity */
    private $holder = null;

    /** @var Vector3  */
    private $end;

    /** @var Vector3[] */
    private $goal = [];
    private $goalIndex = -1;

    private $stopDelay = 0;

    /** @var AStarCalculator */
    private $calculator = null;

    public function __construct(Entity $entity){
        $this->holder = $entity;
        $this->calculator = new AStarCalculator($this);
    }

    public function update(){
        $pos = $this->holder->getLocation();
        $holder = $this->holder;
        $target = $holder->getTargetEntity();
        if($target === null || !$holder->hasInteraction($target, $pos->distanceSquared($target->getPosition()))){
            $near = PHP_INT_MAX;
            $target = null;
            foreach($holder->getWorld()->getEntities() as $k => $t){
                if(
                    $t === $this
                    || !($t instanceof Living)
                    || ($distance = $pos->distanceSquared($t->getPosition())) > $near
                    || !$holder->hasInteraction($t, $distance)
                ){
                    continue;
                }
                $near = $distance;
                $target = $t;
            }
            $holder->setTargetEntity($target);
        }

        if($holder->getTargetEntity() === null){
            if(!empty($this->goal)){
                if($this->goalIndex < 0){
                    $this->setEnd($this->makeRandomGoal());
                }else{
                    $next = $this->next();
                    if(($next->x - $pos->x) ** 2 + ($next->z - $pos->z) ** 2 < 0.15){
                        --$this->goalIndex;
                    }
                }
            }

            if($this->stopDelay >= 60 || $this->end === null){
                $this->setEnd($this->makeRandomGoal());
            }
        }

        if($this->goalIndex < 0 || empty($this->goal)) {
            $this->goal = $this->calculator->calculate();
            if($this->goal === null){
                $this->setEnd($this->makeRandomGoal());
            }else{
                $this->goalIndex = count($this->goal) - 1;
            }
        }
    }

    public function next() : ?Position{
        return $this->goalIndex >= 0 ? $this->goal[$this->goalIndex] : null;
    }

    public function getEnd() : Vector3{
        return $this->end;
    }

    public function setEnd(Vector3 $pos) : void{
        $this->end = $pos;
        $this->goal = [];
        $this->goalIndex = -1;
        $this->calculator->reset();
    }

    public function makeRandomGoal() : Vector3{
        $x = mt_rand(10, 30);
        $z = mt_rand(10, 30);
        return $this->holder->getPosition()->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
    }

    public function getHolder() : Entity{
        return $this->holder;
    }

    public function addStopDelay(int $add) : void{
        $this->stopDelay += $add;
    }

}