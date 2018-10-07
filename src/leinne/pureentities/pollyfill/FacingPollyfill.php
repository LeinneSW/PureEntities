<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace leinne\pureentities\pollyfill;

class FacingPollyfill{
    public const AXIS_Y = 0;
    public const AXIS_Z = 1;
    public const AXIS_X = 2;

    public const FLAG_AXIS_POSITIVE = 1;

    public const DOWN = self::AXIS_Y << 1;
    public const UP = (self::AXIS_Y << 1) | self::FLAG_AXIS_POSITIVE;
    public const NORTH = self::AXIS_Z << 1;
    public const SOUTH = (self::AXIS_Z << 1) | self::FLAG_AXIS_POSITIVE;
    public const WEST = self::AXIS_X << 1;
    public const EAST = (self::AXIS_X << 1) | self::FLAG_AXIS_POSITIVE;
}
