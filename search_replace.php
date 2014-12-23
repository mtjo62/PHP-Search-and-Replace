<?php

/**
 * Search and replace class for text based files
 *
 * @package PHP Search & Replace
 * @version 1.0.1
 * @author MT Jordan <mtjo62@gmail.com>
 * @copyright 2014
 * @license zlib/libpng
 * @link
 */

class search_replace
{
    /**********************************************
     * Internal private variables
     *********************************************/

    /**
     * Path of directory to be searched
     *
     * @access private
     * @var    str
     */
    private $dir_path;

    /**
     * User defined array of inclusive extensions
     *
     * @access private
     * @var    array
     */
    private $set_extension = array();

    /**
     * Array of search file paths
     *
     * @access private
     * @var    array
     */
    private $file_array;

    /**
     * Count of valid files to be searched
     *
     * @access private
     * @var    int
     */
    private $file_cnt = 0;

    /**
     * Recursive iterator type flag
     *
     * @access private
     * @var    bool
     */
    private $set_recursive = false;

    /**
     * Match case flag
     *
     * @access private
     * @var    bool
     */
    private $set_case = false;

    /**
     * Match whole word flag
     *
     * @access private
     * @var    bool
     */
    private $set_whole_word = false;

    /**
     * Count of modified files
     *
     * @access private
     * @var    mixed
     */
    private $mod_cnt = 0;

    /**
     * Use regular expression flag
     *
     * @access private
     * @var    bool
     */
    private $set_regex;

    /**
     * String to be searched - needle
     *
     * @access private
     * @var    mixed
     */
    private $search_needle;

    /**
     * Replacement string
     *
     * @access private
     * @var    mixed
     */
    private $search_replacement;

    /**********************************************
     * Constructor
     *********************************************/

    /**
     * Constructor
     *
     * @access public
     * @param  str   $dir
     * @param  mixed $needle
     * @param  mixed $replacement
     */
    public function __construct( $dir, $needle, $replacement )
    {
        $this->dir_path = $dir;
        $this->search_needle = trim( $needle );
        $this->search_replacement = trim( $replacement );
    }

    /**********************************************
     * Private methods
     *********************************************/

    /**
     * Verify occurance of needle in string using regex or strpos
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function count_needle( $string )
    {
        $needle_cnt = false;

        if ( $this->set_regex )
        {
            $needle_cnt = $this->count_needle_regex( $string );
        }
        elseif ( $this->set_whole_word )
        {
            $needle_cnt = $this->count_needle_whole_word( $string );
        }
        else
        {
            $needle_cnt = $this->count_needle_default( $string );
        }

        return $needle_cnt;
    }

    /**
     * Verify occurance of needle in string
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function count_needle_default( $string )
    {
        if ( $this->set_case )
        {
            $needle_cnt = strpos( $string, $this->search_needle );
        }
        else
        {
            $needle_cnt = stripos( $string, $this->search_needle );
        }

        return ( $needle_cnt !== false ) ? true : false;
    }

    /**
     * Verify occurance of needle in string using regex
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function count_needle_regex( $string )
    {
        $expression = ( $this->set_case ) ? "`{$this->search_needle}`uU" : "`{$this->search_needle}`iuU";

        return ( preg_match( $expression, $string ) ) ? true : false;
    }

    /**
     * Verify occurance of whole word in string
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function count_needle_whole_word( $string )
    {
        $needle = preg_quote( $this->search_needle );
        $expression = ( $this->set_case ) ? "`\b$needle\b`uU" : "`\b$needle\b`iuU";

        return ( preg_match( $expression, $string ) ) ? true : false;
    }

    /**
     * Determine that file is text/* mime type
     *
     * @access private
     * @param  mixed $file
     * @param  str   $ext
     * @return bool
     */
    private function get_mime_type( $file )
    {
        $return_value = false;

        //determine if file is text/* mime type - fall back to the deprecated mime_content_type if fileinfo extension not enabled
        if ( function_exists( 'finfo_open' ) )
        {
            $return_value = ( substr_count( finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $file ), 'text/' ) ) ? true : false;
        }
        else
        {
            $return_value = ( substr_count( mime_content_type( $file ), 'text/' ) ) ? true : false;
        }

