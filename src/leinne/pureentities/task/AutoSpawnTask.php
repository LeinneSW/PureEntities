<?php

declare(strict_types=1);

namespace leinne\pureentities\task;

use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\neutral\IronGolem;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\neutral\ZombifiedPiglin;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Sheep;
use pocketmine\entity\Location;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoSpawnTask extends Task{

    public function onRun() : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $k => $player){
            if(mt_rand(1, 200) !== 1){
                continue;
            }

            $radX = mt_rand(3, 24);
            $radZ = mt_rand(3, 24);
            $pos = $player->getLocation()->floor();
            $pos->y = $player->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

            $entityClasses = [
                [Cow::class, Pig::class, Sheep::class, Chicken::class, Mooshroom::class, IronGolem::class],//, "Slime", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem"],
                [Zombie::class, Creeper::class, Skeleton::class, Spider::class, ZombifiedPiglin::class]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
            ];
            $entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 4)](Location::fromObject($pos, $player->getWorld()));
            $entity->spawnToAll();
        }
    }

}