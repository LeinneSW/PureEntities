<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\inventory\MonsterInventory;
use pocketmine\entity\Entity;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class Monster extends LivingBase{

    protected MonsterInventory $inventory;

    protected bool $allowWeaponDamage = false;

    /** @var float[] */
    private array $minDamage = [0.0, 0.0, 0.0, 0.0];
    /** @var float[] */
    private array $maxDamage = [0.0, 0.0, 0.0, 0.0];

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        if($nbt->getTag("MinDamages") !== null){
            $this->minDamage = $nbt->getListTag("MinDamages")->getAllValues();
        }
        if($nbt->getTag("MaxDamages") !== null){
            $this->minDamage = $nbt->getListTag("MaxDamages")->getAllValues();
        }

        $this->inventory = new MonsterInventory($this);
        if($nbt->getTag("HeldItem") !== null){
            $item = Item::nbtDeserialize($nbt->getCompoundTag("HeldItem"));
        }else{
            $item = $this->getDefaultHeldItem();
        }

        if(!$item->isNull()){
            $this->inventory->setItemInHand($item);
        }

        $this->inventory->getListeners()->add(new CallbackInventoryListener(
            function(Inventory $inv, int $slot, Item $oldItem) : void{
                /** @var MonsterInventory $inv */
                $itemStack = TypeConverter::getInstance()->coreItemStackToNet($inv->getItemInHand());
                foreach($this->getViewers() as $viewer){
                    $viewer->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->getId(), ItemStackWrapper::legacy($itemStack), 0, ContainerIds::INVENTORY));
                }
            },
            function(Inventory $inv, array $oldContents) : void{
                /** @var MonsterInventory $inv */
                $itemStack = TypeConverter::getInstance()->coreItemStackToNet($inv->getItemInHand());
                foreach($this->getViewers() as $viewer){
                    $viewer->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->getId(), ItemStackWrapper::legacy($itemStack), 0, ContainerIds::INVENTORY));
                }
            }
        ));
    }

    public function canBreakDoors() : bool{
        return true;
    }

    public function canSpawnPeaceful() : bool{
        return false;
    }

    public function canInteractTarget() : bool{
        return $this->isAttackable() && parent::canInteractTarget();
    }

    public function getDefaultHeldItem() : Item{
        return ItemFactory::air();
    }

    public function getInventory() : MonsterInventory{
        return $this->inventory;
    }

    public function canInteractWithTarget(Entity $target, float $distance) : bool{
        return $this->fixedTarget || ($target instanceof Player && $target->isSurvival() && $target->spawned && $target->isAlive() && !$target->closed && $distance <= 324);
    }

    public function isAttackable() : bool{
        return $this->getMaxDamage() > 0 || ($this->allowWeaponDamage && $this->inventory->getItemInHand()->getAttackPoints() > 0);
    }

    public function setAllowWeaponDamage(bool $value) : void{
        $this->allowWeaponDamage = $value;
    }
    
    public function isAllowWeaponDamage() : bool{
        return $this->allowWeaponDamage;
    }
    
    /**
     * @param int $difficulty
     *
     * @return float[]
     */
    public function getDamages(int $difficulty = -1) : array{
        return [$this->getMinDamage($difficulty), $this->getMaxDamage($difficulty)];
    }

    public function getResultDamage(int $difficulty = -1) : float{
        $damages = $this->getDamages($difficulty);
        $damage = $damages[0] === $damages[1] ? $damages[0] : $damages[0] + lcg_value() * ($damages[1] - $damages[0]);
        if($this->allowWeaponDamage){
            $damage += $this->inventory->getItemInHand()->getAttackPoints();
        }
        return $damage;
    }

    public function getMinDamage(int $difficulty = -1) : float{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->minDamage[$difficulty];
    }

    public function getMaxDamage(int $difficulty = -1) : float{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->maxDamage[$difficulty];
    }

    public function setMinDamage(float $damage, int $difficulty = -1) : void{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $this->minDamage[$difficulty] = min($damage, $this->maxDamage[$difficulty]);
    }

    public function setMaxDamage(float $damage, int $difficulty = -1) : void{
        if($difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $this->maxDamage[$difficulty] = max($damage, $this->minDamage[$difficulty]);
    }

    public function setDamage(float $damage, int $difficulty = -1) : void{
        if($difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $this->minDamage[$difficulty] = $this->maxDamage[$difficulty] = $damage;
    }

    /**
     * @param float[] $damages
     */
    public function setDamages(array $damages) : void{
        foreach($damages as $i => $damage){
            $this->minDamage[$i] = $this->maxDamage[$i] = (float) $damage;
        }
    }

    /**
     * @param float[] $damages
     */
    public function setMinDamages(array $damages) : void{
        foreach($damages as $i => $damage){
            $this->minDamage[$i] = min((float) $damage, $this->maxDamage[$i]);
        }
    }

    /**
     * @param float[] $damages
     */
    public function setMaxDamages(array $damages) : void{
        foreach($damages as $i => $damage){
            $this->maxDamage[$i] = max((float) $damage, $this->minDamage[$i]);
        }
    }

    protected function sendSpawnPacket(Player $player) : void{
        parent::sendSpawnPacket($player);

        $player->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->id, ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->inventory->getItemInHand())), 0, ContainerIds::INVENTORY));
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();

        $min = [];
        $max = [];
        for($i = 0; $i < 4; ++$i){
            $min[$i] = new DoubleTag($this->minDamage[$i]);
            $max[$i] = new DoubleTag($this->maxDamage[$i]);
        }
        $nbt->setTag("MinDamages", new ListTag($min));
        $nbt->setTag("MaxDamages", new ListTag($max));
        $nbt->setTag("HeldItem", $this->inventory->getItemInHand()->nbtSerialize());
        return $nbt;
    }

}