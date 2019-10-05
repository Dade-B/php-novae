<?php
	namespace Novae\Core\Event;

	class Listener implements ListenerInterface{

		private $callable;

		public function __construct( callable $callable )
		{
			$this->callable = $callable;
		}

		public function unsubscribe()
		{
			throw new ToDo("Refactor object relationship between dispatchers and Listeners so that the listener is aware of where it is subscribed");
		}

		public function __invoke( object $event )
		{
			return ($this->callable)($event);
		}

	}
