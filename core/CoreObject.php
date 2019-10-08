<?php
	namespace Novae;

	/*
		The base class for all of our later classes,

		getter / setter support:
			Create function getFoo for retrieving $obj->foo,
			Create setFoo for setting $obj->foo (or preventing it)

		change handler: --- ToDo: revisit naming
			Create function __updated_foo($to, $from), and it will be called
			when $obj->foo is upated from $from to $to;   This is not an event, because it
			is intended to be running at a lower level, rather than having events emitted
			for every property change, to a potentially empty set of listeners

		By default any property can be written or read via __get and __set (being
			stored in to CoreObject->$data

		Restricting / freezing properties will be in the later class that will implement
		other validation and parsing needs.

		The getter/setter causes case sensitivity, but we're assuming lowerCamelCase for properties.....
	*/

	class CoreObject {
		protected $data = [];

		private $isCnstructing = FALSE; // block changed handlers when initializing properties from the constructor

		// basic __construct allows us to pass in array data.
		public function __construct( $data = [] )
		{
			$this->isConstructing = TRUE;

			if (is_array($data))
			{
				foreach($data as $key => $value) // ensure getters/setters will be used, rather than setting directly in to data
					$this->{$key} = $value;
			}

			$this->isConstructing = FALSE;
		}

		// this static cache needs to store information per-class and per-property; even though $this
		// is instanced, PHP will cause -this- static property (whether declared in the function or in the
		// class itself) to shared across all derived classes ( i.e.   __get in 'A' and 'B' where 'A' and 'B' both
		// extend this class, will have the same __property_setting_cache)
		private function &getStaticCachePointer($called_class, $key = FALSE)
		{
			static $__property_setting_cache = [];
			$cache = &$__property_setting_cache[$called_class];
			if ($key !== FALSE)
				$cache = &$cache[$key];
			if (!is_array($cache))
				$cache = [];

			return $cache;
		}

		public function __get( $key )
		{
			$cache = &$this->getStaticCachePointer(get_called_class(), $key);

			$func = "get".ucfirst($key);

			if (!isset($cache["has-getter"]))
				$cache["has-getter"] = method_exists($this, $func);

			if ($cache["has-getter"])
				return $this->{$func}();

			return $this->data[$key];
		}

		public function __set( $key, $value )
		{
			$called_class = get_called_class();
			$cache = &$this->getStaticCachePointer($called_class, $key);

			$setFunc = "set".ucfirst($key);

			if (!isset($cache["has-setter"]))
				$cache["has-setter"] = method_exists($this, $setFunc);

			if (!isset($cache["has-updated-callback"]))
				$cache["has-updated-callback"] = method_exists($this, "__updated_".$key);

			if ($cache["has-setter"])
			{
				if ($cache["has-updated-callback"] && !$this->isConstructing)
					$oldValue = $this->{$key};
				$this->{$setFunc}( $value );
				if ($cache["has-updated-callback"] && !$this->isConstructing)
				{
					$newValue = $this->$key; // not using $value in case the setter changed it
					$this->{"__updated_".$key}( $newValue, $oldValue );
				}
				return;
			}

			return $this->data[$key] = $value;
		}

	}
