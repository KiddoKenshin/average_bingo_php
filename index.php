<?php

// Bingo Logic -PHP-
$verbose = FALSE;
function outputEcho($message, $force = FALSE) {
	global $verbose;
	
	if ($verbose || $force) {
		echo $message . '<br />';
		
		ob_flush();
		flush();
	}
}

$freeSpace = TRUE;
$sheetSize = 5; // 5 x 5, Odd numbers, 3 onwards
$maxBingoInt = 100; // 1 ~ max
$contestants = 1000; // How many players
$maxBingoHit = 500;
$redrawCount = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$freeSpace = ($_POST['freespace'] == 'true' ? TRUE : FALSE);
	$sheetSize = intval($_POST['sheetsize']);
	$maxBingoInt = intval($_POST['maxbingoint']);
	$contestants = intval($_POST['contestants']);
	$maxBingoHit = intval($_POST['maxbingohit']);
	$redrawCount = intval($_POST['redrawcount']);
}

$bingoSheets = array();
$chosenNumbers = array();
$bingoedCount = 0;

$totalDrawResults = array();

if ($maxBingoHit < 1 || $maxBingoHit > $contestants) {
	outputEcho('Hit count not properly set!');
	exit;
}

if ($sheetSize % 2 == 0) {
	outputEcho('Sheet size is not an odd value!');
	exit;
}

if ($maxBingoInt < pow($sheetSize, 2)) {
	outputEcho('Max Bingo Value is smaller than sheet!');
	exit;
}

if ($maxBingoHit > $contestants) {
	outputEcho('Max Bingo Count is larger than total players!');
	exit;
}

function getRandom($max) {
	// 0 ~ (max - 1)
	return rand(0, $max - 1);
}

function createBingoSheet() {
	global $sheetSize;
	global $freeSpace;
	global $maxBingoInt;
	
	$sheetArray = array();
	$currentNum = -1;
	
	$useSheetSize = pow($sheetSize, 2);
	
	for ($i = 0; $i < $useSheetSize; $i++) {
		
		if ($i == (($useSheetSize - 1) / 2) && $freeSpace) {
			$currentNum = 0;
		} else {
			$currentNum = getRandom($maxBingoInt) + 1;
			while(count($sheetArray) != 0 && in_array($currentNum, $sheetArray)) {
				$currentNum = getRandom($maxBingoInt) + 1;
			};
		}
		
		$sheetArray[] = $currentNum;
	}
	
	return $sheetArray;
}

function checkBingo($sheetArray) {
	global $sheetSize;
	global $chosenNumbers;
	
	$isBingo = FALSE;
	
	for ($i = 0; $i < $sheetSize; $i++) {
		
		$hit = 0;
		$offset = $i * $sheetSize;
		// Pattern Rows
		for ($j = 0; $j < $sheetSize; $j++) {
			if (in_array($sheetArray[$offset + $j], $chosenNumbers)) {
				$hit++;
			}
		}
		
		if ($hit == $sheetSize) {
			$isBingo = TRUE;
			outputEcho('Hit Rows: ' . $i);
			break;
		}
		
		$hit = 0;
		// Pattern Columns
		for ($j = 0; $j < $sheetSize; $j++) {
			if (in_array($sheetArray[$i + ($j * $sheetSize)], $chosenNumbers)) {
				$hit++;
			}
		}
		
		if ($hit == $sheetSize) {
			$isBingo = TRUE;
			outputEcho('Hit Columns: ' . $i);
			break;
		}
	}
	
	// Pattern Cross 1
	if (!$isBingo) {
		$cHit = 0;
		for ($i = 0; $i < $sheetSize; $i++) {
			if (in_array($sheetArray[$i * ($sheetSize + 1)], $chosenNumbers)) {
				$cHit++;
			}
		}
		
		if ($cHit == $sheetSize) {
			$isBingo = TRUE;
			outputEcho('Hit Cross1');
		}
	}
	
	// Pattern Cross 2
	if (!$isBingo) {
		$cHit = 0;
		for ($i = 0; $i < $sheetSize; $i++) {
			if (in_array($sheetArray[($sheetSize - 1) * $i], $chosenNumbers)) {
				$cHit++;
			}
		}
		
		if ($cHit == $sheetSize) {
			$isBingo = TRUE;
			outputEcho('Hit Cross2');
		}
	}
	
	return $isBingo;
}

