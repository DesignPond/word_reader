<?php

use Pond\File;
use Chumper\Zipper\Zipper;

class FileTest extends PHPUnit_Framework_TestCase {

	/**
	 * Find extension
	 *
	 * @return void
	 */
	public function testGetExtensionOfFile()
	{
        $file = new File(new Zipper());

        $test = getcwd().'files/test.docx';
        $file->getExtension($test);

        $this->assertEquals('docx', $file->extension);
	}

    public function testRenameFile(){

        $file = new File(new Zipper());

        $test   = 'test.docx';
        $expect = getcwd().'/files/unzip/test.zip';

        $file->renameToZip($test);

        $this->assertFileExists($expect);
    }
    public function testUnzipFile(){

        $file = new File(new Zipper());

        $file->filename = getcwd().'/files/unzip/test.zip';
        $expect = getcwd().'/files/doc/word/document.xml';

        $file->unzip();

        $this->assertFileExists($expect);
    }

}
