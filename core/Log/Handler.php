<?php
	namespace Novae\Log;


	class Handler implements HandlerInterface {
		use HandlerTrait;

		function __invoke( $event )
		{
			$eventString = $this->formatMessage( $event );

			if (PHP_SAPI == "CLI")
			{
				file_put_contents("php://stderr", $eventString);
			}
			else
			{
				echo $eventString;
			}
		}


		// todo:   add filters
		function __construct( $filter = [] )
		{
			if (is_string($filter))
			{
				if (in_array(strtolower($filter), ["debug", "info", "notice", "warning", "error", "critical", "alert", "emergency", "trace"]))
					$filter = ["logType" => strtolower($filter)];
				else
					$filter = ["name" => $filter];
			}

			if (!is_array($filter))
				throw new ToDo("Replace this exception when validation exceptions exist"); // ToDo: see note

			if (!array_key_exists("__class", $filter))
				$filter["__class"] = "Novae\\Log";
			else if (empty($filter["__class"]) || $filter["__class"] === FALSE)
				unset($filter["__class"]);

			\Novae\Event\Stream::on($filter, $this);
		}
	}
