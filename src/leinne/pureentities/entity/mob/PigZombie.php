<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use pocketmine\entity\Creature;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class PigZombie extends WalkMonster{

    const NETWORK_ID = self::ZOMBIE_PIGMAN;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    /** @var bool */
    private $angry = \false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setMaxHealth($health = $nbt->getInt("MaxHealth", 20));
        if($nbt->hasTag("HealF", FloatTag::class)){
            $health = $nbt->getFloat("HealF");
        }elseif($nbt->hasTag("Health")){
            $healthTag = $nbt->getTag("Health");
            $health = (float) $healthTag->getValue(); //Older versions of PocketMine-MP incorrectly saved this as a short instead of a float
        }
        $this->setHealth($health);
        $this->setAngry($nbt->getByte('Angry', 0) !== 0);
        $this->inventory->setItemInHand(ItemFactory::get(Item::GOLD_SWORD));
    }

    public function getName() : string{
        return 'Zombie Pigman';
    }

    public function hasInteraction(Creature $target, float $distance) : bool{
        return $this->isAngry() && parent::hasInteraction($target, $distance);
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
        if($this->getSpeed() < 3.5 && $this->isAngry() && $target instanceof Creature){
            $this->setSpeed(3.5);
        }elseif($this->getSpeed() === 3.5){
            $this->setSpeed(1);
        }

        if(($target = parent::checkInteract()) === \null){
            return \false;
        }

        if($this->attackDelay >= 15 && ($damage = $this->getResultDamage()) > 0){
            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = EntityEventPacket::ARM_SWING;
            $this->server->broadcastPacket($this->hasSpawned, $pk);

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
        $nbt->setInt("MaxHealth", $this->getMaxHealth());
        $nbt->setByte("Angry", $this->angry ? 1 : 0);

        return $nbt;
    }

    public function getXpDropAmount() : int{
        //TODO: 정확한 수치 모름
        return 0;
    }

}