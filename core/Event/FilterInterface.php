<?php
	namespace Novae\Event;

	interface FilterInterface {
		public function verify( EventInterface $event );

	}
