<?php
	namespace Novae\Core\Event;
	interface EventInterface {

		public function getTimestamp();
		public function setTimestamp($timestamp);
		public function getData();
		public function setData($data);
	}
