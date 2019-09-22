<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Stair;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\math\AxisAlignedBB;

class EntityAI{

    const WALL = 0;
    const AIR = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const STAIR = 4;

    public static function checkJumpState(World $world, AxisAlignedBB $aabb, Vector3 $motion) : int{
        return self::checkBlockState(new Position(
            ($motion->x > 0 ? $aabb->maxX : $aabb->minX) + $motion->x,
            $aabb->minY,
            ($motion->z > 0 ? $aabb->maxZ : $aabb->minZ) + $motion->z,
            $world
        ));
    }

    public static function checkBlockState(Position $pos) : int{
        $block = $pos->getWorld()->getBlock($pos->floor());
        $blockBox = $block->getCollisionBoxes()[0] ?? null;
        if($blockBox === null){
            return EntityAI::AIR;
        }elseif(($aabb = $block->getSide(Facing::UP, 2)->getCollisionBoxes()[0] ?? null) !== null){
            if($aabb->minY - $blockBox->maxY >= 1){
                return EntityAI::SLAB;
            }
            return EntityAI::WALL;
        }

        if(($up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null) === null){ /** y + 1(머리)에 아무 블럭이 없을 때 */
            if($blockBox->maxY - $blockBox->minY > 1){ /** 울타리 */
                return EntityAI::WALL;
            }elseif($blockBox->maxY === $pos->y){ /** 반블럭 위 */
                return EntityAI::AIR;
            }else{
                if($block instanceof Stair){
                    return EntityAI::STAIR;
                }
                return $blockBox->maxY - $pos->y === 0.5 ? EntityAI::SLAB : EntityAI::BLOCK;
            }
        }elseif($up->maxY - $pos->y === 1.0){ /** 반블럭 위에서 반블럭 * 3 점프 */
            return EntityAI::BLOCK;
        }
        return EntityAI::WALL;
    }

    /*public static function quickSort(array &$data, int $left, int $right) : void{
        $keys = array_keys($data);
        $pivot = $data[$keys[$left]];
        for($i = $left, $j = $right; $i < $j; --$j){
            while($data[$keys[$j]]->fscore >= $pivot->fscore && $i < $j){
                --$j;
            }

            if($i < $j){
                $data[$keys[$i]] = $data[$keys[$j]];
            }

            while($data[$keys[$i]]->fscore <= $pivot->fscore && $i < $j){
                ++$i;
            }

            if($i >= $j){
                break;
            }
            $data[$keys[$j]] = $data[$keys[$i]];
        }
        $data[$keys[$i]] = $pivot;

        if($i > $left){
            self::quickSort($data, $left, $i - 1);
        }
        if($i < $right){
            self::quickSort($data, $i + 1, $right);
        }
    }*/

    public static function quickSort(array &$data, int $left, int $right) : void{
        $pivot = $left;
        $j = $pivot;
        $i = $left + 1;

        $keys = array_keys($data);
        if($left < $right){
            for(; $i <= $right; ++$i){
                if($data[$keys[$i]]->fscore < $data[$keys[$pivot]]->fscore){
                    ++$j;
                    [$data[$keys[$j]], $data[$keys[$i]]] = [$data[$keys[$i]], $data[$keys[$j]]];
                }
            }
            [$data[$keys[$left]], $data[$keys[$j]]] = [$data[$keys[$j]], $data[$keys[$left]]];
            $pivot = $j;

            self::quickSort($data, $left, $pivot - 1);
            self::quickSort($data, $pivot + 1, $right);
        }
    }

}
