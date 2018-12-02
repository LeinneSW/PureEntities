<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\EntityBase;
use pocketmine\entity\Ageable;
use pocketmine\nbt\tag\CompoundTag;

abstract class Animal extends EntityBase implements Ageable{

    public function isBaby() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_BABY);
    }

}