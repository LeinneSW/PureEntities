<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\block\utils\Color;
use pocketmine\entity\Creature;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class Sheep extends Animal{

    use WalkEntityTrait;

    const NETWORK_ID = self::SHEEP;

    public $width = 0.9;
    public $height = 1.3;
    public $eyeHeight = 1.2;

    private $color = 0;

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        if($nbt->hasTag("Color")){
            $this->color = $nbt->getByte("Color");
        }else{
            //TODO: 양 색상 랜덤
            $this->color = Color::WHITE;
        }
    }

    public function getDefaultMaxHealth() : int{
        return 8;
    }

    public function getName() : string{
        return 'Sheep';
    }

    public function getColor() : int{
        return $this->color;
    }

    public function setColor(int $color){
        $this->color = $color;
        $this->getDataPropertyManager()->setPropertyValue(self::DATA_COLOR, self::DATA_TYPE_BYTE, $color);
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
            ItemFactory::get(Item::WOOL, $this->color, 1),
            ItemFactory::get($this->fireTicks > 0 ? Item::COOKED_MUTTON : Item::RAW_MUTTON, 0, \mt_rand(1, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return \mt_rand(1, 3);
    }

}