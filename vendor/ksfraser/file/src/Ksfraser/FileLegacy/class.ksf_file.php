<?php

require_once( 'class.fa_origin.php' );
//require_once( 'class.origin.php' );
//require_once( 'defines.inc.php' );

// Legacy bridge: allow using the namespaced implementation even when
// this legacy class is loaded via manual requires (without Composer autoload).
if( ! class_exists( '\\Ksfraser\\File\\KsfFile', false ) )
{
	require_once __DIR__ . '/../File/Defines.php';
	require_once __DIR__ . '/../File/Exception/FileException.php';
	require_once __DIR__ . '/../File/KsfFile.php';
}

/*
if( ! defined( 'company_path' ) )
{
	function company_path()
	{
		return "";
	}
}
*/

/**
 * @deprecated Use namespaced classes under Ksfraser\\File\\ (e.g. Ksfraser\\File\\KsfFile, Ksfraser\\File\\FileIO).
 */
class ksf_file extends fa_origin
{
	protected $fp;	//!< @var handle File Pointer
	protected $filename;	//!< @var string name of output file
	protected $tmp_dir;	//!< @var string temporary directory name
	protected $path;	//!<DIR where are the images stored.  default company/X/images...
	protected $file_contents;	//!<binary data stream - file contents
	protected $bDeleteFile;	//!<bool should we delete the file once we are done with it.
	protected $linecount;	//!<int
        protected $filesize;    //!<int
        protected $filepath;    //!<string full path
        protected $filecontents;        //!<binary file contents.
/**
        protected $deliminater; //!< fputcsv control value
        protected $enclosure;   //!< fputcsv control value
        protected $escape_char; //!< fputcsv control value
  **/ 
	protected $bOpenedWrite;	//!<bool opened for writing.
	protected $psr_file;	//!< \Ksfraser\File\KsfFile delegate
        /**//*****************************************************
        * Construct the File handling class
        *
        * @param string filename
        * @param string (optional)path
        * @return none sets internal variables.
        ***********************************************************/
        function __construct( $filename = "file.txt", $path = null )
	{
		parent::__construct();
		$this->filename = $filename;
		$this->bOpenedWrite = false;
		if(  defined( 'company_path' ) )
		{
			$this->path = company_path() . '/images';
		}
		else
		{
		}
                if( null !== $path )
                {
                        $this->path = $path;
                }
                else
                {
					$path = (string) getcwd();
					$this->path = $path;
                }
		$this->bDeleteFile = false;

				if( strlen( $this->path ) > 1 )
					$this->filepath = $this->path . '/' . $this->filename;
				else
					$this->filepath = $this->filename;
				$this->filesize = file_exists( $this->filepath ) ? filesize( $this->filepath ) : 0;
	}
	function __destruct()
	{
		if( isset( $this->fp ) )
			$this->close();
		if( $this->bDeleteFile )
		{
			if( file_exists( $this->filename ) )
			{
				$this->unlink();
			}
		}
	}
	/**//***********************************************
	* Delete (unlink) a file
	*
	*	Will delete symlink on Linux
	*	On Windows to delte a symlink to a directory rmdir must be used
	*
	* @param string filename (optional) deletes ->filename
	* @return bool Did we succeed
	******************************/
	function unlink( $filename = null )
	{
		try {
			return $this->get_psr_file()->unlink( $filename );
		} catch( Exception $e ) {
			if( null !== $filename )
				return unlink( $filename );
			return unlink( $this->filename );
		}
	}
	/**//*********************************************
	* Alias to unlink
	*
	* @param string filename (optional) deletes ->filename
	* @return bool Did we succeed
	******************************/
	function delete( $filename = null )
	{
		return $this->unlink( $filename );
	}
        /**//*****************************************************
        * Open the file
        *
        * @param none uses internal variables.
        * @return none sets internal variables.
        ***********************************************************/
	function open()
	{
		try {
			$psr = $this->get_psr_file();
			$psr->open();
			$this->fp = $psr->getHandle();
			$this->filepath = $psr->getFilePath();
			$this->filesize = file_exists( $this->filepath ) ? filesize( $this->filepath ) : 0;
		} catch( Exception $e ) {
			throw new Exception( "Unable to set Fileponter when trying to open ". $this->filename . ': ' . $e->getMessage(), KSF_FILE_OPEN_FAILED );
		}
	}
	function open_for_write()
	{
		try {
			$psr = $this->get_psr_file();
			$psr->open_for_write();
			$this->fp = $psr->getHandle();
			$this->filepath = $psr->getFilePath();
			$this->bOpenedWrite = true;
		} catch( Exception $e ) {
			throw new Exception( "Unable to set Fileponter when trying to open ". $this->filename . ': ' . $e->getMessage(), KSF_FILE_OPEN_FAILED );	
		}
	}
	function close()
	{
		if( !isset( $this->fp ) )
			throw new Exception( "Trying to close a Fileponter that isn't set", KSF_FILE_PTR_NOT_SET );
		try {
			$this->get_psr_file()->close();
		} catch( Exception $e ) {
			fflush( $this->fp );
			fclose( $this->fp );
		}
		$this->fp = null;
	}
	/**//***************************************
	*
	*
	*
	*
	* @param string
	* @returns none
	*********************************************/
        function write_chunk( $line )
        {
					if( !isset( $this->fp ) )
							throw new Exception( "Fileponter not set", KSF_FILE_PTR_NOT_SET );
					$this->get_psr_file()->write_chunk( $line );
        }
	/**//***************************************
	*
	*
	*
	*
	* @param string
	* @returns none
	*********************************************/
        function write_line( $line )
        {
					if( !isset( $this->fp ) )
							throw new Exception( "Fileponter not set", KSF_FILE_PTR_NOT_SET );
					$this->get_psr_file()->write_line( $line );
        }
	/**//***************************************
	*
	*
	*
	*
	* @param array
	* @returns Exception
	*********************************************/
        function write_array_to_csv( $arr )
        {
		throw new Exception( "You are using the wrong class.  Use ksf_file_csv", KSF_FCN_REFACTORED   );
        }

