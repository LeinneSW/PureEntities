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

class Cow extends Animal{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::COW;

    public $width = 1.5;
    public $height = 1.2;
    public $eyeHeight = 1.0;

    public function getDefaultMaxHealth() : int{
        return 10;
    }

    public function getName() : string{
        return 'Cow';
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget || $target instanceof Player && $target->isAlive() && !$target->closed && $distanceSquare <= 64
            && $target->getInventory()->getItemInHand()->getId() === ItemIds::SEEDS; //TODO: 아이템 유인 구현
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        // TODO: Implement interactTarget() method.
        return false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get(ItemIds::LEATHER, 0, mt_rand(0, 2)),
            ItemFactory::get($this->fireTicks > 0 ? ItemIds::STEAK : ItemIds::RAW_BEEF, 0, mt_rand(1, 3)),
        ];
    }

    public function getXpDropAmount() : int{
        return mt_rand(1, 3);
    }

}