        return ( $this->set_mime ) ? true : $return_value;
    }

    /**
     * Iterates through directory and saves file if it meets search/replace arguments
     *
     * @access private
     * @return null
     */
    private function iterator()
    {
        $dir = new DirectoryIterator( $this->dir_path );

        while ( $dir->valid() )
        {
            if ( $this->validate_file( $dir ) )
            {
                $this->write_to_file( $dir->getPathname(), $this->string_replace( file_get_contents( $dir->getPathname() ), $dir->key() ) );
            }

            $dir->next();
        }
    }

    /**
     * Recursively iterates through directory and saves file if it meets search/replace arguments
     *
     * @access private
     * @return null
     */
    private function recursive_iterator()
    {
        $dir = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->dir_path ) );

        while ( $dir->valid() )
        {
            if ( $this->validate_file( $dir ) )
            {
                $this->write_to_file( $dir->key(), $this->string_replace( file_get_contents( $dir->key() ), $dir->key() ) );
            }

            $dir->next();
        }
    }

    /**
     * Performs string replacement functions
     *
     * @access private
     * @param  str $string
     * @param  str $path
     * @return mixed
     */
    private function string_replace( $string, $path )
    {
        $return_value = false;

        if ( $this->count_needle( $string ) )
        {
            if ( $this->set_regex )
            {
                $replace = $this->string_replace_regex( $string );
            }
            elseif ( $this->set_whole_word )
            {
                $replace = $this->string_replace_whole_word( $string );
            }
            else
            {
                $replace = $this->string_replace_default( $string );
            }

            if ( $replace !== false )
            {
                $this->mod_cnt++;
                $this->file_array[] = $path . ' - ' . $replace[1];
                $return_value = $replace[0];
            }
        }

        return $return_value;
    }

    /**
     * Default string replacement
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function string_replace_default( $string )
    {
        $count = 0;

        if ( $this->set_case )
        {
            $replace = str_replace( $this->search_needle, $this->search_replacement, $string, $count );
        }
        else
        {
            $replace = str_ireplace( $this->search_needle, $this->search_replacement, $string, $count );
        }

        return ( is_string( $replace ) ) ? array( $replace, $count ) : false;
    }

    /**
     * Regex string replacement
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function string_replace_regex( $string )
    {
        $count = 0;
        $expression = ( $this->set_case ) ? "`{$this->search_needle}`u" : "`{$this->search_needle}`iu";

        $replace = preg_replace( $expression, $this->search_replacement, $string, -1, $count );

        return ( $replace !== null ) ? array( $replace, $count ) : false;
    }

    /**
     * Whole word string replacement
     *
     * @access private
     * @param  str $string
     * @return mixed
     */
    private function string_replace_whole_word( $string )
    {
        $count = 0;
        $needle = preg_quote( $this->search_needle );
        $expression = ( $this->set_case ) ? "`\b$needle\b`u" : "`\b$needle\b`iu";

        $replace = preg_replace( $expression, $this->search_replacement, $string, -1, $count );

        return ( $replace !== null ) ? array( $replace, $count ) : false;
    }

    /**
     * Determine if file has valid extension, is text/* mime type and is readable/writable
     *
     * @access private
     * @param  mixed $dir
     * @return bool
     */
    private function validate_file( $dir )
    {
        $return_value = false;
        $valid_ext = true;

        //if PHP version < than 5.3.6, use pathinfo() to get extension
        if ( version_compare( phpversion(), '5.3.6', '<' ) )
        {
            $path_parts = pathinfo( $dir->getPathname() );
            $file_ext = $path_parts['extension'];
        }
        else
        {
            $file_ext = $dir->getExtension();
        }

        //determine if user defined extension was requested
        if ( count( $this->set_extension ) )
        {
            $valid_ext = ( in_array( $file_ext, $this->set_extension ) ) ? true : false;
        }

        //determine if is file, is readable and writable, is text/* mime type and an inclusive extension
        if ( !$dir->isDot() && $dir->isFile() && $dir->isReadable() && $dir->isWritable()&& $this->get_mime_type( $dir->getPathname() ) && $valid_ext )
        {
            $this->file_cnt++;
            $return_value = true;
        }

        return $return_value;
    }

    /**
     * Write modified string to file
     *
     * @access public
     * @param  str $path
     * @param  str $replace
     * @return array
     */
    private function write_to_file( $path, $replace )
    {
        if ( $replace !== false )
        {
            file_put_contents( $path, $replace );
        }
    }

    /**********************************************
     * Public methods
     *********************************************/

    /**
     * Perform search & replace and return results
     *
     * @access public
     * @return array
     */
    public function get_results()
    {
        if ( $this->set_recursive )
        {
            $this->recursive_iterator();
        }
        else
        {
            $this->iterator();
        }

        //return number of files searched, modified, file path and number of replace instances
        return array( $this->file_cnt, $this->mod_cnt, $this->file_array );
    }

    /**
     * Set case-sensitive flag
     *
     * @access public
     * @param  bool $case
     * @return bool
     */
    public function set_case( $case=false )
    {
        $this->set_case = $case;
    }

    /**
     * Set user defined inclusive extensions to search
     *
     * @access public
     * @param  str $extension
     * @return array
     */
    public function set_extension( $extension=null )
    {
        //setting this variable will overide $this->set_mime
        $this->set_extension = array_filter( explode( ',', strtolower( $extension ) ) );
    }

    /**
     * Set MIME type flag
     *
     * @access public
     * @param  bool $mime
     * @return bool
     */
    public function set_mime( $mime=false )
    {
        $this->set_mime = $mime;
    }

    /**
     * Set search with regex flag
     *
     * @access public
     * @param  bool $regex
     * @return bool
     */
    public function set_regex( $regex=false )
    {
        $this->set_regex = $regex;
    }

    /**
     * Set recursive iterator flag
     *
     * @access public
     * @param  bool $recursive
     * @return bool
     */
    public function set_recursive( $recursive=false )
    {
        $this->set_recursive = $recursive;
    }

    /**
     * Set search whole word flag
     *
     * @access public
     * @param  bool $whole_word
     * @return bool
     */
    public function set_whole_word( $whole_word=false )
    {
        $this->set_whole_word = $whole_word;
    }
}

/* EOF search_replace.php */
/* Location: ./search_replace.php */
