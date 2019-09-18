<?php

declare(strict_types=1);

namespace leinne\pureentities\tile;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\tile\Spawnable;
use pocketmine\world\World;

class MobSpawner extends Spawnable {

    protected $entityId = -1;
    protected $spawnRange;
    protected $maxNearbyEntities;
    protected $requiredPlayerRange;

    public $delay = 0;

    protected $minSpawnDelay;
    protected $maxSpawnDelay;

    public function __construct(World $world, Vector3 $pos){
        parent::__construct($world, $pos);
    }

    public function readSaveData(CompoundTag $nbt) : void{
        $this->entityId = $nbt->getInt('EntityId', -1);
        $this->spawnRange = $nbt->getShort('SpawnRange', 8);
        $this->minSpawnDelay = $nbt->getInt('MinSpawnDelay', 200);
        $this->maxSpawnDelay = $nbt->getInt('MaxSpawnDelay', 8000);
        $this->maxNearbyEntities = $nbt->getShort('MaxNearbyEntities', 25);
        $this->requiredPlayerRange = $nbt->getShort('RequiredPlayerRange', 20);
    }

    public function hasValidEntityId() : bool{
        return $this->entityId > 0;
    }

    public function getMinSpawnDelay() : int{
        return min($this->minSpawnDelay, $this->maxSpawnDelay);
    }

    public function getMaxSpawnDelay() : int{
        return max($this->minSpawnDelay, $this->maxSpawnDelay);
    }

    public function addAdditionalSpawnData(CompoundTag $tag) : void{
        $tag->setInt('EntityId', $this->entityId);
    }

    public function setSpawnEntityType(int $entityId) : void{
        $this->entityId = $entityId;
    }

    public function setMinSpawnDelay(int $minDelay) : void{
        $this->minSpawnDelay = $minDelay;
    }

    public function setMaxSpawnDelay(int $maxDelay) : void{
        $this->maxSpawnDelay = $maxDelay;
    }

    public function setSpawnDelay(int $minDelay, int $maxDelay) : void{
        if($minDelay > $maxDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
        $this->maxSpawnDelay = $maxDelay;
    }

    public function setRequiredPlayerRange(int $range) : void{
        $this->requiredPlayerRange = $range;
    }

    /**
     * @return mixed
     */
    public function getRequiredPlayerRange() {
        return $this->requiredPlayerRange;
    }

    public function setMaxNearbyEntities(int $count) : void{
        $this->maxNearbyEntities = $count;
    }

    /**
     * @return mixed
     */
    public function getMaxNearbyEntities() {
        return $this->maxNearbyEntities;
    }

    /**
     * @return mixed
     */
    public function getSpawnRange() {
        return $this->spawnRange;
    }

    /**
     * @return int
     */
    public function getEntityId(): int {
        return $this->entityId;
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setInt('EntityId', $this->entityId);
        $nbt->setShort('SpawnRange', $this->spawnRange);
        $nbt->setInt('MinSpawnDelay', $this->minSpawnDelay);
        $nbt->setInt('MaxSpawnDelay', $this->maxSpawnDelay);
        $nbt->setShort('MaxNearbyEntities', $this->maxNearbyEntities);
        $nbt->setShort('RequiredPlayerRange', $this->requiredPlayerRange);
    }
}