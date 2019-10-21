<?php

declare(strict_types=1);

namespace leinne\pureentities\vehicle;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Vehicle extends Entity{

    public function interact(Player $player, Item $item) : void{
        //TODO
    }

}