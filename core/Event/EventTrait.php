<?php
	namespace Novae\Event;

	trait EventTrait {
		private $__eventTrait_data;
		private $__eventTrait_timestamp = FALSE;
		private $__eventTrait_name = FALSE;

		public function setTimestamp($to)
		{
			throw new ToDoException("timestamps are read-only"); // replace when the exception framework exists
		}

		public function setData( $data )
		{
			throw new ToDoException("Event data is read-only"); // replace when the exception framework exists
		}

		public function setName()
		{
			throw new ToDoException("Event data is read-only"); // replace when the exception framework exists
		}

		public function getTimestamp()
		{
			return $this->__eventTrait_timestamp;
		}

		public function getData()
		{
			return $this->__eventTrait_data;
		}

		public function getName()
		{
			return $this->__eventTrait_name;
		}


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

/*			{
				asort($strings_lengthMap);
				reset($strings_lengthMap);

				$thisStringKey = key($strings_lengthMap);
				$newData["name"] = $strings[$thisStringKey];
				unset($strings[$thisStringKey]);
			}
*/
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

				$this->__eventTrait_timestamp = $data["timestamp"];
			}
			else
				$this->__eventTrait_timestamp = microtime(TRUE);

			if (isset($data["name"]))
				$this->__eventTrait_name = $data["name"];

			$this->__eventTrait_data = $data;
		}


	}
