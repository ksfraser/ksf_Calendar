<?php

require_once __DIR__ . '/class.ksf_file.php';
require_once( 'class.ksf_ui.php' );

if( ! class_exists( '\\Ksfraser\\File\\KsfFileCsv', false ) )
{
	require_once __DIR__ . '/../File/KsfFileCsv.php';
}
if( ! class_exists( '\\Ksfraser\\File\\ResourceWriter', false ) )
{
	require_once __DIR__ . '/../File/Exception/FileException.php';
	require_once __DIR__ . '/../File/ResourceWriter.php';
}

/*******************************************************//**
 *
 *
 * Inherits the path of company/images for destination directory
 *
 * **********************************************************/
/**
 * @deprecated Upload handling is application/framework-specific; prefer composing upload parsing with Ksfraser\\File\\ readers.
 */
class ksf_file_upload extends ksf_file
{
	protected $upload_ok;
	protected $files_array;		//!< array List of filenames of files we uploaded
	protected $filepaths_array;	//!< array List of full path filenames of files we uploaded
	protected $ui_class;		//!< class that has the UI screens required for this class to work
	protected $upload_button_name;
	protected $upload_button_label;
	protected $upload_file_field_name;
	protected $b_upload_single_file;
	protected $a_data;		//!< array data returned from file type handler
	function __construct( $filename, $ui_c = null, $upload_file_field_name = "import_files", $b_upload_single_file = true )
	{
		parent::__construct( $filename );
		$this->upload_ok = FALSE;
		$this->files_array = array();
		if( null == $ui_c )
			$this->ui_class = new ksf_ui_class();
		else
			$this->ui_class = $ui_c;
		$this->upload_file_field_name = $upload_file_field_name;
		$this->b_upload_single_file = $b_upload_single_file;
		$this->a_data = array();
	}
	function open()
	{
		// Delegate to the namespaced implementation via the legacy bridge.
		$this->open_for_write();
	}
	function process_files()
	{
		//var_dump( $_FILES );
		/* If $this->b_upload_single_file true, following subarrays don't have 0/1/...
		 * array(1) 
		 * { 
		 * 	["import_files"]=> array(5) 
		 * 	{ 
		 * 		["name"]=> array(2) 
		 * 		{ 
		 * 			[0]=> string(37) "Gator Price list April 2017 Item2.csv" 
		 * 			[1]=> string(32) "Gator Price list April 2017.xlsx" 
		 * 		} 
		 * 		["type"]=> array(2) 
		 * 		{ 
		 * 			[0]=> string(24) "application/octet-stream" 
		 * 			[1]=> string(24) "application/octet-stream" 
		 * 		} 
		 * 		["tmp_name"]=> array(2) 
		 * 		{ 
		 * 			[0]=> string(46) "C:\Bitnami\redmine-3.0.3-0\php\tmp\phpEC9B.tmp" 
		 * 			[1]=> string(46) "C:\Bitnami\redmine-3.0.3-0\php\tmp\phpECAC.tmp" 
		 * 		} 
		 * 		["error"]=> array(2) 
		 * 		{ 
		 * 			[0]=> int(0) 
		 * 			[1]=> int(0) 
		 * 		} 
		 * 		["size"]=> array(2) 
		 * 		{ 
		 * 			[0]=> int(114550) 
		 * 			[1]=> int(32990) 
		 * 		} 
		 * 	} 
		 * }
		 *  */
		if( isset( $_POST['file_type'] ) )
			$type = $_POST['file_type'];
		else
			$type = null;
		if( isset( $_POST[ 'seperator' ] ) )
		 	$seperator = $_POST[ 'seperator' ];
		else
			$seperator = ',';
		if( $this->b_upload_single_file )
		{
			if ( isset( $_FILES[ $this->upload_file_field_name ] ) && $_FILES[ $this->upload_file_field_name ]['name'] != '')
			{
				$filename = $_FILES[ $this->upload_file_field_name ]['tmp_name'];
				$size = $_FILES[ $this->upload_file_field_name ]['size'];
				$error = $_FILES[ $this->upload_file_field_name ]['error'];
				if( !$error )
					$this->a_data[] = $this->process_single_file( $filename, $size, $seperator, $type );
			}
		}
		else
		{
			$filecount = count( $_FILES[ $this->upload_file_field_name ]['tmp_name'] ); //How many files
			for( $count = 0; $count < $filecount; $count++ )
			{
				$filename = $_FILES[ $this->upload_file_field_name ][$count]['tmp_name'];
				$size = $_FILES[ $this->upload_file_field_name ][$count]['size'];
				$error = $_FILES[ $this->upload_file_field_name ][$count]['error'];
				if( !$error )
					$this->a_data[] = $this->process_single_file( $filename, $size, $seperator, $type );
			}
		
		}
		var_dump( $this->a_data );
	}
	function process_single_file( $filename, $size, $separator=',', $type = 'csv' )
	{
		if( $type == 'csv' )
		{
			$fc = new \Ksfraser\File\KsfFileCsv( (string) $filename, (int) $size, (string) $separator, false, false, '' );
			$fc->open();
			$fc->readcsv_entire();
			$fc->close();
			$this->data = array( 'count' => $fc->getLineCount(), 'header' => $fc->getHeaderLine() , 'data' => $fc->getLines() );
		}
		return $this->data;
	}
	function upload_form($b_multi=false, $action="", $name="") 
	{

		if( null == $this->ui_class )
			throw new Exception( "UI Class not set" );
		$this->ui_class->div_start('doc_tbl');
		$this->ui_class->form_start( $b_multi, false, $action, $name );
		$this->ui_class->instructions_table();
		$this->ui_class->table_start(TABLESTYLE);
		$this->ui_class->table_header( array(_("Select File(s)"), '') );
		if( $this->b_upload_single_file )
			$multi = "' />";
		else
			$multi = "[]' multiple />";
		label_row(_("Files"), "<input type='file' name='" . $this->upload_file_field_name . $multi);
		start_row();
		label_cell('Upload', "class='label'");
		if( !isset( $this->upload_button_name ) )
			throw new Exception( "Button Name not set", KSF_FIELD_NOT_SET );
		if( !isset( $this->upload_button_label ) )
			throw new Exception( "Button Name not set", KSF_FIELD_NOT_SET );
		submit_cells( $this->upload_button_name, _($this->upload_button_label) );
		end_row();
		$this->ui_class->table_end(1);
		$this->ui_class->form_end();
		div_end();
	}
	function file_put_contents( $content )
	{
		$writer = new \Ksfraser\File\ResourceWriter();
		$writer->writeBytes( $this->path . "/" . $this->filename, (string) $content );
		$this->filepaths_array[] = $this->path . "/" . $this->filename;
		$this->files_array[] = $this->filename;
	}
	function copy_file()
	{
		if( isset( $this->fp ) )
			$this->close();
		foreach( $_FILES['files']['name'] as $id=>$fname) 
		{
    			echo "Processing file `$fname`\n";
			$content = file_get_contents($_FILES['files']['tmp_name'][$id]);
			$this->set( "filename", $fname );
			$this->file_put_contents( $content );
		}

	}

}

?>
