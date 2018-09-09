<?php

declare(strict_types=1);

namespace milk\pureentities\entity\mob;

use milk\pureentities\entity\EntityBase;
use milk\pureentities\inventory\MobInventory;
use pocketmine\entity\Creature;
use pocketmine\item\TieredTool;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;

abstract class Monster extends EntityBase {

    /** @var MobInventory */
    protected $inventory;

    protected $attackDelay = 0;

    private $minDamage = [0, 0, 0, 0];
    private $maxDamage = [0, 0, 0, 0];

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->inventory = new MobInventory($this);
    }

    public function isHostility(Creature $target, float $distance) : bool{
        return $target instanceof Player && $target->isSurvival() && $target->spawned && $target->isAlive() && !$target->closed && $distance <= 121;
    }

    public function getDamages(?int $difficulty = \null) : array{
        return [$this->getMinDamage($difficulty), $this->getMaxDamage($difficulty)];
    }

    public function getResultDamage(?int $difficulty = \null) : int{
        return \mt_rand(...$this->getDamages($difficulty)) + (($item = $this->inventory->getItemInHand()) instanceof TieredTool ? $item->getAttackPoints() : 0);
    }

    public function getMinDamage(?int $difficulty = \null) : int{
        if($difficulty === \null || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->minDamage[$difficulty];
    }

    public function getMaxDamage(?int $difficulty = \null) : int{
        if($difficulty === \null || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->maxDamage[$difficulty];
    }

    public function setDamage(int $damage, ?int $difficulty = \null){
        if($difficulty === \null || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->minDamage[$difficulty] = $damage[$difficulty];
        $this->maxDamage[$difficulty] = $damage[$difficulty];
    }

    public function setDamages(array $damages){
        if(count($damages) > 3){
            for($i = 0; $i < 4; $i++){
                $this->minDamage[$i] = (int) $damages[$i];
                $this->maxDamage[$i] = (int) $damages[$i];
            }
        }
    }

    public function setMinDamage($damage, int $difficulty = \null){
        if(is_array($damage)){
            for($i = 0; $i < 4; $i++){
                $this->minDamage[$i] = \min($damage[$i], $this->getMaxDamage($i));
            }
            return;
        }elseif($difficulty === \null){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        if($difficulty >= 1 && $difficulty <= 3){
            $this->minDamage[$difficulty] = \min((int) $damage, $this->getMaxDamage($difficulty));
        }
    }

    public function setMaxDamage(int $damage, ?int $difficulty = \null){
        if($difficulty === \null || $difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->maxDamage[$difficulty] = max((int) $damage, $this->getMaxDamage($difficulty));
    }

    public function setMaxDamages(array $damages){
        if(count($damages) > 3){
            for ($i = 0; $i < 4; $i++) {
                $this->maxDamage[$i] = \max((int) $damages[$i], $this->getMaxDamage($i));
            }
            return;
        }
    }

    protected function sendSpawnPacket(Player $player) : void{
        parent::sendSpawnPacket($player);

        $this->inventory->sendContents($player);
    }

    public function getInventory() : MobInventory{
        return $this->inventory;
    }

}