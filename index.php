<?php 

require "oai_harvester.php";

$counter = 0;

function processRecord($xml) {
  global $counter;
//   if ($counter++ > 100) {
//     die;
//   }
  $record = new SimpleXMLElement($xml);
  $identifier = $record->header->identifier;
  $datestamp = $record->header->datestamp;
  $setspec = $record->header->setSpec;
  
  
//   echo "<h1>" . htmlspecialchars($identifier) . "</h1>";
//   echo "<p>" . htmlspecialchars($datestamp) . "</p>";
  
  // Find OAI dublin core records
  $props = array(
        "title" => null,
        "description" => null
      );
  $recordXml = $record->metadata->record;

  foreach($recordXml->children('http://purl.org/dc/elements/1.1/') as $prop) {
     $props[$prop->getName()] = (string)$prop;
  }

  mysql_query(
      "UPDATE oai_harvest SET" .
      " `type`='" . mysql_real_escape_string($props['type']) . "'," .
      " setspec='" . mysql_real_escape_string($setspec) . "'," .
      " title='" . mysql_real_escape_string($props['title']) . "'," . 
      " description='" . mysql_real_escape_string($props['description']) . "'," . 
      " recordxml='" . mysql_real_escape_string($xml) . "'," . 
      " datestamp='" . mysql_real_escape_string($datestamp) . "'" . 
      " WHERE identifier = '" . mysql_real_escape_string($identifier) . "'"
      );
  echo mysql_error();
  if (mysql_affected_rows() === 0) {
      mysql_query(
          "INSERT INTO oai_harvest (identifier, `type`, setspec, datestamp, recordxml, title, description) VALUES (" .
          "'" . mysql_real_escape_string($identifier) . "'," .
          "'" . mysql_real_escape_string($props['type']) . "', " .
          "'" . mysql_real_escape_string($setspec) . "', " .
          "'" . mysql_real_escape_string($datestamp) . "'," . 
          "'" . mysql_real_escape_string($xml) . "'," . 
          "'" . mysql_real_escape_string($props['title']) . "'," . 
          "'" . mysql_real_escape_string($props['description']) . "')");
    echo mysql_error();
  }
  
  echo ".";
}

mysql_connect("localhost", "root", "root");
mysql_select_db("communityhub");

function initDB() {
  mysql_query(<<<SQL
      CREATE TABLE IF NOT EXISTS `oai_harvest` (
        `identifier` CHAR(255) NOT NULL,
        `type` CHAR(255) NOT NULL,
        `setspec` CHAR(255) NOT NULL,
        `datestamp` DATETIME,
        `recordxml` LONGTEXT,
        `title` LONGTEXT,
        `description` LONGTEXT,
        PRIMARY KEY (`identifier`),
        KEY `type` (`type`))
        ENGINE=InnoDb DEFAULT CHARSET=utf8
SQL
  );
}

initDB();

// harvest_all("http://oai.555systems.nl/oai/", array("metadataPrefix" => "ese", "set" => "WFM"), "processRecord");
harvest_all("http://oai.555systems.nl/oai/", array("metadataPrefix" => "ese"), "processRecord");
