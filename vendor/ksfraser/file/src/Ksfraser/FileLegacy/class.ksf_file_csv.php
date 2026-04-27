<?php

require_once __DIR__ . '/class.ksf_file.php';

if( ! class_exists( '\\Ksfraser\\File\\KsfFileCsv', false ) )
{
	require_once __DIR__ . '/../File/KsfFileCsv.php';
}

/**
 * @deprecated Prefer format-aware services under Ksfraser\\File\\ (e.g. Ksfraser\\File\\FileIO with CsvFormat).
 */
class ksf_file_csv extends ksf_file
{
	protected $size;
	protected $separator;
	protected $lines = array();	//!<array of arrays once run
	protected $linecount;
	protected $b_header;
	protected $b_skip_header;
	private $grabbed_header;
	protected $headerline;
	protected $enclosure;	//!<char
	protected $escapechar;	//!<char
	protected $deliminater;	//!<char
	protected $fieldcount;	//!<int

	/**
	 * @return \Ksfraser\File\KsfFileCsv
	 */
	protected function get_psr_file()
	{
		/** @var \Ksfraser\File\KsfFileCsv $psr */
		$psr = parent::get_psr_file();
		return $psr;
	}
	/**//******************************************
	* Setup the CSV reading class file
	*
	* @param string filename
	* @param int size of a line
	* @param char separator what character separates the CSV
	* @param bool is there a header
	* @param bool b_skip_header skip processing the header
	* @return none
	***********************************************/
	function __construct( $filename, $size, $separator, $b_header = false, $b_skip_header = false, $path = null )
	{
		parent::__construct( $filename, $path );
		$this->size = $size;
		$this->separator = $separator;
		$this->linecount = 0;
		$this->b_header = $b_header;
		$this->b_skip_header = $b_skip_header;
		$this->grabbed_header = false;
		$this->enclosure = '"';
		$this->escapechar = '\\';
		$this->deliminater = $separator;
		$this->fieldcount = 0;
		$this->linecount = 0;
	}
	/**//**************************************************
	* Read a line from a CSV file
	*
	* @param none
	* @returns array the csv line split up.
	*******************************************************/
	/*@array@*/function readcsv_line()
	{
		/** @var \Ksfraser\File\KsfFileCsv $psr */
		$psr = $this->get_psr_file();
		$line = $psr->readcsv_line();
		$this->headerline = $psr->getHeaderLine();
		$this->linecount = $psr->getLineCount();
		return $line;
	}
	function readcsv_entire()
	{
		try {
			/** @var \Ksfraser\File\KsfFileCsv $psr */
			$psr = $this->get_psr_file();
			if( ! isset( $this->fp ) )
				$this->open();
			$psr->readcsv_entire();
			$this->lines = $psr->getLines();
			$this->linecount = $psr->getLineCount();
			$this->headerline = $psr->getHeaderLine();
		} catch( Exception $e ) {
			display_notification( $e->getMessage() );
			$this->lines = array();
			return;
		}
	}
        /**//***************************************
        *
        *
        *
        *
        * @param
        * @returns
        *********************************************/
        function write_array_to_csv( $arr )
        {
				if( !isset( $this->fp ) )
				{
						throw new Exception( "Fileponter not set", KSF_FILE_PTR_NOT_SET );
				}
				if( ! $this->bOpenedWrite )
				{
						throw new Exception( "Fileponter was not opened for writing", KSF_FILE_READONLY );
				}
				/** @var \Ksfraser\File\KsfFileCsv $psr */
				$psr = $this->get_psr_file();
				$psr->write_array_to_csv( $arr );
        }

	protected function create_psr_file()
	{
		return new \Ksfraser\File\KsfFileCsv( (string) $this->filename, (int) $this->size, (string) $this->separator, (bool) $this->b_header, (bool) $this->b_skip_header, (string) $this->path );
	}


}

?>
