<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\vehicle;

use leinne\pureentities\entity\Vehicle;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class Boat extends Vehicle{

    /** @var float */
    protected $gravity = 0.08;

    public static function getNetworkTypeId() : string{
        return EntityIds::BOAT;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.5625, 1.375);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        //TODO: 보트 종류
        $this->setMaxHealth(1);
        $this->setHealth(1);
    }

    public function getName() : string{
        return "Boat";
    }

    public function getRidingPositions() : array{
        return [new Vector3(0, 1, 0), new Vector3(0, 1, 0)];
    }

    public function setPassenger(Entity $entity, int $index) : bool{
        if(parent::setPassenger($entity, $index)){
            $properties = $entity->getNetworkProperties();
            $properties->setByte(EntityMetadataProperties::RIDER_ROTATION_LOCKED, 1);
            $properties->setFloat(EntityMetadataProperties::RIDER_MAX_ROTATION, 90);
            $properties->setFloat(EntityMetadataProperties::RIDER_MIN_ROTATION, -90);
            return true;
        }
        return false;
    }

    public function handleAnimatePacket(AnimatePacket $packet) : bool{
        if($this->getRider() !== null){
            switch($packet->action){
                case AnimatePacket::ACTION_ROW_RIGHT:
                    $this->getNetworkProperties()->setFloat(EntityMetadataProperties::PADDLE_TIME_RIGHT, $packet->float);
                    return true;
                case AnimatePacket::ACTION_ROW_LEFT:
                    $this->getNetworkProperties()->setFloat(EntityMetadataProperties::PADDLE_TIME_LEFT, $packet->float);
                    return true;
            }
        }
        return false;
    }

    protected function onDeath() : void{
        $this->getWorld()->dropItem($this->location, VanillaItems::OAK_BOAT());
    }
}