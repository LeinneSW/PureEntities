<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path;

use leinne\pureentities\entity\ai\navigator\EntityNavigator;
use pocketmine\world\Position;

abstract class PathFinder{

    protected EntityNavigator $navigator;

    public function __construct(EntityNavigator $navigator){
        $this->navigator = $navigator;
    }

    /**
     * 기존에 탐색했던 데이터를 제거합니다
     */
    public abstract function reset() : void;

    /**
     * 최적 경로를 탐색해 결과를 도출합니다
     *
     * @return Position[]|null
     */
    public abstract function search() : ?array;

}