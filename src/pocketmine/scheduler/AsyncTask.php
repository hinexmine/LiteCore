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

namespace pocketmine\scheduler;

use pocketmine\Server;

/**
 * Class used to run async tasks in other threads.
 *
 * An AsyncTask does not have its own thread. It is queued into an AsyncPool and executed if there is an async worker
 * with no AsyncTask running. Therefore, an AsyncTask SHOULD NOT execute for more than a few seconds. For tasks that
 * run for a long time or infinitely, start another {@link \pocketmine\Thread} instead.
 *
 * WARNING: Do not call PocketMine-MP API methods, or save objects (and arrays containing objects) from/on other Threads!!
 */
abstract class AsyncTask extends \Threaded implements \Collectable {

	/** @var AsyncWorker $worker */
	public $worker = null;

	private $result = null;
	private $serialized = false;
	private $cancelRun = false;
	/** @var int */
	private $taskId = null;

	private $crashed = false;

	private $isGarbage = false;

	private $isFinished = false;

	/**
	 * @return bool
	 */
	public function isGarbage() : bool{
		return $this->isGarbage;
	}

	public function setGarbage(){
		$this->isGarbage = true;
	}

	/**
	 * @return bool
	 */
	public function isFinished() : bool{
		return $this->isFinished;
	}

	public function run(){
		$this->result = null;
		$this->isGarbage = false;

		if($this->cancelRun !== true){
			try{
				$this->onRun();
			}catch(\Throwable $e){
				$this->crashed = true;
				$this->worker->handleException($e);
			}
		}

		$this->isFinished = true;
		//$this->setGarbage();
	}

	/**
	 * @return bool
	 */
	public function isCrashed(){
		return $this->crashed;
	}

	/**
	 * @return mixed
	 */
	public function getResult(){
		return $this->serialized ? unserialize($this->result) : $this->result;
	}

	public function cancelRun(){
		$this->cancelRun = true;
	}

	/**
	 * @return bool
	 */
	public function hasCancelledRun(){
		return $this->cancelRun === true;
	}

	/**
	 * @return bool
	 */
	public function hasResult(){
		return $this->result !== null;
	}

	/**
	 * @param mixed $result
	 * @param bool  $serialize
	 */
	public function setResult($result, $serialize = true){
		$this->result = $serialize ? serialize($result) : $result;
		$this->serialized = $serialize;
	}

	/**
	 * @param $taskId
	 */
	public function setTaskId($taskId){
		$this->taskId = $taskId;
	}

	/**
	 * @return int
	 */
	public function getTaskId(){
		return $this->taskId;
	}

	/**
	 * Gets something into the local thread store.
	 * You have to initialize this in some way from the task on run
	 *
	 * @param string $identifier
	 *
	 * @return mixed
	 */
	public function getFromThreadStore($identifier){
		global $store;
		return $this->isGarbage() ? null : $store[$identifier];
	}

	/**
	 * Saves something into the local thread store.
	 * This might get deleted at any moment.
	 *
	 * @param string $identifier
	 * @param mixed  $value
	 */
	public function saveToThreadStore($identifier, $value){
		global $store;
		if(!$this->isGarbage()){
			$store[$identifier] = $value;
		}
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public abstract function onRun();

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function onCompletion(Server $server){

	}

	public function cleanObject(){
		foreach($this as $p => $v){
			if(!($v instanceof \Threaded) and !in_array($p, ["isFinished", "isGarbage", "cancelRun"])){
				$this->{$p} = null;
			}
		}

	    $this->setGarbage();
    }
}
