<?php namespace Pond;

use Chumper\Zipper\Zipper;

class File{

    protected $zipper;

    public $extension;
    public $filename;

    public function __construct(Zipper $zipper)
    {
        $this->zipper = $zipper;
    }

    public function extract($file){

        //unlink(getcwd().'/files/unzip/'.$file);

        $this->renameToZip($file);
        $this->unzip();

        return true;
    }

    public function getExtension($file){

        $this->extension = pathinfo($file, PATHINFO_EXTENSION);
    }

    public function renameToZip($file){

        copy(getcwd().'/files/'.$file, getcwd().'/files/unzip/'.$file);

        chmod(getcwd().'/files/unzip/'.$file, 777);

        $info = pathinfo( $file );
        $name = $info['filename'];
        $ext  = $info['extension'];

        rename(getcwd()."/files/unzip/".$name.'.'.$ext, getcwd()."/files/unzip/".$name.".zip");

        $this->filename = getcwd()."/files/unzip/".$name.".zip";
    }

    public function unzip(){

        if (!file_exists(getcwd().'/files/doc')) {
            mkdir(getcwd().'/files/doc', 0777, true);
        }

        $this->zipper->make($this->filename)->extractTo(getcwd().'/files/doc');

    }

}