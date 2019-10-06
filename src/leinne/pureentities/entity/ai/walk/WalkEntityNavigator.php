<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityNavigator;

use leinne\pureentities\entity\ai\Helper;
use pocketmine\math\Math;
use pocketmine\world\Position;

class WalkEntityNavigator extends EntityNavigator{

    /** @var Helper */
    protected $helper = null;

    public function setEnd(Position $pos) : void{
        parent::setEnd($pos);

        $this->getHelper()->reset();
    }

    public function makeRandomGoal() : Position{
        $x = mt_rand(8, 25);
        $z = mt_rand(8, 25);

        $pos = $this->holder->getPosition();
        $pos->x = Math::floorFloat($pos->x) + 0.5 + (mt_rand(0, 1) ? $x : -$x);
        $pos->z = Math::floorFloat($pos->z) + 0.5 + (mt_rand(0, 1) ? $z : -$z);
        return $pos;
    }

    public function getHelper() : Helper{
        return $this->helper ?? $this->helper = new AStarHelper($this);
    }

}