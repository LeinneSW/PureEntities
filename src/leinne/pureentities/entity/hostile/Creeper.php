<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\world\Explosion;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\sound\FlintSteelSound;
use pocketmine\world\sound\IgniteSound;

class Creeper extends Monster implements Explosive{
    use WalkEntityTrait;

    public const DEFAULT_FUSE = 30;

    private bool $ignited = false;

    private bool $explode = false;

    private bool $powered = false;

    private int $fuse = self::DEFAULT_FUSE;

    private float $force = 3.0;

    public static function getNetworkTypeId() : string{
        return EntityIds::CREEPER;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->force = $nbt->getFloat("Force", 3.0);
        $this->ignited = $nbt->getByte("ignited", 0) !== 0;
        $this->powered = $nbt->getByte("powered", 0) !== 0;
        $this->fuse = $nbt->getShort("Fuse", self::DEFAULT_FUSE);
        $this->setSpeed(0.95);
    }

    public function getName() : string{
        return 'Creeper';
    }

    public function getInteractDistance() : float{
        return 3;
    }

    public function isPowered() : bool{
        return $this->powered;
    }

    public function setPowered(bool $value) : void{
        $this->powered = $value;
    }

    public function isAttackable() : bool{
        return $this->force > 0;
    }

    public function getForce() : float{
        return $this->force;
    }

    public function setForce(float $force) : void{
        $this->force = $force;
    }

    public function getFuse() : int{
        return $this->fuse;
    }

    public function setFuse(int $fuse) : void{
        $this->fuse = $fuse;
    }

    public function ignite() : void{
        $this->ignited = true;
        $this->setSpeed(0);
    }

    public function interact(Player $player, Item $item) : bool{
        if($item instanceof FlintSteel && !$this->ignited){
            $this->ignite();
            $item->applyDamage(1);
            $player->broadcastAnimation(new ArmSwingAnimation($player));
            $this->getWorld()->addSound($this->location, new FlintSteelSound());
            return true;
        }
        return false;
    }

    public function explode() : void{
        $ev = new ExplosionPrimeEvent($this, $this->force);
        $ev->call();

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this->getPosition(), $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

    public function interactTarget() : bool{
        if(!$this->canInteractTarget() && !$this->ignited){
            if($this->fuse < self::DEFAULT_FUSE){
                ++$this->fuse;
                $this->explode = false;
            }elseif($this->getSpeed() === 0.4){
                $this->setSpeed(0.95);
            }
            return false;
        }

        $this->setSpeed(0.4);
        if(!$this->explode){
            //TODO: Correct explosion sound
            $this->getWorld()->addSound($this->location, new IgniteSound());
        }
        $this->explode = true;
        if(--$this->fuse < 0){
            $this->flagForDespawn();
            $this->explode();
        }
        return false;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setInt(EntityMetadataProperties::FUSE_LENGTH, $this->fuse);
        $properties->setGenericFlag(EntityMetadataFlags::IGNITED, $this->explode);
        $properties->setGenericFlag(EntityMetadataFlags::POWERED, $this->powered);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setShort("Fuse", $this->fuse);
        $nbt->setFloat("Force", $this->force);
        $nbt->setByte("ignited", $this->ignited ? 1 : 0);
        $nbt->setByte("powered", $this->powered ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        return [
            VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}