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
				return (strtolower($event->getName()) == $this->filter);

			else if (is_array($this->filter))
			{
				$match = TRUE;
				$data = null;
				foreach( $this->filter as $key => $value)
				{
					$data = $data ?? $event->getData(); // todo:   Remove duplication when base object exists
					if (!isset($data[$key]) && !is_null($value))
					{
						$match = FALSE;
						break;
					}
					else if ((is_string($value)||is_numeric($value)) != (is_string($data[$key])||is_numeric($data[$key])))
					{
						$match = FALSE;
						throw new Exception("ToDo:   comparison of event filter + event data key '".$key." is not equally numeric/string;   determine effective exception or failthrough in the future");
						break;
					}
					else if (is_string($value) || $is_numeric($value))
					{
						if ($value != $data[$key])
						{
							$match = FALSE;
							break;
						}
					}
					else
					{
						throw new Exception("ToDo:   determine other types to compare in the future");
					}
				}

				return $match;
			}
			else
				throw new Exception("ToDo: unsupported log filter type; finish Novae\Event\Filter system");
		}

	}
