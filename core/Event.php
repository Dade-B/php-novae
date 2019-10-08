<?php
	namespace Novae;

	class Event extends CoreObject implements Event\EventInterface {
		use \Novae\Event\EventTrait; // ToDo: update when multi-constructor support exists
		function __construct( ...$data )
		{
			$this->__construct_EventTrait(...$data);
		}

	}
