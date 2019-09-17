<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;
use pocketmine\player\Player;

class Spider extends Monster{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::SPIDER;

    //TODO: Spider's Size
    public $width = 1.4;
    public $height = 0.9;
    public $eyeHeight = 0.7;

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
        ++$this->attackDelay;
        if(($target = $this->checkInteract()) === \null || !$this->canAttackTarget()){
            return \false;
        }

        if($this->attackDelay >= 20 && ($damage = $this->getResultDamage()) > 0){
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
    }

    public function getDrops() : array{
        $drops = [
            ItemFactory::get(ItemIds::STRING, 0, \mt_rand(0, 2))
        ];

        if(
            $this->lastDamageCause instanceof EntityDamageByEntityEvent
            && $this->lastDamageCause->getDamager() instanceof Player
            && \mt_rand(1, 3) === 1
        ){
            $drops[] = ItemFactory::get(ItemIds::SPIDER_EYE, 0, 1);
        }
        return $drops;
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}