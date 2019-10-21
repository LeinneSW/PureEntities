<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;

use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;

class Skeleton extends Monster{

    use WalkEntityTrait;

    const NETWORK_ID = EntityLegacyIds::SKELETON;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(1.2);
    }

    public function getDefaultHeldItem() : Item{
        return ItemFactory::get(ItemIds::BOW);
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

            $nbt = EntityFactory::createBaseNBT(
                $this->getPosition()->add(0, $this->eyeHeight, 0),
                $this->getDirectionVector(),
                ($this->getLocation()->getYaw() > 180 ? 360 : 0) - $this->getLocation()->getYaw(),
                -$this->getLocation()->getPitch()
            );
            $arrow = new Arrow($this->getWorld(), $nbt, $this, $baseForce >= 1);
            //TODO: 올바른 화살 대미지[1~4(쉬움, 보통), 1~5(어려움)]
            //$arrow->setBaseDamage($arrow->getBaseDamage() + $this->getResultDamage());
            $arrow->setPickupMode(($item = $this->inventory->getItemInHand())->hasEnchantment(Enchantment::INFINITY()) ? Arrow::PICKUP_CREATIVE : Arrow::PICKUP_NONE);

            if(($punchLevel = $item->getEnchantmentLevel(Enchantment::PUNCH())) > 0){
                $arrow->setPunchKnockback($punchLevel);
            }

            if(($powerLevel = $item->getEnchantmentLevel(Enchantment::POWER())) > 0){
                $arrow->setBaseDamage($arrow->getBaseDamage() + (($powerLevel + 1) / 2));
            }

            if($item->hasEnchantment(Enchantment::FLAME())){
                $arrow->setOnFire($arrow->getFireTicks() * 20 + 100);
            }

            $this->interactDelay = 0;
            $ev = new EntityShootBowEvent($this, ItemFactory::get(ItemIds::ARROW, 0, 1), $arrow, $baseForce * 3);
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
                        $this->getWorld()->addSound($this->getPosition(), new LaunchSound(), $this->getViewers());
                    }
                }else{
                    $entity->spawnToAll();
                }
            }
        }
        return $this->getLocation()->distanceSquared($target->getPosition()) <= 7.84; //2.5 ** 2
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
        //TODO: 드롭 아이템 개수
        return [
            ItemFactory::get(ItemIds::BOW, 0, 0),
            ItemFactory::get(ItemIds::ARROW, 0, 0),
        ];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}