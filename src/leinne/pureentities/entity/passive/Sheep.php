<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\block\utils\DyeColor;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class Sheep extends Animal{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::SHEEP;

    public $width = 0.9;
    public $height = 1.3;
    public $eyeHeight = 1.2;

    /** @var DyeColor */
    private $color;

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        if($nbt->hasTag("Color")){
            $this->color = DyeColor::fromMagicNumber($nbt->getByte("Color"));
        }else{
            //TODO: 양 색상 랜덤
            $this->color = DyeColor::WHITE();
        }

        $this->getNetworkProperties()->setByte(EntityMetadataProperties::COLOR, $this->color->getMagicNumber());
    }

    public function getDefaultMaxHealth() : int{
        return 8;
    }

    public function getName() : string{
        return 'Sheep';
    }

    public function getColor() : DyeColor{
        return $this->color;
    }

    public function setColor(int $color){
        $this->color = $color;
        $this->getNetworkProperties()->setByte(EntityMetadataProperties::COLOR, $color);
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Living $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function hasInteraction(Living $target, float $distanceSquare) : bool{
        return $target instanceof Player && $target->isAlive() && !$target->closed && $distanceSquare <= 64
            && $target->getInventory()->getItemInHand()->getId() === ItemIds::SEEDS; //TODO: 아이템 유인 구현
    }

    public function interactTarget() : bool{
        // TODO: Implement interactTarget() method.
        return \false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get(ItemIds::WOOL, $this->color->getMagicNumber(), 1),
            ItemFactory::get($this->fireTicks > 0 ? ItemIds::COOKED_MUTTON : ItemIds::RAW_MUTTON, 0, \mt_rand(1, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return \mt_rand(1, 3);
    }

}