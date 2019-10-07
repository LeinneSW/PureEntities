<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Lava;
use pocketmine\block\Stair;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const PASS = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const UP_SLAB = 4;
    const DOOR = 5;

    /** @var int[] */
    private static $cache = [];

    public static function getHash(Vector3 $pos) : string{
        return "{$pos->x}:{$pos->y}:{$pos->z}";
    }

    /**
     * 특정 블럭이 어떤 상태인지를 확인해주는 메서드
     *
     * @param Block|Position $data
     *
     * @return int
     * @throws \RuntimeException
     */
    public static function checkBlockState($data) : int{
        if($data instanceof Position){
            $block = $data->world->getBlockAt($data->getFloorX(), $data->getFloorY(), $data->getFloorZ());
        }elseif($data instanceof Block){
            $block = $data;
        }else{
            throw new \RuntimeException("$data is not Block|Position class");
        }

        if(isset(self::$cache[$hash = self::getHash($block->getPos())])){
            return self::$cache[$hash];
        }

        if($block instanceof Door && count($block->getAffectedBlocks()) > 1){ //이웃된 블럭이 있을 때
            return EntityAI::DOOR;
        }elseif($block instanceof Stair){
            return EntityAI::BLOCK;
        }

        $blockBox = $block->getCollisionBoxes()[0] ?? null;
        $boxDiff = $blockBox === null ? 0 : $blockBox->maxY - $blockBox->minY;
        if($boxDiff <= 0){
            if($block instanceof Lava){ //통과 가능 블럭중 예외처리
                return EntityAI::WALL;
            }
            return EntityAI::PASS;
        }elseif($boxDiff > 1){ //울타리라면
            return EntityAI::WALL;
        }elseif($boxDiff <= 0.5){ //반블럭/카펫/트랩도어 등등
            return $blockBox->minY == (int) $blockBox->minY ? EntityAI::SLAB : EntityAI::UP_SLAB;
        }
        return EntityAI::BLOCK; //TODO: 트랩도어
    }

    /**
     * 블럭이 통과 가능한 위치인지를 판단하는 메서드
     *
     * @param Position $pos
     *
     * @return int
     */
    public static function checkPassablity(Position $pos) : int{
        $block = $pos->world->getBlock($pos);
        $state = self::checkBlockState($block);
        switch($state){
            case EntityAI::WALL:
            case EntityAI::DOOR:
                return $state;
            case EntityAI::PASS:
                return self::checkBlockState($block->getSide(Facing::UP)) === EntityAI::PASS ? EntityAI::PASS : EntityAI::WALL;
            case EntityAI::BLOCK:
            case EntityAI::UP_SLAB: //블럭이거나 위에 설치된 반블럭일경우
                $up = self::checkBlockState($upBlock = $block->getSide(Facing::UP));
                if($up !== EntityAI::PASS && $up !== EntityAI::SLAB){
                    return EntityAI::WALL;
                }

                $up2 = self::checkBlockState($block->getSide(Facing::UP, 2));
                if($up === EntityAI::SLAB && $upBlock->getCollisionBoxes()[0]->maxY - $pos->y <= 1){
                    return $up2 === EntityAI::PASS ? EntityAI::BLOCK : EntityAI::WALL;
                }
                return ($up === EntityAI::PASS && $up2 === EntityAI::PASS) ? ($pos->y - (int) $pos->y >= 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL; //현재위치에 따라 반블럭/블럭 으로 구분
            case EntityAI::SLAB:
                return (
                    self::checkBlockState($block->getSide(Facing::UP)) === EntityAI::PASS //y + 1이 통과가능하고
                    && (($up = self::checkBlockState($block->getSide(Facing::UP, 2))) === EntityAI::PASS || $up === EntityAI::UP_SLAB) //y + 2을 통과가능(반블럭 포함)하면
                ) ? EntityAI::SLAB : EntityAI::WALL;
        }
        return EntityAI::WALL;
    }

    public static function updateBlockState(Block $block) : void{
        //TODO: 속도 개선을 위해 캐시데이터 추가
    }

}
