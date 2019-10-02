<?php
	namespace novae\core\TestHelloWorld;

	class SaySomething implements \novae\common\interfaces\SaySomething {

		static public function now()
		{
			echo "Hello World!!\n";

		}

	};
