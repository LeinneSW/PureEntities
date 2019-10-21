<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;

use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

class ZombiePigman extends Monster implements Ageable{

    use WalkEntityTrait{
        entityBaseTick as baseTick;
    }

    const NETWORK_ID = EntityLegacyIds::ZOMBIE_PIGMAN;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    /** @var int */
    private $angry = 0;

    /** @var bool */
    protected $baby = false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setAngry($nbt->getInt('Angry', 0));
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::BABY, $this->baby = $nbt->getByte("IsBaby", 0) !== 0);

        if($this->isBaby()){
            $this->width = 0.3;
            $this->height = 0.975;
            $this->eyeHeight = 0.775;
        }

        $this->setDamages([0, 5, 9, 13]);
    }

    public function getDefaultHeldItem() : Item{
        return ItemFactory::get(ItemIds::GOLD_SWORD);
    }

    public function getName() : string{
        return 'Zombie Pigman';
    }

    public function isBaby() : bool{
        return $this->baby;
    }

    public function canInteractWithTarget(Entity $target, float $distance) : bool{
        return $this->isAngry() && parent::canInteractWithTarget($target, $distance);
    }

    public function attack(EntityDamageEvent $source) : void{
        parent::attack($source);

        if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human){
            $this->setAngry();
        }
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(?int $second = null) : void{
        $this->angry = ($second ?? mt_rand(20, 40)) * 20;
    }

    protected function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->isAlive() && $this->angry > 0){
            --$this->angry;
        }

        return $this->baseTick($tickDiff);
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        $target = $this->getTargetEntity();
        if($this->getSpeed() < 2.7 && $this->isAngry() && $target instanceof Living){
            $this->setSpeed(2.7);
        }elseif($this->getSpeed() === 2.7){
            $this->setSpeed(1.0);
        }

        if($this->interactDelay >= 20){
            $pk = new ActorEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = ActorEventPacket::ARM_SWING;
            foreach($this->hasSpawned as $viewer){
                $viewer->getNetworkSession()->sendDataPacket($pk);
            }

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->interactDelay = 0;
            }
        }
        return true;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setInt("Angry", $this->angry);
        $nbt->setByte("IsBaby", $this->isBaby() ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        return [];
    }

    public function getXpDropAmount() : int{
        if($this->isBaby()){
            return 12;
        }
        return 5;
    }

}