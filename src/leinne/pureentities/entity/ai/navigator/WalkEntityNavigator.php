<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\navigator;

use leinne\pureentities\entity\ai\path\PathFinder;
use leinne\pureentities\entity\ai\path\astar\AStarPathFinder;
use leinne\pureentities\entity\ai\path\SimplePathFinder;
use leinne\pureentities\PureEntities;
use pocketmine\math\Math;
use pocketmine\world\Position;

class WalkEntityNavigator extends EntityNavigator{

    public function canGoNextPath(Position $next) : bool{
        $pos = $this->holder->getPosition();
        return abs($pos->x - $next->x) < 0.1 && abs($pos->z - $next->z) < 0.1;// && $pos->getFloorY() === $next->getFloorY();
    }

    public function makeRandomGoal() : Position{
        $x = mt_rand(10, 30);
        $z = mt_rand(10, 30);

        $pos = $this->holder->getPosition();
        $pos->x = Math::floorFloat($pos->x) + 0.5 + (mt_rand(0, 1) ? $x : -$x);
        $pos->z = Math::floorFloat($pos->z) + 0.5 + (mt_rand(0, 1) ? $z : -$z);
        //$pos->y = $pos->world->getHighestBlockAt((int) $pos->x, (int) $pos->z);
        return $pos;
    }

    public function getDefaultPathFinder() : PathFinder{
        return PureEntities::$enableAstar ? new AStarPathFinder($this) : new SimplePathFinder($this);
    }

}