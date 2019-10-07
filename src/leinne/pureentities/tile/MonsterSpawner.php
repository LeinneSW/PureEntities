<?php

declare(strict_types=1);

namespace leinne\pureentities\tile;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\world\World;

class MonsterSpawner extends Spawnable{

    /** @var string */
    protected $entityTypeId;

    /** @var int */
    protected $spawnRange;

    /** @var int */
    protected $minSpawnDelay;

    /** @var int */
    protected $maxSpawnDelay;

    /** @var int */
    protected $maxNearbyEntities;

    /** @var int */
    protected $requiredPlayerRange;

    /** @var int */
    public $delay = 0;

    public function __construct(World $world, Vector3 $pos){
        parent::__construct($world, $pos);
    }

    public function readSaveData(CompoundTag $nbt) : void{
        if($nbt->hasTag("EntityId", IntTag::class)){
            $this->entityTypeId = AddActorPacket::LEGACY_ID_MAP_BC[$nbt->getInt("EntityId")] ?? ":";
        }elseif($nbt->hasTag("EntityIdentifier", StringTag::class)){
            $this->entityTypeId = $nbt->getString("EntityIdentifier");
        }else{
            $this->entityTypeId = ":";
        }

        $this->spawnRange = $nbt->getShort('SpawnRange', 8);
        $this->minSpawnDelay = $nbt->getInt('MinSpawnDelay', 200);
        $this->maxSpawnDelay = $nbt->getInt('MaxSpawnDelay', 8000);
        $this->maxNearbyEntities = $nbt->getShort('MaxNearbyEntities', 25);
        $this->requiredPlayerRange = $nbt->getShort('RequiredPlayerRange', 20);
    }

    public function hasValidEntityId() : bool{
        return $this->entityTypeId > 0;
    }

    public function getMinSpawnDelay() : int{
        return $this->minSpawnDelay;
    }

    public function getMaxSpawnDelay() : int{
        return $this->minSpawnDelay;
    }

    public function addAdditionalSpawnData(CompoundTag $tag) : void{
        $tag->setString("EntityIdentifier", $this->entityTypeId);
    }

    public function setSpawnEntityType(int $entityId) : void{
        $this->entityTypeId = $entityId;
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

    public function getRequiredPlayerRange() : int{
        return $this->requiredPlayerRange;
    }

    public function setMaxNearbyEntities(int $count) : void{
        $this->maxNearbyEntities = $count;
    }

    public function getMaxNearbyEntities() : int{
        return $this->maxNearbyEntities;
    }

    public function getSpawnRange() : int{
        return $this->spawnRange;
    }

    public function getEntityId() : string{
        return $this->entityTypeId;
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