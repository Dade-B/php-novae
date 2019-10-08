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
		function __construct()
		{
			\Novae\Event\Stream::on(["eventType" => "log"], $this);
		}
	}
