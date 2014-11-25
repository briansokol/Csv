<?php

namespace briansokol\Csv;

use briansokol\Csv\File\Row;
use briansokol\Csv\File\Header;
use briansokol\Csv\Exception\DataInputException;
use briansokol\Csv\Exception\DataOutputException;
use briansokol\Csv\Exception\InputTypeException;
use briansokol\Csv\Exception\RowCountMismatchException;
use briansokol\Csv\Exception\RowCountMismatchInterruptingException;
use briansokol\Csv\Exception\RowDoesNotExistException;

/**
 * This class represents a CSV file.
 *
 * @package briansokol\Csv
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class File implements \Iterator, \Countable {

	/**
	 * @var int
	 */
	protected $position;
	/**
	 * @var Header
	 */
	protected $headers;
	/**
	 * @var array
	 */
	protected $data;


	/**
	 * Creates a new CSV file object.
	 */
	function __construct() {
		$this->position = 0;
		$this->data = array();
		$this->headers = null;
		return $this;
	}

	/**
	 * Based on an initial data source, Row and Header objects are added to the File.
	 *
	 * @param string|array $initialData Either a string designating a file to import or an array of associative arrays.
	 * @param bool $headers If true, the first row of the file will be used as the headers.
	 * @param string $delimiter Delimiter between columns.
	 * @param bool $removeDupHeaders If true, it will ignore additional lines that are identical to the header.
	 * @param int $skipRows The number of rows from the top to skip before data is imported.
	 * @param bool $stopOnError Determines whether the import will stop if a row cannot be imported.
	 *
	 * @throws RowCountMismatchException after completion if the count of columns in one or more rows do not match the count of columns in the header.
	 * @throws RowCountMismatchInterruptingException and cancels data import if the count of columns in a row does not match the count of columns in the header.
	 * @throws DataInputException if the given initial data file does not exist of cannot be read.
	 */
	function setData($initialData, $headers = true, $delimiter = ",", $removeDupHeaders = true, $skipRows = 0, $stopOnError = false) {
		if (is_array($initialData)) {
			$this->position = 0;
			$this->data = array();
			$this->headers = null;
			$errorLines = array();
			$initialData = array_values($initialData);
			foreach ($initialData as $i => $row) {
				if ($i < $skipRows) {
					continue;
				}
				if ($headers) {
					if(empty($this->headers)) {
						$this->headers = new Header($row);
					} else {
						if (!empty($this->headers) && count($this->headers) !== count($row)) {
							$errorLines[] = $i+1;
							if ($stopOnError) {
								$this->position = 0;
								$this->data = array();
								$this->headers = null;
								throw new RowCountMismatchInterruptingException(
									"Row/header column count mismatch [" . implode(",", $errorLines) . "]"
								);
							} else {
								while (count($row) > count($this->headers)) {
									array_pop($row);
								}
								while (count($row) < count($this->headers)) {
									$row[] = "";
								}
							}
						}
						if (($removeDupHeaders && $row !== $this->headers->toArray()) || !$removeDupHeaders) {
							$this->data[] = new Row($row, $this->headers);
						}
					}
				} else {
					$this->data[] = new Row($row);
				}
			}
		} else {
			if (($handle = fopen($initialData, 'r')) !== FALSE) {
				$this->position = 0;
				$this->data = array();
				$this->headers = null;
				$i = 0;
				while (($row = fgetcsv($handle, 2048, $delimiter)) !== FALSE) {
					if ($i < $skipRows) {
						$i++;
						continue;
					}
					if ($headers) {
						if(empty($this->headers)) {
							$this->headers = new Header($row);
						} else {
							if (!empty($this->headers) && count($this->headers) !== count($row)) {
								$errorLines[] = $i+1;
								if ($stopOnError) {
									$this->position = 0;
									$this->data = array();
									$this->headers = null;
									throw new RowCountMismatchInterruptingException(
										"Row/header column count mismatch [" . implode(",", $errorLines) . "]"
									);
								} else {
									while (count($row) > count($this->headers)) {
										array_pop($row);
									}
									while (count($row) < count($this->headers)) {
										$row[] = "";
									}
								}
							}
							if (($removeDupHeaders && $row !== $this->headers->toArray()) || !$removeDupHeaders) {
								$this->data[] = new Row($row, $this->headers);
							}
						}
					} else {
						$this->data[] = new Row($row);
					}
					$i++;
				}
				fclose($handle);
			} else {
				throw new DataInputException("File '$initialData' could not be opened for reading");
			}
			if (!$stopOnError && count($errorLines)) {
				throw new RowCountMismatchException(
					"Row/header column count mismatch [" . implode(",", $errorLines) . "]"
				);
			}
		}
	}

	/**
	 * Returns the row at a given position. Position does not include the header row.
	 * If the row does not exist, returns null.
	 *
	 * @param int $position
	 * @return File/Row|null
	 */
	public function getRow($position) {
		if (is_int($position) && isset($this->data[(int)$position])) {
			return $this->data[(int)$position];
		} else {
			return null;
		}
	}

	/**
	 * Returns the header row (if set) as an array or as a Header object
	 *
	 * @param bool $asArray If true, will return an array, otherwise will return a Header object.
	 * @return array|header|null
	 */
	public function getHeaderRow($asArray = false) {
		if (!empty($this->headers)) {
			if ($asArray) {
				return array_keys($this->data);
			} else {
				return new Header(array_keys($this->data));
			}
		} else {
			return null;
		}
	}


	/**
	 * Adds a row to the CSV file either at the end of the file or at the given position.
	 *
	 * @param Row|array $row The row to add.
	 * @param null $position Optional position to insert row.
	 *
	 * @return $this
	 *
	 * @throws RowCountMismatchException if the count of columns in a row do not match the columns in the header.
	 * @throws InputTypeException if the input row is not an array or an object of type Row.
	 */
	public function addRow($row, $position = null) {
		if ((is_array($row) && !empty($row)) || $row instanceof Row) {
			if ((!empty($this->headers) && count($row) == count($this->headers)) || empty($this->headers)) {
				if (is_array($row)) {
					$row = array_values($row);
				}
				if (!empty($this->headers)) {
					$row = new Row($row, $this->headers);
				} else {
					$row = new Row($row);
				}
				if (!is_null($position) && is_int($position))  {
					if ($position == 0) {
						$this->data = array_merge(array($row), $this->data);
					} elseif ($position == count($this->data)) {
						$this->data[] = $row;
					} else {
						$this->data = array_merge(array_slice($this->data, 0, $position),
							array($row),
							array_slice($this->data, $position, count($this->data)-$position));
					}
				} else {
					$this->data[] = $row;
				}
			} else {
				throw new RowCountMismatchException("Row/header column count mismatch [".count($this->data)."]");
			}
		} else {
			throw new InputTypeException("Input must be a non-empty array or a Row object");
		}
		return $this;
	}

	/**
	 * Adds multiple rows to the CSV file either at the end of the file or starting at the given position.
	 *
	 * @param array $rows An array of arrays to add to the file.
	 * @param null $position Optional position to insert row.
	 *
	 * @throws InputTypeException if the input row is not an array of arrays or an array of objects of type Row.
	 */
	public function addRows($rows, $position = null) {
		if (is_array($rows)) {
			$newRows = array();
			$rows = array_values($rows);
			foreach ($rows as $i => $row) {
				if (!is_array($row) && !$row instanceof Row) {
					throw new InputTypeException("Input must be a non-empty array of arrays or an array of Row object (Row ".($i+1).")");
				}
				if (is_array($row)) {
					$row = array_values($row);
				}
				if ((!empty($this->headers) && count($row) == count($this->headers)) || empty($this->headers)) {
					$newRows[] = $row;
				}
			}
			if (!is_null($position) && is_int($position)) {
				foreach($newRows as $row) {
					$this->addRow($row, $position++);
				}
			} else {
				$this->data = array_merge($this->data, $newRows);
			}
		} else {
			throw new InputTypeException("Input must be a non-empty array of arrays or an array of Row object");
		}
	}

	/**
	 * Deletes the row at the given position.
	 *
	 * @param $position int Index of row to delete.
	 *
	 * @throws RowDoesNotExistException if the row at the given index does not exist.
	 */
	public function deleteRow($position) {
		if (is_int($position) && isset($this->data[(int)$position])) {
			unset($this->data[(int)$position]);
			$this->data = array_values($this->data);
		} else {
			throw new RowDoesNotExistException("Row does not exist (".$position.")");
		}
	}

	/**
	 * Exports a CSV file as text.
	 *
	 * @param string $filename Name of file to export text to. Defaults to standard PHP output.
	 * @param string $delimiter Delimiter to use between columns.
	 * @param string $enclosure Used to wrap fields that contain the delimiter character.
	 *
	 * @throws DataOutputException if the given file cannot be written to.
	 */
	public function exportCsv($filename = "php://output", $delimiter = ",", $enclosure = '"') {
		if (($handle = fopen($filename, 'w')) !== FALSE) {
			if (!empty($this->headers)) {
				fputcsv($handle, $this->headers->toArray(), $delimiter, $enclosure);
			}
			foreach($this->data as $row) {
				fputcsv($handle, $row->toArray(), $delimiter, $enclosure);
			}
			fclose($handle);
		} else {
			throw new DataOutputException("File '$filename' could not be opened for writing");
		}
	}

	public function count() {
		return count($this->data);
	}

	public function rewind() {
		$this->position = 0;
	}

	public function current() {
		return $this->data[$this->position];
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		return isset($this->data[$this->position]);
	}
	
	static public function getInstance() {
        return new static();
    }
}
