<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\navigator;

use leinne\pureentities\entity\LivingBase;
use leinne\pureentities\entity\ai\path\PathFinder;
use pocketmine\entity\Living;
use pocketmine\math\Math;
use pocketmine\world\Position;

abstract class EntityNavigator{

    /** 벽 등의 장애로 인해 멈춰있는 시간을 나타냅니다 */
    private int $stopDelay = 0;

    protected ?Position $goal = null;

    /** @var Position[] */
    protected ?array $path = [];

    protected int $pathIndex = -1;

    protected LivingBase $holder;

    protected ?PathFinder $pathFinder = null;

    public abstract function makeRandomGoal() : Position;

    public abstract function getDefaultPathFinder() : PathFinder;

    public function __construct(LivingBase $entity){
        $this->holder = $entity;
    }

    public function update() : void{
        $pos = $this->holder->getLocation();
        $holder = $this->holder;
        $target = $holder->getTargetEntity();
        if($target === null || !$holder->canInteractWithTarget($target, $near = $pos->distanceSquared($target->getPosition()))){
            //$target = $pos->world->getNearestEntity($pos, 0, Living::class); TODO: 엔티티의 최대 탐지 거리 메서드를 추가하여 매우 빠른 엔티티 탐색
            $near = PHP_INT_MAX;
            $target = null;
            foreach($holder->getWorld()->getEntities() as $k => $t){ //이것이 굉장한 렉을 유발함
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

    public function getHolder() : LivingBase{
        return $this->holder;
    }

    public function getGoal() : Position{
        return $this->goal ??= $this->makeRandomGoal();
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
        ){ //최종 목적지의 정수값이 변경된 경우
            $this->path = [];
            $this->stopDelay = 0;
            $this->pathIndex = -1;
            $this->getPathFinder()->reset();
        }elseif(count($this->path) > 0){ //현재 진행중인 경로가 있을경우
            $this->path[0] = $pos; //마지막 경로를 변경한다
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
        return $this->pathFinder ??= $this->getDefaultPathFinder();
    }

}