<?php

namespace briansokol\Csv\File;

use briansokol\Csv\File\GenericRow;
use briansokol\Csv\Exception\InputTypeException;

require_once("GenericRow.php");

/**
 * This class represents a header row of a CSV file.
 *
 * @package briansokol\Csv
 * @subpackage briansokol\Csv\File
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class Header extends GenericRow {

	/**
	 * Creates a Header with no data.
	 *
	 * @return Header $this;
	 */
	public function __construct() {
		parent::__construct();
		return $this;
	}
	
	/**
	 * Populates the Header with data.
	 *
	 * @param array $header Array of data containing the columns of the header row.
	 *
	 * @throws InputTypeException if the given header data is not an array.
	 */
	public function setData($header) {
		if (is_array($header) && !empty($header)) {
			$this->data = array_values($header);
		} else {
			throw new InputTypeException("Input must be a non-empty array");
		}
	}
}
