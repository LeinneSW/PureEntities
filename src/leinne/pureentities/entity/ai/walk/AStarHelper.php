<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\Helper;

use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class AStarHelper implements Helper{

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

    /** @var WalkEntityNavigator */
    private $navigator;

    public static function init(int $tick, int $block) : void{
        self::$maximumTick = $tick;
        self::$blockPerTick = $block;
    }

    public function __construct(WalkEntityNavigator $navigator){
        $this->navigator = $navigator;
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
        $end->y = $this->calculateYPos($end);
        if($this->findTick === -1){
            $this->reset(false);
            $pos = $this->navigator->getHolder()->getPosition();
            $pos->x = Math::floorFloat($pos->x) + 0.5;
            $pos->z = Math::floorFloat($pos->z) + 0.5;
            $this->openNode[] = Node::create($pos, $end);
        }

        $result = -1;
        while(++$this->findTick <= self::$blockPerTick){
            if(empty($this->openNode)){
                $result = 0;
                break;
            }

            $this->sortOpenNode(0, count($this->openNode) - 1);
            $parent = array_shift($this->openNode);
            unset($this->openHash[EntityAI::getHash($parent)]);

            $beforeY = $parent->y;
            $parent->y = $this->calculateYPos($parent);
            $hash = EntityAI::getHash($parent);
            if($parent->y !== $beforeY){
                $p = $parent->getParentNode();
                if($p !== null){
                    $parent->setGoal($p->getGoal() + $p->distanceSquared($parent));
                }
                //$this->onChange[$hash] = true;
            }

            if(isset($this->closeNode[$hash]) && $this->closeNode[$hash]->getGoal() <= $parent->getGoal()){ //다른 Y값으로 이미 최적 경로에 도달했을 경우
                continue;
            }

            $this->closeNode[$hash] = $parent;
            if($parent->getFloorX() === $end->getFloorX() && $parent->getFloorZ() === $end->getFloorZ() && $parent->getFloorY() === $end->getFloorY()){
                $result = 1;
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

        if($result === 1){
            $last = array_pop($this->closeNode);
            $result = [$last];
            while(($node = array_pop($this->closeNode)) !== null){
                if($last->getParentNode()->getId() === $node->getId()){
                    $last = $node;
                    //if(isset($this->onChange[EntityAI::getHash($node)])){
                        $result[] = $node;
                    //}
                }
            }
            return $result;
        }elseif($result === 0){
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
        $facing = [Facing::EAST, Facing::WEST, Facing::SOUTH, Facing::NORTH];
        foreach($facing as $_ => $f){
            $near = $pos->getSide($f);
            $state = $this->getBlockPassablity($near);
            if($state !== EntityAI::WALL){
                $y = $this->calculateYPos($near);
                if($near->y - $y <= 3){
                    $result[] = $near;
                }
            }
        }
        return $result;
    }

    public function getBlockPassablity(Position $pos) : int{
        if(!isset($this->mapCache["{$pos->x}:{$pos->y}:{$pos->z}"])){
            $this->passablity["{$pos->x}:{$pos->y}:{$pos->z}"] = EntityAI::checkPassablity($pos);
        }
        return $this->passablity["{$pos->x}:{$pos->y}:{$pos->z}"];
    }

    public function calculateYPos(Position $pos) : float{
        if(isset($this->yCache[$hash = EntityAI::getHash($pos)])){
            return $this->yCache[$hash];
        }

        $newY = (int) $pos->y;
        switch(EntityAI::checkBlockState($pos)){
            case EntityAI::BLOCK:
                $newY += 1;
                break;
            case EntityAI::SLAB:
                $newY += 0.5;
                break;
            case EntityAI::PASS:
                $newPos = new Vector3(Math::floorFloat($pos->x), $pos->getFloorY(), Math::floorFloat($pos->z));
                for(; $newPos->y >= 0; $newPos->y -= 1){
                    $block = $pos->world->getBlockAt($newPos->x, $newPos->y, $newPos->z);
                    $state = EntityAI::checkBlockState($block);
                    if($state === EntityAI::UP_SLAB || $state === EntityAI::BLOCK || $state === EntityAI::SLAB){
                        foreach($block->getCollisionBoxes() as $_ => $bb){
                            if($newPos->y < $bb->maxY){
                                $newPos->y = $bb->maxY;
                            }
                        }
                        break;
                    }
                }
                $newY = $newPos->y;
                break;
        }
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