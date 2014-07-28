<?php

namespace briansokol\Csv\File;

use briansokol\Csv\File\Header;
use briansokol\Csv\Exception;
use briansokol\Csv\Exception\DataException;

/**
 * This class represents a row of a CSV file.
 *
 * @package briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class Row implements \Iterator, \Countable {

	/**
	 * @var int
	 */
	protected $position;
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Based on an array of data, an object will be constructed that represents a row of a CSV file.
	 *
	 * @param array $row Array of data containing the columns of the row.
	 * @param array|Header|null $header If not null, the row's header will also be stored in the row.
	 *
	 * @throws DataException if the count of columns in a row do not match the columns in the header.
	 * @throws DataException if the given row data is not an array.
	 * @throws DataException if the given header data is not an array.
	 */
	public function __construct($row, $header = null) {
		if (is_array($row) && !empty($row)) {
			$this->data = array_values($row);
		} else {
			throw new DataException("Input must be a non-empty array");
		}
		if (!is_null($header)) {
			if (is_array($header) || $header instanceof Header) {
				if (count($header) == count($row)) {
					if ($header instanceof Header) {
						$header = $header->toArray();
					}
					$this->data = array_combine(array_values($header), $this->data);
				} else {
					throw new DataException("Row column count does not match header column count");
				}
			} else {
				throw new DataException("Supplied header must be an array or a Header object");
			}
		}
	}

	/**
	 * Returns the header row as an array or as a Header object
	 *
	 * @param bool $asArray If true, will return an array, otherwise will return a Header object.
	 * @return array|header
	 */
	public function getHeaderRow($asArray = false) {
		if ($asArray) {
			return array_keys($this->data);
		} else {
			return new Header(array_keys($this->data));
		}
	}

	/**
	 * Sets the header and value of a field at the given position.
	 *
	 * @param string $key The header name.
	 * @param string $value The new value of the field.
	 * @param int $position Column number to change.
	 *
	 * @throws DataException if the given position does not exist.
	 * @throws DataException if given position is not an integer.
	 */
	public function setAtPosition($key, $value, $position) {
		if (!is_int($position)) {
			throw new DataException("Position must be an integer");
		}

		if ($position > count($this->data) || $position < 0) {
			throw new DataException("Position is outside array bounds");
		}

		$header = array_keys($this->data);
		$values = array_values($this->data);

		if ($position == 0) {
			$header = array_merge(array($key), $header);
			$values = array_merge(array($value), $values);
		} elseif ($position == count($this->data)) {
			$header[] = $key;
			$values[] = $value;
		} else {
			$header = array_merge(array_slice($header, 0, $position),
				array($key),
				array_slice($header, $position, count($header)-$position));
			$values = array_merge(array_slice($values, 0, $position),
				array($value),
				array_slice($values, $position, count($value)-$position));
		}
		$this->data = array_combine($header, $values);
	}

	/**
	 * Returns the row as an associative array.
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->data;
	}

	public function __get($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
	}

	public function __set($key, $value) {
		return $this->data[$key] = $value;
	}

	public function __isset($key) {
		return isset($this->data[$key]) ? true : false;
	}

	public function __unset($key) {
		unset($this->data[$key]);
	}

	public function __toString() {
		$outputArray = array();
		foreach ($this->data as $value) {
			if (is_array($value)) {
				$value = $this->arrayToString($value);
			}
			if (strpos($value, ",")) {
				$value = '"'.$value.'"';
			}
			$outputArray[] = $value;
		}
		return implode(",", $outputArray);
	}

	public function count() {
		return count($this->data);
	}

	public function rewind() {
		$this->position = 0;
	}

	public function current() {
		$values = array_values($this->data);
		return $values[$this->position];
	}

	public function key() {
		$keys = array_keys($this->data);
		return $keys[$this->position];
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		$values = array_values($this->data);
		return isset($values[$this->position]);
	}

	/**
	 * Utility function to convert an array to a string representation.
	 *
	 * @param $array
	 * @return string
	 */
	protected function arrayToString($array) {
		if (is_array($array)) {
			$outputArray = array_values($array);
			foreach ($array as $i => $value) {
				if (is_array($value)) {
					$outputArray[] = $this->arrayToString($array);
				} elseif (!is_object($value)) {
					$outputArray[] = $value;
				}
			}
			$output = "[";
			$output .= implode(" | ", $outputArray);
			$output .= "]";
			return $output;
		}
	}
}
