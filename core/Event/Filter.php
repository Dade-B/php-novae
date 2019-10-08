<?php
	namespace Novae\Event;

	class Filter implements FilterInterface {

		private $filter = null;

		//ToDo:  allow filters on the event data and other context
		public function __construct($eventName)
		{
			if (is_string($eventName))
				$this->filter = strtolower($eventName);
			else
				$this->filter = $eventName;
		}

		public function verify( EventInterface $event )
		{
			if (is_string($this->filter))
				return (strtolower($event->name) == $this->filter);

			else if (is_array($this->filter))
			{
				$match = TRUE;
				//ToDo:  refactor to simplify this....
				foreach( $this->filter as $key => $value)
				{
					if ($key == "__class") // need to retrieve manually since we aren't using getDataArray
						$eventValue = get_class($event);
					else
						$eventValue = $event->{$key};

					if ($eventValue === $value)
						continue;

					if (is_null($eventValue) != is_null($value))
					{
						$match = FALSE;
						break;
					}
					// compare numeric strings equally to numbers/floats,
					// compare bool values to numeric values
					else if ((is_numeric($value)||is_bool($value)) && (is_numeric($eventValue)||is_bool($eventValue)))
					{
						if ($value+0 != $eventValue+0)
						{
							$match = FALSE;
							break;
						}
					}
					else if (!is_string($value) || !is_string($eventValue))
					{
						throw new Exception("ToDo:   determine other types to compare in the future");
					}
					else if ($value != $eventValue)
					{
						$match = FALSE;
						break;
					}
				}

				return $match;
			}
			else
				throw new Exception("ToDo: unsupported log filter type; finish Novae\Event\Filter system");
		}

	}
