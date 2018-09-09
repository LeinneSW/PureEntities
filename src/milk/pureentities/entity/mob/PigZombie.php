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

class PigZombie extends Monster{

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

    public function isHostility(Creature $target, float $distance) : bool{
        return $this->isAngry() && parent::isHostility($target, $distance);
    }

    public function attack(EntityDamageEvent $source) : void{
        parent::attack($source);

        if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human){
            $this->setAngry();
        }
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->server->getDifficulty() < 1){
            $this->close();
            return \false;
        }

        if($this->closed){
            return \false;
        }

        ++$this->attackDelay;

        parent::entityBaseTick($tickDiff);

        $target = $this->checkTarget();

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        $diff = \abs($x) + \abs($z);
        if($diff === 0){
            return \true;
        }

        $calX = $x / $diff;
        $calZ = $z / $diff;

        if(!$this->interactTarget() && $this->onGround){
            $this->motion->x += $this->getSpeed() * 0.12 * $calX;
            $this->motion->z += $this->getSpeed() * 0.12 * $calZ;
        }

        $this->yaw = -atan2($calX, $calZ) * 180 / M_PI;
        $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));

        return \true;
    }

    public function isAngry() : bool{
        return $this->angry;
    }

    public function setAngry(bool $angry = \true) : void{
        $this->angry = $angry;
    }

    public function getName() : string{
        return 'PigZombie';
    }

    public function interactTarget() : bool{
        if($this->getSpeed() < 2.4 && $this->isAngry() && $this->target instanceof Creature){
            $this->setSpeed(2.4);
        }elseif($this->getSpeed() === 2.4){
            $this->setSpeed(0.9);
        }

        if(
            !($this->target instanceof Creature)
            || \abs($this->x - $this->target->x) > 0.35
            || \abs($this->z - $this->target->z) > 0.35
            || \abs($this->y - $this->target->y) > 0.001
        ){
            return \false;
        }

        if($this->attackDelay >= 15 && ($damage = $this->getResultDamage()) > 0){
            $ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $this->target->attack($ev);

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

}