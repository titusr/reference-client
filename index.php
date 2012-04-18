<?php 

require "oai_harvester.php";

function processRecord($xml) {
  $record = new SimpleXMLElement($xml);
  $identifier = $record->header->identifier;
  $datestamp = $record->header->datestamp;
  
//   echo "<h1>" . htmlspecialchars($identifier) . "</h1>";
//   echo "<p>" . htmlspecialchars($datestamp) . "</p>";
  
  // Find OAI dublin core records
  $props = array(
        "title" => null,
        "description" => null
      );
  foreach($record->metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/') as $dcrec) {
    foreach($dcrec->children('http://purl.org/dc/elements/1.1/') as $prop) {
     $props[$prop->getName()] = (string)$prop;
    }
  }
  
  mysql_query(
      "UPDATE oai_harvest SET" .
      " title='" . mysql_real_escape_string($props['title']) . "'," . 
      " description='" . mysql_real_escape_string($props['description']) . "'," . 
      " recordxml='" . mysql_real_escape_string($xml) . "'," . 
      " datestamp='" . mysql_real_escape_string($datestamp) . "'" . 
      " WHERE identifier = '" . mysql_real_escape_string($identifier) . "'"
      );
  echo mysql_error();
  if (mysql_affected_rows() === 0) {
      mysql_query(
          "INSERT INTO oai_harvest (identifier, datestamp, recordxml, title, description) VALUES (" .
          "'" . mysql_real_escape_string($identifier) . "'," . 
          "'" . mysql_real_escape_string($datestamp) . "'," . 
          "'" . mysql_real_escape_string($xml) . "'," . 
          "'" . mysql_real_escape_string($props['title']) . "'," . 
          "'" . mysql_real_escape_string($props['description']) . "')");
    echo mysql_error();
  }
  
  echo ".";
}

mysql_connect("localhost", "root", "");
mysql_select_db("oaitest");

function initDB() {
  mysql_query(<<<SQL
      CREATE TABLE IF NOT EXISTS `oai_harvest` (
        `identifier` CHAR(255) NOT NULL, 
        `datestamp` DATETIME,
        `recordxml` LONGTEXT,
        `title` LONGTEXT,
        `description` LONGTEXT,
        PRIMARY KEY (`identifier`)) ENGINE=InnoDb DEFAULT CHARSET=utf8
SQL
  );
}

initDB();

harvest_all("http://localhost:8010/fedora/oai", array("metadataPrefix" => "oai_dc"), "processRecord");
