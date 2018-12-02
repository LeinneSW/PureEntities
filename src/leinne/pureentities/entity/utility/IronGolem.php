<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\utility;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;

class IronGolem extends Monster{

    use WalkEntityTrait;

    const NETWORK_ID = self::IRON_GOLEM;

    public $width = 1.4;
    public $height = 2.7;
    public $eyeHeight = 2.5;

    private $neutral = \true;

    public function __construct(Level $level, CompoundTag $nbt, bool $isCreated = \false){
        parent::__construct($level, $nbt);

        $this->neutral = !$isCreated;
    }

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(1.3);
        $this->setMaxDamages([0, 11, 21, 31]);
        $this->setMinDamages([0, 4, 7, 10]);
    }

    public function getDefaultMaxHealth() : int{
        return 100;
    }

    public function getName() : string{
        return 'IronGolem';
    }

    public function isNeutral() : bool{
        return $this->neutral;
    }

    public function setNeutral(bool $value) : void{
        $this->neutral = $value;
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
        return ($this->isNeutral() || !($target instanceof Player)) && $target->isAlive() && !$target->closed && $distanceSquare <= 324;
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
            $target->setMotion($target->getMotion()->add(0, 0.5, 0));

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
    }

    public function getDrops() : array{
        return [
            ItemFactory::get(Item::IRON_INGOT, 0, \mt_rand(3, 5)),
            ItemFactory::get(Item::POPPY, 0, \mt_rand(0, 2)),
        ];
    }

    public function getXpDropAmount() : int{
        return 0;
    }
}