<?php

/*
 * This is an example application managing Linked Data
 * 
 * This is meant to illustrate how to use Erfurt to construct a Linked Data processing application
 * 
 */

/**
 * This file is part of the {@link http://aksw.org/Projects/Erfurt Erfurt} project.
 *
 * @copyright Copyright (c) 2012 Olivier Berger and Institut Mines-Telecom
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

// Reuse the same content as indicated in the README : standard Erfurt initialisation

$main_dir = rtrim(dirname(__FILE__), '/\\');

# Set include paths
$includePath  = get_include_path() . PATH_SEPARATOR;

$includePath .= $main_dir . '/Erfurt/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/Erfurt/Erfurt/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/Zend/'. PATH_SEPARATOR;

set_include_path($includePath);

# Include Zend Autoloader
require_once 'Zend/Loader/Autoloader.php';

# Configure Zend Autoloader
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Erfurt_');

# Creating an instance of Erfurt API
$app = Erfurt_App::getInstance();

// Add some logging message (check log config.ini settings to be sure it appears in your logs)
$logger = Erfurt_App::getInstance()->getLog();
$logger->info('Example application started!');

# Authentification on Erfurt (needed for model access)
$dbUser = $app->getStore()->getDbUser();
$dbPass = $app->getStore()->getDbPassword();
$app->authenticate($dbUser, $dbPass);


// Load Henry Story's FAOF profile into a model named after the document's URL
$modelUri = 'http://bblfish.net/people/henry/card';
$foafUri = $modelUri.'#me';

# Get a new model
try {
    # Create it if it doesn't exist
    $model = $app->getStore()->getNewModel($modelUri);
} catch (Erfurt_Store_Exception $e) {
    # Get it if it already exists
    $model = $app->getStore()->getModel($modelUri);
}

// Go fetch the data on the Web

// This could be used, but uses PHP's fopen, which may not be best for Linked Data consumption 
//$ret = $app->getStore()->importRdf($model, $modelUri, 'xml');
// This one will load the data using a HTTP request handled in Erfurt
$ret = $app->getStore()->importRdf($model, $modelUri, 'xml',Erfurt_Syntax_RdfParser::LOCATOR_URL);

// Now, process the loaded data using SPARQL queries

// Find Henry's name
$query = 'SELECT ?n WHERE {<'. $foafUri .'> foaf:name ?n.}';
$res = $model->sparqlQuery($query);

$name = $res[0]['n'];

echo '<b><a href="'. $foafUri .'">'. $name .'</a> knows:</b>';

// Now, find the names of Henry's contacts
$query = 'SELECT ?u ?n WHERE {<'. $foafUri .'> foaf:knows ?u. ?u foaf:name ?n.}';
$res = $model->sparqlQuery($query);

if(count($res) > 0) {
	echo '<ul>';
}
foreach ($res as $line) {
	$person_name = $line['n'];
	$person_foaf = $line['u'];
	echo '<li><a href="'. $person_foaf .'">'. $person_name .'</a></li>';
}
if(count($res) > 0) {
	echo '</ul>';
}


