<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use pocketmine\entity\Ageable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

abstract class Animal extends LivingBase implements Ageable{
    protected bool $baby = false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->baby = $nbt->getByte("IsBaby", mt_rand(0, 99) < 5 ? 1 : 0) !== 0;
        if($this->isBaby()){
            $this->setScale(0.5);
        }
    }

    public function isBaby() : bool{
        return $this->baby;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::BABY, $this->baby);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("IsBaby", $this->isBaby() ? 1 : 0);
        return $nbt;
    }

}