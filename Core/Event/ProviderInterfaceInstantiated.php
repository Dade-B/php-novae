<?php
	namespace Novae\Core\Event;

	interface ProviderInterface {
		public function registerToProvider(callable $listener);
	}