	/**//***************************************
	*
	*
	*
	*
	* @param none
	* @returns bool
	*********************************************/
	/*@bool@*/function make_path()
	{
		return $this->get_psr_file()->make_path();
	}
	/**//***************************************
	*
	*
	*
	*
	* @param none
	* @returns bool
	*********************************************/
	/*@bool@*/function pathExists()
	{
		return $this->get_psr_file()->pathExists();
	}
	/***************************************************************
	 * Check for the existance of a file
	 *
	 * 
	 * @return bool
	 * *************************************************************/
	/*@bool@*/function fileExists()
	{
		return $this->get_psr_file()->fileExists();
	}
	function validateVariables()
	{
                if( !isset( $this->path ) )
                        throw new Exception( "Path variable not set", KSF_FIELD_NOT_SET );
                if( !isset( $this->filename )  )
                        throw new Exception( "filename variable not set", KSF_FIELD_NOT_SET );
	}
	/**//***************************************************************
	* Use PHP functions to read the file contents entire.
	*
	* @param none uses internal variables
	* @returns none sets internal variables
	********************************************************************/
	function getFileContents()
	{
		try {
			$this->file_contents = $this->get_psr_file()->getFileContents();
		} catch( Exception $e ) {
			throw new Exception( $e->getMessage(), KSF_FIELD_NOT_SET );
		}
	}
	/**//***************************************************************
	* Grab a filename from the webserver after an upload.
	*
	* @param int id which file (on multi upload) to grab.  Default 0
	* @returns none sets internal variables
	********************************************************************/
	function uploadFileName( $id = 0 )
	{
		try {
			$this->get_psr_file()->uploadFileName( (int) $id );
			$this->filepath = $this->get_psr_file()->getFilePath();
			$this->filesize = file_exists( $this->filepath ) ? filesize( $this->filepath ) : 0;
		} catch( Exception $e ) {
			throw new Exception( $e->getMessage(), KSF_VAR_NOT_SET );
		}
	}
	/**//*********************************************************************
	* Read the entire file using fread
	*
	* @param none
	* @returns stream file contents
	*************************************************************************/
	function fread()
	{
				if( ! isset( $this->fp ) )
				{
						throw new Exception( "File Pointer not set, can't read", KSF_FILED_NOT_SET );
				}
				$this->filecontents = $this->get_psr_file()->fread();
				return $this->filecontents;
	}
	/**//********************************************************************************
     	* Remove the BOM (Byte Order Mark) from the beginning of the import row if it exists
	*
	* This function came from SuiteCRM ImportFile
	*
	* @param none
     	* @return void
     	*/
    	private function setFpAfterBOM()
    	{
        	if($this->fp === FALSE)
            		return;
        	rewind($this->fp);
        	$bomCheck = fread($this->fp, 3);
        	if($bomCheck != pack("CCC",0xef,0xbb,0xbf)) {
            		rewind($this->fp);
        	}
    	}

