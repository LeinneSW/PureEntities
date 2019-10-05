<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityNavigator;

use pocketmine\entity\Living;
use pocketmine\math\Math;
use pocketmine\world\Position;

class WalkEntityNavigator extends EntityNavigator{

    private $stopDelay = 0;

    /** @var AStarHelper */
    protected $helper = null;

    public function update() : void{
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
                $next = $this->next();
                if($next !== null && (abs($next->x - $pos->x) < 0.1 && abs($next->z - $pos->z) < 0.1)){// && abs($next->y - $pos->y) < 0.1)){
                    --$this->goalIndex;
                }

                if($this->goalIndex < 0){
                    //$this->end = null;
                    $this->setEnd($this->makeRandomGoal());
                }
            }

            if($this->stopDelay >= 60 || $this->end === null){
                $this->setEnd($this->makeRandomGoal());
            }
        }

        /*if($this->end === null){
            return;
        }*/

        if($this->goalIndex < 0 || empty($this->goal)) {
            $this->goal = $this->getHelper()->calculate();
            if($this->goal === null){
                //$this->end = null;
                $this->setEnd($this->makeRandomGoal());
            }else{
                $this->goalIndex = count($this->goal) - 1;
            }
        }
    }

    public function setEnd(Position $pos) : void{
        parent::setEnd($pos);

        $this->getHelper()->reset();
    }

    public function makeRandomGoal() : Position{
        $x = mt_rand(8, 25);
        $z = mt_rand(8, 25);

        $pos = $this->holder->getPosition();
        $pos->x = Math::floorFloat($pos->x) + 0.5 + (mt_rand(0, 1) ? $x : -$x);
        $pos->z = Math::floorFloat($pos->z) + 0.5 + (mt_rand(0, 1) ? $z : -$z);
        return $pos;
    }

    public function addStopDelay(int $add) : void{
        $this->stopDelay += $add;
        if($this->stopDelay < 0){
            $this->stopDelay = 0;
        }
    }

    public function getHelper() : AStarHelper{
        return $this->helper ?? $this->helper = new AStarHelper($this);
    }

}