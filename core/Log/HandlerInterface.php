<?php
	namespace Novae\Log;

	interface HandlerInterface {
		public function __invoke( \Novae\Event\EventInterface $event ); // used as the callback for the EventListener
	}
