<?php

declare(strict_types=1);

namespace milk\pureentities\entity\mob;

use pocketmine\entity\Ageable;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;

class Zombie extends WalkMonster implements Ageable{

    const NETWORK_ID = 32;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(0.9);
        $this->setDamages([0, 3, 4, 6]);
    }

    public function getName() : string{
        return 'Zombie';
    }

    public function isBaby(): bool{
        return \false;
    }

    public function interactTarget() : bool{
        ++$this->attackDelay;
        $target = $this->getTarget();
        if(
            !($target instanceof Creature)
            || !$target->isOnGround()
            || \abs($this->x - $target->x) > $this->width / 2
            || \abs($this->z - $target->z) > $this->width / 2
            || \abs($this->y - $target->y) > 0.5
        ){
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
            ItemFactory::get(Item::ROTTEN_FLESH, 0, \mt_rand(0, 2))
        ];

        if(\mt_rand(0, 199) < 5){
            switch(\mt_rand(0, 2)){
                case 0:
                    $drops[] = ItemFactory::get(Item::IRON_INGOT, 0, 1);
                    break;
                case 1:
                    $drops[] = ItemFactory::get(Item::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = ItemFactory::get(Item::POTATO, 0, 1);
                    break;
            }
        }

        return $drops;
    }

    public function getXpDropAmount() : int{
        return 7;
    }

}