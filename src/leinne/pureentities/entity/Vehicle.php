<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

abstract class Vehicle extends Entity{
    /** @var self[] */
    public static array $riders = [];

    /** @var Entity[] */
    private array $passengers = [];

    public abstract function getName() : string;

    /** @return Vector3[] */
    public abstract function getRidingPositions() : array;

    public function updateMotion(float $x, float $y) : void{}

    public function absoluteMove(Vector3 $pos, float $yaw = 0.0, float $pitch = 0.0) : void{
        $this->location = Location::fromObject($pos, $this->location->world, $yaw, $pitch);
    }

    public function handleAnimatePacket(AnimatePacket $packet) : bool{
        return false;
    }

    public function isEmpty() : bool{
        return count($this->passengers) === 0;
    }

    public function getRider() : ?Entity{
        return $this->getPassenger(0);
    }

    public function getPassenger(int $index) : ?Entity{
        return $this->passengers[$index] ?? null;
    }

    /** @return Entity[] */
    public function getPassengers() : array{
        return $this->passengers;
    }

    public function interact(Player $player, Item $item) : bool{
        $this->addPassenger($player);
        return true;
    }

    public function setPassenger(Entity $entity, int $index) : bool{
        $pos = $this->getRidingPositions();
        if(!isset($pos[$index])){
            return false;
        }

        if(isset($this->passengers[$index])){
            $this->removePassenger($this->passengers[$index]);
        }

        $this->passengers[$index] = $entity;
        self::$riders[$entity->getId()] = $this;

        $networkProperties = $entity->getNetworkProperties();
        $networkProperties->setGenericFlag(EntityMetadataFlags::RIDING, true);
        $networkProperties->setGenericFlag(EntityMetadataFlags::SITTING, true);
        $networkProperties->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, $pos[$index]);
        $this->broadcastLink($entity, $index === 0 ? EntityLink::TYPE_RIDER : EntityLink::TYPE_PASSENGER);
        return true;
    }

    public function addPassenger(Entity $entity) : bool{
        $index = null;
        $pos = $this->getRidingPositions();
        for($i = 0, $len = count($pos); $i < $len; ++$i){
            if(!isset($this->passengers[$i])){
                $index = $i;
                break;
            }
        }
        if($index === null){
            return false;
        }

        $this->setPassenger($entity, $index);
        return true;
    }

    public function removePassenger(Entity $entity) : bool{
        $index = array_search($entity, $this->passengers, true);
        if($index === false){
            return false;
        }

        unset($this->passengers[$index]);
        unset(self::$riders[$entity->getId()]);
        $this->passengers = array_values($this->passengers);

        $networkProperties = $entity->getNetworkProperties();
        $networkProperties->setGenericFlag(EntityMetadataFlags::RIDING, false);
        $networkProperties->setGenericFlag(EntityMetadataFlags::SITTING, false);

        $this->broadcastLink($entity, EntityLink::TYPE_REMOVE);
        return true;
    }

    protected function broadcastLink(Entity $player, int $type) : void{
        $pk = new SetActorLinkPacket();
        $pk->link = new EntityLink($this->getId(), $player->getId(), $type, true, true);
        $this->server->broadcastPackets($this->hasSpawned, [$pk]);
    }
}