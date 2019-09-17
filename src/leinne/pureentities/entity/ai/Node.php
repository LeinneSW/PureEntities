<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

class Node{

    public $id;
    
    /**
     * F = G + H
     * @var float
     */
    public $fscore;
    
    /**
     * 부모 노드와의 거리
     * @var float
     */
    public $gscore;

    /**
     * 현재 노드와 목적지까지의 최단 거리
     * @var float
     */
    public $hscore
    
    /** @var int */
    public $parentNode;

}
