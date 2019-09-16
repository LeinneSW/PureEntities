<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\inventory;

use leinne\pureentities\entity\Monster;

use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\player\Player;

class MonsterInventory extends BaseInventory{

    /** @var Monster */
    protected $holder;

    public function __construct(Monster $mob){
        $this->holder = $mob;
        parent::__construct(1);
    }

    public function setSize(int $size): void{
        throw new \BadMethodCallException("MobInventory can only carry one item at a time");
    }

    public function getItemInHand() : Item{
        return $this->getItem(0);
    }

    public function setItemInHand(Item $item) : void{
        $this->setItem(0, $item);

        foreach($this->holder->getViewers() as $viewer){
            $viewer->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->holder->getId(), $item, 0, ContainerIds::INVENTORY));
        }
    }

    /**
     * @param Player $viewer
     */
    public function sendContents(Player $viewer) : void{
        $viewer->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->holder->getId(), $this->getItemInHand(), 0, ContainerIds::INVENTORY));
    }

    public function getHolder() : Monster{
        return $this->holder;
    }

}