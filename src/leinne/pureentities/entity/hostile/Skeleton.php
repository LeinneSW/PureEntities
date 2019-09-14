<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\WalkEntityTrait;

use pocketmine\entity\Living;
use pocketmine\entity\Entity;
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
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\world\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
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
        $target = $this->getTarget();
        if(!($target instanceof Living)){
            return \false;
        }

        if(++$this->attackDelay >= 32 && \mt_rand(1, 30) === 1){
            $p = ($this->attackDelay - 20) / 20;
            $baseForce = \min((($p ** 2) + $p * 2) / 3, 1);

            $nbt = EntityFactory::createBaseNBT(
                $this->getPosition()->add(0, $this->eyeHeight, 0),
                $this->getDirectionVector(),
                ($this->getLocation()->getYaw() > 180 ? 360 : 0) - $this->getLocation()->getYaw(),
                -$this->getLocation()->getPitch()
            );
            $arrow = new Arrow($this->level, $nbt, $this, $baseForce >= 1);
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
                        $this->attackDelay = 0;
                        $entity->spawnToAll();
                        $this->level->addSound($this->getPosition(), new LaunchSound(), $this->getViewers());
                    }
                }else{
                    $entity->spawnToAll();
                }
            }
        }
        return $this->getLocation()->distanceSquared($target) <= 7.84; //2.5 ** 2
    }

    public function interactTarget() : bool{
        if($this->inventory->getItemInHand() instanceof Bow){
            return $this->interactTargetBow();
        }

        ++$this->attackDelay;
        if(($target = $this->checkInteract()) === \null || !$this->canAttackTarget()){
            return \false;
        }

        if($this->attackDelay >= 20 && ($damage = $this->getResultDamage()) > 0){
            $pk = new ActorEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = ActorEventPacket::ARM_SWING;
            $this->server->broadcastPackets($this->hasSpawned, [$pk]);

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->attackDelay = 0;
            }
        }
        return \true;
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