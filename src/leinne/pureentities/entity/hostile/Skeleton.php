<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;

class Skeleton extends Monster{
    use WalkEntityTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::SKELETON;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.9, 0.6);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(1.2);
    }

    public function getDefaultHeldItem() : Item{
        return VanillaItems::BOW();
    }

    public function getName() : string{
        return 'Skeleton';
    }

    public function interactTargetBow() : bool{
        $target = $this->getTargetEntity();
        if($target === null){
            return false;
        }

        if(++$this->interactDelay >= 32 && mt_rand(1, 30) === 1){
            $p = ($this->interactDelay - 20) / 20;
            $baseForce = min((($p ** 2) + $p * 2) / 3, 1);

            $arrow = new Arrow(Location::fromObject(
                $this->getEyePos(),
                $this->getWorld(),
                ($this->location->yaw > 180 ? 360 : 0) - $this->location->yaw,
                -$this->location->pitch
            ), $this, $baseForce >= 1);
            $arrow->setMotion($this->getDirectionVector());
            //TODO: 올바른 화살 대미지[2~5(쉬움, 보통), 3~5(어려움)]
            //$arrow->setBaseDamage($arrow->getBaseDamage() + $this->getResultDamage());
            $arrow->setPickupMode(($item = $this->inventory->getItemInHand())->hasEnchantment(VanillaEnchantments::INFINITY()) ? Arrow::PICKUP_CREATIVE : Arrow::PICKUP_NONE);

            if(($punchLevel = $item->getEnchantmentLevel(VanillaEnchantments::PUNCH())) > 0){
                $arrow->setPunchKnockback($punchLevel);
            }

            if(($powerLevel = $item->getEnchantmentLevel(VanillaEnchantments::POWER())) > 0){
                $arrow->setBaseDamage($arrow->getBaseDamage() + (($powerLevel + 1) / 2));
            }

            if($item->hasEnchantment(VanillaEnchantments::FLAME())){
                $arrow->setOnFire($arrow->getFireTicks() * 20 + 100);
            }

            $this->interactDelay = 0;
            $ev = new EntityShootBowEvent($this, $item, $arrow, $baseForce * 3);
            $ev->call();

            $entity = $ev->getProjectile();
            if($ev->isCancelled()){
                $entity->flagForDespawn();
            }else{
                $entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
                if($entity instanceof Projectile){
                    $launch = new ProjectileLaunchEvent($entity);
                    $launch->call();

                    if($launch->isCancelled()){
                        $entity->flagForDespawn();
                    }else{
                        $entity->spawnToAll();
                        $this->getWorld()->addSound($this->location, new LaunchSound());
                    }
                }else{
                    $entity->spawnToAll();
                }
            }
        }
        return $this->location->distanceSquared($target->location) <= 7.84; //2.5 ** 2
    }

    public function interactTarget() : bool{
        if($this->inventory->getItemInHand() instanceof Bow){
            return $this->interactTargetBow();
        }

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
        return [
            VanillaItems::BONE()->setCount(mt_rand(0, 2)),
            VanillaItems::ARROW()->setCount(mt_rand(0, 2)),
        ];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}