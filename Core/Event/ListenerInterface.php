<?php
	namespace Novae\Core\Event;

	interface ListenerInterface {
		public function __invoke( object $event );
		public function unsubscribe();
	}
