<?php
	require_once("autoload.php");
	\Novae\Common\AutoLoadPSR4::register(["Novae"=> __DIR__."/core", "PSR" => __DIR__."/PSR", ]);


	// temporily add a default log handler that echos everything
	new \Novae\Log\Handler();
