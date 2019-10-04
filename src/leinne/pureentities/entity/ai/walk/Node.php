<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use pocketmine\world\Position;

class Node extends Position{

    private static $nextId = 0;

    /** @var int */
    private $id;
    
    /**
     * F = G + H
     * @var float
     */
    public $fscore = 0.0;
    
    /**
     * 현재까지 이동한 거리
     * @var float
     */
    public $gscore = 0.0;

    /**
     * 휴리스틱 값
     * @var float
     */
    public $hscore = 0.0;
    
    /** @var ?int */
    public $parentNode = null;

    public static function create(Position $pos, Position $goal, ?Node $parent = null) : self{
        $node = new self;
        $node->id = Node::$nextId++;
        $node->x = $pos->x;
        $node->y = $pos->y;
        $node->z = $pos->z;
        $node->world = $pos->world;
        if($parent !== null){
            $node->parentNode = $parent->id;
            $node->gscore = $parent->gscore + $pos->distanceSquared($parent);
        }
        $node->hscore = $pos->distanceSquared($goal);
        $node->fscore = $node->gscore + $node->hscore;

        return $node;
    }

    public function getId() : int{
        return $this->id;
    }

    public function getHash() : string{
        return "{$this->x}:{$this->y}:{$this->z}";
    }

}
