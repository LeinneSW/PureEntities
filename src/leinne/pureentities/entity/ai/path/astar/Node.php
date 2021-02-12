<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path\astar;

use pocketmine\world\Position;

class Node extends Position{

    private static int $nextId = 0;

    private int $id;
    
    /** 현재까지 이동한 거리 */
    private float $goal = 0.0;

    /** 휴리스틱 값 */
    private float $heuristic = 0.0;

    private ?Node $parentNode = null;

    public static function create(Position $pos, Position $end, ?Node $parent = null) : self{
        $node = new self($pos->x, $pos->y, $pos->z, $pos->world);
        $node->id = ++Node::$nextId;
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
