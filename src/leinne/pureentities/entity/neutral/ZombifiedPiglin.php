<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\Ageable;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class ZombifiedPiglin extends Monster implements Ageable{
    use WalkEntityTrait{
        entityBaseTick as baseTick;
    }

    private bool $angry = false;

    protected bool $baby = false;

    public static function getNetworkTypeId() : string{
        return EntityIds::ZOMBIE_PIGMAN;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.9, 0.6);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->baby = $nbt->getByte("IsBaby", 0) !== 0;
        $this->angry = $nbt->getByte('Angry', 0) !== 0;
        $this->breakDoor = $nbt->getByte("CanBreakDoors", 1) !== 0;
        if($this->isBaby()){
            $this->setScale(0.5);
        }

        $this->setDamages([0, 5, 8, 12]);
    }

    public function getDefaultHeldItem() : Item{
        return VanillaItems::GOLDEN_SWORD();
    }

    public function getName() : string{
        return 'Zombified Piglin';
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
            $this->setAngry(true);
        }
    }

    public function isAngry() : bool{
        return $this->angry;
    }

    public function setAngry(bool $value) : void{
        $this->angry = $value;
    }

    protected function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->isAlive() && $this->angry){
            //TODO: 얘 화 어떻게 풀림?
            //--$this->angry;
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
            $this->broadcastAnimation(new ArmSwingAnimation($this));

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->interactDelay = 0;
            }
        }
        return true;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::BABY, $this->baby);
        $properties->setGenericFlag(EntityMetadataFlags::ANGRY, $this->angry);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("IsBaby", $this->baby ? 1 : 0);
        $nbt->setByte("IsAngry", $this->angry ? 1 : 0);
        $nbt->setByte("CanBreakDoors" , $this->breakDoor ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        $drops = [
            VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 1)),
            VanillaItems::GOLD_NUGGET()->setCount(mt_rand(0, 1)),
        ];

        if(
            $this->lastDamageCause instanceof EntityDamageByEntityEvent
            && $this->lastDamageCause->getDamager() instanceof Player
        ){
            if(mt_rand(0, 199) < 5){
                $drops[] = VanillaItems::GOLD_INGOT();
            }

            if(mt_rand(0, 199) < 17){
                $drops[] = VanillaItems::GOLDEN_SWORD();
            }
        }
        return $drops;
    }

    public function getXpDropAmount() : int{
        if($this->isBaby()){
            return 12;
        }
        return 5;
    }

}