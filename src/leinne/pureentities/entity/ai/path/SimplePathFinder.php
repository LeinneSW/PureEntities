<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path;

use pocketmine\world\Position;

class SimplePathFinder extends PathFinder{

    public function reset() : void{}

    /**
     * 최적 경로를 탐색해 결과를 도출합니다
     *
     * @return Position[]|null
     */
    public function search(): ?array{
        return [$this->navigator->getGoal()];
    }

}