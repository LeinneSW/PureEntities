<?php

declare(strict_types=1);

namespace leinne\pureentities\task;

use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\neutral\ZombiePigman;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;

use leinne\pureentities\entity\passive\Sheep;
use pocketmine\entity\EntityFactory;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoSpawnTask extends Task{

    public function onRun(int $currentTick) : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $k => $player){
            if(\mt_rand(1, 200) !== 1){
                continue;
            }

            $radX = \mt_rand(3, 24);
            $radZ = \mt_rand(3, 24);
            $pos = $player->getLocation()->floor();
            $pos->y = $player->level->getHighestBlockAt($pos->x += \mt_rand(0, 1) ? $radX : -$radX, $pos->z += \mt_rand(0, 1) ? $radZ : -$radZ) + 1;

            $entityIds = [
                [Cow::class, Pig::class, Sheep::class, Chicken::class, Mooshroom::class],//, "Slime", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem"],
                [Zombie::class, Creeper::class, Skeleton::class, Spider::class, ZombiePigman::class]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
            ];
            $entity = EntityFactory::create($entityIds[\mt_rand(0, 1)][\mt_rand(0, 4)], $player->getLocation()->getWorld(), EntityFactory::createBaseNBT($pos));
            $entity->spawnToAll();
        }
    }

}