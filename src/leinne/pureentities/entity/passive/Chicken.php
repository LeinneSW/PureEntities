<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;

use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;
use pocketmine\player\Player;

class Chicken extends Animal{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::CHICKEN;

    public $width = 1;
    public $height = 0.8;
    public $eyeHeight = 0.6;

    public function getDefaultMaxHealth() : int{
        return 4;
    }

    public function getName() : string{
        return 'Chicken';
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function hasInteraction(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget || $target instanceof Player && $target->isAlive() && !$target->closed && $distanceSquare <= 64
            && $target->getInventory()->getItemInHand()->getId() === ItemIds::SEEDS; //TODO: 아이템 유인 구현
    }

    public function interactTarget() : bool{
        // TODO: Implement interactTarget() method.
        return false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get($this->fireTicks > 0 ? ItemIds::COOKED_CHICKEN : ItemIds::RAW_CHICKEN, 0, 1),
        ];
    }

    public function getXpDropAmount() : int{
        return mt_rand(1, 3);
    }

}