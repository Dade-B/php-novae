<?php
	namespace Novae\Core\TestHelloWorld;

	class SaySomething implements \Novae\Common\Interfaces\SaySomething {

		static public function now()
		{
			echo "Hello World!!\n";

		}

	};
