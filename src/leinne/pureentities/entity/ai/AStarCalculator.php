<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\world\Position;

class AStarCalculator{

    private static $maximumTick = 50;
    private static $blockPerTick = 200;

    /** @var Node[] */
    private $openNode = [];
    /** @var Node[] */
    private $closeNode = [];

    /** @var array */
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
        $this->closeNode = [];
    }

    /**
     * @return Position[]
     */
    public function calculate() : ?array{
        if(++$this->findCount > self::$maximumTick){
            return null;
        }

        $end = $this->navigator->getEnd();
        if($this->findTick === -1){
            $pos = $this->navigator->getHolder()->getPosition();
            $pos->x = Math::floorFloat($pos->x) + 0.5;
            $pos->z = Math::floorFloat($pos->z) + 0.5;

            $start = Node::create($pos, $end);
            $this->mapCache = [];
            $this->closeNode = [];
            $this->openNode = [$start];
        }

        $valid = false;
        while(++$this->findTick <= self::$blockPerTick){
            if(empty($this->openNode)){
                break;
            }

            if(count($this->openNode) > 1){
                EntityAI::quickSort($this->openNode, 0, count($this->openNode) - 1);
            }
            $parent = array_shift($this->openNode);
            $this->closeNode["{$parent->position->x}:{$parent->position->y}:{$parent->position->z}"] = $parent;
            if(abs($parent->position->x - $end->x) < 1 && abs($parent->position->z - $end->z) < 1){
                $valid = true;
                break;
            }

            foreach($this->getNear($parent) as $_ => $pos){
                $key = "{$pos->x}:{$pos->y}:{$pos->z}";
                if(isset($this->closeList[$key])){ /** 이미 최적 경로를 찾은 경우 */
                    continue;
                }

                $node = Node::create($pos, $end, $parent);
                if(isset($this->openNode[$key]) && $this->openNode[$key]->gscore <= $node->gscore){ /** 기존 노드보다 이동 거리가 더 길 경우 */
                    continue;
                }
                $this->openNode[$key] = $node;
            }
        }

        if($valid){
            $last = array_pop($this->closeNode);
            $result = [$last->position];
            while(($node = array_pop($this->closeNode)) !== null){
                if($last->parentNode === $node->id){
                    $result[] = $node->position;
                    $last = $node;
                }
            }
            return $result;
        }else{
            if($this->findTick > 100){
                $this->findTick = 0;
            }
        }
        return [];
    }

    /**
     * @param Node $node
     *
     * @return Position[]
     */
    public function getNear(Node $node) : array{
        $result = [];
        //$diagonal = ["1:1" => 1, "1:-1" => 1, "-1:1" => 1, "-1:-1" => 1,];
        $facing = [Facing::EAST, Facing::WEST, Facing::SOUTH, Facing::NORTH];
        foreach($facing as $_ => $f){
            $near = $node->position->getSide($f);
            if(isset($this->mapCache["{$near->x}:{$near->y}:{$near->z}"])){
                $cache = $this->mapCache["{$near->x}:{$near->y}:{$near->z}"];
                if($cache[0] !== EntityAI::WALL){
                    $result[] = $cache[1];
                }
            }else{
                $state = EntityAI::checkBlockState($near);
                $this->mapCache["{$near->x}:{$near->y}:{$near->z}"] = [$state, $near];
                if($state !== EntityAI::WALL){
                    $result[] = $near;
                    $this->calculateYPos($state, $near);
                }
            }

            /*if($state === EntityAI::WALL) {
                switch($f){
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
                }
            }*/
        }

        /*foreach($diagonal as $index => $isWall){
            $i = explode(":", $index);
            $near = clone $node->position;
            $near->x += (int) $i[0];
            $near->z += (int) $i[1];
            if($isWall || ($state = $this->mapCache["{$near->x}:{$near->y}:{$near->z}"] ?? EntityAI::checkBlockState($near)) === EntityAI::WALL){
                $this->mapCache["{$near->x}:{$near->y}:{$near->z}"] = EntityAI::WALL;
                continue;
            }

            $result[] = $near;
            $this->mapCache["{$near->x}:{$near->y}:{$near->z}"] = $state;
            $this->calculateYPos($state, $near);
        }*/
        return $result;
    }

    public function calculateYPos(int $state, Position $pos) : void{
        switch($state){
            case EntityAI::STAIR:
            case EntityAI::BLOCK:
                $pos->y += 1;
                break;
            case EntityAI::SLAB:
                $pos->y += 0.5;
                break;
            case EntityAI::AIR:
                $blockPos = $pos->floor();
                while(--$blockPos->y > 0){
                    $aabb = $pos->world->getBlock($blockPos)->getCollisionBoxes()[0] ?? null;
                    if($aabb !== null){
                        if($aabb->maxY - $aabb->minY > 0.5){
                            ++$blockPos->y;
                        }elseif($aabb->maxY - $aabb->minY === 0.5){
                            $blockPos->y += 0.5;
                        }
                        break;
                    }
                }
                $pos->y = $blockPos->y;
                break;
        }
    }

}