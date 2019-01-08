<?php

declare(strict_types=1);

namespace leinne\pureentities;

use leinne\pureentities\entity\EntityBase;
use leinne\pureentities\entity\neutral\ZombiePigman;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\utility\IronGolem;
use leinne\pureentities\entity\utility\SnowGolem;
use leinne\pureentities\task\AutoSpawnTask;
use leinne\pureentities\tile\MobSpawner;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\SpawnEgg;
use pocketmine\level\Position;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\TileFactory;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase implements Listener{

    private $data = [];

    public function onLoad(){
        /** Register hostile */
//        EntityFactory::register(Blaze::class, \false, ['minecraft:blaze']);
        EntityFactory::register(Creeper::class, \false, ['minecraft:creeper']);
//        EntityFactory::register(Enderman::class, \false, ['minecraft:enderman']);
//        EntityFactory::register(Ghast::class, \false, ['minecraft:ghast']);
//        EntityFactory::register(MagmaCube::class, \false, ['minecraft:magmacube']);
//        EntityFactory::register(Silverfish::class, \false, ['minecraft:silverfish']);
        EntityFactory::register(Skeleton::class, \false, ['minecraft:skeleton']);
//        EntityFactory::register(Slime::class, \false, ['minecraft:slime']);
        EntityFactory::register(Zombie::class, \false, ['Zombie', 'minecraft:zombie']);
        //EntityFactory::register(ZombieVillager::class, \false, ['minecraft:zombie_villager']);

        /** Register neutral */
//        EntityFactory::register(CaveSpider::class, \false, ['minecraft:cavespider']);
        EntityFactory::register(ZombiePigman::class, \false, ['ZombiePigman', 'minecraft:zombie_pigman']);
        EntityFactory::register(Spider::class, \false, ['Spider', 'minecraft:spider']);

        /** Register passive */
        EntityFactory::register(Chicken::class, \false, ['Chicken', 'minecraft:chicken']);
        EntityFactory::register(Cow::class, \false, ['Cow', 'minecraft:cow']);
        EntityFactory::register(Mooshroom::class, \false, ['Mooshroom', 'minecraft:mooshroom']);
        EntityFactory::register(Pig::class, \false, ['Pig', 'minecraft:pig']);
//        EntityFactory::register(Rabbit::class, \false, ['Rabbit', 'minecraft:rabbit']);
        EntityFactory::register(Sheep::class, \false, ['Sheep', 'minecraft:sheep']);

        /** Register tameable */
//        EntityFactory::register(Ocelot::class, \false, ['minecraft:ocelot']);
//        EntityFactory::register(Wolf::class, \false, ['minecraft:wolf']);

        /** Register utility */
        EntityFactory::register(IronGolem::class, \false, ['IronGolem', 'minecraft:iron_golem']);
//        EntityFactory::register(SnowGolem::class, \false, ['SnowGolem', 'minecraft:snow_golem']);

        /** Register Projectile */
//        EntityFactory::register(SmallFireBall::class, \false, ['minecraft:smallfireball']);
//        EntityFactory::register(LargeFireBall::class, \false, ['minecraft:largefireball']);

        TileFactory::register(MobSpawner::class, ["MobSpanwer", 'minecraft:mob_spawner']);

        foreach(EntityFactory::getKnownTypes() as $k => $className){
            /** @var Living|string $className */
            if(\is_a($className, EntityBase::class, \true) && $className::NETWORK_ID !== -1){
                ItemFactory::registerItem(new SpawnEgg(Item::SPAWN_EGG, $className::NETWORK_ID, $className, "Spawn " . (new \ReflectionClass($className))->getShortName()), \true);
            }
        }

        $this->getServer()->getLogger()->info(TextFormat::AQUA . '[PureEntities]All entities were registered');
    }

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->data = $this->getConfig()->getAll();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if(($this->data["autospawn"]["enable"] ?? "true") === "true"){
            $this->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask(), (int) ($this->data["autospawn"]["tick"] ?? 80));
        }

        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been enabled');
    }

    public function onDisable() : void{
        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been disabled');
    }

    public function onInteractEvent(PlayerInteractEvent $ev) : void{
        if($ev->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        if($item->getId() === Item::SPAWN_EGG && $block->getId() === Item::MONSTER_SPAWNER){
            $ev->setCancelled();

            $tile = $block->level->getTile($block);
            if($tile instanceof MobSpawner){
                $tile->setSpawnEntityType($item->getDamage());
            }else{
                if($tile !== \null){
                    $tile->close();
                }

                $tile = TileFactory::create("MobSpawner", $block->level, $block);
                $tile->readSaveData(new CompoundTag('', [
                    new IntTag('EntityId', $item->getDamage()),
                ]));
                $tile->level->addTile($tile);
            }
        }
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $ev) : void{
        if($ev->isCancelled()){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        if($block->getId() === Block::JACK_O_LANTERN || $block->getId() === Block::PUMPKIN){
            if(
                $block->getSide(Facing::DOWN)->getId() === Block::SNOW_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === Block::SNOW_BLOCK
            ){
                try{
                    $entity = EntityFactory::create(SnowGolem::class, $block->level, EntityFactory::createBaseNBT(Position::fromObject($block->add(0.5, -2, 0.5), $block->level)));
                }catch(\Exception $e){
                    $player->sendMessage(TextFormat::RED . 'Error');
                    return;
                }
                $ev->setCancelled();
                for($y = 1; $y < 3; $y++){
                    $block->getLevel()->setBlock($block->subtract(0, $y, 0), new Air());
                }
                $entity->spawnToAll();

                if($player->isSurvival()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }elseif(
                $block->getSide(Facing::DOWN)->getId() === Block::IRON_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === Block::IRON_BLOCK
            ){
                $down = $block->getSide(Facing::DOWN);
                if(($first = $down->getSide(Facing::EAST))->getId() === Block::IRON_BLOCK){
                    $second = $down->getSide(Facing::WEST);
                }

                if(!isset($second) && ($first = $down->getSide(Facing::NORTH))->getId() === Block::IRON_BLOCK){
                    $second = $down->getSide(Facing::SOUTH);
                }

                if(!isset($second) || $second->getId() !== Block::IRON_BLOCK){
                    return;
                }

                $nbt = EntityFactory::createBaseNBT(Position::fromObject($pos = $block->add(0.5, -2, 0.5), $block->level));
                $nbt->setString("Owner", $player->getName());
                try{
                    $entity = EntityFactory::create(IronGolem::class, $block->level, $nbt);
                }catch(\Exception $e){
                    $player->sendMessage(TextFormat::RED . 'Error');
                    return;
                }
                $ev->setCancelled();
                $entity->spawnToAll();

                $down->getLevel()->setBlock($pos, new Air());
                $down->getLevel()->setBlock($first, new Air());
                $down->getLevel()->setBlock($second, new Air());
                $down->getLevel()->setBlock($block->add(0, -1, 0), new Air());

                if($player->isSurvival()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }
        }
    }

    //TODO: SilverFish
    /*public function BlockBreakEvent(BlockBreakEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if(
            (
                $block->getId() === Block::STONE
                or $block->getId() === Block::STONE_WALL
                or $block->getId() === Block::STONE_BRICK
                or $block->getId() === Block::STONE_BRICK_STAIRS
            ) && ($block->level->getBlockLightAt((int) $block->x, (int) $block->y, (int) $block->z) < 12 and mt_rand(1, 5) < 2)
        ){
            $entity = PureEntities::create('Silverfish', $block);
            if($entity !== \null){
                $entity->spawnToAll();
            }
        }
    }*/

}