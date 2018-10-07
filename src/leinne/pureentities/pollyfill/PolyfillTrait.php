<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace leinne\pureentities\pollyfill;

use pocketmine\entity\Entity;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;


$reflector = new \ReflectionClass(Entity::class);
$reflectionMethod = $reflector->getMethod("saveNBT");
$returnType = (string) $reflectionMethod->getReturnType();

if($returnType === CompoundTag::class){ //API 4.0.0

	/**
	 * This trait override most methods in the {@link Entity} abstract class.
	 */
	trait PolyfillTrait{
		/**
		 * saveNBT() Polyfill
		 *
		 * @see Entity::saveNBT()
		 */

		/**
		 * 부모 클래스의 saveNBT() 호출 후
		 * saveNBTSafe() 메소드를 호출
		 */
		public function saveNBT() : CompoundTag{
			return $this->saveNBTSafe();
		}

		public function saveNBTSafe() : CompoundTag{
			return $this->parentSaveNBT();
		}

		public function parentSaveNBT() : CompoundTag{
			return parent::saveNBT();
		}
	}
}else{ //API 3.2.3

	/**
	 * This trait override most methods in the {@link Entity} abstract class.
	 *
	 * @property CompoundTag namedtag
	 */
	trait PolyfillTrait{
		/**
		 * saveNBT() Polyfill
		 *
		 * @see Entity::saveNBT()
		 */

		/**
		 * 부모 클래스의 saveNBT() 호출 후
		 * saveNBTSafe() 메소드를 호출
		 */
		public function saveNBT() : void{
			$this->namedtag = $this->saveNBTSafe();
		}

		public function saveNBTSafe() : CompoundTag{
			return $this->parentSaveNBT();
		}

		public function parentSaveNBT() : CompoundTag{
			parent::saveNBT();
			return $this->namedtag;
		}
	}
}
