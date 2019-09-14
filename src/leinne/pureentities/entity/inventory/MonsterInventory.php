<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\inventory;

use leinne\pureentities\entity\Monster;
use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;

class MonsterInventory extends BaseInventory{

    /** @var Monster */
    protected $holder;

    public function __construct(Monster $mob){
        $this->holder = $mob;
        parent::__construct(1);
    }

    public function getName() : string{
        return "Monster";
    }

    public function setSize(int $size): void{
        throw new \BadMethodCallException("MobInventory can only carry one item at a time");
    }

    public function getItemInHand() : Item{
        return $this->getItem(0);
    }

    public function setItemInHand(Item $item) : void{
        $this->setItem(0, $item);
        $this->sendHeldItem($this->holder->getViewers());
    }

    /**
     * @param Player|Player[] $target
     */
    public function sendContents($target) : void{
        $this->sendHeldItem($target);
    }

    /**
     * Sends the currently-held item to specified targets.
     * @param Player|Player[] $target
     */
    public function sendHeldItem($target) : void{
        $item = $this->getItemInHand();

        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getHolder()->getId();
        $pk->item = $item;
        $pk->inventorySlot = $pk->hotbarSlot = 0;
        $pk->windowId = WindowTypes::INVENTORY;

        if(!\is_array($target)){
            $target->getNetworkSession()->sendDataPacket($pk);
        }else{
            $this->getHolder()->getWorld()->getServer()->broadcastPacket($target, $pk);
        }
    }

    public function getHolder() : Monster{
        return $this->holder;
    }

}