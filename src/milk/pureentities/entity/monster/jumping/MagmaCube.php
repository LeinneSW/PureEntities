<?php

namespace milk\pureentities\entity\monster\jumping;

use milk\pureentities\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class MagmaCube extends JumpingMonster{
    const NETWORK_ID = 42;

    public $width = 1.2;
    public $height = 1.2;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 0.8;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function getName() : string{
        return "MagmaCube";
    }

    public function isFireProof() : bool{
        return \true;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        return [];
    }

}
