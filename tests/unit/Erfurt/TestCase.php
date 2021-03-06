<?php
class Erfurt_TestCase extends PHPUnit_Framework_TestCase
{
    private $_dbWasUsed      = false;
    private $_testConfig     = null;
    
    protected function tearDown()
    {
        // If test case used the database, we delete all models in order to clean up th environment
        if ($this->_dbWasUsed) {
            $this->authenticateDbUser();
            $store = Erfurt_App::getInstance()->getStore();
            $config = Erfurt_App::getInstance()->getConfig();

            foreach ($store->getAvailableModels(true) as $graphUri => $true) {
                if ($graphUri !== $config->sysont->schemaUri && $graphUri !== $config->sysont->modelUri) {
                    $store->deleteModel($graphUri);
                }              
            }
            
            // Delete system models after all other models are deleted.
            $store->deleteModel($config->sysont->modelUri);
            $store->deleteModel($config->sysont->schemaUri);
            
            $this->_dbWasUsed = false;
        }
        
        $this->_testConfig = null; // force reload on each test e.g. because of db params
        Erfurt_App::reset();
    }
    
    public function authenticateAnonymous()
    {
        Erfurt_App::getInstance()->authenticate();
    }
    
    public function authenticateDbUser()
    {
        $store = Erfurt_App::getInstance()->getStore();
        $dbUser = $store->getDbUser();
        $dbPass = $store->getDbPassword();
        Erfurt_App::getInstance()->authenticate($dbUser, $dbPass);
    }
    
    public function getDbUser()
    {
        $store = Erfurt_App::getInstance()->getStore();
        return $store->getDbUser();
    }
    
    public function getDbPassword()
    {
        $store = Erfurt_App::getInstance()->getStore();
        return $store->getDbPassword();
    }
    
    public function markTestNeedsDatabase()
    {
        $this->markTestNeedsTestConfig();

        $dbName = null;
        if ($this->_testConfig->store->backend === 'virtuoso') {
            if (isset($this->_testConfig->store->virtuoso->dsn)) {
                 $dbName = $this->_testConfig->store->virtuoso->dsn;
            }
        } else if ($this->_testConfig->store->backend === 'zenddb') {
            if (isset($this->_testConfig->store->zenddb->dbname)) {
                $dbName = $this->_testConfig->store->zenddb->dbname;
            }
        }

        if ((null === $dbName) || (substr($dbName, -5) !== '_TEST')) {
            $this->markTestSkipped(); // make sure a test db was selected!
        }

        try {
            $store = Erfurt_App::getInstance()->getStore();
            $store->checkSetup();
            $this->_dbWasUsed = true;
        } catch (Erfurt_Store_Exception $e) {
            if ($e->getCode() === 20) {
                // Setup successfull
                $this->_dbWasUsed = true;
            } else {
                $this->markTestSkipped();
            }
        } catch (Erfurt_Exception $e2) {
            $this->markTestSkipped();
        }

        $config = Erfurt_App::getInstance()->getConfig();

        $this->assertTrue(Erfurt_App::getInstance()->getStore()->isModelAvailable($config->sysont->modelUri, false));
        $this->assertTrue(Erfurt_App::getInstance()->getStore()->isModelAvailable($config->sysont->schemaUri, false));
        
        $this->authenticateAnonymous();
    }
    
    public function markTestNeedsCleanZendDbDatabase()
    {
        $this->markTestNeedsZendDb();
        
        $store = Erfurt_App::getInstance()->getStore();
        $sql = 'DROP TABLE IF EXISTS ' . implode(',', $store->listTables()) . ';';
        $store->sqlQuery($sql);
        
        // We do not clean up the db on tear down, for it is empty now.
        $this->_dbWasUsed = false;
        Erfurt_App::reset();
        
        $this->_loadTestConfig();
    }
    
    public function markTestUsesDb()
    {
        $this->_dbWasUsed = true;
    }
    
    public function markTestNeedsTestConfig()
    {
        $this->_loadTestConfig();

        if ($this->_testConfig === false) {
            $this->markTestSkipped();
        }
    }
    
    public function getTestConfig()
    {
        return $this->_testConfig;
    }
    
    public function markTestNeedsVirtuoso()
    {
        $this->markTestNeedsTestConfig();
        $this->_testConfig->store->backend = 'virtuoso';
        $this->markTestNeedsDatabase();
    }
    
    public function markTestNeedsZendDb()
    {
        $this->markTestNeedsTestConfig();
        $this->_testConfig->store->backend = 'zenddb';
        $this->markTestNeedsDatabase();
    }
    
    private function _loadTestConfig()
    {
        if (null === $this->_testConfig) {
            if (is_readable(_TESTROOT . 'config.ini')) {
                $this->_testConfig = new Zend_Config_Ini((_TESTROOT . 'config.ini'), 'private', array( 'allowModifications' =>true));
            } else if (is_readable(_TESTROOT . 'config.ini.dist')) {
                $this->_testConfig = new Zend_Config_Ini((_TESTROOT . 'config.ini.dist'), 'private', array( 'allowModifications' =>true));
            } else {
                $this->_testConfig = false;
            }
        }

        $app = Erfurt_App::getInstance(false);

        // We always reload the config in Erfurt, for a test may have changed values 
        // and we need a clean environment.
        if ($this->_testConfig !== false) {
            $app->loadConfig($this->_testConfig);
        } else {
            $app->loadConfig();
        }

        // Disable versioning
        $app->getVersioning()->enableVersioning(false);

        // For tests we have no session!
        $auth = Erfurt_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_NonPersistent());
        $app->setAuth($auth);
    }
}
