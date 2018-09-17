<?php

declare(strict_types=1);

namespace leinne\pureentities\task;

use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoSpawnTask extends Task{

    public function onRun(int $currentTick) : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if(\mt_rand(1, 300) !== 1){
                continue;
            }

            $radX = \mt_rand(3, 24);
            $radZ = \mt_rand(3, 24);
            $pos = $player->floor();
            $pos->y = $player->level->getHighestBlockAt($pos->x += \mt_rand(0, 1) ? $radX : -$radX, $pos->z += \mt_rand(0, 1) ? $radZ : -$radZ);

            $entityIds = [
                ["Cow", "Pig", "Sheep", "Chicken"],//, "Slime", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem"],
                ["Zombie", "Creeper", "Skeleton", "Spider", "PigZombie"]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
            ];
            $entity = Entity::createEntity($entityIds[\mt_rand(0, 1)][\mt_rand(0, 4)] ?? 0, $player->level, Entity::createBaseNBT($pos));
            if($entity !== \null){
                $entity->spawnToAll();
            }
        }
    }

}