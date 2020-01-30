<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path\astar;

use pocketmine\world\Position;

class Node extends Position{

    private static $nextId = 0;

    /** @var int */
    private $id;
    
    /**
     * 현재까지 이동한 거리
     * @var float
     */
    private $goal = 0.0;

    /**
     * 휴리스틱 값
     * @var float
     */
    private $heuristic = 0.0;
    
    /** @var Node|null */
    private $parentNode = null;

    public static function create(Position $pos, Position $end, ?Node $parent = null) : self{
        $node = new self;
        $node->id = ++Node::$nextId;
        $node->x = $pos->x;
        $node->y = $pos->y;
        $node->z = $pos->z;
        $node->world = $pos->world;
        $node->heuristic = $pos->distanceSquared($end);
        if($parent !== null){
            $node->parentNode = $parent;
            $node->goal = $parent->goal + $pos->distanceSquared($parent);
        }
        return $node;
    }

    public function getId() : int{
        return $this->id;
    }

    public function getGoal() : float{
        return $this->goal;
    }

    public function getFitness() : float{
        return $this->heuristic + $this->goal;
    }

    public function getParentNode() : ?Node{
        return $this->parentNode;
    }

    public function setGoal(float $score) : void{
        $this->goal = $score;
    }

    public function setParentNode(Node $node) : void{
        $this->parentNode = $node;
    }
}
