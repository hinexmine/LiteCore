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

use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\MobSpellParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Arrow extends Projectile {
	const NETWORK_ID = 80;

	public $width = 0.5;
	public $length = 0.5;
	public $height = 0;

	protected $gravity = 0.05;
	protected $drag = 0.01;

	protected $damage = 2;

	protected $isCritical;
	protected $potionId;

	/**
	 * Arrow constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param Entity|null $shootingEntity
	 * @param bool        $critical
	 */
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, $critical = false){
		$this->isCritical = (bool) $critical;
		if(!isset($nbt->Potion)){
			$nbt->Potion = new ShortTag("Potion", 0);
		}
		parent::__construct($level, $nbt, $shootingEntity);
		$this->potionId = $this->namedtag["Potion"];
	}

	/**
	 * @return bool
	 */
	public function isCritical() : bool{
		return $this->isCritical;
	}

	/**
	 * @return int
	 */
	public function getPotionId() : int{
		return $this->potionId;
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);

		if(!$this->hadCollision and $this->isCritical){
			$this->level->addParticle(new CriticalParticle($this->add(
				$this->width / 2 + mt_rand(-100, 100) / 500,
				$this->height / 2 + mt_rand(-100, 100) / 500,
				$this->width / 2 + mt_rand(-100, 100) / 500)));
		}elseif($this->onGround){
			$this->isCritical = false;
		}

		if($this->potionId != 0){
			if(!$this->onGround or ($this->onGround and ($currentTick % 4) == 0)){
				$color = Potion::getColor($this->potionId - 1);
				$this->level->addParticle(new MobSpellParticle($this->add(
					$this->width / 2 + mt_rand(-100, 100) / 500,
					$this->height / 2 + mt_rand(-100, 100) / 500,
					$this->width / 2 + mt_rand(-100, 100) / 500), $color[0], $color[1], $color[2]));
			}
			$hasUpdate = true;
		}

		if($this->age > 1200){
			$this->kill();
			$hasUpdate = true;
		}

		$this->timings->stopTiming();

		return $hasUpdate;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = Arrow::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}