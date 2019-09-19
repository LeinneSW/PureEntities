<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Stair;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use pocketmine\math\AxisAlignedBB;

class EntityAI{

    const WALL = 0;
    const AIR = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const STAIR = 4;

    public static function checkBlockState(World $world, AxisAlignedBB $aabb, ?Vector3 $motion = null) : int{
        if($motion === null){
            $block = $world->getBlock(new Vector3(
                (int) ($aabb->maxX + $aabb->minX) / 2,
                (int) $aabb->minY,
                (int) ($aabb->maxZ + $aabb->minZ) / 2
            ));
        }else{
            $block = $world->getBlock(new Vector3(
                (int) (($motion->x > 0 ? $aabb->maxX : $aabb->minX) + $motion->x),
                (int) $aabb->minY,
                (int) (($motion->z > 0 ? $aabb->maxZ : $aabb->minZ) + $motion->z)
            ));
        }

        if(!isset($block->getCollisionBoxes()[0])){
            return EntityAI::AIR;
        }elseif(isset($block->getSide(Facing::UP, 2)->getCollisionBoxes()[0])){
            return EntityAI::WALL;
        }

        $blockBox = $block->getCollisionBoxes()[0];
        if(($up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null) === null){ /** 위에 아무 블럭이 없을 때 */
            if($blockBox->maxY - $blockBox->minY > 1 || $blockBox->maxY === $aabb->minY){ /** 울타리 or 반블럭 위 */
                return EntityAI::WALL;
            }else{
                if($block instanceof Stair){
                    return EntityAI::STAIR;
                }
                return $blockBox->maxY - $aabb->minY === 0.5 ? EntityAI::SLAB : EntityAI::BLOCK;
            }
        }elseif($up->maxY - $aabb->minY === 1.0){ /** 반블럭 위에서 반블럭 * 3 점프 */
            return EntityAI::BLOCK;
        }
        return EntityAI::WALL;
    }

    public static function quickSort(array &$data, int $left, int $right) : void{
        if($right === -1){
            $right = count($data) - 1;
        }

        $keys = array_keys($data);
        $pivot = $data[$keys[$left]];
        for($i = $left, $j = $right; $i < $j; --$j){
            while($data[$keys[$j]]->fscore >= $pivot->fscore && $i < $j)
                --$j;
            if($i < $j)
                $data[$keys[$i]] = $data[$keys[$j]];
            while($data[$keys[$i]]->fscore <= $pivot->fscore && $i < $j)
                ++$i;
            if($i >= $j) break;
            $data[$keys[$j]] = $data[$keys[$i]];
        }
        $data[$keys[$i]] = $pivot;
        if($i > $left) self::quickSort($data, $left, $i - 1);
        if($i < $right) self::quickSort($data, $i + 1, $right);
    }

}
