<?php

declare(strict_types=1);

namespace leinne\pureentities\block;

use pocketmine\block\MonsterSpawner as PMMonsterSpawner;
use pocketmine\entity\EntityFactory;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MonsterSpawner extends PMMonsterSpawner{

    public function onScheduledUpdate(): void {
        $spawner = $this->getPos()->getWorld()->getTile($this->getPos());
        if (!$spawner instanceof \leinne\pureentities\tile\MonsterSpawner) {
            return;
        }
        if(!$spawner->hasValidEntityId()){
            $spawner->close();
            return;
        }

        if($spawner->closed){
            return;
        }

        if(++$spawner->delay >= mt_rand($spawner->getMinSpawnDelay(), $spawner->getMaxSpawnDelay())){
            $spawner->delay = 0;

            $list = [];
            $isValid = false;
            foreach($spawner->getPos()->getWorld()->getEntities() as $k => $entity){
                if($entity->getPosition()->distance($spawner->getPos()) <= $spawner->getRequiredPlayerRange()){
                    if($entity instanceof Player){
                        $isValid = true;
                    }
                    $list[] = $entity;
                    break;
                }
            }

            if($isValid && count($list) < $spawner->getMaxNearbyEntities()){
                $nbt = EntityFactory::createBaseNBT($pos = new Position(
                    $spawner->getPos()->getX() + mt_rand(-$spawner->getSpawnRange(), $spawner->getSpawnRange()),
                    $spawner->getPos()->getY(),
                    $spawner->getPos()->getZ() + mt_rand(-$spawner->getSpawnRange(), $spawner->getSpawnRange()),
                    $spawner->getPos()->getWorld()
                ));
                $nbt->setString("identifier", $spawner->getEntityId());
                $entity = EntityFactory::createFromData($spawner->getPos()->getWorld(), $nbt);
                if($entity !== null){
                    $entity->spawnToAll();
                }
            }
        }
    }

}