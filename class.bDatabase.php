<?php

/*
 * Copyright (C) 2015 bened_000
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of class
 *
 * @author bened_000
 */
class database {

    public $PDO;
    public $STMT;
    public $debug;
    public $lastCommand;
    Public $commands; //= array('0' => array('STATEMENT'=>false, 'BIND' => false, 'PARAMETER'=>false ,'TIME' => false , 'ERROR' => false,'DATA' => false , 'ROWS'=>false) );

    
    /**
     * Create a Database Connection
     * @param type $dsn   -> Optional server and DB DNS
     * @param type $username -> Optional db username Username
     * @param type $passwd -> Optional db Password
     * @param type $pdo -> ready PDO Connection
     * @param type $debug -> debug class 
     */
    function __construct($dsn = false, $username = false, $passwd = false, $pdo = false, $debug = false) {

        //echo ' DATABASE CONNECTION CREATE ' . __METHOD__;

        $this->setDebug($debug);
        $this->addDebugLine('Create DatabaseObject', 1);
        if (is_a($pdo, 'pdo')) {
            $this->PDO = $pdo;
        } else {
            try {

                $this->PDO = new PDO($dsn, $username, $passwd);
                $this->getErrorPDO();
            } catch (Exception $ex) {
                // var_dump($ex);
                $this->addDebugLine($ex, -1);
            }
        }
    }

    /*
      function setDebug($debug = false) {

      if (is_a($debug, 'debug')) {
      $this->debug = $debug;
      } else {

      $this->debug = new debug();

      }
      }
     */

    /**
     *  Debug Code
     * @param type $message
     * @param type $code
     */
    protected function addDebugLine($message, $code = 10) {

        /* $this->debug->addDebugLine($message, $code, 'database'); */

        $this->debug[] = $message;
        if ($code < 0){
            var_dump($message);
            die();
        }
    }
   
    
    
    /**
     * 
     */
    function getErrorPDO() {
        if (($this->PDO->errorCode() != '00000') or ( is_null($this->PDO->errorCode()) != null)) {
            $infoArray = $this->PDO->errorInfo();
            $infoTable = basefunctions::getArrayTable($infoArray);
            var_dump($this->PDO->errorCode());
            $this->addDebugLine('Error On PDO ->' . $infoTable, -1);
        }
    }
    /**
     * 
     * @param type $exit
     * @return type
     */
    private function getErrorSTMT($exit = true) {
        if (($this->STMT->errorCode() != '00000')) {
            $infoArray = $this->STMT->errorInfo();
            $infoTable = basefunctions::getArrayTable($infoArray);

            if ($exit) {
                $errorcode = -1;
            } else {
                $errorcode = 1;
            }

            $this->addDebugLine('Error On STMT -> ' . $infoTable, $errorcode);
        }
        return $this->STMT->errorCode();
    }

    
    /**
     *  returns a Statemet from the selectet Database
     * @param type $statement 
     *              Obj sqlstatementbuild
     *            or array ['STATEMENT'] = 'select * from xxx'
     *                     ['BIND'] = array($bindkey => $bindvar )
     *                     ['PARAMETER'] = array ($PARAMETER_0, $PARAMETER_1)
     * 
     * @return array ('TIME' => $time
            , 'DATA' => $data
            , 'ROWS' => count($data)
            , 'STATEMENT' => $statement
            , 'BIND' => $bind
            , 'PARAMETER' => $parameter ) 
     */
    
    function execute($statement) {
        $time_start = microtime(true);
        if (!is_a($this->PDO, 'PDO')) {
            $this->addDebugLine('ERROR IN EXECUT PDO IS NOT A PDO OBJECT', -1);
        }

        $query = '';
        $binds = false;
        $parameter = false;

        /* Seperate Statement Data */
        if (is_a($statement, 'sqlStatementBuilder')) {
            $query = $statement->getStatement();
            $bind = $statement->getBinds();
            $parameter = $statement->getParameters();
        } elseif (is_array($statement) && isset($statement['STATEMENT'])) {
            $query = $statement['STATEMENT'];
            if (isset($statement['BIND'])) {
                $bind = $statement['BIND'];
            }

            if (isset($statement['PARAMETER'])) {
                $parameter = $statement['PARAMETER'];
            }
        } elseif (is_string($statement)) {
            $query = $statement;
        }

        $this->STMT = $this->PDO->prepare($query);

        if (is_array($binds)) {
            foreach ($binds as $bind => $val) {
                $this->STMT->bindParam($bind, $val);
            }
        }


        try {
            if (is_array($parameter)) {

                $this->STMT->execute($parameter);
            } else {
                $this->STMT->execute();
            }
            $data = $this->STMT->fetchAll(pdo::FETCH_ASSOC);
        } catch (Exception $ex) {
            $data = false;
            var_dump($ex);
        }

        $this->getErrorSTMT(false);

        // Time Messaure
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $command = array('TIME' => $time
            , 'DATA' => $data
            , 'ROWS' => count($data)
            , 'STATEMENT' => $statement
            , 'BIND' => $bind
            , 'PARAMETER' => $parameter
        );

        $this->commands[] = $command;

        return $command;
    }

}



