<?php

/**
 * This is a simple sql tokenizer / parser.
 * I needed code to get the total count of a paged recordset, so I wrote this.
 * It seems to handle sane mssql/mysql queries.
 * 
 * PROTOTYPE
 *
 * @author Justin Carlson <justin.carlson@gmail.com>
 * @license LGPL
 * @version 0.0.3
 */


class Sqlparser {
	
	
    
    var $handle = null;    
    
    public static $allowed_tokens = array(
    	'select' => array('select','load','fetch','get') );
    	
    public static $strip_quotes = array(
    	'select' => TRUE,
    	'from' => TRUE
    );
    	
    private $tags_used = array();
    	
    public static $querysections = array('alter', 'create', 'drop', 'select', 'delete', 'insert', 'update','from','where','limit','order','depth','load','fetch','get');
    public static $operators = array('=', '<>', '<', '<=', '>', '>=', 'like', 'clike', 'slike', 'not', 'is', 'in', 'between');
    public static $types = array('character', 'char', 'varchar', 'nchar', 'bit', 'numeric', 'decimal', 'dec', 'integer', 'int', 'smallint', 'float', 'real', 'double', 'date', 'datetime', 'time', 'timestamp', 'interval', 'bool', 'boolean', 'set', 'enum', 'text');
    public static $conjuctions = array('by', 'as', 'on', 'into', 'from', 'where', 'with');
    public static $funcitons = array('avg', 'count', 'max', 'min', 'sum', 'nextval', 'currval', 'concat');
    public static $reserved = array('absolute', 'action', 'add', 'all', 'allocate', 'and', 'any', 'are', 'asc', 'ascending', 'assertion', 'at', 'authorization', 'begin', 'bit_length', 'both', 'cascade', 'cascaded', 'case', 'cast', 'catalog', 'char_length', 'character_length', 'check', 'close', 'coalesce', 'collate', 'collation', 'column', 'commit', 'connect', 'connection', 'constraint', 'constraints', 'continue', 'convert', 'corresponding', 'cross', 'current', 'current_date', 'current_time', 'current_timestamp', 'current_user', 'cursor', 'day', 'deallocate', 'declare', 'default', 'deferrable', 'deferred', 'desc', 'descending', 'describe', 'descriptor', 'diagnostics', 'disconnect', 'distinct', 'domain', 'else', 'end', 'end-exec', 'escape', 'except', 'exception', 'exec', 'execute', 'exists', 'external', 'extract', 'false', 'fetch', 'first', 'for', 'foreign', 'found', 'full', 'get', 'global', 'go', 'goto', 'grant', 'group', 'having', 'hour', 'identity', 'immediate', 'indicator', 'initially', 'inner', 'input', 'insensitive', 'intersect', 'isolation', 'join', 'key', 'language', 'last', 'leading', 'left', 'level', 'limit', 'local', 'lower', 'match', 'minute', 'module', 'month', 'names', 'national', 'natural', 'next', 'no', 'null', 'nullif', 'octet_length', 'of', 'only', 'open', 'option', 'or', 'order', 'outer', 'output', 'overlaps', 'pad', 'partial', 'position', 'precision', 'prepare', 'preserve', 'primary', 'prior', 'privileges', 'procedure', 'public', 'read', 'references', 'relative', 'restrict', 'revoke', 'right', 'rollback', 'rows', 'schema', 'scroll', 'second', 'section', 'session', 'session_user', 'size', 'some', 'space', 'sql', 'sqlcode', 'sqlerror', 'sqlstate', 'substring', 'system_user', 'table', 'temporary', 'then', 'timezone_hour', 'timezone_minute', 'to', 'trailing', 'transaction', 'translate', 'translation', 'trim', 'true', 'union', 'unique', 'unknown', 'upper', 'usage', 'user', 'using', 'value', 'values', 'varying', 'view', 'when', 'whenever', 'work', 'write', 'year', 'zone', 'eoc');
    public static $startparens = array('{', '(');
    public static $endparens = array('}', ')');
    public static $tokens = array(',', ' ');
    
    public $query = array();
    
    function __construct()
    {
    	
    }

