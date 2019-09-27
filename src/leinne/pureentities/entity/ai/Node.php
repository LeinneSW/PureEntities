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
     * 현재까지 이동한 거리
     * @var float
     */
    public $gscore = 0.0;

    /**
     * 휴리스틱 값
     * @var float
     */
    public $hscore = 0.0;

    public $wall = false;
    
    /** @var ?int */
    public $parentNode = null;

    public static function create(Position $pos, Vector3 $goal, ?Node $parent = null) : self{
        $node = new self;
        $node->id = Node::$nextId++;
        $node->position = $pos;
        if($parent !== null){
            $parentPos = $parent->position;
            $node->parentNode = $parent->id;
            $node->gscore = $parent->gscore + (abs($parentPos->x - $pos->x) === 1 && abs($parentPos->z - $pos->z) === 1 ? 1.4 : 1);
        }
        $node->hscore = ($goal->x - $pos->x) ** 2 + ($goal->z - $pos->z) ** 2;
        $node->fscore = $node->gscore + $node->hscore;

        return $node;
    }

    public function getPosition() : Position{
        return $this->position->asPosition();
    }

}
