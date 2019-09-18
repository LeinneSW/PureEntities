<?php


namespace leinne\pureentities\block;


use pocketmine\block\MonsterSpawner;
use pocketmine\entity\EntityFactory;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MobSpawner extends MonsterSpawner{

    public function onScheduledUpdate(): void {
        $spawner = $this->getPos()->getWorld()->getTile($this->getPos());
        if (!$spawner instanceof \leinne\pureentities\tile\MobSpawner) {
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
            $isValid = \false;
            foreach($spawner->getPos()->getWorld()->getEntities() as $k => $entity){
                if($entity->distance($spawner) <= $spawner->getRequiredPlayerRange()){
                    if($entity instanceof Player){
                        $isValid = \true;
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
                $nbt->setInt("id", $spawner->getEntityId());
                $entity = EntityFactory::createFromData($spawner->getPos()->getWorld(), $nbt);
                if($entity !== \null){
                    $entity->spawnToAll();
                }
            }
        }
    }

}