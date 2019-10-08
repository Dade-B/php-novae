<?php
	namespace Novae\Event;

	trait EventTrait {
		// Unpack arbitrary data provided to the LogEntry constructor, in to Log Data
		private function __eventTrait_unpackData($data)
		{
			$newData = [];
			$strings = [];
			$unindexedObjectsByClass = [];
			foreach($data as $arg)
			{
				if (is_string($arg) && !is_numeric($arg))
				{
					$strings[] = $arg;
				}

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
				// ToDo:  arbtrary, unindexed numbers?
			}


			// name will be the shortest string, message, the concatenation of the remainder, if not provided via the arbitrary indexed data
			if ($strings && !array_key_exists("name", $newData))
				$newData["name"] = array_shift($strings);

			if ($strings && !isset($newData["message"]))
				$newData["message"] = implode("\n", $strings);

			if ($strings && !isset($newData["strings"]))
				$newData["strings"] = $strings;

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
			}
			else
				$data["timestamp"] = microtime(TRUE);

			foreach($data as $key => &$value)
				$this->data[$key] = &$value;
		}


	}
