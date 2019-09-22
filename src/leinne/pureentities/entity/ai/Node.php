<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\math\Vector3;
use pocketmine\world\Position;

class Node{

    private static $nextId = 0;

    /** @var int */
    public $id;
    
    /** @var Position */
    public $position;
    
    /**
     * F = G + H
     * @var float
     */
    public $fscore = 0.0;
    
    /**
     * 부모 노드와의 거리
     * @var float
     */
    public $gscore = 0.0;

    /**
     * 현재 노드와 목적지까지의 택시 거리
     * @var float
     */
    public $hscore = 0.0;
    
    /** @var ?int */
    public $parentNode = null;

    public static function create(Position $pos, float $gscore, Vector3 $goal, ?int $parentNode = null) : self{
        $node = new self;
        $node->id = Node::$nextId++;
        $node->position = $pos;
        $node->hscore = abs($goal->x - $pos->x) + abs($goal->z - $pos->z);
        $node->gscore = $gscore;
        $node->fscore = $gscore + $node->hscore;
        $node->parentNode = $parentNode;

        return $node;
    }

    public function getPosition() : Position{
        return $this->position->asPosition();
    }

}
