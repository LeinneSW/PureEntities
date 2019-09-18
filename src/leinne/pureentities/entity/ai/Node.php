<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\math\AxisAlignedBB;

class Node{

    /** @var int */
    public $id;
    
    /** @var AxisAlignedBB */
    public $boundingBox;
    
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

    /**
     * @param int $id
     * @param AxisAlignedBB $aabb
     * @param float $fscore
     * @param float $gscore
     * @param $hscore
     * @param int $parentNode
     *
     * @return Node
     */
    public static function create(int $id, AxisAlignedBB $aabb, float $gscore, float $hscore, ?int $parentNode = null) : self{
        $node = new self;
        $node->id = $id;
        $node->boundingBox = $aabb;
        $node->fscore = $gscore + $hscore;
        $node->gscore = $gscore;
        $node->hscore = $hscore;
        $node->parentNode = $parentNode;

        return $node;
    }

}
