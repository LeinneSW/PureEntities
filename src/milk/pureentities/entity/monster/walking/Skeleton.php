<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;

class Skeleton extends WalkingMonster implements ProjectileSource{
    const NETWORK_ID = 34;

    public $width = 0.65;
    public $height = 1.8;

    public function getName() : string{
        return 'Skeleton';
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;

            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $arrow = Entity::createEntity('Arrow', $this->level, new CompoundTag('', [
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

            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, 1.2);
            $this->server->getPluginManager()->callEvent($ev);

            $projectile = $ev->getProjectile();
            if($ev->isCancelled()){
                $projectile->kill();
            }elseif($projectile instanceof Projectile){
                $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($projectile));
                if($launch->isCancelled()){
                    $projectile->kill();
                }else{
                    $projectile->spawnToAll();
                    $this->level->addSound(new LaunchSound($this), $this->getViewers());
                }
            }
        }
    }

    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->item = new Bow();
        $pk->inventorySlot = $pk->hotbarSlot = 0;
        $player->dataPacket($pk);
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        //TODO: 밝기 측정 추후수정
        /*$time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(1);
        }*/
        return $hasUpdate;
    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [
                Item::get(Item::BONE, 0, mt_rand(0, 2)),
                Item::get(Item::ARROW, 0, mt_rand(0, 3)),
            ];
        }
        return [];
    }

}
