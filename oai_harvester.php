<?php 

/**
 * Harvest a single batch of records from the OAI endpoint at $provider_url and call 
 * the provided callback function with the full XML of each record.
 * 
 * @param string $provider_url base URL of the OAI endpoint
 * @param array $oai_params additional parameters for the ListRecords 
 *                 verb, like <code>from</code>, <code>metadataPrefix</code>, etc.
 * @param string $record_callback name of the callback function
 * @param array $resumptionToken optional parameter, if not null: resume harvesting
 *             using this resumptionToken
 * 
 * @return resumptionToken info containing keys <code>expirationDate</code>, 
 *          </code>cursor</code> and <code>resumptionToken</code>, 
 *          or <code>null</code> if no resumptionToken present
 * 
 * @author Martijn Vogten
 */
function harvest($provider_url, $oai_params, $record_callback, $resumptionToken = null) {
  $reader = new XMLReader();
  
  if ($resumptionToken) {
    $url = $provider_url . "?verb=ListRecords&resumptionToken=" . rawurlencode($resumptionToken["resumptionToken"]);
  } else {
    $paramstr = "&";
    foreach($oai_params as $key => $val) {
      $paramstr .= $key . "=" . rawurlencode($val) . "&";
    }
    $url = $provider_url . "?verb=ListRecords" . $paramstr;
  }
  
  if (!$reader->open($url)) throw new Exception("Could not open URL: " . $url);
  
  while($reader->read() && $reader->name !== 'record');
  
  while($reader->name === 'record') {
    $xml = $reader->readOuterXml();
    if (!$xml) throw new Exception("readOuterXml failed: " . $url);

    $record_callback($xml);
    
    // Skip to the next element, ignore whitespace
    do {
       if (!$reader->next()) throw new Exception("reader->next() failed: " . $url);
    } while ($reader->nodeType !== XMLReader::ELEMENT);
  }
  
  $resumptionToken = null;
  if ($reader->name === "resumptionToken") {
    $resumptionToken = parseResumptionToken($reader->readOuterXml());
  }
  
  $reader->close();
  
  return $resumptionToken;
}

/**
 * Harvest all records from the OAI endpoint at $provider_url and call 
 * the provided callback function with the full XML of each record.
 * 
 * 
 * @param string $provider_url base URL of the OAI endpoint
 * @param array $oai_params additional parameters for the ListRecords 
 *                 verb, like <code>from</code>, <code>metadataPrefix</code>, etc.
 * @param string $record_callback name of the callback function
 * 
 * @return nothing
 * 
 * @author Martijn Vogten
 */
function harvest_all($provider_url, $oai_params, $record_callback) {
  $resumptionToken = null;
  do {
    $resumptionToken = harvest($provider_url, $oai_params, $record_callback, $resumptionToken);
  } while ($resumptionToken);
}

// Creates a PHP array containing resumptionToken info
function parseResumptionToken($xml) {
  $resumption_xml = new SimpleXMLElement($xml);
  $resumptionToken = array(
      "expirationDate" => (string)$resumption_xml['expirationDate'],
      "cursor" => (string)$resumption_xml['cursor'],
      "resumptionToken" => (string)$resumption_xml
  );
  return $resumptionToken;
}

