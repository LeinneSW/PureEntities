<?php

declare(strict_types=1);

namespace milk\pureentities\entity\mob;

use pocketmine\entity\Creature;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;

class PigZombie extends WalkMonster{

    const NETWORK_ID = 36;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    /** @var bool */
    private $angry = \false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(0.9);
        if($nbt->hasTag('Angry', ByteTag::class)){
            $this->angry = $nbt->getByte('Angry') !== 0 ? \true : \false;
        }
        $this->inventory->setItemInHand(ItemFactory::get(Item::GOLD_SWORD));
    }

    public function getName() : string{
        return 'PigZombie';
    }

    public function isHostility(Creature $target, float $distance) : bool{
        return $this->isAngry() && parent::isHostility($target, $distance);
    }

    public function attack(EntityDamageEvent $source) : void{
        parent::attack($source);

        if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human){
            $this->setAngry();
        }
    }

    public function isAngry() : bool{
        return $this->angry;
    }

    public function setAngry(bool $angry = \true) : void{
        $this->angry = $angry;
    }

    public function interactTarget() : bool{
        ++$this->attackDelay;
        $target = $this->getTarget();
        if($this->getSpeed() < 2.4 && $this->isAngry() && $target instanceof Creature){
            $this->setSpeed(2.4);
        }elseif($this->getSpeed() === 2.4){
            $this->setSpeed(0.9);
        }

        if(
            !($target instanceof Creature)
            || !$target->isOnGround()
            || \abs($this->x - $target->x) > $this->width / 2
            || \abs($this->z - $target->z) > $this->width / 2
            || \abs($this->y - $target->y) > 0.5
        ){
            return \false;
        }

        if($this->attackDelay >= 15 && ($damage = $this->getResultDamage()) > 0){
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("Angry", $this->angry ? 1 : 0);

        return $nbt;
    }

    public function getXpDropAmount() : int{
        return 7;
    }

}