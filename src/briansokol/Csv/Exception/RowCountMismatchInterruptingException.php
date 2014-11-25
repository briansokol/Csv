<?php

namespace briansokol\Csv\Exception;

/**
 * Exception thrown when the number of elements in a row does not match expectations.
 * If thrown during a batch import, this exception will  interrupt the import of additinal rows.
 *
 * @package briansokol\Csv
 * @subpackage briansokol\Csv\Exception
 * @author Brian Sokol <Brian.Sokol@gmail.com>
 */
class RowCountMismatchInterruptingException extends \Exception {

}
