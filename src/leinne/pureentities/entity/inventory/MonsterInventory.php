<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\inventory;

use leinne\pureentities\entity\Monster;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;

class MonsterInventory extends SimpleInventory {

    protected Monster $holder;

    public function __construct(Monster $mob){
        $this->holder = $mob;
        parent::__construct(1);
    }

    public function getItemInHand() : Item{
        return $this->getItem(0);
    }

    public function setItemInHand(Item $item) : void{
        $this->setItem(0, $item);
    }

    public function getHolder() : Monster{
        return $this->holder;
    }

}