<?php

namespace briansokol\Csv\File;

use briansokol\Csv\Exception;

class Row implements \Iterator, \Countable {

	protected $position;
	protected $data;

	public function __construct($row, $header = null) {
		if (is_array($row) && !empty($row)) {
			$this->data = array_values($row);
		} else {
			throw new Exception\Data("Input must be a non-empty array");
		}
		if (!is_null($header)) {
			if (is_array($header)) {
				if (count($header) == count($row)) {
					$this->data = array_combine(array_values($header), $this->data);
				} else {
					throw new Exception\Data("Row column count does not match header column count");
				}
			} else {
				throw new Exception\Data("Supplied header must be an array");
			}
		}
	}

	public function getHeaderRow() {
		return array_keys($this->data);
	}

	public function setAtPosition($key, $value, $position) {
		if (!is_int($position)) {
			throw new Exception\Data("Position must be an integer");
		}

		if ($position > count($this->data) || $position < 0) {
			throw new Exception\Data("Position is outside array bounds");
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
