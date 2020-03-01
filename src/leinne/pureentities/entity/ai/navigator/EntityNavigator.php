<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\navigator;

use leinne\pureentities\entity\ai\path\SimplePathFinder;
use leinne\pureentities\entity\EntityBase;
use leinne\pureentities\entity\ai\path\PathFinder;

use pocketmine\entity\Living;
use pocketmine\math\Math;
use pocketmine\world\Position;

class EntityNavigator{

    /**
     * 엔티티가 같은 위치에 벽 등의 장애로 인해 멈춰있던 시간을 나타냅니다
     *
     * @var int
     */
    private $stopDelay = 0;

    /** @var Position  */
    protected $goal;

    /** @var Position[] */
    protected $path = [];
    /** @var int */
    protected $pathIndex = -1;

    /** @var EntityBase */
    protected $holder;

    /** @var PathFinder */
    protected $pathFinder = null;

    public function __construct(EntityBase $entity){
        $this->holder = $entity;
    }

    public function makeRandomGoal() : Position{
        $x = mt_rand(10, 30);
        $y = mt_rand(10, 30);
        $z = mt_rand(10, 30);

        $pos = $this->holder->getPosition();
        $pos->x = Math::floorFloat($pos->x) + 0.5 + (mt_rand(0, 1) ? $x : -$x);
        $pos->y = Math::floorFloat($pos->z) + 0.5 + (mt_rand(0, 1) ? $y : -$y);
        $pos->z = Math::floorFloat($pos->z) + 0.5 + (mt_rand(0, 1) ? $z : -$z);
        return $pos;
    }

    public function getDefaultPathFinder() : PathFinder{
        return new SimplePathFinder($this);
    }

    public function update() : void{
        $pos = $this->holder->getLocation();
        $holder = $this->holder;
        $target = $holder->getTargetEntity();
        if($target === null || !$holder->canInteractWithTarget($target, $near = $pos->distanceSquared($target->getPosition()))){
            $near = PHP_INT_MAX;
            $target = null;
            foreach($holder->getWorld()->getEntities() as $k => $t){
                if(
                    $t === $this
                    || !($t instanceof Living)
                    || ($distance = $pos->distanceSquared($t->getPosition())) > $near
                    || !$holder->canInteractWithTarget($t, $distance)
                ){
                    continue;
                }
                $near = $distance;
                $target = $t;
            }
        }

        if($target !== null){ //따라갈 엔티티가 있는경우
            $holder->setTargetEntity($target);
        }elseif( //없는 경우
            $this->stopDelay >= 100 //장애물에 의해 막혀있거나
            || (!empty($this->path) && $this->pathIndex < 0) //목표지점에 도달했다면
        ){
            $this->setGoal($this->makeRandomGoal());
        }

        if($this->holder->onGround && ($this->pathIndex < 0 || empty($this->path))){ //최종 목적지에 도달했거나 목적지가 변경된 경우
            $this->path = $this->getPathFinder()->search();
            if($this->path === null){
                $this->setGoal($this->makeRandomGoal());
            }else{
                $this->pathIndex = count($this->path) - 1;
            }
        }
    }

    public function next() : ?Position{
        if($this->pathIndex >= 0){
            $next = $this->path[$this->pathIndex];
            if($this->canGoNextPath($next)){
                --$this->pathIndex;
            }

            if($this->pathIndex < 0){
                return null;
            }
        }
        return $this->pathIndex >= 0 ? $this->path[$this->pathIndex] : null;
    }

    public function addStopDelay(int $add) : void{
        $this->stopDelay += $add;
        if($this->stopDelay < 0){
            $this->stopDelay = 0;
        }
    }

    public function canGoNextPath(Position $path) : bool{
        return $this->holder->getPosition()->distanceSquared($path) < 0.04;
    }

    public function getHolder() : EntityBase{
        return $this->holder;
    }

    public function getGoal() : Position{
        return $this->goal ?? $this->goal = $this->makeRandomGoal();
    }

    public function setGoal(Position $pos) : void{
        if($this->goal === null){
            $this->goal = $pos;
            return;
        }

        if(
            Math::floorFloat($pos->x) !== Math::floorFloat($this->goal->x) ||
            (int) $pos->y !== (int) $this->goal->y ||
            Math::floorFloat($pos->z) !== Math::floorFloat($this->goal->z)
        ){ //위치가 변경된 경우
            $this->path = [];
            $this->stopDelay = 0;
            $this->pathIndex = -1;
            $this->getPathFinder()->reset();
        }elseif(count($this->path) > 0){
            $this->path[0] = $pos;
        }
        $this->goal = $pos;
    }

    public function updateGoal() : void{
        $this->path = [];
        $this->stopDelay = 0;
        $this->pathIndex = -1;
        $this->getPathFinder()->reset();
    }

    public function getPathFinder() : PathFinder{
        return $this->pathFinder ?? $this->pathFinder = $this->getDefaultPathFinder();
    }

}