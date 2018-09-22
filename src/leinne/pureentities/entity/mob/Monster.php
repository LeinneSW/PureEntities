<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use leinne\pureentities\entity\EntityBase;
use leinne\pureentities\inventory\MonsterInventory;
use pocketmine\entity\Creature;
use pocketmine\item\TieredTool;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;

abstract class Monster extends EntityBase{

    /** @var MonsterInventory */
    protected $inventory;

    protected $attackDelay = 0;

    private $minDamage = [0, 0, 0, 0];
    private $maxDamage = [0, 0, 0, 0];

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->inventory = new MonsterInventory($this);
    }

    public function getInventory() : MonsterInventory{
        return $this->inventory;
    }

    public function hasInteraction(Creature $target, float $distance) : bool{
        return $target instanceof Player && $target->isSurvival() && $target->spawned && $target->isAlive() && !$target->closed && $distance <= 169;
    }

    public function getDamages(int $difficulty = -1) : array{
        return [$this->getMinDamage($difficulty), $this->getMaxDamage($difficulty)];
    }

    public function getResultDamage(int $difficulty = -1) : int{
        return \mt_rand(...$this->getDamages($difficulty)) + $this->inventory->getItemInHand()->getAttackPoints();
    }

    public function getMinDamage(int $difficulty = -1) : int{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return \min($this->minDamage[$difficulty], $this->maxDamage[$difficulty]);
    }

    public function getMaxDamage(int $difficulty = -1) : int{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return \max($this->minDamage[$difficulty], $this->maxDamage[$difficulty]);
    }

    public function setMinDamage(int $damage, int $difficulty = -1) : void{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->minDamage[$difficulty] = $damage;
    }

    public function setMaxDamage(int $damage, int $difficulty = -1) : void{
        if($difficulty === \null || $difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->maxDamage[$difficulty] = $damage;
    }

    public function setDamage(int $damage, int $difficulty = -1) : void{
        $this->setMinDamage($damage, $difficulty);
        $this->setMaxDamage($damage, $difficulty);
    }

    public function setDamages(array $damages) : void{
        $this->setMinDamages($damages);
        $this->setMaxDamages($damages);
    }

    public function setMinDamages(array $damages) : void{
        if(count($damages) > 3) for ($i = 0; $i < 4; $i++) {
            $this->setMinDamage((int) $damages[$i], $i);
        }
    }

    public function setMaxDamages(array $damages) : void{
        if(count($damages) > 3) for ($i = 0; $i < 4; $i++) {
            $this->setMaxDamage((int) $damages[$i], $i);
        }
    }

    protected function sendSpawnPacket(Player $player) : void{
        parent::sendSpawnPacket($player);

        $this->inventory->sendContents($player);
    }

}