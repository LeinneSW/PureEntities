<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\animal;

use pocketmine\entity\Creature;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;

class Sheep extends Animal{

    const NETWORK_ID = self::SHEEP;

    //TODO: 크기를 몰라 구현 지연중...
    /*public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;*/

    protected function initEntity(CompoundTag $nbt = null): void{
        parent::initEntity($nbt);
		if($nbt === null){
			$nbt = $this->namedtag;
		}

        $health = $nbt->getInt("MaxHealth", 8);
        $this->setMaxHealth($health);
        if($nbt->hasTag("HealF", FloatTag::class)){
            $health = $nbt->getFloat("HealF");
        }elseif($nbt->hasTag("Health")){
            $healthTag = $nbt->getTag("Health");
            $health = (float) $healthTag->getValue();
        }
        $this->setHealth($health);
    }

    public function getName() : string{
        return 'Sheep';
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
        return $target instanceof Player && $target->isAlive() && !$target->closed && $target->getInventory()->getItemInHand()->getId() === Item::SEEDS && $distanceSquare <= 49;
    }

    public function interactTarget() : bool{
        // TODO: Implement interactTarget() method.
        return \false;
    }

}