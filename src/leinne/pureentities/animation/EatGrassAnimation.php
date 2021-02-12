<?php

declare(strict_types=1);

namespace leinne\pureentities\animation;

use pocketmine\entity\animation\Animation;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ActorEventPacket;

class EatGrassAnimation implements Animation{
    private Entity $entity;

    public function __construct(Entity $entity){
        $this->entity = $entity;
    }

    public function encode() : array{
        return [
            ActorEventPacket::create($this->entity->getId(), ActorEventPacket::EAT_GRASS_ANIMATION, 0)
        ];
    }
}