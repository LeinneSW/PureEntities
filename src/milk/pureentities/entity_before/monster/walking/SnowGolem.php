<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;
use pocketmine\entity\Creature;

class SnowGolem extends WalkingMonster implements ProjectileSource{
    const NETWORK_ID = 21;

    public $width = 0.6;
    public $height = 1.8;

    public function initEntity(){
        parent::initEntity();

        $this->setFriendly(\true);
    }

    public function getName() : string{
        return 'SnowGolem';
    }

    public function targetOption(Creature $creature, $distance){
        return !($creature instanceof Player) && $creature->isAlive() && $distance <= 60;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 23  && \mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;

            $yaw = $this->yaw + \mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + \mt_rand(-120, 120) / 10;
            $snowball = Entity::createEntity('Snowball', $this->level, new CompoundTag('', [
                'Pos' => new ListTag('Pos', [
                    new DoubleTag('', $this->x + (-\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5)),
                    new DoubleTag('', $this->y + 1.62),
                    new DoubleTag('', $this->z +(\cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5))
                ]),
                'Motion' => new ListTag('Motion', [
                    new DoubleTag('', -\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.44),
                    new DoubleTag('', -\sin(\deg2rad($pitch)) * 1.44),
                    new DoubleTag('', \cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.44)
                ]),
                'Rotation' => new ListTag('Rotation', [
                    new FloatTag('', $yaw),
                    new FloatTag('', $pitch)
                ]),
            ]), $this);

            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($snowball));
            if($launch->isCancelled()){
                $snowball->kill();
            }else{
                $snowball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::SNOWBALL, 0, 15)];
        }
        return [];
    }

}
