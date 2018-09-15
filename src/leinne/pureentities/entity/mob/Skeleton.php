<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;

class Skeleton extends WalkMonster{

    const NETWORK_ID = EntityIds::SKELETON;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(1.2);
        $this->inventory->setItemInHand(ItemFactory::get(Item::BOW));
    }

    public function getName() : string{
        return 'Skeleton';
    }

    public function interactTarget() : bool{
        $target = $this->getTarget();
        if(!($target instanceof Creature)){
            return \false;
        }

        if(++$this->attackDelay >= 32 && \mt_rand(1, 22) === 1){
            $p = ($this->attackDelay - 22) / 20;
            $force = \min((($p ** 2) + $p * 2) / 3, 1) * 2;

            $nbt = Entity::createBaseNBT(
                $this->add(0, $this->eyeHeight, 0),
                $this->getDirectionVector(),
                ($this->yaw > 180 ? 360 : 0) - $this->yaw,
                -$this->pitch
            );
            $arrow = new Arrow($this->level, $nbt, $this, $force === 2);
            $arrow->setPickupMode(($item = $this->inventory->getItemInHand())->hasEnchantment(Enchantment::INFINITY) ? Arrow::PICKUP_CREATIVE : Arrow::PICKUP_NONE);

            if(($punchLevel = $item->getEnchantmentLevel(Enchantment::PUNCH)) > 0){
                $arrow->setPunchKnockback($punchLevel);
            }

            if(($powerLevel = $item->getEnchantmentLevel(Enchantment::POWER)) > 0){
                $arrow->setBaseDamage($arrow->getBaseDamage() + (($powerLevel + 1) / 2));
            }

            if($item->hasEnchantment(Enchantment::FLAME)){
                $arrow->setOnFire($arrow->getFireTicks() * 20 + 100);
            }

            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $force);
            $this->server->getPluginManager()->callEvent($ev);

            $entity = $ev->getProjectile();
            if($ev->isCancelled()){
                $entity->flagForDespawn();
            }else{
                $entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
                if($entity instanceof Projectile){
                    $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($entity));
                    if($launch->isCancelled()){
                        $entity->flagForDespawn();
                    }else{
                        $this->attackDelay = 0;
                        $entity->spawnToAll();
                        $this->level->addSound(new LaunchSound($this), $this->getViewers());
                    }
                }else{
                    $entity->spawnToAll();
                }
            }
        }
        return $this->distanceSquared($target) <= 7.84; //2.5 ** 2
    }

    public function getXpDropAmount() : int{
        //TODO: 정확한 수치 모름
        return 0;
    }

}