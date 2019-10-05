<?php
	namespace Novae\Core\Event;

	trait EventTrait {
		private $__eventTrait_data;
		private $__eventTrait_timestamp = FALSE;

		public function setTimestamp($to)
		{
			// ToDo:   Read only exception
			return FALSE;
		}

		public function setData( $data )
		{
			// ToDo:   Read only exception
		}

		public function getTimestamp()
		{
			return $this->__eventTrait_timestamp;
		}

		public function getData()
		{
			return $this->__eventTrait_data;
		}

		// Unpack arbitrary data provided to the LogEntry constructor, in to Log Data
		private function __eventTrait_unpackData($data)
		{
			$newData = [];
			$message = FALSE;
			$unindexedObjectsByClass = [];
			foreach($data as $arg)
			{
				if ($message === FALSE && is_string($arg))
					$message = $arg;

				else if (is_array($arg))
				{
					foreach( $arg as $nestedKey => $nestedData )
					{
						$newData[$nestedKey] = $nestedData;
					}
				}

				else if (is_object($arg))
				{
					$className = get_class($arg);
					$unindexedObjectsByClass[$className][] = $arg;
				}
			}

			if (!isset($newData["message"]) && $message !== FALSE)
				$newData["message"] = $message;

			foreach($unindexedObjectsByClass as $className => $list)
			{
				if (isset($newData[$className]))
					continue;

				/* ex.	log("Message", $userInstance, $foobarInstance1, $foobarInstance2) 
					result:  [ \User => $user, \Foobar => [ $foobarInstance1, $foobarInstance2 ]]
				*/
				if (count($list) == 1) 
					$newData[$className] = reset($list);
				else
					$newData[$className] = $list;
			}

			return $newData;
		}

		// It's intended that this is called by the consumer, with-out removing their ability to provide their own constructor
		public function __construct_EventTrait(...$data)
		{
			$data = $this->__eventTrait_unpackData($data);

			if (isset($data["timestamp"]))
			{
				if (is_string($data["timestamp"]))
				{
					try {
						$date = new DateTime($data["timestamp"]);
						$data["timestamp"] = $date->format("U.u");
					}
					catch (Exception $e)
					{
						throw new ToDo(); // Data type exception needed
					}
				}

				if (!is_numeric($data["timestamp"]))
					throw new ToDo(); // Data type exception needed

				$this->__eventTrait_timestamp = $data["timestamp"];
			}
			else
				$this->__eventTrait_timestamp = microtime(TRUE);

			$this->__eventTrait_data = $data;
		}


	}
