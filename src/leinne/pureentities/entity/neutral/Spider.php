<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class Spider extends Monster{
    use WalkEntityTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::SPIDER;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.9, 1.4);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setDamages([0, 2, 2, 3]);
    }

    public function getDefaultMaxHealth() : int{
        return 16;
    }

    public function getName() : string{
        return 'Spider';
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        if($this->interactDelay >= 20){
            $target = $this->getTargetEntity();
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->interactDelay = 0;
            }
        }
        return true;
    }

    public function getDrops() : array{
        $drops = [
            VanillaItems::STRING()->setCount(mt_rand(0, 2))
        ];

        if(
            $this->lastDamageCause instanceof EntityDamageByEntityEvent
            && $this->lastDamageCause->getDamager() instanceof Player
            && !mt_rand(0, 2)
        ){
            $drops[] = VanillaItems::SPIDER_EYE();
        }
        return $drops;
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}