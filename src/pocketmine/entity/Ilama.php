<?php

/*
 * _      _ _        _____               
 *| |    (_) |      / ____|              
 *| |     _| |_ ___| |     ___  _ __ ___ 
 *| |    | | __/ _ \ |    / _ \| '__/ _ \
 *| |____| | ||  __/ |___| (_) | | |  __/
 *|______|_|\__\___|\_____\___/|_|  \___|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author genisyspromcpe
 * @link https://github.com/genisyspromcpe/LiteCore
 *
 *
*/

namespace pocketmine\entity;

use pocketmine\item\Item as ItemItem;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Ilama extends Animal {
	const NETWORK_ID = 29;

	const CREAMY = 0;
	const WHITE = 1;
	const BROWN = 2;
	const GRAY = 3;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 0;

	public $dropExp = [1, 3];

	/**
	 * @return string
	 */
	public function getName(){
		return "Ilama";
	}

	public function initEntity(){
		$this->setMaxHealth(30);
		$this->setDataProperty(Entity::DATA_VARIANT, Entity::DATA_TYPE_INT, rand(0, 3));
		parent::initEntity();
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

	/**
	 * @return array
	 */
	public function getDrops(){
		$drops = [
			ItemItem::get(ItemItem::LEATHER, 0, mt_rand(0, 2))
		];

		return $drops;
	}
}
