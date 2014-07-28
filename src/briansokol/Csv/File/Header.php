<?php

namespace briansokol\Csv\File;

use briansokol\Csv\Exception;
use briansokol\Csv\Exception\DataException;

/**
 * This class represents a header row of a CSV file.
 *
 * @package briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class Header implements \Iterator, \Countable {

	/**
	 * @var int
	 */
	protected $position;
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Based on an array of data, an object will be constructed that represents a header row of a CSV file.
	 *
	 * @param array $header Array of data containing the columns of the header row.
	 *
	 * @throws DataException if the given header data is not an array.
	 */
	public function __construct($header) {
		if (is_array($header) && !empty($header)) {
			$this->data = array_values($header);
		} else {
			throw new DataException("Input must be a non-empty array");
		}
	}

	/**
	 * Returns the header as an associative array.
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
