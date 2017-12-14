<?php
$term = $_GET['suggestions_for'];
	
$url = "http://localhost:8983/solr/irhw4/suggest?q=".urlencode($term)."&wt=json&indent=true";
				
$json_info = file_get_contents($url);

echo $json_info;

$js = json_decode($json_info, true);
            
?>