function performDraw() {
	global $maxBingoInt;
	global $chosenNumbers;
	global $sheetSize;
	global $bingoSheets;
	
	global $bingoedCount;
	global $maxBingoHit;
	global $freeSpace;
	global $totalDrawResults;
	
	$randNum = getRandom($maxBingoInt) + 1;
	while(in_array($randNum, $chosenNumbers)) {
		$randNum = getRandom($maxBingoInt) + 1;
	};
	
	$chosenNumbers[] = $randNum;
	
	if (count($chosenNumbers) >= $sheetSize) {
		
		$tempIdStore = array();
		for ($i = 0; $i < count($bingoSheets); $i++) {
			$res = checkBingo($bingoSheets[$i]);
			if ($res) {
				$tempIdStore[] = $i;
			}
		}
		
		if (count($tempIdStore) > 0) {
			$bingoedCount += count($tempIdStore);
			$tempIdStore =  array_reverse($tempIdStore);
			for ($j = 0; $j < count($tempIdStore); $j++) {
				array_splice($bingoSheets, $tempIdStore[$j], 1);
			}
		}
		
	}
	
	if (count($bingoSheets) > 0 && $bingoedCount < $maxBingoHit) {
		performDraw();
	} else {
		$totalDrawResults[] = (count($chosenNumbers) - ($freeSpace ? 1 : 0));
		outputEcho('Completed');
	}
}

function start() {
	global $redrawCount;
	global $bingoedCount;
	global $bingoSheets;
	global $chosenNumbers;
	global $freeSpace;
	
	global $totalDrawResults;
	global $maxBingoHit;
	global $contestants;
	
	
	for ($i = 0; $i < $redrawCount; $i++) {
		
		outputEcho('Current run: ' . $i, TRUE);
		
		// Init
		$bingoedCount = 0;
		$bingoSheets = array();
		$chosenNumbers = array();
		if ($freeSpace) {
			$chosenNumbers[] = 0;
		}
		
		// Populate Sheets
		for ($x = 0; $x < $contestants; $x++) {
			$bingoSheets[] = createBingoSheet();
		}
		
		performDraw();
	}
	
	$average = 0;
	for ($z = 0; $z < count($totalDrawResults); $z++) {
		$average += $totalDrawResults[$z];
	}
	$average /= count($totalDrawResults);
	
	outputEcho('Average draws for ' . $maxBingoHit . ' Bingo out of ' . $contestants . ' Players: ' . $average . ' draws', TRUE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$result = set_time_limit(0);
	outputEcho('-POST METHOD-', TRUE);
	if (!$result) {
		outputEcho('Warning! Unable to unset Timeout!', TRUE);
	}
	
	
	start();
} else { ?>

<html>
<body>
	Average Bingo calculator<br />
	<br />
	-GET METHOD-<br />
	<br />
	INPUT YOUR DESIRED CALCULATION:<br />
	<form method="post">
		<table cellpadding="0" cellspacing="5">
			<tr>
				<td>Use FreeSpace:</td>
				<td>
					<select name="freespace">
						<option value="true" selected>YES</option>
						<option value="false">NO</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td>Sheet Size:</td>
				<td>
					<input name="sheetsize" value="5" /><br />
					(Odd numbers, larger than 3)
				</td>
			</tr>
			<tr valign="top">
				<td>Max Bingo Value:</td>
				<td>
					<input name="maxbingoint" value="100" /><br />
					(The max value appear on sheet, must larger than "Sheet Size x Sheet Size")
				</td>
			</tr>
			<tr>
				<td>Total Players:</td>
				<td>
					<input name="contestants" value="1000" />
				</td>
			</tr>
			<tr valign="top">
				<td>Max Bingo Count:</td>
				<td>
					<input name="maxbingohit" value="500" /><br />
					(Max Bingo allowed, must be same or lower than Total Players)
				</td>
			</tr>
			<tr valign="top">
				<td>Total runs:</td>
				<td>
					<input name="redrawcount" value="10" />
				</td>
			</tr>
		</table>
		
		<input type="submit" value="GO!" />
	</form>
</body>
</html>


<?php }

