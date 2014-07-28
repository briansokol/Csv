<?php

namespace briansokol\Csv;

class File implements \Iterator, \Countable {

	protected $position;
	protected $headers;
	protected $data;

	function __construct($initialData, $headers = true, $delimiter = ",", $removeDupHeaders = true) {
		$this->position = 0;
		$this->data = array();
		$this->headers = null;

		if (is_array($initialData)) {
			$initialData = array_values($initialData);
			foreach ($initialData as $i => $row) {
				if ($headers) {
					if(empty($this->headers)) {
						$this->headers = $row;
					} else {
						if (!empty($this->headers) && count($this->headers) !== count($row)) {
							throw new Exception\Data("Row column count does not match header column count (Row ".($i+1).")");
						}
						if (($removeDupHeaders && $row !== $this->headers) || !$removeDupHeaders) {
							$this->data[] = new File\Row($row, $this->headers);
						}
					}
				} else {
					$this->data[] = new File\Row($row);
				}
			}
		} else {
			if (($handle = fopen($initialData, 'r')) !== FALSE) {
				$i = 0;
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
					if ($headers) {
						if(empty($this->headers)) {
							$this->headers = $row;
						} else {
							if (!empty($this->headers) && count($this->headers) !== count($row)) {
								throw new Exception\Data("Row column count does not match header column count (Row ".($i+1).")");
							}
							if (($removeDupHeaders && $row !== $this->headers) || !$removeDupHeaders) {
								$this->data[] = new File\Row($row, $this->headers);
							}
						}
					} else {
						$this->data[] = new File\Row($row);
					}
					$i++;
				}
				fclose($handle);
			} else {
				throw new Exception\Io("File '$initialData' could not be opened for reading");
			}
		}
		return true;
	}

	public function getRow($position) {
		if (is_int($position) && isset($this->data[(int)$position])) {
			return $this->data[(int)$position];
		} else {
			return null;
		}
	}

	public function getHeaderRow() {
		if (!empty($this->headers)) {
			return $this->headers;
		} else {
			return null;
		}
	}

	public function addRow($row, $position = null) {
		if ((is_array($row) && !empty($row)) || $row instanceof File\Row) {
			if (is_array($row)) {
				$row = array_values($row);
			}
			// TODO fix this function so that it's checking that the number of headers matches the number of columns
			if ((!empty($this->headers) && count($row) == count($this->headers)) || empty($this->headers)) {
				if (!is_null($position) && is_int($position)) {
					$this->data = array_merge(array_slice($this->data, 0, $position),
						$row,
						array_slice($this->data, $position, count($this->data)-$position));
				} else {
					$this->data[] = $row;
				}
			}
		} else {
			throw new Exception\Data("Input must be a non-empty array or a Row object");
		}
	}

	public function addRows($rows, $position = null) {
		if (is_array($rows)) {
			$newRows = array();
			$rows = array_values($rows);
			foreach ($rows as $i => $row) {
				if (!is_array($row) && !$row instanceof File\Row) {
					throw new Exception\Data("Input must be a non-empty array of arrays or an array of Row object (Row ".($i+1).")");
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
			throw new Exception\Data("Input must be a non-empty array of arrays or an array of Row object");
		}
	}

	public function deleteRow($position) {
		if (is_int($position) && isset($this->data[(int)$position])) {
			unset($this->data[(int)$position]);
			$this->data = array_values($this->data);
		} else {
			throw new Exception\Data("Row does not exist (".$position.")");
		}
	}

	public function getCsv($filename = "php://output", $delimiter = ",", $enclosure = '"') {
		if (($handle = fopen($filename, 'w')) !== FALSE) {
			if (!empty($this->headers)) {
				fputcsv($handle, $this->headers, $delimiter, $enclosure);
			}
			foreach($this->data as $row) {
				fputcsv($handle, $row->toArray(), $delimiter, $enclosure);
			}
			fclose($handle);
		} else {
			throw new Exception\Io("File '$filename' could not be opened for writing");
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
