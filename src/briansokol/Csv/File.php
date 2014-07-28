<?php

namespace briansokol\Csv;
use briansokol\Csv\File\Row;
use briansokol\Csv\File\Header;
use briansokol\Csv\Exception\DataException;
use briansokol\Csv\Exception\IoException;

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
	 * Based on an initial data source, an object is constructed to represent the file.
	 * Row objects will be created to store each row of data.
	 *
	 * @param string|array $initialData Either a string designating a file to import or an array of associative arrays.
	 * @param bool $headers If true, the first row of the file will be used as the headers.
	 * @param string $delimiter Delimiter between columns.
	 * @param bool $removeDupHeaders If true, it will ignore additional lines that are identical to the header.
	 *
	 * @throws DataException if the count of columns in a row do not match the columns in the header.
	 * @throws IoException if the given initial data file does not exist of cannot be read.
	 */
	function __construct($initialData, $headers = true, $delimiter = ",", $removeDupHeaders = true) {
		$this->position = 0;
		$this->data = array();
		$this->headers = null;

		if (is_array($initialData)) {
			$initialData = array_values($initialData);
			foreach ($initialData as $i => $row) {
				if ($headers) {
					if(empty($this->headers)) {
						$this->headers = new Header($row);
					} else {
						if (!empty($this->headers) && count($this->headers) !== count($row)) {
							throw new DataException("Row column count does not match header column count (Row ".($i+1).")");
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
				$i = 0;
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
					if ($headers) {
						if(empty($this->headers)) {
							$this->headers = new Header($row);
						} else {
							if (!empty($this->headers) && count($this->headers) !== count($row)) {
								throw new DataException("Row column count does not match header column count (Row ".($i+1).")");
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
				throw new IoException("File '$initialData' could not be opened for reading");
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
	 * @throws DataException if the count of columns in a row do not match the columns in the header.
	 * @throws DataException if the input row is not an array or an object of type Row.
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
				throw new DataException("Row column count does not match header column count");
			}
		} else {
			throw new DataException("Input must be a non-empty array or a Row object");
		}
		return $this;
	}

	/**
	 * Adds multiple rows to the CSV file either at the end of the file or starting at the given position.
	 *
	 * @param array $rows An array of arrays to add to the file.
	 * @param null $position Optional position to insert row.
	 *
	 * @throws DataException if the input row is not an array of arrays or an array of objects of type \briansokol\Csv\File\Row.
	 */
	public function addRows($rows, $position = null) {
		if (is_array($rows)) {
			$newRows = array();
			$rows = array_values($rows);
			foreach ($rows as $i => $row) {
				if (!is_array($row) && !$row instanceof Row) {
					throw new DataException("Input must be a non-empty array of arrays or an array of Row object (Row ".($i+1).")");
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
			throw new DataException("Input must be a non-empty array of arrays or an array of Row object");
		}
	}

	/**
	 * Deletes the row at the given position.
	 *
	 * @param $position Index of row to delete.
	 *
	 * @throws DataException if the row at the given index does not exist.
	 */
	public function deleteRow($position) {
		if (is_int($position) && isset($this->data[(int)$position])) {
			unset($this->data[(int)$position]);
			$this->data = array_values($this->data);
		} else {
			throw new DataException("Row does not exist (".$position.")");
		}
	}

	/**
	 * Exports a CSV file as text.
	 *
	 * @param string $filename Name of file to export text to. Defaults to standard PHP output.
	 * @param string $delimiter Delimiter to use between columns.
	 * @param string $enclosure Used to wrap fields that contain the delimiter character.
	 *
	 * @throws IoException if the given file cannot be written to.
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
			throw new IoException("File '$filename' could not be opened for writing");
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
}
