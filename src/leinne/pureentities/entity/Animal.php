<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use pocketmine\entity\Ageable;
use pocketmine\nbt\tag\CompoundTag;

abstract class Animal extends EntityBase implements Ageable{

    /** @var bool */
    protected $baby;

    public function isBaby() : bool{
        return $this->baby;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("IsBaby", $this->isBaby() ? 1 : 0);
        return $nbt;
    }

}