	/**//*************************************************************************
     	* Determine the number of lines in this file.
     	*
     	* @return int
     	*/
    	function getNumberOfLinesInfile()
    	{
			if( ! isset( $this->fp ) )
				return 0;
			$this->linecount = $this->get_psr_file()->getNumberOfLinesInfile();
			return $this->linecount;
    	}

	protected function create_psr_file()
	{
		return new \Ksfraser\File\KsfFile( (string) $this->filename, (string) $this->path );
	}

	protected function get_psr_file()
	{
		if( ! isset( $this->psr_file ) )
			$this->psr_file = $this->create_psr_file();
		return $this->psr_file;
	}
/*
        fwrite() - Binary-safe file write
        fsockopen() - Open Internet or Unix domain socket connection
        popen() - Opens process file pointer
        fgets() - Gets line from file pointer
        fgetss() - Gets line from file pointer and strip HTML tags
        fscanf() - Parses input from a file according to a format
        file() - Reads entire file into an array
        fpassthru() - Output all remaining data on a file pointer
        fseek() - Seeks on a file pointer
        ftell() - Returns the current position of the file read/write pointer
        rewind() - Rewind the position of a file pointer
        unpack() - Unpack data from binary string
        readfile() - Outputs a file
        file_put_contents() - Write data to a file
        stream_get_contents() - Reads remainder of a stream into a string
        stream_context_create() - Creates a stream context

basename — Returns trailing name component of path
chgrp — Changes file group
chmod — Changes file mode
chown — Changes file owner
clearstatcache — Clears file status cache
copy — Copies file
delete — See unlink or unset
dirname — Returns a parent directory's path
disk_free_space — Returns available space on filesystem or disk partition
disk_total_space — Returns the total size of a filesystem or disk partition
diskfreespace — Alias of disk_free_space
fclose — Closes an open file pointer
fdatasync — Synchronizes data (but not meta-data) to the file
feof — Tests for end-of-file on a file pointer
fflush — Flushes the output to a file
fgetc — Gets character from file pointer
fgetcsv — Gets line from file pointer and parse for CSV fields
fgets — Gets line from file pointer
fgetss — Gets line from file pointer and strip HTML tags
file_exists — Checks whether a file or directory exists
file_get_contents — Reads entire file into a string
file_put_contents — Write data to a file
file — Reads entire file into an array
fileatime — Gets last access time of file
filectime — Gets inode change time of file
filegroup — Gets file group
fileinode — Gets file inode
filemtime — Gets file modification time
fileowner — Gets file owner
fileperms — Gets file permissions
filesize — Gets file size
filetype — Gets file type
flock — Portable advisory file locking
fnmatch — Match filename against a pattern
fopen — Opens file or URL
fpassthru — Output all remaining data on a file pointer
fputcsv — Format line as CSV and write to file pointer
fputs — Alias of fwrite
fread — Binary-safe file read
fscanf — Parses input from a file according to a format
fseek — Seeks on a file pointer
fstat — Gets information about a file using an open file pointer
fsync — Synchronizes changes to the file (including meta-data)
ftell — Returns the current position of the file read/write pointer
ftruncate — Truncates a file to a given length
fwrite — Binary-safe file write
glob — Find pathnames matching a pattern
is_dir — Tells whether the filename is a directory
is_executable — Tells whether the filename is executable
is_file — Tells whether the filename is a regular file
is_link — Tells whether the filename is a symbolic link
is_readable — Tells whether a file exists and is readable
is_uploaded_file — Tells whether the file was uploaded via HTTP POST
is_writable — Tells whether the filename is writable
is_writeable — Alias of is_writable
lchgrp — Changes group ownership of symlink
lchown — Changes user ownership of symlink
link — Create a hard link
linkinfo — Gets information about a link
lstat — Gives information about a file or symbolic link
mkdir — Makes directory
move_uploaded_file — Moves an uploaded file to a new location
parse_ini_file — Parse a configuration file
parse_ini_string — Parse a configuration string
pathinfo — Returns information about a file path
pclose — Closes process file pointer
popen — Opens process file pointer
readfile — Outputs a file
readlink — Returns the target of a symbolic link
realpath_cache_get — Get realpath cache entries
realpath_cache_size — Get realpath cache size
realpath — Returns canonicalized absolute pathname
rename — Renames a file or directory
rewind — Rewind the position of a file pointer
rmdir — Removes directory
set_file_buffer — Alias of stream_set_write_buffer
stat — Gives information about a file
symlink — Creates a symbolic link
tempnam — Create file with unique file name
tmpfile — Creates a temporary file
touch — Sets access and modification time of file
umask — Changes the current umask
unlink — Deletes a file
*/

}
?>
