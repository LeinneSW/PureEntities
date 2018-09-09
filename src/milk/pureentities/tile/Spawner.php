<?php

declare(strict_types=1);

namespace milk\pureentities\tile;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;

class Spawner extends Spawnable{

    protected $entityId = -1;
    protected $spawnRange;
    protected $maxNearbyEntities;
    protected $requiredPlayerRange;

    protected $delay = 0;

    protected $minSpawnDelay;
    protected $maxSpawnDelay;

    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);

        $this->scheduleUpdate();
    }

    protected function readSaveData(CompoundTag $nbt): void{
        if($nbt->hasTag('EntityId', ShortTag::class)){
            $this->entityId = $nbt['EntityId'];
        }

        if(!$nbt->hasTag('SpawnRange', ShortTag::class)){
            $nbt->setShort('SpawnRange', 8);
        }

        if(!$nbt->hasTag('MinSpawnDelay', ShortTag::class)){
            $nbt->setShort('MinSpawnDelay', 200);
        }

        if(!$nbt->hasTag('MaxSpawnDelay', ShortTag::class)){
            $nbt->setShort('MaxSpawnDelay', 8000);
        }

        if(!$nbt->hasTag('MaxNearbyEntities', ShortTag::class)){
            $nbt->setShort('MaxNearbyEntities', 25);
        }

        if(!$nbt->hasTag('RequiredPlayerRange', ShortTag::class)){
            $nbt->setShort('RequiredPlayerRange', 20);
        }

        $this->spawnRange = $nbt->getShort('SpawnRange');
        $this->minSpawnDelay = $nbt->getShort('MinSpawnDelay');
        $this->maxSpawnDelay = $nbt->getShort('MaxSpawnDelay');
        $this->maxNearbyEntities = $nbt->getShort('MaxNearbyEntities');
        $this->requiredPlayerRange = $nbt->getShort('RequiredPlayerRange');
    }

    public function onUpdate() : bool{
        if($this->closed){
            return \false;
        }

        if(++$this->delay >= mt_rand($this->minSpawnDelay, $this->maxSpawnDelay)){
            $this->delay = 0;

            $list = [];
            $isValid = \false;
            foreach($this->level->getEntities() as $entity){
                if($entity->distance($this) <= $this->requiredPlayerRange){
                    if($entity instanceof Player){
                        $isValid = \true;
                    }
                    $list[] = $entity;
                    break;
                }
            }

            if($isValid && count($list) <= $this->maxNearbyEntities){
                $pos = new Position(
                    $this->x + mt_rand(-$this->spawnRange, $this->spawnRange),
                    $this->y,
                    $this->z + mt_rand(-$this->spawnRange, $this->spawnRange),
                    $this->level
                );
                $entity = Entity::createEntity($this->entityId, $pos->level, Entity::createBaseNBT($pos));
                if($entity !== null){
                    $entity->spawnToAll();
                }
            }
        }
        return \true;
    }

    public function addAdditionalSpawnData(CompoundTag $tag) : void{
        $tag->setTag(new StringTag('id', 'MobSpawner'));
        $tag->setTag(new IntTag('EntityId', $this->entityId));
    }

    public function setSpawnEntityType(int $entityId){
        $this->entityId = $entityId;
        $this->onChanged();
    }

    public function setMinSpawnDelay(int $minDelay){
        if($minDelay > $this->maxSpawnDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
    }

    public function setMaxSpawnDelay(int $maxDelay){
        if($this->minSpawnDelay > $maxDelay){
            return;
        }

        $this->maxSpawnDelay = $maxDelay;
    }

    public function setSpawnDelay(int $minDelay, int $maxDelay){
        if($minDelay > $maxDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
        $this->maxSpawnDelay = $maxDelay;
    }

    public function setRequiredPlayerRange(int $range){
        $this->requiredPlayerRange = $range;
    }

    public function setMaxNearbyEntities(int $count){
        $this->maxNearbyEntities = $count;
    }

    protected function writeSaveData(CompoundTag $nbt): void{
        $nbt->setShort('EntityId', $this->entityId);
        $nbt->setShort('SpawnRange', $this->spawnRange);
        $nbt->setShort('MinSpawnDelay', $this->minSpawnDelay);
        $nbt->setShort('MaxSpawnDelay', $this->maxSpawnDelay);
        $nbt->setShort('MaxNearbyEntities', $this->maxNearbyEntities);
        $nbt->setShort('RequiredPlayerRange', $this->requiredPlayerRange);
    }
}