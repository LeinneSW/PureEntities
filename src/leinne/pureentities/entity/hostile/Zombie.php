<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;

use pocketmine\entity\Ageable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

class Zombie extends Monster implements Ageable{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::ZOMBIE;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected $stepHeight = 0.6;

    /** @var bool */
    protected $baby = false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::BABY, $this->baby = ($nbt->getByte("IsBaby", 0) !== 0));

        if($this->isBaby()){
            $this->width = 0.3;
            $this->height = 0.975;
            $this->eyeHeight = 0.775;
        }

        $this->setSpeed(0.9);
        $this->setDamages([0, 2, 3, 4]);
    }

    public function getName() : string{
        return 'Zombie';
    }

    public function isBaby(): bool{
        return $this->baby;
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        if($this->interactDelay >= 20){
            $pk = new ActorEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = ActorEventPacket::ARM_SWING;
            foreach($this->hasSpawned as $viewer){
                $viewer->getNetworkSession()->sendDataPacket($pk);
            }

            $target = $this->getTargetEntity();
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
        $nbt->setByte("IsBaby", $this->isBaby() ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        $drops = [
            ItemFactory::get(ItemIds::ROTTEN_FLESH, 0, mt_rand(0, 2))
        ];

        if(mt_rand(0, 199) < 5){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = ItemFactory::get(ItemIds::IRON_INGOT, 0, 1);
                    break;
                case 1:
                    $drops[] = ItemFactory::get(ItemIds::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = ItemFactory::get(ItemIds::POTATO, 0, 1);
                    break;
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