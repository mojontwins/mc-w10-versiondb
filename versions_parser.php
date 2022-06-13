<?php

	// Get architecture
	if (count ($argv) < 2 || ($argv [1] != 'x64' && $argv [1] != 'x86' && $argv [1] != 'arm')) {
		usage (); 
		exit -1;
	}

	$architecture = $argv [1];

	// Read & parse file

	$entries = file (
		'https://raw.githubusercontent.com/MCMrARM/mc-w10-versiondb/master/versions.txt',
		FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
	);

	// Converted entries is indexed by version

	$converted = array ();

	// 0 release | 1 beta | 2 preview

	$mode = 0;

	// Parse each line
	foreach ($entries as $entry) {
		// first tokenize by space:
		// HASH FILE NUMBER

		$entry_a = explode (' ', $entry);

		if (count ($entry_a) > 1) {
			$hash = $entry_a [0];
			$file = $entry_a [1];

			// Next tokenize by underscore _
			// PACKAGE VERSION ARCHITECTURE

			$file_a = explode ('_', $file);

			// Process only if architecture fits
			if ($architecture == $file_a [2]) {

				// Version must be somewhat parsed
				$version = $file_a [1];
				$version_a = explode ('.', $version);

				$version_corrected = '';
			
				if ($version_a [0] == '0') {
					if (intval ($version_a [1]) < 1510) {
						$version_corrected = 
							$version_a [0] . '.' .
							substr ($version_a [1], 0, 2) . '.' .
							substr ($version_a [1], 2) . '.0';
					} else {
						$version_corrected = 
							$version_a [0] . '.' .
							substr ($version_a [1], 0, 2) . '.' .
							substr ($version_a [1], 3) . '.' .
							$version_a [2];
					}
				} else {
					if ($version_a [1] == '0') {
						$version_corrected = '1.0.0.16';
					} else {
						// Two different ways to calculate depending on $version_a [1]:
						if (intval ($version_a [1] <= 16)) {

							// In this case, I have to expand the element at [2]
							// XX0YYY, problem is that 0 is a rather weak separator in 
							// this case.

							// Easiest, if strlen is 1 or 2, it si 0.N
							if (strlen ($version_a [2]) <= 2) {
								$version_corrected = 
									$version_a [0] . '.' .
									$version_a [1] . '.0.'. 
									$version_a [2];
							} else {						

								// XX seems to be whether 1 or 2 digits long
								// 1 digit long is 
								// a) strlen is 3
								// b) strlen is 4, pos (1) == 0 and pos 2 != 0
								if (strlen ($version_a [2]) == 3) {
									$version_corrected = 
										$version_a [0] . '.' .
										$version_a [1] . '.' . 
										substr ($version_a [2], 0, 1) . '.' .
										substr ($version_a [2], 2);
								} else if (strlen ($version_a [2]) == 4) {
									if ($version_a [2][2] != '0') {
										$version_corrected = 
											$version_a [0] . '.' .
											$version_a [1] . '.' . 
											substr ($version_a [2], 0, 1) . '.' .
											substr ($version_a [2], 2);
									} else {
										$version_corrected = 
											$version_a [0] . '.' .
											$version_a [1] . '.' . 
											substr ($version_a [2], 0, 2) . '.' .
											substr ($version_a [2], 3);
									}
								} else {
									$version_corrected = 
										$version_a [0] . '.' .
										$version_a [1] . '.' . 
										substr ($version_a [2], 0, 2) . '.' .
										substr ($version_a [2], 3);
								}
							}
						} else {
							$subsubver = str_pad ($version_a [2], 4, '0', STR_PAD_LEFT);
							$version_corrected = 
								$version_a [0] . '.' .
								$version_a [1] . '.' . 
								intval (substr ($version_a [2], 0, 2)) . '.' .
								intval (substr ($version_a [2], 2));
						}
					}
				} 

				// If already stored, don't process!
				if (!isset ($converted [$version_corrected])) {
					$output = '[' . '"' . $version_corrected . '","' . $hash . '",' . $mode . ']';
					$converted [$version_corrected] = $output;
				}
			}
		} else {
			// Change mode
			switch ($entry) {
				case 'Releases': 	$mode = 0; break;
				case 'Beta': 		$mode = 1; break;
				case 'Preview': 	$mode = 2; break;
			}
		}


	}

	// Output
	echo "[";
	echo implode(',', $converted);
	echo "]\n";


	function usage () {
		echo "php parseversion.php x64|x86|arm";
	}
  