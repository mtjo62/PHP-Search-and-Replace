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
    private $ext_array;

    /**
     * Array of modified file paths
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
     * Iterator type flag - single:false or recursive:true
     *
     * @access private
     * @var    bool
     */
    private $iterator;

    /**
     * Match case flag
     *
     * @access private
     * @var    bool
     */
    private $match_case;

    /**
     * Match whole word flag
     *
     * @access private
     * @var    bool
     */
    private $match_whole;

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
    private $reg_expression;

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

    /**
     * Constructor
     *
     * @access public
     * @param  str   $dir
     * @param  mixed $needle
     * @param  mixed $replacement
     * @param  str   $ext
     * @param  bool  $reg_exp
     * @param  bool  $iterator
     * @param  bool  $whole
     * @param  bool  $case
     */
    public function __construct( $dir, $needle=null, $replacement=null, $ext=null, $reg_exp=false, $iterator=false, $whole=false, $case=false )
    {
        $this->dir_path = $dir;
        $this->ext_array = array_filter( explode( ',', strtolower( $ext ) ) );
        $this->iterator = $iterator;
        $this->search_needle = trim( $needle );
        $this->search_replacement = $replacement;
        $this->match_whole = $whole;
        $this->match_case = $case;
        $this->reg_expression = $reg_exp;
        $this->search_replace_init();
    }

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

        if ( $this->reg_expression )
        {
            $needle_cnt = $this->count_needle_regex( $string );
        }
        elseif ( $this->match_whole )
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
        if ( $this->match_case )
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
        $expression = ( $this->match_case ) ? "`{$this->search_needle}`uU" : "`{$this->search_needle}`iuU";

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
        $expression = ( $this->match_case ) ? "`\b$needle\b`uU" : "`\b$needle\b`iuU";

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

        return $return_value;
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
     * Return results
     *
     * @access public
     * @return array
     */
    public function print_results()
    {
        //return number of files searched, modified, file path and number of replace instances
        return array( $this->file_cnt, $this->mod_cnt, $this->file_array );
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
     * Call iterator methods
     *
     * @access private
     * @return null
     */
    private function search_replace_init()
    {
        if ( $this->iterator )
        {
            $this->recursive_iterator();
        }
        else
        {
            $this->iterator();
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
            if ( $this->reg_expression )
            {
                $replace = $this->string_replace_regex( $string );
            }
            elseif ( $this->match_whole )
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

        if ( $this->match_case )
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
        $expression = ( $this->match_case ) ? "`{$this->search_needle}`u" : "`{$this->search_needle}`iu";

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
        $expression = ( $this->match_case ) ? "`\b$needle\b`u" : "`\b$needle\b`iu";

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
        if ( count( $this->ext_array ) )
        {
            $valid_ext = ( in_array( $file_ext, $this->ext_array ) ) ? true : false;
        }

        //determine if is file, is readable and writable, is text/* mime type and an inclusive extension
        if ( !$dir->isDot() && $dir->isFile() && $dir->isReadable() && $dir->isWritable() && $this->get_mime_type( $dir->getPathname() ) && $valid_ext )
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
}

/* EOF search_replace.php */
/* Location: ./search_replace.php */