class basefunctions {

//put your code here


    const StringTypeAll = 'ALL';
    const StringTypeCharacter = 'CHARACTER';
    const StringTypeUmlaute = 'UMLAUTE';

    static function getCleanString($string, $type = 'ALL') {

        $type = strtoupper($type);
        $umlaute = array('ä'
            , 'Ä'
            , 'Ü'
            , 'ü'
            , 'Ö'
            , 'ö');
        $character = array('+'
            , '*'
            , '-'
            , '/'
            , '\\'
            , '?'
            , '!'
            , '"'
            , '§'
            , '$'
            , '%'
            , '&'
            , '('
            , ')'
            , '='
            , '`'
            , '@'
            , '€'
            , '°'
            , '^'
            , '²'
            , '³'
            , ']'
            , '['
            , '{'
            , '}'
            , '['
            , ','
            , '.'
            , '_'
            , ';'
            , ':'
            , ' ');


        switch ($type) {
            case 'ALL':

                $search = array_merge($umlaute, $character);
                break;
            case 'CHARACTER':
                $search = $character;
                break;
            case 'UMLAUTE':
                $search = $umlaute;
                break;
            default:
                $search = array_push($umlaute, $character);
                break;
        }

        // create an Array with all Empty Charecter
        foreach ($search as $key) {
            $replace[] = '';
        }

        $return = str_replace($search, $replace, $string);
        return $return;
    }

    static function getValidVar($value, $type) {

        $type = strtoupper($type);

        switch ($type) {
            case 'TEXT' :
                if (is_string($value)) {
                    return true;
                } else {
                    return false;
                }
                break;

            case 'NUMERIC' :
                if (is_numeric($value)) {
                    return true;
                } else {
                    return false;
                }
                break;

            case 'REAL' :
                if (is_real($value)) {
                    return true;
                } else {
                    return false;
                }

                break;

            case 'BOOL' :
                if (is_bool($value)) {
                    return true;
                } else {
                    return false;
                }

                break;



            case 'HOURE' :
                if (is_numeric($value)) {
                    return true;
                } else {
                    return false;
                }

                break;

            case 'MONEY' :

                if (is_numeric($value)) {
                    return true;
                } else {
                    return false;
                }
                break;
            default :
                return true;
                break;

            /* case 'DATETIME' :

              if (($value)) {
              return true;
              } else {
              return false;
              }
              break;

              case 'DATETIME' :
              if (is_bool($value)) {
              return true;
              } else {
              return false;
              }
              break;
              case 'DATE' :
              if (is_bool($value)) {
              return true;
              } else {
              return false;
              }

              break;

              case 'TIME' :
              if (is_bool($value)) {
              return true;
              } else {
              return false;
              }

              break; */
        }
    }

    static function getArrayTable($dataArray, $maxDim = 5, $dim = 0) {
        $return = '<table>';

        if (is_array($dataArray)) {
            foreach ($dataArray as $row => $data) {
                $return .= '<tr><td>';
                if (is_array($data)) {

                    $return .= basefunctions::getArrayTable($data, $maxDim, $dim ++);
                } else {
                    $return .= $row . ' >> ' . $data;
                }
            }
            $return .= '</td></tr>';
        }
        $return .= '</table>';
        return $return;
    }

}


/*
$rand = rand('1', '2000');

$stmt = new sqlStatementBuilder('INSERT', 'BIND');

$stmt->setTableOrProc('testdb');
$stmt->addColumn('name', false, 'Name' . $rand);
$stmt->addColumn('besch', false, 'Beschreibung ' . $rand);
$statement = $stmt->buildStatement();
*/

$statement = array ('STATEMENT' => 'select * from xxx where id = :var1 ;'
                   ,'BIND' => array(':var1' => '1')
                   ,'PARAMETER' => false);

$db = new database('mysql:host=localhost;dbname=test', 'root', '');

$execute = $db->execute($statement);
var_dump($execute);