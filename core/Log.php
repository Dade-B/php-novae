<?php
	namespace Novae;

	class Log implements \Novae\Event\EventInterface
	{
		use \Novae\Event\EventTrait { __construct_EventTrait as __construct; }



		static private $logMethodMap = [
			"trace" =>	[ "logType" => "trace" ], // not rfc
			"insert" => 	[ ], // logType is specified in the arguments, or an EventObject is being sent in
			// below here is all rfc 5424 log types
			"debug" => 	[ "logType" => "debug" ],
			"info" =>	[ "logType" => "info" ],
			"notice" =>	[ "logType" => "notice" ],
			"warning" =>	[ "logType" => "warning" ],
			"warn" =>	[ "logType" => "warning" ],
			"error" => 	[ "logType" => "error" ],
			"critical" =>	[ "logType" => "critical" ],
			"alert" =>	[ "logType" => "alert" ],
			"emergency" =>	[ "logType" => "emergency" ],
		];

		static public function __callStatic($method, $args)
		{
			if (!isset(self::$logMethodMap[$method]))
			{
				trigger_error("Call to undefined static method ".__CLASS__."::".$method.";   logMethodMap has no related key", E_USER_ERROR);
				return;
			}

			$logClass = __CLASS__;
			$logEntry = new $logClass(self::$logMethodMap[$method], ...$args);
			return \Novae\Event\Stream::emit($logEntry);
		}
	}
