<?php

declare(strict_types=1);

namespace leinne\pureentities\tile;

use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class MonsterSpawner extends Spawnable{

    protected ?string $entityTypeId = null;

    protected int $spawnRange;

    protected int $minSpawnDelay;

    protected int $maxSpawnDelay;

    protected int $maxNearbyEntities;

    protected int $requiredPlayerRange;

    public int $delay = 0;

    public function addAdditionalSpawnData(CompoundTag $tag) : void{
        $tag->setString("EntityIdentifier", $this->entityTypeId);
    }

    public function readSaveData(CompoundTag $nbt) : void{
        if($nbt->getTag("EntityId") instanceof IntTag){
            $this->setEntityId(LegacyEntityIdToStringIdMap::getInstance()->legacyToString($nbt->getInt("EntityId")));
        }elseif($nbt->getTag("EntityIdentifier") instanceof StringTag){
            $this->setEntityId($nbt->getString("EntityIdentifier"));
        }

        $this->setSpawnRange($nbt->getShort('SpawnRange', 8));
        $this->setSpawnDelay($nbt->getInt('MinSpawnDelay', 200), $nbt->getInt('MaxSpawnDelay', 8000));
        $this->maxNearbyEntities = $nbt->getShort('MaxNearbyEntities', 25);
        $this->requiredPlayerRange = $nbt->getShort('RequiredPlayerRange', 20);
    }

    public function hasValidEntityId() : bool{
        return $this->entityTypeId !== null;
    }

    public function getEntityId() : string{
        return $this->entityTypeId;
    }

    public function getSpawnRange() : int{
        return $this->spawnRange;
    }

    public function getMinSpawnDelay() : int{
        return $this->minSpawnDelay;
    }

    public function getMaxSpawnDelay() : int{
        return $this->maxSpawnDelay;
    }

    public function getMaxNearbyEntities() : int{
        return $this->maxNearbyEntities;
    }

    public function getRequiredPlayerRange() : int{
        return $this->requiredPlayerRange;
    }

    public function setEntityId(?string $entityId) : bool{
        if($entityId !== null && LegacyEntityIdToStringIdMap::getInstance()->stringToLegacy($entityId) === null){
            return false;
        }
        $this->entityTypeId = $entityId;
        return true;
    }

    public function setSpawnRange(int $range) : void{
        $this->spawnRange = max(1, $range);
    }

    public function setMinSpawnDelay(int $minDelay) : void{
        $this->minSpawnDelay = max(0, $minDelay);
    }

    public function setMaxSpawnDelay(int $maxDelay) : void{
        $this->maxSpawnDelay = max(0, $maxDelay);
    }

    public function setSpawnDelay(int $minDelay, int $maxDelay) : void{
        if($minDelay > $maxDelay){
            return;
        }

        $this->setMinSpawnDelay($minDelay);
        $this->setMaxSpawnDelay($maxDelay);
    }

    public function setMaxNearbyEntities(int $count) : void{
        $this->maxNearbyEntities = $count;
    }

    public function setRequiredPlayerRange(int $range) : void{
        $this->requiredPlayerRange = $range;
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setString('EntityIdentifier', $this->entityTypeId);
        $nbt->setShort('SpawnRange', $this->spawnRange);
        $nbt->setInt('MinSpawnDelay', $this->minSpawnDelay);
        $nbt->setInt('MaxSpawnDelay', $this->maxSpawnDelay);
        $nbt->setShort('MaxNearbyEntities', $this->maxNearbyEntities);
        $nbt->setShort('RequiredPlayerRange', $this->requiredPlayerRange);
    }

}