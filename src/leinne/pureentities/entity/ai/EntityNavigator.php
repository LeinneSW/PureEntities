<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\entity\Entity;
use pocketmine\world\Position;

abstract class EntityNavigator{

    /** @var Position  */
    protected $end;

    /** @var Position[] */
    protected $goal = [];
    protected $goalIndex = -1;

    protected $holder = null;

    public function __construct(Entity $entity){
        $this->holder = $entity;
    }

    public abstract function update() : void;

    public function getHolder() : Entity{
        return $this->holder;
    }

    public function next() : ?Position{
        return $this->goalIndex >= 0 ? $this->goal[$this->goalIndex] : null;
    }

    public function getEnd() : ?Position{
        return $this->end;
    }

    public function setEnd(Position $pos) : void{
        $this->end = $pos;
        $this->goal = [];
        $this->goalIndex = -1;
    }

}