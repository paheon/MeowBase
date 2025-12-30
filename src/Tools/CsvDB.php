<?php
/**
 * CsvDB Class
 * 
 * This class is used to manage CSV data and provide database-like features.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\File;

class CsvDB implements \Iterator {
	
	use ClassBase;
	const			REC_ROW_ID			= "csvRowID";
	const			REC_CREATE_DATE 	= "csvCreate";
	const			REC_UPDATE_DATE 	= "csvUpdate";
	
	// Data //
	protected		string 	$csvFile	= "";				// CSV data file name
	protected		array 	$header		= [];		    	// Field header
	protected		array 	$data		= [];	    		// Data List
	protected       string  $splitRegex = "";               // Regulator express for splitting line
	protected		string 	$seperator	= ",";				// Seperator character
	protected		string 	$enclosure	= "\"";				// Enclosure character
	protected		string 	$escape		= '\\';				// Escape character
	protected		string 	$terminator = "\n";				// Termination character
	protected		string 	$bom		= "";               // BOM Mark of file

	
	// Process queue //
	protected		array	$queue		= [];			// Process queue
	
	// Iterator //
	protected		int 	$currPtr	= 0;
	protected		int		$currKey	= 0;
	protected		array	$keyList    = [];
	
	// System fields //
	protected		array	$sysFields 	= [
								CsvDB::REC_ROW_ID		=> CsvDB::REC_ROW_ID, 		
								CsvDB::REC_CREATE_DATE	=> CsvDB::REC_CREATE_DATE, 	
								CsvDB::REC_UPDATE_DATE	=> CsvDB::REC_UPDATE_DATE, 	
							];

	// Constructor //
	public function __construct(string $csvFile, ?array $header = null) {
		
		$this->denyWrite = array_merge($this->denyWrite, [ 
			'header', 'data', 'splitRegex', 'seperator', 'enclosure', 'terminator', 'queue', 'currPtr', 'currKey', 'keyList', 'sysFields' 
		]);

		// handling non-ascii file name //
		$this->bom = chr(0xEF).chr(0xBB).chr(0xBF); // setlocale(LC_ALL, "en_US.UTF-8");

		$this->rebuildSplitRegex();			// Rebuild splite regular expression
		$this->clearQueue();				// Default queue
		$this->setCsvFile($csvFile);		// Set file name 
		$this->setHeader($header);			// Set header
		$this->createCSVFile();				// create csv if not exist
	}
	
    // Iterator functions //
    public function current():mixed {
		$ptr = current($this->keyList);
		if (!is_null($ptr) && isset($this->data[$ptr])) {
			return $this->data[$ptr];
		}
		return false;
    }
    public function key():mixed {
        return key($this->keyList);
    }

    public function next():void {
		next($this->keyList);
    }

    public function rewind():void {
		reset($this->keyList);		
    }

    public function valid():bool {
        return key($this->keyList) !== null;
    }
	
	// Getter //
	public function getRow(int $rowID = 0):array|false {
		// Use keyList to find data key if it maps rowID to data key
		return $this->data[$rowID] ?? false;
	}
	
	// Setter //
	public function setSeperator(string $seperator):void	{   $this->seperator  = $seperator;     $this->rebuildSplitRegex();		}
	public function setEnclosure(string $enclosure):void	{	$this->enclosure = $enclosure;      $this->rebuildSplitRegex();		}
	public function setEscape(string $escape):void			{	$this->escape = $escape;			$this->rebuildSplitRegex();     }
	public function setTerminator(string $terminator):void	{	$this->terminator = $terminator;	$this->rebuildSplitRegex();     }
	public function setCsvFile(string $fileName):void {
		$this->csvFile = $fileName;
		// Add .csv extension if not exist
		$fileParts = pathinfo($fileName);
		if (!isset($fileParts['extension'])) $this->csvFile .= ".csv";
	}

	// Set csv file header //
	public function setHeader(?array $header = null):void { 
		$newHeader = [];
		if (!is_null($header)) {
			foreach($header as $label) {			
				$newHeader[$label] = $label;
			}
		}	
		$this->header = array_merge($this->sysFields, $newHeader);
	}	
	
	// Set data to a row //
	public function setRow(array $rowRec, ?int $rowID = null):int {
		// Prepare data //
		$newRec = $this->fillupByHeader($rowRec);

		// Get rowID //
		$replaceRec = false;	// false: add new record, true: replace old record
		if (is_null($rowID)) {
			// Get rowID from new record //
			if (isset($rowRec[CsvDB::REC_ROW_ID]) && is_int($rowRec[CsvDB::REC_ROW_ID]) && $rowRec[CsvDB::REC_ROW_ID] >= 0) {
				$rowID = $newRec[CsvDB::REC_ROW_ID];
				$replaceRec = true;
			} 
		} else {
			if ($rowID >= 0) {
				$replaceRec = true;
			} else {
				$rowID = null;
			}
		}

		// Replace old record //
		if ($replaceRec) {
			//if (array_search($rowID, $this->keyList) !== false) {
			if (isset($this->data[$rowID])) {
				$this->data[$rowID] = array_replace($this->data[$rowID], $newRec);
			} else {
				$replaceRec = false;
			}
		} 

		// Add new record //
		if (!$replaceRec) {	
			if (!is_int($rowID)) {
				$rowID = (count($this->keyList) > 0) ? (int)max($this->keyList) + 1 : 0;
			}
			$newRec[CsvDB::REC_ROW_ID] = $rowID;
			if (!$newRec[CsvDB::REC_CREATE_DATE]) {
				$newRec[CsvDB::REC_CREATE_DATE] = date("Y-m-d H:i:s");
			}	
			if (!$newRec[CsvDB::REC_UPDATE_DATE]) {
				$newRec[CsvDB::REC_UPDATE_DATE] = date("Y-m-d H:i:s");
			}		
			$newRowNum = count($this->data);
			$this->keyList[$newRowNum] = $rowID;
			$this->data[$rowID] = $newRec;
		}
		return $rowID;
	}

	// Rebuild split regular expression //
	protected function rebuildSplitRegex():void {
		//$regex = '/(?:\s*,\s*|\n|^)("(?:(?:"")*[^"]*)*"|[^",\n]*|(?:\n|$))/m';
   		$this->splitRegex = '/(?:\s*'.$this->seperator.'\s*|'.$this->terminator.'|^)('.$this->enclosure.'(?:(?:'.$this->enclosure.$this->enclosure.')*[^'.$this->enclosure.']*)*'.$this->enclosure.'|[^'.$this->enclosure.$this->seperator.$this->terminator.']*|(?:'.$this->terminator.'|$))/m';
	}
	
	// Split a line into elements // 	
	protected function splitLine(string $csvLine, array $header = []):array {
		$elemList = [];
		$keyList = [];

		preg_match_all($this->splitRegex, $csvLine, $matches, PREG_SET_ORDER, 0);
		if (count($header) > 0) {
			$idx = 0;
			foreach($header as $field) {
				$keyList[$idx++] = $field;
			}
		}
		foreach($matches as $idx => $matchItem) {
			// Extract elements //
			$elem = $matchItem[1];
			if (substr($elem, 0, 1) === $this->enclosure) {
				$elem = substr($elem, 1, -1);
			}
			// Convert double quote //
			$elem = str_replace($this->enclosure.$this->enclosure, $this->enclosure, $elem);			
			
			// Add header //
			if (count($header) == 0) {
				$elemList[$idx] = $elem;				
			} else if ($idx < count($keyList)) {
				$elemList[$keyList[$idx]] = $elem;
			}
		}	
		return $elemList;
	}
	
	// Build a CSV line //
	protected function composeLine(array $elemList, bool $byHeader = true):string {
		$regex = '/['.$this->enclosure.$this->seperator.'\s\n\r]/';
		if ($byHeader) {
			$saveElemList = [];
			foreach($this->header as $key) {
				$saveElemList[$key] = (isset($elemList[$key])) ? $elemList[$key] : "";
			}
		} else {
			$saveElemList = $elemList;
		}		
		$out = "";
		foreach($saveElemList as $value) {
			// Add seperator //
			if ($out !== "") $out .= $this->seperator;
			// Convert elements' double quote //
  			$value = str_replace($this->enclosure, $this->enclosure.$this->enclosure, $value);
			
			// Add double quote if escape characters found //
  			if (preg_match($regex, $value, $matchs)) {
    			$out .= '"'.$value.'"';
			} else {
				$out .= $value;
			}
		}
		$out .= "\n";
		return $out;
	}

	// Lock file (non-blocking) with retries //
	protected function lockWithRetry($fp, int $maxRetries = 500):bool {
		$this->lastError = "";
		$retries = 0; 
		if ($maxRetries < 1) $maxRetries = 1;
		while (!flock($fp, LOCK_EX|LOCK_NB) && ++$retries < $maxRetries) {
            usleep(rand(50, 1000));
        } 
		$locked = ($retries != $maxRetries);
		if (!$locked) {
			$this->lastError = "Failed to lock file after $maxRetries retries";
			$this->throwException($this->lastError, 1);
		}
		return ($retries != $maxRetries);
	}

	// Fillup empty data field by header //	
	protected function fillupByHeader(array $data, ?array $header = null):array {
		$newData = [];
		foreach($header ?? $this->header as $label) {
			$newData[$label] = $data[$label] ?? "";
		}
		return $newData;
	}

	// Evaluate criteria with AND/OR support
	protected function evaluateCriteria(array $row, array $criteria): bool {

		// Handle AND/OR operators
		foreach ($criteria as $key => $value) {
			// Extract operator and comment from key
			$parts = explode('#', $key);
			$operator = trim($parts[0]);
			if ($operator === 'AND') {
				foreach ($value as $op => $condition) {
					$subCondition = [ $op =>$condition ];
					if (!$this->evaluateCriteria($row, $subCondition)) {
						return false;
					}
				}
				return true;
			}
			if ($operator === 'OR') {
				foreach ($value as $op => $condition) {
					$subCondition = [ $op => $condition ];
					if ($this->evaluateCriteria($row, $subCondition)) {
						return true;
					}
				}
				return false;
			}
		}

		// Handle regular conditions
		foreach($criteria as $field => $value) {
			// Extract operator from field name if present
			$operator = "=";
			$fieldName = $field;
			
			// Check for operator in field name (e.g., "age[>=]")
			if (preg_match('/^(.+)\[(.+)\]$/', $field, $matches)) {
				$fieldName = trim($matches[1]);
				$operator = trim($matches[2] ?? "=");
			}
			
			// Evaluate the condition
			if (!$this->evaluateOperator($row[$fieldName], $operator, $value)) {
				return false;
			}
		}
		return true;
	}

	// Evaluate Medoo-style operators
	protected function evaluateOperator(string $fieldValue, string $operator, mixed $compareValue): bool {
		switch ($operator) {
			case '=':
				if (is_null($compareValue)) {
					// IS //
					return $fieldValue === null;
				} else if (is_array($compareValue)) {
					// IN //
					return in_array($fieldValue, $compareValue);
				}
				return $fieldValue == $compareValue;
			case '!':
			case '!=':
				if (is_null($compareValue)) {
					// NOT IS //
					return $fieldValue !== null;
				} else if (is_array($compareValue)) {
					// NOT IN //
					return !in_array($fieldValue, $compareValue);
				}
				return $fieldValue != $compareValue;
			case '>':
				return $fieldValue > $compareValue;
			case '>=':
				return $fieldValue >= $compareValue;
			case '<':
				return $fieldValue < $compareValue;
			case '<=':
				return $fieldValue <= $compareValue;
			case '~':
				return strpos($fieldValue, $compareValue) !== false;
			case '!~':
				return strpos($fieldValue, $compareValue) === false;
			case '<>':
				if (is_array($compareValue) && count($compareValue) >= 2) {
					return $fieldValue >= $compareValue[0] && $fieldValue <= $compareValue[1];
				}
				return false;
			case '><':
				if (is_array($compareValue) && count($compareValue) >= 2) {
					return $fieldValue < $compareValue[0] || $fieldValue > $compareValue[1];
				}
				return false;
			default:
				return false;
		}
	}
	
	// Generate a empty record with header struture //
	public function genEmptyRec(mixed $value = ""):array {
		return array_fill_keys(array_keys($this->header), $value);
	}

	// Create a empty csv file (write header only) //
	public function createCSVFile(bool $overwrite = false):bool {
		$this->lastError = "";
		if ($overwrite || !file_exists($this->csvFile)) {
			$headerLine = $this->bom.$this->composeLine($this->header, false);
			if (file_put_contents($this->csvFile, $headerLine) === false) {
				$this->lastError = "Failed to create csv file: ".$this->csvFile;
				$exitCode = 4;
				$this->throwException($this->lastError, $exitCode);
				return false;
			}
			return true;
		}
		$this->lastError = "Overwrite protected or file already exists: ".$this->csvFile;
		$exitCode = 9;
		$this->throwException($this->lastError, $exitCode);
		return false;
	}

    // Append header label //
	public function appendHeader(string $label):void {
		$this->header[$label] = $label;
	}
    // Remove header label //
	public function removeHeader(string $label):void {
		if (isset($this->header[$label])) {
			unset($this->header[$label]);
		}
	}
	
	// Clear //
	public function clearRec():void {
		$this->data = [];
		$this->keyList = [];
		$this->rewind();
	}
	public function clearQueue():void {
		$this->queue = [ "add" => [], "del" => [], "update" => [] ];
	}
	
	// Queue process //
	public function queueAppend(array $rowRec):void {
		// Add create tiem stamp //
		$rowRec[CsvDB::REC_CREATE_DATE] = $rowRec[CsvDB::REC_CREATE_DATE] ?? date("Y-m-d H:i:s");
		// Add record to add queue //
		$this->queue["add"][] = $rowRec;
	}
	public function queueDelete(array $criteria):void {
		$this->queue["del"][] = $criteria;
	}
	public function queueUpdate(array $criteria, array $rowRec):void {
		// Add update time stamp //
		$rowRec[CsvDB::REC_UPDATE_DATE] = $rowRec[CsvDB::REC_UPDATE_DATE] ?? date("Y-m-d H:i:s");

		// Add record to update qutue //
		$this->queue["update"][] = array(
			"criteria"	=> $criteria,
			"rec"		=> $rowRec
		);
	}
	
	// Process queue //
	public function runQueue(bool $forceUseHeader = false):array {

		$this->lastError = "";

		// Read source file and write to temp file //
		$readFileName  = $this->csvFile;
		$file = new File();
		$writeFileName = $file->genTempFile($file->getFilePath($readFileName), "csvtmp_");
		$readFile = $writeFile = false;
		$result = [
			"add"	 => [],
			"update" => [],
			"del"	 => [],
		];
		$exitCode = 0; 

		// Create file if csv file not exist //
		if (!file_exists($readFileName)) {
			$this->save();
		}

		$lineCnt = 0;
		while ($writeFile = fopen($writeFileName, 'w')) {
				
			try {
				// Lock output file //
				if (!$this->lockWithRetry($writeFile)) {
					$this->lastError = "Failed to lock file";
					$exitCode = 1;
					break;	
				}
			} catch (\Exception $e) {
				$this->lastError = "Failed to lock file";
				$exitCode = 1;
				break;	
			}

			// Ensure point to the file beginning after lock //	
			if (!rewind($writeFile)) {
				$this->lastError = "Failed to rewind file";
				$exitCode = 2;
				break;	
			};		

			// Open read file // 
			$lineCnt = -1;
			$writeHeader = false;
			$defHeader = [];
			$data = [];
			$accLine = "";
			$rowID = 0;
			if ($readFile = fopen($readFileName, 'r')) {				
				while (!feof($readFile)) {			
					// Load a line //
					$line = fgets($readFile);
					// Skip empty line //
					if ($accLine == "" && strlen($line) == 0) continue;
					// check enclosure marks //
					$accLine .= $line;
					$enclCount = substr_count($accLine, $this->enclosure);
					if ($enclCount % 2) continue;			// Single enclosure find, load more line
					$line = rtrim($accLine, "\n\r");
					$accLine = "";
					
					// Check whether header is written //
					if ($lineCnt == -1) {
						// Read header //
						if ($forceUseHeader) {
							// Write class header to write file //
							$defHeader = $this->header;
							$line = $this->bom.$this->composeLine($this->header, false);
							if (fwrite($writeFile, $line) === false) {
								$this->lastError = "Failed to write header";
								$exitCode = 3;
								break;
							}
						} else {	
							// Load header from read file and update class header //
							$line = ltrim($line, $this->bom);
							$newHeader = $this->splitLine($line);
							$defHeader = $this->sysFields;	// Preload system fields
							foreach($newHeader as $label) {
								if ($label === "") {
									$this->lastError = "Invalid header";
									$exitCode = 4;
									break 2;			// break while loop and exit
								}
								$defHeader[$label] = $label;	// Add header to defHeader
							}
							// merget current header//
							$this->setHeader($defHeader);

							// Write header //
							$line = $this->bom . $this->composeLine($defHeader, false);
							if (fwrite($writeFile, $line) === false) {
								$this->lastError = "Failed to write header";
								$exitCode = 3;
								break;
							}
						}	

						// Clear data //
						$this->clearRec();
						$writeHeader = true;

					} else {
					
						// Read data line //
						$data = $this->splitLine($line, $defHeader);

						// Process system fields, rowID, create date, and update date //
						foreach($this->sysFields as $field) {
							if (isset($data[$field])) {
								// Update rowID only for existing system fields //
								if ($field == CsvDB::REC_ROW_ID) {
									// Check where integer value //
									$rowID = $data[CsvDB::REC_ROW_ID] = (int)$data[CsvDB::REC_ROW_ID];
								}	
							} else {
								if ($field == CsvDB::REC_ROW_ID ) {
									$this->lastError = "Missing row ID";
									$exitCode = 8;
									break 2; // Break outer loop
								} else {
									$data[$field] = "";
								}
							}
						}				

						// Delete row //
						// Delete queue: ['del' => [ 
						// 					'criteria' => [ field0 => $value0, ... ], 
						// 					'criteria' => [ field1 => $value1, ... ], 
						// 					... ] ]
						foreach($this->queue["del"] as $idx => $criteria) {
							if ($this->evaluateCriteria($data, $criteria)) {
								$result["del"][$idx][$lineCnt] = $data[CsvDB::REC_ROW_ID];
								$data = false;						// Clear data prevent update
								break;
							}
						}
						
						// Update row //
						// Update queue: ['update' => [ 
						//                 updateList0 => [ 'criteria' => [ field0 => $value0, ... ], 'rec' => [ field0 => $value0, field1 => $value1, ... ], ... ], 
						//                 updateList1 => [ 'criteria' => [ field1 => $value1, ... ], 'rec' => [ field0 => $value0, field1 => $value1, ... ], ... ], 
						//                 ... ] ]
						if (is_array($data)) {		// Skip if no data found
							foreach($this->queue["update"] as $idx => $updateList) {
								if ($this->evaluateCriteria($data, $updateList["criteria"])) {
									$result["update"][$idx][$lineCnt] = (int)$data[CsvDB::REC_ROW_ID];
									$data[CsvDB::REC_UPDATE_DATE] = date("Y-m-d H:i:s");
									foreach($updateList["rec"] as $field => $value) {
										if (in_array($field, $this->sysFields)) continue;	// Cannot change the system fields
										$data[$field] = $value;
									}
								}	
							}
							
							// Use read record to fill up empty field //
							$data = $this->fillupByHeader($data, $forceUseHeader ? $this->header : $defHeader);
							$newRowID = $this->setRow($data, $rowID);

							// Write to file //
							if (isset($this->data[$newRowID])) {
								$newRow = $this->data[$newRowID];
								$line = $this->composeLine($newRow);
								if (fwrite($writeFile, $line) === false) {
									$this->lastError = "Failed to write data";
									$exitCode = 4;
									break;
								}
							} else {
								$this->lastError = "Data not found with rowID=$newRowID";								
								$exitCode = 4;
								break;
							}
						}
					}
					$lineCnt++;
				}
				
				// Close read file //
				fclose($readFile);
				
				if ($exitCode == 0) {

					// Write header if not write before (mainly for write data to empty csv file)//
					if (!$writeHeader) {
						$line = $this->bom . $this->composeLine($this->header, false);
						if (fwrite($writeFile, $line) === false) {
							$this->lastError = "Failed to write header";
							$exitCode = 3;
							break;
						}	
					}
					// Add new records //
					// Add queue: ['add' => [ 
					// 					recList0 => [ field0 => $value0, ... ], 
					// 					recList1 => [ field1 => $value1, ... ], 
					// 					... ] ]
					foreach($this->queue["add"] as $idx => $addRec) {

						// Setup a record with header structure //
						$data = $this->fillupByHeader($addRec, $forceUseHeader ? $this->header : $defHeader);
						$rowID = $this->setRow($data);
						// Write to file using the processed data from setRow //
						$processedData = $this->getRow($rowID);
						if ($processedData === false) {
							$this->lastError = "Cannot obtain record from rowID=$rowID";
							$exitCode = 5;
							break;
						}
						$line = $this->composeLine($processedData);
						if (fwrite($writeFile, $line) === false) {
							$this->lastError = "Failed to write data";
							$exitCode = 5;
							break;
						}
						$result["add"][$idx][$lineCnt] = $rowID;
						$lineCnt++;
					}
				}
			}

			// Close write file //
			fflush($writeFile);           			// flush output before releasing the lock
			flock($writeFile, LOCK_UN); 			// Release lock
			fclose($writeFile);						// Close file but not release the lock

			if ($exitCode == 0) {
				$retries = 0; 
				$maxRetries = 50;
				// Overwrite the csv file //
				while (!copy($writeFileName, $this->csvFile) && ++$retries < $maxRetries) {
					usleep(rand(50, 1000));
				} 
				if ($retries >= $maxRetries) {
					// Create fail file //
					$serial = 0;
					$serialLimit = 100;
					$failFile = $this->csvFile.".fail";
					while(file_exists($failFile) && $serial < $serialLimit) {
						$failFile = $this->csvFile.".fail.".$serial++;
					}
					// Reset fail file name if all serial are used //
					if ($serial >= $serialLimit) $failFile = $this->csvFile.".fail";
					// Remove old fail file //
					if (file_exists($failFile)) unlink($failFile);					
					// Rename temp file to fail file //
					if (!rename($writeFileName, $failFile)) {
						$this->lastError = "Failed to rename temp file '$writeFileName' to fail file '$failFile'";
						$exitCode = 5;
					}
					$this->lastError = "Failed to copy file from '$writeFileName' to '$this->csvFile'. Fail file is '$failFile' is created";
					$exitCode = 6;
				} 
			}	
			break;
		}			
		// Remove temp file //
		if (file_exists($writeFileName)) @unlink($writeFileName);

		// Set result //
		$result["exitCode"]	 = $exitCode;
		$result["lineCount"] = $lineCnt;

		// Throw exception if error occured //
		if ($exitCode != 0) {
			$this->throwException($this->lastError, $exitCode);
		}
		return $result;
	}

	// Read all data from csv file //
	public function load(?string $fileName = null):int {		
		$this->lastError = "";

		// Set file name //
		if (!is_null($fileName)) $this->setCsvFile($fileName);
		
		// Read csv file //
		$this->clearRec();
		$header = [];
		$accLine = "";
		$exitCode = 0;
		$rowID = 0;
		$lineCnt = -1;
		if ($csvFile = @fopen($this->csvFile, "r")) {
			while(!feof($csvFile)) {
				// Load a line //
				$line = fgets($csvFile);
				// Skip empty line //
				if ($accLine == "" && strlen($line) == 0) continue;
				// check enclosure marks //
				$accLine .= $line;
				$enclCount = substr_count($accLine, $this->enclosure);
				if ($enclCount % 2) continue;			// Single enclosure find, load more line
				$line = rtrim($accLine, "\n\r");
				$accLine = "";

				// Check whether header is written //
				if ($lineCnt == -1) {
					// Read header //
					$line = ltrim($line, $this->bom);		// Remove BOM
					$newHeader = $this->splitLine($line);
					if (count($newHeader) == 0) {
						$this->lastError = "Empty header";
						$exitCode = 4;
						$this->throwException($this->lastError, $exitCode);
						break;		// break to external loop
					}
					foreach($newHeader as $label) {
						if (trim($label) == "") {
							$this->lastError = "Invalid header";
							$exitCode = 4;
							$this->throwException($this->lastError, $exitCode);
							break 2;		// break to external loop
						}
						$header[] = $label;
					} 

					$this->setHeader($header);
					$this->data = [];
					$this->keyList = [];
				} else {
					// Read data line //
					$data = $this->splitLine($line, $header);
					if (count($data) == 0) {
						$this->lastError = "Invalid data";
						$exitCode = 3;
						$this->throwException($this->lastError, $exitCode);
						break;		// break to external loop
					}

					// Non-system class generated CSV file (missed sysFields) //
					foreach($this->sysFields as $field) {	
						if (isset($data[$field])) {
							if ($field == CsvDB::REC_ROW_ID) {
								$rowID = (int)$data[CsvDB::REC_ROW_ID];
							}
						} else {
							if ($field == CsvDB::REC_ROW_ID ) {
								$rowID = $data[CsvDB::REC_ROW_ID] = $lineCnt;
							} else {
								$data[$field] = "";
							}
						}
					}

					// Add to data //
					$this->setRow($data, $rowID);
				}
				$lineCnt++;
			}
		} else {
			$this->lastError = "Failed to open file '$this->csvFile'";
			$exitCode = 1;
			$this->throwException($this->lastError, $exitCode);
		}
		if (is_resource($csvFile)) {
			fclose($csvFile);
		}
		return $exitCode;
	}

	// Write all data to csv file //
	//
	// Return : 0 - no error (>0 error occured)
	public function save(?string $fileName = null):int {
		$this->lastError = "";

		// Set file name //
		if (!is_null($fileName)) {
			$this->setCsvFile($fileName);
		}	
		$exitCode = 0;
		$tmpFileName = $this->csvFile.".tmp";
		$tmpFile = fopen($tmpFileName, 'wb');
		while ($tmpFile) {
			// Lock output file //
			try {
				// Lock output file //
				if (!$this->lockWithRetry($tmpFile)) {
					$this->lastError = "Failed to lock file '$tmpFileName'";
					$exitCode = 1;
					break;	
				}
			} catch (\Exception $e) {
				$this->lastError = "Failed to lock file: ".$e->getMessage();
				$exitCode = 1;
				break;	
			}

			// Ensure point to the file beginning after lock //
			if (!rewind($tmpFile)) {
				$this->lastError = "Failed to rewind file";
				$exitCode = 2;
				break;	
			};		
			// Write header //
			$line = $this->bom.$this->composeLine($this->header, false);
			if (fwrite($tmpFile, $line) === false) {
				$this->lastError = "Failed to write header";
				$exitCode = 3;
				break;
			}	
				
			// Write data //
			foreach($this->keyList as $rowID) {
				$fieldList = $this->data[$rowID];
				$line = $this->composeLine($fieldList);
				if (fwrite($tmpFile, $line) === false) {
					$this->lastError = "Failed to write data";
					$exitCode = 4;
					break;
				}
			}
			break;
		}
		if ($tmpFile !== false) {
			fflush($tmpFile);           			// flush output before releasing the lock
    		flock($tmpFile, LOCK_UN); 				// Release lock
			fclose($tmpFile);						// Close file but not release the lock
			if (!copy($tmpFileName, $this->csvFile)) {
				$this->lastError = "Failed to copy file from '$tmpFileName' to '$this->csvFile'";
				$exitCode = 6;
			}
			if (!unlink($tmpFileName)) {
				$this->lastError = "Failed to remove temp file '$tmpFileName'";
				$exitCode = 5;
			}
		}	
		if ($exitCode != 0) {
			$this->throwException($this->lastError, $exitCode);
		}
		return $exitCode;
	}
	

	// Search by field matching //
	public function search(array $criteria = [], ?string $field = null, bool $asc = true):array|false {
		$result = [];
		foreach($this->data as $rowID => $row) {
			if ($this->evaluateCriteria($row, $criteria)) {
				$result[] = $row;
			}
		}
		if (!is_null($field)) {
			$field = trim($field);
			if (!in_array($field, $this->header)) {
				$this->lastError = "Field '$field' not found in header";
				$this->throwException($this->lastError, 9);
			}
	
			if ($asc) {
				usort($result, function($a, $b) use ($field) {
					return $a[$field] <=> $b[$field];
				});
			} else {
				usort($result, function($a, $b) use ($field) {
					return $b[$field] <=> $a[$field];
				});
			}
		}

		return $result;
	}

	// Get minimum value of a field //
	public function getMin(string $field):mixed {
		$field = trim($field);
		if (!in_array($field, $this->header)) {
			$this->lastError = "Field '$field' not found in header";
			$this->throwException($this->lastError, 9);
			return null;
		}

		$min = null;
		foreach($this->data as $row) {
			$value = $row[$field];
			if ($min === null) {
				$min = $value;
			} else {
				if ($value < $min) {
					$min = $value;
				}
			}
		}
		return $min;
	}

	// Get maximum value of a field //
	public function getMax(string $field):mixed {
		$field = trim($field);
		if (!in_array($field, $this->header)) {
			$this->lastError = "Field '$field' not found in header";
			$this->throwException($this->lastError, 9);
			return null;
		}

		$max = null;
		foreach($this->data as $row) {
			$value = $row[$field];
			if ($max === null) {
				$max = $value;
			} else {
				if ($value > $max) {
					$max = $value;
				}
			}
		}
		return $max;
	}

	// Search by custom function //
	public function customSearch(callable $function):array|false {
		$result = [];
		foreach($this->data as $row) {
			if ($function($row)) {
				$result[] = $row;
			}
		}
		return $result;
	}	

	// Sort data by rowID //
	public function sortByRowID(bool $asc = true):void {
		if ($asc) {
			sort($this->keyList, SORT_NUMERIC);			
		} else {
			rsort($this->keyList, SORT_NUMERIC);
		}
		$this->rewind();
	}
}
