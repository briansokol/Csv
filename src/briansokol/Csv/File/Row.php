<?php

namespace briansokol\Csv\File;

use briansokol\Csv\File\GenericRow;
use briansokol\Csv\File\Header;
use briansokol\Csv\Exception\InputTypeException;
use briansokol\Csv\Exception\RowCountMismatchException;

require_once("GenericRow.php");

/**
 * This class represents a row of a CSV file.
 *
 * @package briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class Row extends GenericRow {

	/**
	 * Based on an array of data, an object will be constructed that represents a row of a CSV file.
	 *
	 * @param array $row Array of data containing the columns of the row.
	 * @param array|Header|null $header If not null, the row's header will also be stored in the row.
	 *
	 * @throws RowCountMismatchException if the count of columns in a row do not match the columns in the header.
	 * @throws InputTypeException if the given row data is not an array.
	 * @throws InputTypeException if the given header data is not an array.
	 */
	public function __construct($row, $header = null) {
		parent::__construct();

		if (is_array($row) && !empty($row)) {
			$this->data = array_values($row);
		} else {
			throw new InputTypeException("Input must be a non-empty array");
		}
		if (!is_null($header)) {
			if (is_array($header) || $header instanceof Header) {
				if (count($header) == count($row)) {
					if ($header instanceof Header) {
						$header = $header->toArray();
					}
					$this->data = array_combine(array_values($header), $this->data);
				} else {
					throw new RowCountMismatchException("Row/header column count mismatch");
				}
			} else {
				throw new InputTypeException("Supplied header must be an array or a Header object");
			}
		}
	}

	/**
	 * Returns the header row as an array or as a Header object
	 *
	 * @param bool $asArray If true, will return an array, otherwise will return a Header object.
	 * @return array|Header
	 */
	public function getHeaderRow($asArray = false) {
		if ($asArray) {
			return array_keys($this->data);
		} else {
			return new Header(array_keys($this->data));
		}
	}
}