    /**
     * Simple SQL Tokenizer
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license GPL
     * @param string $sqlQuery
     * @return token array
     */
    public static function Tokenize($sqlQuery,$cleanWhitespace = true) {
        
        /**
         * Strip extra whitespace from the query
         */
        if($cleanWhitespace) {
         $sqlQuery = ltrim(preg_replace('/[\\s]{2,}/',' ',$sqlQuery));
        }
                
        /** 
         * Regular expression based on SQL::Tokenizer's Tokenizer.pm by Igor Sutton Lopes
         **/
        $regex = '('; # begin group
        $regex .= '(?:--|\\#)[\\ \\t\\S]*'; # inline comments
        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)'; # logical operators 
        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")'; # empty single/double quotes
        $regex .= '|".*?(?:(?:""){1,}"|(?<!["\\\\])"(?!")|\\\\"{2})|\'.*?(?:(?:\'\'){1,}\'|(?<![\'\\\\])\'(?!\')|\\\\\'{2})'; # quoted strings
        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/'; # c style comments
        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)'; # words, placeholders, database.table.column strings
        $regex .= '|[\t\ ]+'; 
        $regex .= '|[\.]'; #period
        
        $regex .= ')'; # end group
        
        // get global match
        preg_match_all( '/' . $regex . '/smx', $sqlQuery, $result );
        
        // return tokens
        return $result[0];
    
    }

    /**
     * Simple SQL Parser
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @param string $sqlQuery
     * @param bool optional $cleanup
     * @return SqlParser Object
     */
    public function ParseString($sqlQuery) {

        // copy and cut the query
        $tokens = Sqlparser::Tokenize( $sqlQuery );
        $tokenCount = count( $tokens );
        $queryParts = array();
        $section = $tokens[0];
        
        // parse the tokens
        for ($t = 0; $t < $tokenCount; $t ++) {
            
            if (in_array( $tokens[$t], Sqlparser::$startparens )) {
                
                $sub = $this->readsub( $tokens, $t );
                $this->query[$section].= $sub;
                
            } else {
                
                if(in_array(strtolower($tokens[$t]),Sqlparser::$querysections) && !isset($this->query[$tokens[$t]])) {
                    $section = strtolower($tokens[$t]);
                }
                
                // rebuild the query in sections
                if ($this->query[$section]=='' ) $this->query[$section] = '';
                $this->query[$section] .= $tokens[$t];   
                
            }                       
             
        }

        return TRUE;
    }
   
     /**
     * Parses a section of a query ( usually a sub-query or where clause )
     *
     * @param array $tokens
     * @param int $position
     * @return string section
     */
    private function readsub($tokens, &$position) {
        
        $sub = $tokens[$position];
        $tokenCount = count( $tokens );
        $position ++;
        while ( ! in_array( $tokens[$position], self::$endparens ) && $position < $tokenCount ) {
            
            if (in_array( $tokens[$position], self::$startparens )) {
                $sub.= $this->readsub( $tokens, &$position );
                $subs++;
            } else {
                $sub.= $tokens[$position];
            }
            $position ++;
        }
        $sub.= $tokens[$position];       
        return $sub;
    }
        
        
    /**
     * Returns manipulated sql to get the number of rows in the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function getCountQuery() {
        
        $this->query['select'] = 'select count(*) as `count` ';
        unset($this->query['limit']);
        #die(implode('',$this->query));
        return implode('',$this->query);
        
    }
    
    /**
     * Returns manipulated sql to get the unlimited number of rows in the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function getLimitedCountQuery() {
        
        $this->query['select'] = 'select count(*) as `count` ';
        return implode('',$this->query);
        
    }
    
    /**
     * Returns the select section of the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    
    public function hasError()
    {
    	$select = $this->get('select');
    	
    	if(empty($select))
    	{
    		$this->error = 'You have not specified any items to load in your '.$this->tags_used['select'].' statement (use * for all types of item)';
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    public function getError()
    {
    	return $this->error;
    }
    
    public function getTokenStatement($token)
    {
    	$token_array = array($token);
    	
    	$tokens = Sqlparser::$allowed_tokens[$token];
    	
    	if(isset($tokens))
    	{
    		$token_array = $tokens;
    	}
    
    	foreach ($token_array as $checktoken)
    	{
    		if(!empty($this->query[$checktoken]))
    		{
    			$this->tags_used[$token] = $checktoken;
    			
    			$ret = $this->query[$checktoken];
    			
    			if(preg_match('/'.$checktoken.'[\s\n]+(.*)/i', $ret, $matches))
       			{
        			$ret = $matches[1];
        		}
        		
        		$ret = preg_replace('/^\s+/', '', $ret);
        		$ret = preg_replace('/\s+$/', '', $ret);
        		
        		if(Sqlparser::$strip_quotes[$token])
        		{
        			$ret = preg_replace('/^[\"\']/', '', $ret);	
        			$ret = preg_replace('/[\"\']$/', '', $ret);
        		}
    			
    			return $ret;	
    		}
    	}
    	
    	return NULL;
    }
    

    /**
     * Returns the where section of the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function get($which)
    {
    	    
        return $this->getTokenStatement($which);
        
    }
          
    /**
     * Returns the where section of the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function getArray( ) {
        
        return $this->query;
        
    }
}
?> 