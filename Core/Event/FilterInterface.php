<?php
	namespace Novae\Core\Event;

	interface FilterInterface {
		public function verify( EventInterface $event );

	}
