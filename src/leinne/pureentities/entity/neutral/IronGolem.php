<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class IronGolem extends Monster{
    use WalkEntityTrait;

    private bool $playerCreated = false;

    public static function getNetworkTypeId() : string{
        return EntityIds::IRON_GOLEM;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(2.9, 1.4);
    }

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->playerCreated = $nbt->getByte("PlayerCreated", 0) !== 0;

        $this->setSpeed(0.25);
        $this->setMinDamages([0, 4.75, 7.5, 11.25]);
        $this->setMaxDamages([0, 11.75, 21.5, 32.25]);
    }

    public function getDefaultMaxHealth() : int{
        return 100;
    }

    public function getName() : string{
        return "Iron Golem";
    }

    public function isPlayerCreated() : bool{
        return $this->playerCreated;
    }

    public function canSpawnPeaceful() : bool{
        return true;
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        if($target instanceof Player && ($this->isPlayerCreated() || !$target->isSurvival())){
            return false;
        }elseif($target instanceof IronGolem){
            return false;
        }
        return $this->fixedTarget || ($target instanceof Monster || !$this->isPlayerCreated()) && $target->isAlive() && !$target->closed && $distanceSquare <= 324;
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        if($this->interactDelay >= 20){
            $target = $this->getTargetEntity();
            if($target instanceof Player){
                $damage = $this->getResultDamage();
            }else{
                $damage = $this->getResultDamage(2);
            }

            if($damage >= 0){
                $this->broadcastAnimation(new ArmSwingAnimation($this));

                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
                $target->attack($ev);
                if(!$ev->isCancelled()){
                    $this->interactDelay = 0;
                    $target->setMotion($target->getMotion()->add(0, 0.45, 0));
                }
            }
        }
        return true;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("PlayerCreated", $this->playerCreated ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        return [
            VanillaItems::IRON_INGOT()->setCount(mt_rand(3, 5)),
            ItemFactory::getInstance()->get(ItemIds::POPPY, 0, mt_rand(0, 2)),
        ];
    }

    public function getXpDropAmount() : int{
        return 0;
    }

}