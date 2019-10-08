<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Lava;
use pocketmine\block\Stair;
use pocketmine\math\Facing;
use pocketmine\math\Math;
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
    //private static $cache = [];

    public static function getHash(Vector3 $pos) : string{
        return Math::floorFloat($pos->x) . ":{$pos->getFloorY()}:" . Math::floorFloat($pos->z);
        //return "{$pos->getFloorX()}:{$pos->getFloorY()}:{$pos->getFloorZ()}";
    }

    /**
     * 특정 블럭이 어떤 상태인지를 확인해주는 메서드
     *
     * @param Block|Position $data
     *
     * @return int
     */
    public static function checkBlockState($data) : int{
        if($data instanceof Position){
            /*if(isset(self::$cache[$hash = self::getHash($data)])){
                return self::$cache[$hash];
            }*/
            $block = $data->world->getBlockAt(Math::floorFloat($data->x), $data->getFloorY(), Math::floorFloat($data->z));
        }elseif($data instanceof Block){
            /*if(isset(self::$cache[$hash = self::getHash($data->getPos())])){
                return self::$cache[$hash];
            }*/
            $block = $data;
        }else{
            throw new \RuntimeException("$data is not Block|Position class");
        }

        //$value = EntityAI::BLOCK;
        if($block instanceof Door && count($block->getAffectedBlocks()) > 1){ //이웃된 블럭이 있을 때
            return EntityAI::DOOR;
        }elseif($block instanceof Stair){
            return EntityAI::BLOCK;
        }//else{
            $blockBox = $block->getCollisionBoxes()[0] ?? null;
            $boxDiff = $blockBox === null ? 0 : $blockBox->maxY - $blockBox->minY;
            if($boxDiff <= 0){
                if($block instanceof Lava){ //통과 가능 블럭중 예외처리
                    return EntityAI::WALL;
                }//else{
                    return EntityAI::PASS;
                //}
            }elseif($boxDiff > 1){ //울타리라면
                return EntityAI::WALL;
            }elseif($boxDiff <= 0.5){ //반블럭/카펫/트랩도어 등등
                return $blockBox->minY == (int) $blockBox->minY ? EntityAI::SLAB : EntityAI::UP_SLAB;
            }
        //}
        //self::$cache[$hash] = $value;
        //return $value;
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
        $block = $pos->world->getBlockAt(Math::floorFloat($pos->x), $pos->getFloorY(), Math::floorFloat($pos->z));
        $state = self::checkBlockState($block); //현재 위치에서의 블럭 상태가
        switch($state){
            case EntityAI::WALL:
            case EntityAI::DOOR: //벽이거나 문이라면 체크가 더이상 필요 없음
                return $state;
            case EntityAI::PASS: //통과가능시에
                //윗블럭도 통과 가능하다면 통과판정 아니라면 벽 판정
                return self::checkBlockState($block->getSide(Facing::UP)) === EntityAI::PASS ? EntityAI::PASS : EntityAI::WALL;
            case EntityAI::BLOCK:
            case EntityAI::UP_SLAB: //블럭이거나 위에 설치된 반블럭일경우
                $up = self::checkBlockState($upBlock = $block->getSide(Facing::UP)); //y+1의 블럭이
                if($up === EntityAI::SLAB){ //반블럭 이라면
                    $up2 = self::checkBlockState($block->getSide(Facing::UP, 2));
                    if($upBlock->getCollisionBoxes()[0]->maxY - $pos->y <= 1){
                        return $up2 === EntityAI::PASS ? EntityAI::BLOCK : EntityAI::WALL;
                    }
                }elseif($up === EntityAI::PASS){ //통과가능시에
                    //y+ 2도 통과 가능이라면
                    return self::checkBlockState($block->getSide(Facing::UP, 2)) === EntityAI::PASS ?
                        //점프 대상 블럭의 최대 y값과 자신의 위치의 차가 반블럭 이하라면 반블럭 판정 아니라면 블럭 판정
                        ($block->getCollisionBoxes()[0]->maxY - $pos->y <= 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL;
                }
                return EntityAI::WALL;
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
