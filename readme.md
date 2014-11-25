Csv
===

By Brian Sokol

A simple library for working with CSV files in PHP

Requirements
------------

PHP 5.3+.

Usage
-----
This library supports method chaining where possible.

### Creating a *File* object iteratively
This object represents a collection of rows with or without a header row.

    $csv = new \briansokol\Csv\File();

#### Define a header row by array
After the header is set, the number of columns in a row must match the header.

    $csv->setHeader(array('A', 'B', 'C'));
    
#### Define a header row using a *Header* object

##### Factory chaining method (PHP 5+)
    $header = \briansokol\Csv\File\Header::getInstance()->setData(array('A', 'B', 'C'));
    $csv->setHeader($header);

##### Constructor chaining method (PHP 5.4+)
    $header = (new \briansokol\Csv\File\Header())->setData(array('A', 'B', 'C'));
    $csv->setHeader($header);

#### Add rows from arrays
This method will also accept a *Row* object.

    $csv->addRow(array('1', '2', 3'))
        ->addRows(array('4', '5', 6'), array('7', '8', '9'));
        
#### Add a row from using a *Row* object

##### Factory chaining method (PHP 5+)
    $row = \briansokol\Csv\File\Row::getInstance()->setData(array('1', '2', '3'));
    $csv->addRow($row);

##### Constructor chaining method (PHP 5.4+)
    $row = (new \briansokol\Csv\File\Row())->setData(array('1', '2', '3'));
    $csv->addRow($row);

### Creating a *File* object from a CSV file
An existing CSV file can be read into the object.
Consider the following CSV file, *file.csv*:

    A,B,C
    1,2,3
    4,5,6
    7,8,9

#### Factory chaining method (PHP 5+)
    $csv = \briansokol\Csv\File::getInstance()->setData("/path/to/file.csv");

#### Constructor chaining method (PHP 5.4+)
    $csv = (new \briansokol\Csv\File())->setData("/path/to/file.csv");
    
### Reading the contents of a *File* object
**File** objects can be iterated or accessed like an array.
Each iteration sets a local **Row** object, which can be accessed as an object or an array.

    foreach($csv as $row) {
        echo $row['A'];
        echo $row->B;
    }
    
Outputs:

    124578
    
### Outputing a *File* to CSV format
If a file name is provided, the contents will be written to that file. Otherwise, the output will be sent to PHP's standard output.

    $csv->exportCsv();
    
*or*

    $csv->exportCsv("path/to/output.csv");
    
Outputs:

    A,B,C
    1,2,3
    4,5,6
    7,8,9