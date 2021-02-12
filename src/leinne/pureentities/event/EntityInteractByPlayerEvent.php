<?php

declare(strict_types=1);

namespace leinne\pureentities\event;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\entity\EntityEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class EntityInteractByPlayerEvent extends EntityEvent implements Cancellable{
    use CancellableTrait;

    private Player $player;

    private Item $item;

    public function __construct(Entity $entity, Player $player, Item $item){
        $this->entity = $entity;
        $this->player = $player;
        $this->item = $item;
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getItem() : Item{
        return $this->item;
    }

}