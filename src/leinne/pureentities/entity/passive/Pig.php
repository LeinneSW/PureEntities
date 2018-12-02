<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\entity\Creature;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;

class Pig extends Animal{

    use WalkEntityTrait;

    const NETWORK_ID = self::PIG;

    public $width = 1.5;
    public $height = 1.0;
    public $eyeHeight = 1.62;

    public function getDefaultMaxHealth() : int{
        return 10;
    }

    public function getName() : string{
        return 'Pig';
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Creature $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function hasInteraction(Creature $target, float $distanceSquare) : bool{
        return $target instanceof Player && $target->isAlive() && !$target->closed && $distanceSquare <= 64
            && $target->getInventory()->getItemInHand()->getId() === Item::SEEDS; //TODO: 아이템 유인 구현
    }

    public function interactTarget() : bool{
        // TODO: Implement interactTarget() method.
        return \false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get($this->fireTicks > 0 ? Item::COOKED_PORKCHOP : Item::RAW_PORKCHOP, 0, \mt_rand(1, 3))
        ];
    }

    public function getXpDropAmount() : int{
        return \mt_rand(1, 3);
    }

}