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

		----THEORY----
		Other ORM frameworks use actual properties for their object/entity data.

		The one advantage of this that I agree with, is that code completion will be able to assist with that
		property via the code editor being able to read the class.

		The downsides of this that cause me to be against that, are:
			- Forces use of reflections for highly dynamic classes (for example, a parent
				class that needs to know about its sibling class)
			- Information can't be translated from an object of one type to another and back
				with-out loss, for example a Person and User, where User is derived from Person,
				and adds username/password, but Person doesn't contain these properties --
				if we need Person and a User is serialized in to a Person due to being transmitted
				or stored via JSON to a place expecting a Person, then User's properties won't
				necessarily be stored or properly reloaded.
			- PHP typehinting isn't (and probably will never be) as capable as HTML pattern input
				checking; it does not support formatting, or the C equivalent of unions, or validation
				of nested arrays, therefore indicating that a property must be an int, string, or otherwise,
				isn't entirely useful when dealing with translating remote data (including user input).
				For example, a phone number should be able to be entered in to an API that is expecting
				a phone number in various formats, but then standardized when stored locally in to the
				format most useful to us.    There is no PHP type for Phone Number that allows us to
				type check that the property is being set to something that represents a valid phone number.

			- Prefering for properties to by dynamically typed allows dynamically adding getters/setters
				later with-out refactoring.  For example, Order->tax may inially be a property
				with a value set when Order->subtotal is set (rather than dynamically calculated
				via a getTax) - but then a reason comes up to use a getter
				With this method, function getTax will be written, and Order->tax is still accesible,
				whereas in the property paradigm, all code has to be re-written to access tax via getTax;
				many developers resolve this by ALWAYS using getters and setters, even for properties that
				aught not to need them, so that people know to just expect to call ->getTax() rather than
				accessing ->tax -- this results in additional coding
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

		public function getDataArray()
		{
			$data = $this->data;

			if (!isset($data["__class"]))
				$data["__class"] = get_called_class();

			return $data;
		}

	}
