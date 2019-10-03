<?php
	namespace Novae\Core\Event;
	abstract class Event {

		abstract public function getTimestamp();
		abstract public function setTimestamp($timestamp);
		abstract public function getData();
		abstract public function setData($data);
	}
