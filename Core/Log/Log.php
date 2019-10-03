<?php
	namespace Novae\Core\Log;

	class Log extends \Novae\Core\Event\Event {
		private $timestamp = FALSE;
		private $data = [];

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
			return $this->timestamp;
		}

		public function getData()
		{
			return $this->data;
		}

		// Unpack arbitrary data provided to the LogEntry constructor, in to Log Data
		protected function unpackData($data)
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

			var_Dump(["data" => $data, "newData" => $newData]);

			return $newData;
		}

		public function __construct(...$data)
		{
			$data = $this->unpackData($data);

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

				$this->timestamp = $data["timestamp"];
			}
			else
				$this->timestamp = microtime(TRUE);

			$this->data = $data;
		}


	}
