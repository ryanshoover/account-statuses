<?php
/**
 * Class for processing CSV file creation for account status
 *
 * Given a CSV with account information, append the account status
 * and download a new CSV file
 *
 * Typical usage:
 * $csv_processor = WPEProcessCsv::get_instance();
 * $csv_processor->process_csv( $path_to_my_csv_file );
 */

if ( ! class_exists( 'WPEProcessCsv' ) ) {
class WPEProcessCsv {

	protected $date_pattern = '%\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}%';

	private $input_file = false;
	private $input_length = 200;
	private $header = [];
	public  $csv_records = [];

	public $api_url = '';
	public $api_records = [];

	public $output_records = [];

	/**
	 * Get a singleton of this class, save the system resources
	 *
	 * @return class Singleton of the class
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance )
			$instance = new static();

		return $instance;
	}

	private function __clone(){}

	private function __wakeup(){}

	protected function __construct() {}

	/**
	 * The mother function. This will create the new CSV
	 *
	 * @param  string The path to a CSV file
	 */
	public function process_csv( $file_path ) {
		// Turn on line ending detection if it's not already set
		ini_set('auto_detect_line_endings', true);

		$this->get_csv( $file_path );
		$this->get_api();

		$this->output_records = $this->create_output_records();

		$this->put_new_csv( $this->output_records );
	}

	/**
	 * Wrapper function for the various methods to get and
	 * process the CSV file
	 *
	 * @param  string The path to the CSV file
	 */
	public function get_csv( $file_path ) {
		$this->input_file 	= $this->open_input_file( $file_path );
		$this->header 		= $this->get_csv_header();
		$this->csv_records 	= $this->get_csv_data();
		$this->close_input_file();
	}

	/**
	 * Open the file
	 *
	 * @param  string The path to a CSV file
	 * @return file   The opened file structure
	 */
	private function open_input_file( $file_path ) {
		return fopen( $file_path, "r" );
	}

	/**
	 * Close the file
	 */
	private function close_input_file() {
		if ( $this->input_file ) {
			fclose( $this->input_file );
		}
	}

	/**
	 * Get the first line and treat it as a header for the file
	 *
	 * @return array The header line from the CSV
	 */
	private function get_csv_header() {
		if ( false == $this->input_file ) {
			return array();
		}

		return fgetcsv( $this->input_file, $this->input_length );
	}

	/**
	 * Get the data from the CSV
	 *
	 * Loop through all lines of the CSV and get the data
	 * Plus, sanitize the values
	 *
	 * @return array Multidimensional array of all the CSV's data
	 */
	private function get_csv_data() {
		$data = [];

		if ( false == $this->input_file ) {
			return $data;
		}

		while ( false != ( $row = fgetcsv( $this->input_file, $this->input_length ) ) ) {

			$row_new = [];

			foreach ( $this->header as $i => $heading_label ) {
				$value = $row[ $i ];

				// Convert all number strings to numbers
				$value = is_numeric( $value ) ? intval( $value ) : $value;

				// Convert all dates to a standard format
				$value = preg_match( $this->date_pattern, $value ) ? date( 'Y-m-d', strtotime( $value ) ) : $value;

                $row_new[ $heading_label ] = $value;
            }

            $data[ $row[0] ] = $row_new;
		}

		return $data;
	}

	/**
	 * Wrapper function for the API methods
	 */
	public function get_api() {
		$this->api_url = 'http://interview.wpengine.io/v1/accounts/';

		$this->api_records = $this->get_api_records();
	}

	/**
	 * Get all the records from the API
	 *
	 * Uses the Account IDs from the CSV file as the guide to getting the API records
	 *
	 * @return array  The records retrieved from the API
	 */
	private function get_api_records() {

		$data = [];

		foreach ( $this->csv_records as $id => $record  ) {
			$url = $this->api_url . $id;

			$api_response = $this->get_url_data( $url );

			$api_record = (array) json_decode( $api_response );

			$data[] = $api_record;
		}

		return $data;
	}

	/**
	 * Process the CSV and API arrays into a single value
	 *
	 * @return array  Single array with merged data
	 */
	public function create_output_records() {
		$data = [];

		$output_header = $this->header;

		$output_header[] = 'Status';
		$output_header[] = 'Status Set On';
		$data[] = $output_header;

		foreach ( $this->csv_records as $id => $csv_record ) {
			$key = array_search( $id, array_column( $this->api_records, 'account_id' ) );

			$data[] = $this->merge_arrays( $id, $csv_record, $this->api_records[ $key ] );
		}

		return $data;
	}

	/**
	 * Since we have a combined array, output it to a new file
	 *
	 * The new file is downloaded by the user's browser
	 *
	 * @param  array The multidimensional array that makes up the new CSV
	 * @return file  The new CSV is downloaded by the user's browser
	 */
	public function put_new_csv( $records ) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=account_statuses.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$line = '';

			foreach ( $record as $r ) {
				$line .= $this->maybe_encode_csv_field( $r ) . ',';
			}

			$line = rtrim( $line, ',' );

			echo $line . "\n";
		}
	}

	/**
	 * Helper function to merge the arrays
	 *
	 * Drops the first value of the second array
	 * and merges the two arrays
	 * (the first value is assumed to be a shared key)
	 *
	 * @param  int    The account ID as a primary key
	 * @param  array  The record from the CSV file
	 * @param  array  The record from the API
	 * @return array  The merged arrays
	 */
	protected function merge_arrays( $id, $csv, $api ) {

		$data = array_values( $csv );

		array_shift( $api );

		$data = array_merge( $data, $api );

		return $data;
	}

	/**
	 * Helper function to encode a CSV field
	 *
	 * If the CSV field has a reserved character, encode it by wrapping in quotes
	 *
	 * @param  string  The string that needs encoded
	 * @return string  The string after encoding
	 */
	protected function maybe_encode_csv_field( $string ) {
	    if( strpos( $string, ',') !== false ||
	    	strpos($string, '"')  !== false ||
	    	strpos($string, "\n") !== false ) {
	        $string = '"' . str_replace('"', '""', $string) . '"';
	    }

	    return $string;
	}

	/**
	 * Helper function to cURL a URL
	 *
	 * @param  string  URL to query
	 * @return string  Body of the URL
	 */
	protected function get_url_data( $url ) {
		$ch         = curl_init();
		$timeout    = 5;
		$user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

		curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_FAILONERROR, true );

		$data = curl_exec($ch);

		curl_close($ch);

		return $data;
	}
}
}

