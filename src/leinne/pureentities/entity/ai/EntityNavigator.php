<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use leinne\pureentities\entity\EntityBase;

use pocketmine\entity\Living;
use pocketmine\world\Position;

abstract class EntityNavigator{

    /**
     * 엔티티가 같은위치에 벽 등의 장애로 인해 멈춰있던 시간을 나타냅니다
     *
     * @var int
     */
    private $stopDelay = 0;

    /** @var Position  */
    protected $end;

    /** @var Position[] */
    protected $goal = [];
    /** @var int */
    protected $goalIndex = -1;

    /** @var EntityBase */
    protected $holder;

    public function __construct(EntityBase $entity){
        $this->holder = $entity;
    }

    public abstract function getHelper() : Helper;

    public abstract function makeRandomGoal() : Position;

    public function update() : void{
        $pos = $this->holder->getLocation();
        $holder = $this->holder;
        $target = $holder->getTargetEntity();
        if($target === null || !$holder->hasInteraction($target, $near = $pos->distanceSquared($target->getPosition()))){
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

        if($target !== null && $this->getEnd()->distanceSquared($target->getPosition())){
            $this->setEnd($target->getPosition());
            return;
        }

        if(
            $this->stopDelay >= 90
            || (!empty($this->goal) && $this->goalIndex < 0)
        ){
            $this->setEnd($this->makeRandomGoal());
        }

        if($this->goalIndex < 0 || empty($this->goal)) {
            $this->goal = $this->getHelper()->calculate();
            if($this->goal === null){
                $this->setEnd($this->makeRandomGoal());
            }else{
                $this->goalIndex = count($this->goal) - 1;
            }
        }
    }

    public function next() : ?Position{
        if($this->goalIndex >= 0){
            $next = $this->goal[$this->goalIndex];
            if($this->holder->getPosition()->distanceSquared($next) < 0.16){
                --$this->goalIndex;
            }

            if($this->goalIndex < 0){
                return null;
            }
        }
        return $this->goalIndex >= 0 ? $this->goal[$this->goalIndex] : null;
    }

    public function addStopDelay(int $add) : void{
        $this->stopDelay += $add;
        if($this->stopDelay < 0){
            $this->stopDelay = 0;
        }
    }

    public function getHolder() : EntityBase{
        return $this->holder;
    }

    public function getEnd() : Position{
        return $this->end ?? $this->end = $this->makeRandomGoal();
    }

    public function setEnd(Position $pos) : void{
        $this->end = $pos;
        $this->goal = [];
        $this->stopDelay = 0;
        $this->goalIndex = -1;
    }

}