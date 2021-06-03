<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path\astar;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\path\PathFinder;

use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\world\Position;

class AStarPathFinder extends PathFinder{

    /** @var Node[] */
    private array $openNode = [];
    /** @var Node[] */
    private array $openHash = [];

    /** @var Node[] */
    private array $closeNode = [];

    /** @var bool[] */
    private array $onChange = [];

    /** @var int[] */
    private array $yCache = [];

    /** @var int[] */
    private array $passablity = [];

    private int $findTick = -1;
    private int $findCount = 0;

    /** 탐색을 시도할 최대 시간입니다 */
    protected static int $maximumTick = 0;

    /** 1틱마다 몇개의 블럭을 탐색할지 선택합니다 */
    protected static int $blockPerTick = 0;

    public static function setData(int $tick, int $block) : void{
        self::$maximumTick = $tick;
        self::$blockPerTick = $block;
    }

    protected function sort(int $left = 0, ?int $right = null) : void{
        $right = $right ?? (int) (count($this->openNode) / 2);
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
        $this->sort($left, $j - 1);
        $this->sort($j + 1, $right);
    }

    public function reset() : void{
        $this->findTick = -1;
        $this->findCount = 0;

        $this->yCache = [];
        $this->passablity = [];

        $this->onChange = [];
        $this->openNode = [];
        $this->openHash = [];
        $this->closeNode = [];
    }

    /**
     * 최적 경로를 탐색해 결과를 도출합니다
     *
     * @return Position[]|null
     */
    public function search() : ?array{
        if($this->findCount >= self::$maximumTick){
            return null;
        }

        $end = $this->navigator->getGoal();
        $end->y = $this->calculateYOffset($end);
        if($this->findTick === -1){
            $this->reset();
            $pos = $this->navigator->getHolder()->getPosition();
            $pos->x = Math::floorFloat($pos->x) + 0.5;
            $pos->z = Math::floorFloat($pos->z) + 0.5;
            $this->openNode[] = Node::create($pos, $end);
        }

        $finish = false;
        ++$this->findCount;
        while(++$this->findTick <= self::$blockPerTick){
            if(empty($this->openNode)){
                $finish = true;
                break;
            }

            $this->sort();
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
                $this->onChange[$hash] = true;
            }

            if(isset($this->closeNode[$hash]) && $this->closeNode[$hash]->getGoal() <= $parent->getGoal()){ /** 이미 최적 경로를 찾은 경우 */
                continue;
            }

            $this->closeNode[$hash] = $parent;
            if($parent->getFloorX() === $end->getFloorX() && $parent->getFloorZ() === $end->getFloorZ() && $parent->getFloorY() === $end->getFloorY()){
                $finish = true;
                break;
            }

            $near = $this->getNear($parent);
            if(count($near) < 8){
                $this->onChange[$hash] = true;
            }
            foreach($near as $_ => $pos){
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

        if($finish && count($this->closeNode) > 0){ //탐색 완료
            $last = array_pop($this->closeNode);
            $path = [$last->asPosition()];
            while(($node = array_pop($this->closeNode)) !== null){
                if($last->getParentNode()->getId() === $node->getId()){
                    $last = $node;
                    if(isset($this->onChange[EntityAI::getHash($node)])){
                        $path[] = $node->asPosition();
                    }
                }
            }
            return $path;
        }

        //계속 탐색중
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
        $diagonal = ["1:1" => false, "1:-1" => false, "-1:1" => false, "-1:-1" => false];
        foreach(Facing::HORIZONTAL as $f){
            $near = $pos->getSide($f);
            $state = $this->checkPassablity($near);
            if($state === EntityAI::WALL){
                switch($f){
                    case Facing::EAST:
                        $diagonal["1:1"] = true;
                        $diagonal["1:-1"] = true;
                        break;
                    case Facing::WEST:
                        $diagonal["-1:1"] = true;
                        $diagonal["-1:-1"] = true;
                        break;
                    case Facing::SOUTH:
                        $diagonal["1:1"] = true;
                        $diagonal["-1:1"] = true;
                        break;
                    case Facing::NORTH:
                        $diagonal["1:-1"] = true;
                        $diagonal["-1:-1"] = true;
                        break;
                }
            }else{
                if($state === EntityAI::DOOR){
                    if($this->navigator->getHolder()->canBreakDoors()){
                        $result[] = $near;
                    }
                }elseif($near->y - $this->calculateYOffset($near) <= 3){
                    $result[] = $near;
                }
            }
        }

        foreach($diagonal as $index => $isWall){
            $i = explode(":", $index);
            $near = $pos->asPosition();
            $near->x += (int) $i[0];
            $near->z += (int) $i[1];
            $state = $this->checkPassablity($near);
            if($isWall || $state === EntityAI::WALL){
                $this->passablity[EntityAI::getHash($near)] = EntityAI::WALL;
                continue;
            }

            if($state === EntityAI::DOOR){
                if($this->navigator->getHolder()->canBreakDoors()){
                    $result[] = $near;
                }
            }elseif($near->y - $this->calculateYOffset($near) <= 3){
                $result[] = $near;
            }
        }
        return $result;
    }

    public function checkPassablity(Position $pos) : int{
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

}