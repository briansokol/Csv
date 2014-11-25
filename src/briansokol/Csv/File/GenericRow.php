<?php

namespace briansokol\Csv\File;

use briansokol\Csv\Exception\InvalidIndexException;

/**
 * This class represents a generic row of a CSV file.
 * Each row may optionally contain the header keys.
 *
 * @package briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class GenericRow implements \ArrayAccess, \Iterator, \Countable {

	/**
	 * @var int
	 */
	protected $position;
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Creates a generic row with no data.
	 */
	public function __construct() {
		$this->position = 0;
		$this->data = array();
	}

	/**
	 * Sets the key and value of a field at the given position.
	 *
	 * @param string $key The key name.
	 * @param string $value The new value of the field.
	 * @param int $position Column number to change.
	 *
	 * @throws InvalidIndexException if the given position does not exist.
	 * @throws InvalidIndexException if given position is not an integer.
	 */
	public function setAtPosition($key, $value, $position) {
		if (!is_int($position)) {
			throw new InvalidIndexException("Position must be an integer");
		}

		if ($position > count($this->data) || $position < 0) {
			throw new InvalidIndexException("Position is outside array bounds");
		}

		$keys = array_keys($this->data);
		$values = array_values($this->data);

		if ($position == 0) {
			$keys = array_merge(array($key), $keys);
			$values = array_merge(array($value), $values);
		} elseif ($position == count($this->data)) {
			$keys[] = $key;
			$values[] = $value;
		} else {
			$keys = array_merge(array_slice($keys, 0, $position),
				array($key),
				array_slice($keys, $position, count($keys)-$position));
			$values = array_merge(array_slice($values, 0, $position),
				array($value),
				array_slice($values, $position, count($value)-$position));
		}
		$this->data = array_combine($keys, $values);
	}

	/**
	 * Returns the row as an associative array.
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->data;
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
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
