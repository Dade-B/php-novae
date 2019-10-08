<?php
	namespace Novae\Log;

	trait HandlerTrait {

		static public function FormatMessage( \Novae\Event\EventInterface $event )
		{
			$eventData = $event->getData();

			$str = str_pad("", 60, "*")."\n";
			$str .= "Time: ".date("Y-m-d H:i:s", $event->getTimestamp())."\n";
			$str .= "Type: ".$eventData["logType"]."\n";
			$str .= "Name: ".$event->getName()."\n";
			$str .= str_pad("", 60, "*")."\n";

			$column = 0;
			foreach($eventData as $key => $value)
			{
				if (in_array($key, ["timestamp", "logType", "name", "strings"]))
					continue;

				$varStr = str_pad(" ". $key .": ".var_export($value, TRUE), 30, " ");

				if ($varStr > 30)
				{
					if ($column % 2 != 0)
					{
						$str .= "(a)\n";
						$column++;
					}
				}

				$newLinePad = str_pad("", (($column % 2 == 0) ? 0 : 30) + strlen($key)+2, " ");
				$str .= str_replace("\n", "\n".$newLinePad, $varStr);

				if ($column % 2 == 1 || strlen($varStr) > 30)
				{
					$str .= "\n";
					$column = 0;
				}
				else $column++;
			}

			$str .= str_pad("", 60, "*")."\n\n";

			return $str;
		}

	}
