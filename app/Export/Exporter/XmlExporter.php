<?php
/**
 * XmlExporter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry\Entry;
use League\Csv\Writer;
use Storage;

/**
 * Class XmlExporter.
 */
class XmlExporter extends BasicExporter implements ExporterInterface
{
    /** @var string */
    private $fileName;

    /**
     * CsvExporter constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return bool
     *
     * @throws \TypeError
     */
    public function run(): bool
    {
        // create temporary file:
        $this->tempFile();
	$disk = Storage::disk('export');
        

        // necessary for CSV writer:
        $fullPath = storage_path('export') . DIRECTORY_SEPARATOR . $this->fileName;

	//Storage::put($this->fileName,'<xml><export version="1.0">');
	$disk->put($this->fileName,'<xml><export version="1.0">');

        //we create the CSV into memory
        //$writer = Writer::createFromPath($fullPath);
        $rows   = [];

        // get field names for header row:
        $first   = $this->getEntries()->first();
        $headers = [];
        if (null !== $first) {
            $headers = array_keys(get_object_vars($first));
        }
	//print_r($headers);
        
	$rows[] = $headers;
	//Storage::append($this->fileName, '<headers>' . print_r($headers,true). '</headers>');

        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $line = ''; //[];
	    $disk->append($this->fileName,'<transaction>');
            foreach ($headers as $header) {
                //$line[] = $entry->$header;
		// If field = notes then encode data ( base64, base32, | other [todo]
		// notes field may contain xml encoded data 
		// If the future I will add as seperate fields to database
		// also looking to add attachment to the xml output as encoded data inline [todo]
		$line = '<' . $header . '>' . $entry->$header . '</' . $header . '>';
		$disk->append($this->fileName, $line);
            }
	    //Storage::append($this->fileName, print_r($line,true));
	    $disk->append($this->fileName,'</transaction>');
            $rows[] = $line;
        }
	//$writer->insertAll($rows);
	//Storage::append($this->fileName,print_r($rows, true));

	$disk->append($this->fileName,'</export></xml>');

	//print_r($rows);

        return true;
    }

    private function tempFile()
    {
        $this->fileName = $this->job->key . '-records.xml';
        // touch file in export directory:
        $disk = Storage::disk('export');
        $disk->put($this->fileName, '');
    }
}
