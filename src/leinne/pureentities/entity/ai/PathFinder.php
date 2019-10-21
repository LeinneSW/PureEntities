<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\world\Position;

abstract class PathFinder{

    /**
     * 탐색을 시도할 최대 횟수입니다
     *
     * @var int
     */
    protected static $maximumTick = 0;

    /**
     * 1회 탐색마다 몇개의 블럭을 탐색할지 결정합니다
     *
     * @var int
     */
    protected static $blockPerTick = 0;

    /** @var EntityNavigator */
    protected $navigator;

    public static function setData(int $tick, int $block) : void{
        self::$maximumTick = $tick;
        self::$blockPerTick = $block;
    }

    public function __construct(EntityNavigator $navigator){
        $this->navigator = $navigator;
    }

    /**
     * 데이터를 초기화 시키기 위해 사용됩니다
     */
    public abstract function reset() : void;

    /**
     * 최적 경로를 탐색해 결과를 도출합니다
     *
     * @return Position[]|null
     */
    public abstract function calculate() : ?array;

}