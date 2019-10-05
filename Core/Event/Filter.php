<?php
	namespace Novae\Core\Event;

	class Filter implements FilterInterface {

		private $filter = null;

		//ToDo:  allow filters on the event data and other context
		public function __construct(string $eventName)
		{
			$this->filter = strtolower($eventName);
		}

		public function verify( EventInterface $event )
		{
			return (strtolower($event->getName()) == $this->filter);
		}

	}
