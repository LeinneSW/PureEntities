<?php

namespace milk\pureentities\entity_before\monster\flying;

use milk\pureentities\entity_before\monster\FlyingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class Ghast extends FlyingMonster implements ProjectileSource{
    const NETWORK_ID = 41;

    public $width = 4;
    public $height = 4;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.2;
        $this->setMaxHealth(10);
        $this->setDamage([0, 0, 0, 0]);
    }

    public function getName() : string{
        return 'Ghast';
    }

    public function isFireProof() : bool{
        return \true;
    }

    public function targetOption(Creature $creature, $distance){
        return (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 10000;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && \mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 10000){
            $this->attackDelay = 0;

            $yaw = $this->yaw + \mt_rand(-110, 110) / 10;
            $pitch = $this->pitch + \mt_rand(-110, 110) / 10;
            $fireball = Entity::createEntity('LargeFireBall', $this->level, new CompoundTag('', [
                'Pos' => new ListTag('Pos', [
                    new DoubleTag('', $this->x + (-\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5)),
                    new DoubleTag('', $this->y),
                    new DoubleTag('', $this->z +(\cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5))
                ]),
                'Motion' => new ListTag('Motion', [
                    new DoubleTag('', -\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.2),
                    new DoubleTag('', -\sin(\deg2rad($pitch)) * 1.2),
                    new DoubleTag('', \cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.2)
                ]),
                'Rotation' => new ListTag('Rotation', [
                    new FloatTag('', 0),
                    new FloatTag('', 0)
                ]),
            ]), $this);

            if($fireball === \null){
                return;
            }

            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
            if($launch->isCancelled()){
                $fireball->kill();
            }else{
                $fireball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function getDrops() : array{
        return [];
    }

}
