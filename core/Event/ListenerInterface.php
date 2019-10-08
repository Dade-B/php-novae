<?php
	namespace Novae\Event;

	interface ListenerInterface {
		public function __invoke( object $event );
		public function unsubscribe();
	}
