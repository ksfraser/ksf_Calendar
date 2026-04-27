<?php

require_once( 'class.rest_interface.php' );

if( ! class_exists( '\\Ksfraser\\File\\ResourceReader', false ) )
{
	require_once __DIR__ . '/../File/Exception/FileException.php';
	require_once __DIR__ . '/../File/ResourceReader.php';
	require_once __DIR__ . '/../File/ResourceWriter.php';
}

/**
 * @deprecated Prefer injecting a downloader (URL reader) and format resolver under Ksfraser\\File\\.
 */
class file_download extends rest_interface
{
	protected $filename;
	protected $fp;
	protected $tmpdir;
	protected $saveto;	//!< string filename to save to
	function __construct() 
	{ 
                $this->baseurl = '';
                $this->endpoint = '';
                $this->key = "";
		$upc = "";
		$this->tmpdir = ".";
		$this->filename = "";
                parent::__construct( "", "", "", "" );

	}
	function run()
	{
		if( strlen( $this->filename ) > 2 )
		{
			$this->saveto = "";
			if( strlen( $this->tmpdir ) > 2 )
			{
				$this->saveto = $this->tmpdir . "/";
			}
			$this->saveto .= $this->filename;
		}
		else
			return FALSE;
		$this->build_url();
		try {
			$reader = new \Ksfraser\File\ResourceReader();
			$writer = new \Ksfraser\File\ResourceWriter();
			$bytes = $reader->readBytes( $this->url );
			$writer->writeBytes( $this->saveto, $bytes );
			return TRUE;
		} catch( Exception $e ) {
			return FALSE;
		}
	}
        function build_url()
        {
                $this->url = $this->baseurl;
		if( strlen( $this->endpoint ) > 0 )
		{
                	$this->url .=  '/' . $this->endpoint;
		}
		if( strlen( $this->queryval ) > 0 )
		{
                	$this->url .=  '?'  . $this->queryval;
		}
		if( isset( $this->key ) AND strlen( $this->key ) > 2 )
		{
			if( strlen( $this->queryval ) > 0 )
			{
				//Need the ampersand
				$this->url .= '&';
			}
			$this->url .= 'key=' .  $this->key;
		}
                $this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'URL ' . $this->url );
        }
	/***************************************************************//**
         *build_interestedin
         *
         *      This function builds the table of events that we
         *      want to react to and what handlers we are passing the
         *      data to so we can react.
         * ******************************************************************/
        function build_interestedin()
        {
                $this->interestedin["DOWNLOAD_FILENAME"]['function'] = "download_filename";
                $this->interestedin["DOWNLOAD_TMPDIR"]['function'] = "download_tmpdir";
                $this->interestedin["DOWNLOAD_URL"]['function'] = "download_url";
                $this->interestedin["DOWNLOAD_ENDPOINT"]['function'] = "download_endpoint";
                $this->interestedin["DOWNLOAD_QUERY"]['function'] = "download_query";
        }
	function download_url( $caller, $msg )
	{
		if( is_string( $msg ) )
		{
       			$this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'Setting BaseURL ' . $msg );
			$this->baseurl = $msg;
		}
	}
	function download_endpoint( $caller, $msg )
	{
		if( is_string( $msg ) )
		{
       			$this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'Setting endpoint ' . $msg );
			$this->endpoint = $msg;
		}
	}
	function download_query( $caller, $msg )
	{
		if( is_string( $msg ) )
		{
       			$this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'Setting query ' . $msg );
			$this->queryval = $msg;
		}
	}
	function download_filename( $caller, $msg )
	{
		if( is_string( $msg ) )
		{
       			$this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'Setting filename ' . $msg );
			$this->filename = $msg;
		}
	}
	function download_tmpdir( $caller, $msg )
	{
		if( is_string( $msg ) )
		{
       			$this->tell_eventloop( $this, 'NOTIFY_LOG_DEBUG', 'Setting tmpdir ' . $msg );
			$this->tmpdir = $msg;
		}
	}
}

