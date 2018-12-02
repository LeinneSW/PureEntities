<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\utility;

use leinne\pureentities\entity\ai\WalkEntityTrait;
use leinne\pureentities\entity\Monster;

use pocketmine\entity\Creature;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class SnowGolem extends Monster{

    use WalkEntityTrait;

    const NETWORK_ID = self::SNOW_GOLEM;

    public $width = 0.7;
    public $height = 1.9;
    public $eyeHeight = 2.5;

    public function getDefaultMaxHealth() : int{
        return 10;
    }

    public function getName() : string{
        return 'SnowGolem';
    }

    /**
     * TODO: 적대감 변경
     *
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Creature $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function hasInteraction(Creature $target, float $distanceSquare) : bool{
        return $target instanceof Monster && $target->isAlive() && !$target->closed && $distanceSquare <= 196;
    }

    public function interactTarget() : bool{
        // TODO: Implement interactTarget() method.
        return \false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get(Item::SNOWBALL, 0, \mt_rand(0, 15)),
        ];
    }

    public function getXpDropAmount() : int{
        return 0;
    }
}