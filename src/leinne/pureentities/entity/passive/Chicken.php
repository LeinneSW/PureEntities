<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Chicken extends Animal{
    use WalkEntityTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::CHICKEN;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.8, 0.6);
    }

    public function getDefaultMaxHealth() : int{
        return 4;
    }

    public function getName() : string{
        return 'Chicken';
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return false; //TODO: 아이템 유인 구현
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        // TODO: 동물 AI 기능
        return false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::COOKED_CHICKEN : ItemIds::RAW_CHICKEN, 0, 1),
            VanillaItems::FEATHER()->setCount(mt_rand(0, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return mt_rand(1, 3);
    }

}