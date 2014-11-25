<?php

namespace briansokol\Csv\File;

use briansokol\Csv\File\GenericRow;
use briansokol\Csv\Exception\InputTypeException;

require_once("GenericRow.php");

/**
 * This class represents a header row of a CSV file.
 *
 * @package briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class Header extends GenericRow {

	/**
	 * Based on an array of data, an object will be constructed that represents a header row of a CSV file.
	 *
	 * @param array $header Array of data containing the columns of the header row.
	 *
	 * @throws InputTypeException if the given header data is not an array.
	 */
	public function __construct($header) {
		parent::__construct();

		if (is_array($header) && !empty($header)) {
			$this->data = array_values($header);
		} else {
			throw new InputTypeException("Input must be a non-empty array");
		}
	}
}
