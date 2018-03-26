<?php

namespace milk\pureentities\tile;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
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

        if($this->namedtag->hasTag('EntityId', ShortTag::class)){
            $this->entityId = $this->namedtag['EntityId'];
        }

        if(!$this->namedtag->hasTag('SpawnRange', ShortTag::class)){
            $this->namedtag->setShort('SpawnRange', 8);
        }

        if(!$this->namedtag->hasTag('MinSpawnDelay', ShortTag::class)){
            $this->namedtag->setShort('MinSpawnDelay', 200);
        }

        if(!$this->namedtag->hasTag('MaxSpawnDelay', ShortTag::class)){
            $this->namedtag->setShort('MaxSpawnDelay', 8000);
        }

        if(!$this->namedtag->hasTag('MaxNearbyEntities', ShortTag::class)){
            $this->namedtag->setShort('MaxNearbyEntities', 25);
        }

        if(!$this->namedtag->hasTag('RequiredPlayerRange', ShortTag::class)){
            $this->namedtag->setShort('RequiredPlayerRange', 20);
        }

        $this->spawnRange = $this->namedtag->getShort('SpawnRange');
        $this->minSpawnDelay = $this->namedtag->getShort('MinSpawnDelay');
        $this->maxSpawnDelay = $this->namedtag->getShort('MaxSpawnDelay');
        $this->maxNearbyEntities = $this->namedtag->getShort('MaxNearbyEntities');
        $this->requiredPlayerRange = $this->namedtag->getShort('RequiredPlayerRange');

        $this->scheduleUpdate();
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

    public function saveNBT() : void{
        parent::saveNBT();

        $this->namedtag->setShort('EntityId', $this->entityId);
        $this->namedtag->setShort('SpawnRange', $this->spawnRange);
        $this->namedtag->setShort('MinSpawnDelay', $this->minSpawnDelay);
        $this->namedtag->setShort('MaxSpawnDelay', $this->maxSpawnDelay);
        $this->namedtag->setShort('MaxNearbyEntities', $this->maxNearbyEntities);
        $this->namedtag->setShort('RequiredPlayerRange', $this->requiredPlayerRange);
    }

    public function addAdditionalSpawnData(CompoundTag $tag) : void{
        $tag->setString('id', 'MobSpawner');
        $tag->setint('EntityId', $this->entityId);
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

}