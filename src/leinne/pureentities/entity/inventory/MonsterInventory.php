<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\inventory;

use leinne\pureentities\entity\Monster;
use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class MonsterInventory extends BaseInventory{

    /** @var Monster */
    protected $holder;

    public function __construct(Monster $mob){
        $this->holder = $mob;
        parent::__construct();
    }

    public function getName() : string{
        return "Monster";
    }

    public function getDefaultSize() : int{
        return 1;
    }

    public function setSize(int $size){
        throw new \BadMethodCallException("MobInventory can only carry one item at a time");
    }

    public function getItemInHand() : Item{
        return $this->getItem(0);
    }

    public function setItemInHand(Item $item) : bool{
        if($this->setItem(0, $item)){
            $this->sendHeldItem($this->holder->getViewers());
            return \true;
        }

        return \false;
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
        $pk->windowId = ContainerIds::INVENTORY;

        if(!\is_array($target)){
            $target->sendDataPacket($pk);
        }else{
            $this->getHolder()->getLevel()->getServer()->broadcastPacket($target, $pk);
        }
    }

    public function getHolder() : Monster{
        return $this->holder;
    }

}