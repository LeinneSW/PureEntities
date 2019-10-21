<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityNavigator;
use leinne\pureentities\entity\ai\PathFinder;

use pocketmine\math\Math;
use pocketmine\world\Position;

class WalkEntityNavigator extends EntityNavigator{

    public function setEnd(Position $pos) : void{
        parent::setEnd($pos);

        $this->getPathFinder()->reset();
    }

    public function canGoNextNode(Position $next) : bool{
        $pos = $this->holder->getPosition();
        return abs($pos->x - $next->x) < 0.1 && abs($pos->z - $next->z) < 0.1 && $pos->getFloorY() === $next->getFloorY();
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
        return new AStarPathFinder($this);
    }

}