<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;

use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\world\Position;

class AStarHelper{

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

    /** @var int[][] */
    private $mapCache = [];

    private $findTick = -1;
    private $findCount = 0;

    /** @var WalkEntityNavigator */
    private $navigator;

    public static function init(int $tick, int $block) : void{
        self::$maximumTick = $tick;
        self::$blockPerTick = $block;
    }

    public function __construct(WalkEntityNavigator $navigator){
        $this->navigator = $navigator;
    }

    public function reset() : void{
        $this->findTick = -1;
        $this->findCount = 0;

        $this->mapCache = [];
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
        $end->y = $this->calculateYPos($end);
        if($this->findTick === -1){
            //echo "탐색 시작\n";
            ++$this->findTick;
            $pos = $this->navigator->getHolder()->getPosition();
            $pos->x = Math::floorFloat($pos->x) + 0.5;
            $pos->z = Math::floorFloat($pos->z) + 0.5;

            $start = Node::create($pos, $end);
            $this->mapCache = [];
            $this->openHash = [];
            $this->closeNode = [];
            $this->openNode = [$start];
        }

        $valid = null;
        while(++$this->findTick <= self::$blockPerTick){
            if(empty($this->openNode)){
                $valid = false;
                break;
            }

            $this->sortOpenNode(0, count($this->openNode) - 1);
            $parent = array_shift($this->openNode);
            unset($this->openHash[$parent->getHash()]);

            $parent->y = $this->calculateYPos($parent);
            $hash = $parent->getHash();
            if(isset($this->closeNode[$hash]) && $this->closeNode[$hash]->getGoal() <= $parent->getGoal()){ //다른 Y값으로 이미 최적 경로에 도달했을 경우
                continue;
            }

            $this->closeNode[$hash] = $parent;
            if($parent->getFloorX() === $end->getFloorX() && $parent->getFloorZ() === $end->getFloorZ() && $parent->getFloorY() === $end->getFloorY()){
                $valid = true;
                break;
            }

            foreach($this->getNear($parent) as $_ => $pos){
                ++$this->findTick;
                $key = "{$pos->x}:{$pos->y}:{$pos->z}";
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

        if($valid){
            //echo "탐색 끝\n";
            $last = array_pop($this->closeNode);
            $result = [$last];
            while(($node = array_pop($this->closeNode)) !== null){
                if($last->getParentNode() === $node->getId()){
                    $last = $node;
                    $result[] = $node;
                }
            }
            return $result;
        }elseif($valid === false){
            //echo "경로 없음\n";
            return null;
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
        //$diagonal = ["1:1" => 1, "1:-1" => 1, "-1:1" => 1, "-1:-1" => 1];
        $facing = [Facing::EAST, Facing::WEST, Facing::SOUTH, Facing::NORTH];
        foreach($facing as $_ => $f){
            $near = $pos->getSide($f);
            $state = $this->getBlockState($near);
            if($state !== EntityAI::WALL){
                $y = $this->calculateYPos($near);
                if($near->y - $y <= 3){
                    $result[] = $near;
                }
                //continue;
            }
            /*switch($f){
                case Facing::EAST:
                    $diagonal["1:1"] = 0;
                    $diagonal["1:-1"] = 0;
                    break;
                case Facing::WEST:
                    $diagonal["-1:1"] = 0;
                    $diagonal["-1:-1"] = 0;
                    break;
                case Facing::SOUTH:
                    $diagonal["1:1"] = 0;
                    $diagonal["-1:1"] = 0;
                    break;
                case Facing::NORTH:
                    $diagonal["1:-1"] = 0;
                    $diagonal["-1:-1"] = 0;
                    break;
            }*/
        }

        /*foreach($diagonal as $index => $isWall){
            $i = explode(":", $index);
            $near = $pos->asPosition();
            $near->x += (int) $i[0];
            $near->z += (int) $i[1];
            if($isWall){
                $this->mapCache["{$near->x}:{$near->y}:{$near->z}"][0] = EntityAI::WALL;
                continue;
            }elseif($this->getBlockState($near) === EntityAI::WALL){
                continue;
            }

            $result[] = $near;
        }*/
        return $result;
    }

    public function getBlockState(Position $pos) : int{
        if(isset($this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"])){
            $state = $this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"][0];
        }else{
            $state = EntityAI::checkPassablity($pos);
            $this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"][0] = $state;
        }
        return $state;
    }

    public function calculateYPos(Position $pos) : float{
        if(isset($this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"][1])){
            return $this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"][1];
        }
        $y = $pos->y;
        switch($this->getBlockState($pos)){
            case EntityAI::STAIR:
            case EntityAI::BLOCK:
                $y += 1;
                break;
            case EntityAI::SLAB:
                $y += 0.5;
                break;
            case EntityAI::PASS:
                $blockPos = $pos->floor();
                while(--$blockPos->y > 0){
                    $aabb = ($block = $pos->world->getBlock($blockPos))->getCollisionBoxes()[0] ?? null;
                    if($aabb !== null){
                        $diffY = $aabb->maxY - $aabb->minY;
                        if($diffY > 0.5 || $aabb->minY - (int) $aabb->minY === 0.5){
                            ++$blockPos->y;
                        }elseif($diffY === 0.5){
                            $blockPos->y += 0.5;
                        }
                        break;
                    }
                }
                $y = $blockPos->y;
                break;
        }
        $this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"][1] = $y;
        return $y;
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