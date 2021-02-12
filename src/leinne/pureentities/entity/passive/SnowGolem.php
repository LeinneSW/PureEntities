<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use leinne\pureentities\entity\Monster;
use leinne\pureentities\sound\ShearSound;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\item\Shears;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class SnowGolem extends Monster{
    use WalkEntityTrait;

    private bool $pumpkin = true;

    public static function getNetworkTypeId() : string{
        return EntityIds::SNOW_GOLEM;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.4);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->pumpkin = $nbt->getByte("Pumpkin", 1) !== 0;
    }

    public function getDefaultMaxHealth() : int{
        return 4;
    }

    public function getName() : string{
        return 'Snow Golem';
    }

    public function interact(Player $player, Item $item) : bool{
        if($item instanceof Shears && $this->pumpkin){
            $item->applyDamage(1);
            $this->pumpkin = false;
            $this->getWorld()->addSound($this->location, new ShearSound());
            return true;
        }
        return false;
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return false; //TODO: 적대감 변경
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        //TODO: 눈덩이 던지기
        return false;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::SHEARED, !$this->pumpkin);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("Pumpkin", $this->pumpkin ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        return [
            VanillaItems::SNOWBALL()->setCount(mt_rand(0, 15))
        ];
    }

    public function getXpDropAmount() : int{
        return 0;
    }
}