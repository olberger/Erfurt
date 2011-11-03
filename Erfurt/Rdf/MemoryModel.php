<?php
/**
 * This file is part of the {@link http://erfurt-framework.org Erfurt} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * A set of Statements (memory model) / ARC2 index / phprdf array
 *
 * @author {@link http://sebastian.tramp.name Sebastian Tramp}
 * @author {Jonas Brekle <jonas.brekle@gmail.com>}
 */
class Erfurt_Rdf_MemoryModel
{
    protected $statements = array();

    /*
     * model can be constructed with a given array
     */
    function __construct( array $init = array())
    {
        $this->addStatements($init);
    }

    /*
     * checks if there is at least one statement for resource $iri
     */
    public function hasS($s)
    {
        if ($s === null) {
            throw new Exception('need an IRI string as first parameter');
        }
        if (isset($this->statements[$s])) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * checks if there is at least one statement for resource $iri with
     * predicate $p
     */
    public function hasSP($s, $p)
    {
        if (!$this->hasS($s)) {
            return false;
        } else {
            if ($p == null) {
                throw new Exception('need an IRI string as second parameter');
            }
            if (isset($this->statements[$s][$p])) {
                return true;
            } else {
                return false;
            }
        }
    }

    /*
     * search for a value where S and P is fix
     */
    public function hasSPvalue($s, $p, $value)
    {
        if ($value == null) {
            throw new Exception('need a value string as third parameter');
        } else {
            $values = $this->getValues($s, $p);
            foreach ($values as $key => $object) {
                if ($object['value'] == $value) {
                    return true;
                }
            }
            return false;
        }
    }

    /*
     * count statements where S and P is fix
     */
    public function countSP($s, $p)
    {
        if (!$this->hasSP($s, $p)) {
            return 0;
        } else {
            return count($this->statements[$s][$p]);
        }
    }

    /*
     * returns an array of values where S and P is fix
     */
    public function getPO($s)
    {
        if (!$this->hasS($s)) {
            return array();
        } else {
            return $this->statements[$s];
        }
    }

    /*
     * returns an array of values where S and P is fix
     */
    public function getValues($s, $p)
    {
        if (!$this->hasSP($s, $p)) {
            return array();
        } else {
            return $this->statements[$s][$p];
        }
    }

    /*
     * returns the first object value where S and P is fix
     */
    public function getValue($s, $p)
    {
        if (!$this->hasSP($s, $p)) {
            return null;
        } else {
            return $this->statements[$s][$p][0]['value'];
        }
    }

    /*
     * return the statement array, limited to a subject uri
     */
    public function getStatements($iri = null)
    {
        if ($iri == null) {
            return $this->statements;
        } else {
            if ($this->hasS($iri)) {
                return array( $iri => $this->statements[$iri] );
            } else {
                return array();
            }
        }
    }
    
    /*
     * This adds a statement array to the model by merging the arrays
     * This function is the base for all other add functions
     */
    public function addStatements(array $statements)
    {
        $model = $this->statements;
        foreach ($statements as $subjectIri => $subjectArray) {
            if (!isset($model[$subjectIri])) {
                // new subject
                $model[$subjectIri] = $subjectArray;
            } else {
                // existing subject
                foreach ($subjectArray as $predicateIri => $predicateArray) {
                    if (!isset($model[$subjectIri][$predicateIri])) {
                        // new predicate on subject
                        $model[$subjectIri][$predicateIri] = $predicateArray;
                    } else {
                        // existing predicate on subject
                        foreach ($predicateArray as $objectArray) {
                            if (!in_array($objectArray, $model[$subjectIri][$predicateIri])) {
                                // new object for subject/predicate pattern
                                $model[$subjectIri][$predicateIri][] = $objectArray;
                            } else {
                                // same triple
                            }
                        }
                    }
                }
            }
        }
        $this->statements = $model;
    }

    /*
     * adds multiple triples coming from the result of an extended SPARQL query
     */
    public function addStatementsFromSPOQuery(array $res)
    {
        foreach($res['bindings'] as $binding){
            $this->addStatementFromExtendedFormatArray($binding['s'], $binding['p'], $binding['o']);
        }
    }
    /*
     * adds a triple based on the result of an extended SPARQL query
     */
    public function addStatementFromExtendedFormatArray(array $s, array $p, array $o)
    {
        $typeO = $o['type'];
        $object = array();
        $object['value'] = $o['value'];
        switch ($typeO) {
            case 'uri':
                $object['type'] = 'uri';
                break;
            case 'typed-literal':
                $object['type'] = 'literal';
                $object['datatype'] = $o['datatype'];
                break;
            case 'literal':
                $object['type'] = 'literal';
                if (isset($o['xml:lang'])) {
                    $object['lang'] = $o['xml:lang'];
                }
                break;
            //TODO i added bnode, why was it skipped? 
            //btw: the way it was skipped just caused the 'type' field to be missing...
            case 'bnode':
                $object['type'] = 'bnode';
                break;
            default:
                return; // correct way to skip unwanted types
                break;
        }

        $statement = array();
        $s = $s['value']; // is always an IRI (or bnode)
        $p = $p['value']; // is always an IRI

        $pArray[$p] = array(0 => $object);
        $statement[$s] = $pArray;

        $this->addStatements($statement);
    }

    /*
     * add a single statement where the object is a literal
     *
     * @param string $subject   - the statement subject URI string
     * @param string $predicate - the statement predicate URI string
     * @param string $literal   - the literal value string
     * @param string $lang      - the optional xml:lang identifier string
     * @param string $datatype  - the optional datatype URI string
     */
    public function addAttribute($subject, $predicate, $literal = "", $lang = null, $datatype = null)
    {
        if ($subject == null) {
            throw new Exception('need a subject URI as first parameter');
        } else if ($predicate == null) {
            throw new Exception('need a predicate URI as second parameter');
        }
        $newStatements = array();

        // create the object array
        $o = array();
        $o['type'] = 'literal';
        $o['value'] = $literal;
        if (is_string($lang)) {
            $o['lang'] = $lang;
        } else if (is_string($datatype)) {
            $o['datatype'] = $datatype;
        }

        // fill object array into predicate array
        $p =  array();
        $p[$predicate] = array();
        $p[$predicate][] = $o;

        // fill the predicate array into the statements array
        $statements[$subject] = $p;
        // add the statements array to the model
        $this->addStatements($statements);
    }

    /*
     * add a single statement where the object is a resource
     *
     * @param string $subject  - the statement subject URI string
     * @param string $relation - the statement predicate URI string
     * @param string $object   - the statement object URI string
     */
    public function addRelation($subject, $relation, $object = null)
    {
        if ($subject == null) {
            throw new Exception('need a subject URI as first parameter');
        } else if ($relation == null) {
            throw new Exception('need a predicate URI as second parameter');
        } else if ($object == null) {
            throw new Exception('need an object URI as second parameter');
        }

        $newStatements = array();

        // create the object array
        $o = array();
        $o['type'] = 'uri';
        $o['value'] = $object;

        // fill object array into predicate array
        $p =  array();
        $p[$relation] = array();
        $p[$relation][] = $o;

        // fill the predicate array into the statements array
        $statements[$subject] = $p;

        // add the statements array to the model
        $this->addStatements($statements);
    }
    
    public function removeS($subject)
    {
        if (isset($this->statements[$subject])) {
            unset($this->statements[$subject]);
        }
    }
    
    /**
     *removes a predicate p (and its values) of a subject s
     * @param type $subject
     * @param type $predicate 
     */
    public function removePredicateOf($subject, $predicate)
    {
        if (isset($this->statements[$subject]) && isset($this->statements[$subject][$predicate])) {
            unset($this->statements[$subject][$predicate]);
            
            //check if this was the last
            if(count($this->statements[$subject]) == 0){
                unset($this->statements[$subject]);
            }
        }
    }
    
    public function getSubjects()
    {
        return array_keys($this->statements);
    }
}