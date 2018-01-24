<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;

class PigZombie extends WalkingMonster{
    const NETWORK_ID = 36;

    private $angry = 0;

    public $width = 0.72;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.15;
        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag['Angry'];
        }

        $this->setDamage([0, 5, 9, 13]);
    }

    public function isFireProof() : bool{
        return \true;
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag('Angry', $this->angry);
    }

    public function getName() : string{
        return 'PigZombie';
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(int $val){
        $this->angry = $val;
    }

    public function targetOption(Creature $creature, $distance){
        return $this->isAngry() && parent::targetOption($creature, $distance);
    }

    public function attack(EntityDamageEvent $source){
        parent::attack($source);

        if(!$source->isCancelled()){
            $this->setAngry(1200); //TODO: 화가났다는게 틱개념인가 true/false 개념인가...
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if($this->angry > 0){
            $this->angry -= $tickDiff;
        }
        return $hasUpdate;
    }

    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->item = new Sword(Item::GOLDEN_SWORD, 0, 'Gold Sword', TieredTool::TIER_GOLD);
        $pk->inventorySlot = $pk->hotbarSlot = 0;
        $player->dataPacket($pk);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::FLINT, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::GUNPOWDER, 0, 1);
                    break;
                case 2:
                    $drops[] = Item::get(Item::REDSTONE_DUST, 0, 1);
                    break;
            }
        }
        return $drops;
    }

}
