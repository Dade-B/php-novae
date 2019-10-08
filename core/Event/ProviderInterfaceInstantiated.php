<?php
	namespace Novae\Event;

	interface ProviderInterface {
		public function registerToProvider(callable $listener);
	}

