<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class Node{

    /** @var int */
    public $id;
    
    /** @var AxisAlignedBB */
    public $boundingBox;

    /** @var World */
    public $world = null;
    
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
     * @param float $gscore
     * @param Vector3 $goal
     * @param int $parentNode
     *
     * @return Node
     */
    public static function create(int $id, AxisAlignedBB $aabb, World $world, float $gscore, Vector3 $goal, ?int $parentNode = null) : self{
        $node = new self;
        $node->id = $id;
        $aabb->minY = (int) $aabb->minY;
        $aabb->maxY = (int) $aabb->maxY;
        $node->boundingBox = $aabb;
        $node->world = $world;
        $node->gscore = $gscore;

        $pos = $node->getPosition();
        $node->hscore = abs($goal->x - $pos->x) + abs($goal->z - $pos->z);
        $node->fscore = $gscore + $node->hscore;
        $node->parentNode = $parentNode;

        return $node;
    }

    public function getPosition() : Position{
        $aabb = $this->boundingBox;
        return new Position(($aabb->minX + $aabb->maxX) / 2, $aabb->minY, ($aabb->minZ + $aabb->maxZ) / 2, $this->world);
    }

}
