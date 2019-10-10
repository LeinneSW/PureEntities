<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\Helper;

use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\world\Position;

class AStarHelper extends Helper{

    /** @var int */
    private static $maximumTick = 0;
    /** @var int */
    private static $blockPerTick = 0;

    /** @var Node[] */
    private $openNode = [];
    /** @var Node[] */
    private $openHash = [];

    /** @var Node[] */
    private $closeNode = [];

    /** @var array */
    //private $onChange = [];

    /** @var int[] */
    private $yCache = [];

    /** @var int[] */
    private $passablity = [];

    private $findTick = -1;
    private $findCount = 0;

    public static function setData(int $tick, int $block) : void{
        self::$maximumTick = $tick;
        self::$blockPerTick = $block;
    }

    public function reset(bool $full = true) : void{
        if($full){
            $this->findTick = -1;
            $this->findCount = 0;
        }

        $this->yCache = [];
        $this->passablity = [];

        //$this->onChange = [];
        $this->openNode = [];
        $this->openHash = [];
        $this->closeNode = [];
    }

    /**
     * 목적지까지의 경로를 구합니다
     *
     * @return Position[]
     */
    public function calculate() : ?array{
        if(++$this->findCount > self::$maximumTick){
            return null;
        }

        $end = $this->navigator->getEnd();
        $end->y = $this->calculateYOffset($end);
        if($this->findTick === -1){
            $this->reset(false);
            $pos = $this->navigator->getHolder()->getPosition();
            $pos->x = Math::floorFloat($pos->x) + 0.5;
            $pos->z = Math::floorFloat($pos->z) + 0.5;
            $this->openNode[] = Node::create($pos, $end);
        }

        $finish = false;
        while(++$this->findTick <= self::$blockPerTick){
            if(empty($this->openNode)){
                $finish = true;
                break;
            }

            $this->sortOpenNode(0, count($this->openNode) - 1);
            $parent = array_shift($this->openNode);
            unset($this->openHash[EntityAI::getHash($parent)]);

            $beforeY = $parent->y;
            $parent->y = $this->calculateYOffset($parent);
            $hash = EntityAI::getHash($parent);
            if($parent->y !== $beforeY){
                $p = $parent->getParentNode();
                if($p !== null){
                    $parent->setGoal($p->getGoal() + $p->distanceSquared($parent));
                }
                //$this->onChange[$hash] = true;
            }

            if(isset($this->closeNode[$hash]) && $this->closeNode[$hash]->getGoal() <= $parent->getGoal()){ /** 이미 최적 경로를 찾은 경우 */
                continue;
            }

            $this->closeNode[$hash] = $parent;
            if($parent->getFloorX() === $end->getFloorX() && $parent->getFloorZ() === $end->getFloorZ() && $parent->getFloorY() === $end->getFloorY()){
                $finish = true;
                break;
            }

            /*$near = $this->getNear($parent);
            if(count($near) < 4){
                $this->onChange[$hash] = true;
            }
            foreach($near as $_ => $pos){*/
            foreach($this->getNear($parent) as $_ => $pos){
                ++$this->findTick;
                $key = EntityAI::getHash($pos);
                if(isset($this->closeNode[$key])){ /** 이미 최적 경로를 찾은 경우 */
                    continue;
                }

                $node = Node::create($pos, $end, $parent);
                if(isset($this->openHash[$key])){ /** 기존 노드보다 이동 거리가 더 길 경우 */
                    if($this->openHash[$key]->getGoal() > $node->getGoal()){
                        $change = $this->openHash[$key];
                        $change->setGoal($node->getGoal());
                        $change->setParentNode($node->getParentNode());
                    }
                }else{
                    $this->openNode[] = $node;
                    $this->openHash[$key] = $node;
                }
            }
        }

        if($finish){
            $last = array_pop($this->closeNode);
            $finish = [$last];
            while(($node = array_pop($this->closeNode)) !== null){
                if($last->getParentNode()->getId() === $node->getId()){
                    $last = $node;
                    //if(isset($this->onChange[EntityAI::getHash($node)])){
                        $finish[] = $node;
                    //}
                }
            }
            return $finish;
        }

        $this->findTick = 0;
        return [];
    }


    /**
     * 해당 노드가 갈 수 있는 근처의 블럭좌표를 구합니다
     *
     * @param Position $pos
     *
     * @return Position[]
     */
    public function getNear(Position $pos) : array{
        $result = [];
        $facing = [Facing::EAST, Facing::WEST, Facing::SOUTH, Facing::NORTH];
        foreach($facing as $_ => $f){
            $near = $pos->getSide($f);
            $state = $this->checkBlockPassablity($near);
            if($state === EntityAI::DOOR){
                if($this->navigator->getHolder()->canBreakDoor()){
                    $result[] = $near;
                }
            }elseif($state !== EntityAI::WALL){
                $y = $this->calculateYOffset($near);
                if($near->y - $y <= 3){
                    $result[] = $near;
                }
            }
        }
        return $result;
    }

    public function checkBlockPassablity(Position $pos) : int{
        $hash = EntityAI::getHash($pos);
        if(!isset($this->mapCache[$hash])){
            $this->passablity[$hash] = EntityAI::checkPassablity($pos);
        }
        return $this->passablity[$hash];
    }

    public function calculateYOffset(Position $pos) : float{
        if(isset($this->yCache[$hash = EntityAI::getHash($pos)])){
            return $this->yCache[$hash];
        }

        $newY = EntityAI::calculateYOffset($pos);
        $this->yCache[$hash] = $newY;
        for($y = $pos->getFloorY() - 1; $y >= (int) $newY; --$y){
            $this->yCache[Math::floorFloat($pos->x) . ":$y:" . Math::floorFloat($pos->z)] = $newY;
        }
        return $newY;
    }

    /**
     * @param int $left
     * @param int $right
     */
    protected function sortOpenNode(int $left, int $right) : void{
        if($left >= $right){
            return;
        }

        $j = $left;
        for($i = $j + 1; $i <= $right; ++$i){
            if($this->openNode[$i]->getFitness() < $this->openNode[$left]->getFitness()){
                ++$j;
                [$this->openNode[$j], $this->openNode[$i]] = [$this->openNode[$i], $this->openNode[$j]];
            }
        }
        [$this->openNode[$left], $this->openNode[$j]] = [$this->openNode[$j], $this->openNode[$left]];
        $this->sortOpenNode($left, $j - 1);
        $this->sortOpenNode($j + 1, $right);
    }

}