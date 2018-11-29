<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use leinne\pureentities\entity\ai\WalkEntityTrait;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Spider extends Monster{

    use WalkEntityTrait;

    const NETWORK_ID = self::SPIDER;

    //TODO: Spider's Size
    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

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

    public function getDrops() : array{
        return [];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}