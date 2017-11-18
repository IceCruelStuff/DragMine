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

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EnderEye extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::ENDER_EYE, $meta, "EnderEye");
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$item = clone $this;
		$drop = $player->getLevel()->dropItem(new Vector3($player->x, $player->y + $player->getEyeHeight(), $player->z), $item->setCount(1), $directionVector->multiply($this->getThrowForce()));
		$this->count--;
		return true;
	}

	public function getThrowForce() : float{
		return 0.4;
	}
}