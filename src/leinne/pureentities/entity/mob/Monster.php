<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use leinne\pureentities\entity\EntityBase;
use leinne\pureentities\inventory\MonsterInventory;
use pocketmine\entity\Creature;
use pocketmine\inventory\EntityInventoryEventProcessor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\Server;

abstract class Monster extends EntityBase{

    /** @var MonsterInventory */
    protected $inventory;

    protected $attackDelay = 0;

    /** @var float[] */
    private $minDamage = [0.0, 0.0, 0.0, 0.0];
    /** @var float[] */
    private $maxDamage = [0.0, 0.0, 0.0, 0.0];

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        if($nbt->hasTag("MinDamages")){
            $this->minDamage = $nbt->getListTag("MinDamages")->getAllValues();
        }
        if($nbt->hasTag("MaxDamages")){
            $this->minDamage = $nbt->getListTag("MaxDamages")->getAllValues();
        }

        $this->inventory = new MonsterInventory($this);
        $item = $this->getDefaultHeldItem();
        if($nbt->hasTag("HeldItem")){
            $item = Item::nbtDeserialize($nbt->getCompoundTag("HeldItem"));
        }

        if(!$item->isNull()){
            $this->inventory->setItemInHand($item);
        }
        $this->inventory->setEventProcessor(new EntityInventoryEventProcessor($this));
    }

    public function getDefaultHeldItem() : Item{
        return ItemFactory::get(Item::AIR);
    }

    public function getInventory() : MonsterInventory{
        return $this->inventory;
    }

    public function hasInteraction(Creature $target, float $distance) : bool{
        return $target instanceof Player && $target->isSurvival() && $target->spawned && $target->isAlive() && !$target->closed && $distance <= 324;
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
        return ($damages[0] === $damages[1] ? $damages[0] : $damages[0] + \lcg_value() * ($damages[1] - $damages[0])) + $this->inventory->getItemInHand()->getAttackPoints();
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
        $this->minDamage[$difficulty] = \min($damage, $this->maxDamage[$difficulty]);
    }

    public function setMaxDamage(float $damage, int $difficulty = -1) : void{
        if($difficulty === \null || $difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $this->maxDamage[$difficulty] = \max($damage, $this->minDamage[$difficulty]);
    }

    public function setDamage(float $damage, int $difficulty = -1) : void{
        if($difficulty === \null || $difficulty < 1 || $difficulty > 3){
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
            $this->minDamage[$i] = \min((float) $damage, $this->maxDamage[$i]);
        }
    }

    /**
     * @param float[] $damages
     */
    public function setMaxDamages(array $damages) : void{
        foreach($damages as $i => $damage){
            $this->maxDamage[$i] = \max((float) $damage, $this->minDamage[$i]);
        }
    }

    protected function sendSpawnPacket(Player $player) : void{
        parent::sendSpawnPacket($player);

        $this->inventory->sendContents($player);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();

        $min = [];
        $max = [];
        for($i = 0; $i < 4; ++$i){
            $min[$i] = new DoubleTag("", $this->minDamage[$i]);
            $max[$i] = new DoubleTag("", $this->maxDamage[$i]);
        }
        $nbt->setTag(new ListTag("MinDamages", $min));
        $nbt->setTag(new ListTag("MaxDamages", $max));
        $nbt->setTag($this->inventory->getItemInHand()->nbtSerialize(-1, "HeldItem"));
        return $nbt;
    }

}