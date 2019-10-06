<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Lava;
use pocketmine\block\Stair;
use pocketmine\math\Facing;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const PASS = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const UP_SLAB = 4;
    const DOOR = 5;
    //const STAIR = 6;

    /**
     * 특정 블럭이 어떤 상태인지를 확인해주는 메서드
     *
     * @param Block $block
     *
     * @return int
     */
    public static function checkBlockState(Block $block) : int{
        if(count($blocks = $block->getAffectedBlocks()) > 1){ //이웃된 블럭이 있을 때
            $blockA = $blocks[0]->getCollisionBoxes()[0] ?? null;
            $blockB = $blocks[1]->getCollisionBoxes()[0] ?? null;
            if($blockA !== null && $blockB !== null && max($blockA->maxY, $blockB->maxY) - min($blockA->minY, $blockB->minY) == 2){ //그 블럭이 문이라면
                return EntityAI::DOOR;
            }
        }

        if($block instanceof Stair){
            return EntityAI::BLOCK; //TODO: 계단은 확인하기 어려움
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
        }elseif($boxDiff == 1){ //블럭이라면
            return EntityAI::BLOCK;
        }elseif($boxDiff === 0.5){ //반블럭이라면
            return $blockBox->minY - (int) $blockBox->minY === 0.5 ? EntityAI::UP_SLAB : EntityAI::SLAB;
        }
        return $boxDiff > 0 ? EntityAI::BLOCK : EntityAI::PASS;
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
                return (
                    self::checkBlockState($block->getSide(Facing::UP)) === EntityAI::PASS //y + 1이 통과가능하고
                    && self::checkBlockState($block->getSide(Facing::UP, 2)) === EntityAI::PASS //y + 2도 통과가능하면
                ) ? ($pos->y - (int) $pos->y === 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL; //현재위치에 따라 반블럭/블럭 으로 구분
            case EntityAI::SLAB:
                return (
                    self::checkBlockState($block->getSide(Facing::UP)) === EntityAI::PASS //y + 1이 통과가능하고
                    && (($up = self::checkBlockState($block->getSide(Facing::UP, 2))) === EntityAI::PASS || $up === EntityAI::UP_SLAB) //y + 2을 통과가능(반블럭 포함)하면
                ) ? ($pos->y - (int) $pos->y === 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL;
        }
        return EntityAI::WALL;
    }

}
