<?php
include 'SpellCorrector.php'; 
include('simple_html_dom.php');

ini_set('memory_limit', '-1');

// make sure browsers see this page as utf-8 encoded HTML 
header('Content-Type: text/html; charset=utf-8'); 

$limit = 10; 
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false; 

$query_words_corrected = '';

if ($query) { 
    // The Apache Solr Client library should be on the include path 
    // which is usually most easily accomplished by placing in the 
    // same directory as this script ( . or current directory is a default 
    // php include path entry in the php.ini) 
    require_once('solr-php-client-master/Apache/Solr/Service.php'); 
        
    // Spell Check
    $query_words_corrected ='';
    $query_split = explode(" ", $query);
    foreach($query_split as $word) {
        $query_words_corrected = $query_words_corrected.' '.ucfirst(SpellCorrector::correct($word));  
    }
    $correct_query = strtolower(trim(implode(' ', explode(" ",$query_words_corrected))));
    
    // create a new solr service instance - host, port, and corename 
    // path (all defaults in this example) 
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/irhw4/'); 
    
    // if magic quotes is enabled then stripslashes will be needed 
    if (get_magic_quotes_gpc() == 1) { 
        $query = stripslashes($query); 
    } 
    
    // in production code you'll always want to use a try /catch for any 
    // possible exceptions emitted by searching (i.e. connection 
    // problems or a query parsing error)
    try { 
        $radio_selected = $_REQUEST['rankingtype'];
        
        if($_REQUEST['rankingtype']=="pagerank") {
            $param = array('sort' => 'pageRankFile desc');
            $results = $solr->search($query, 0, $limit, $param);
        }
        else {
            $results = $solr->search($query, 0, $limit);
        }
    } 
    catch (Exception $e) { 
        // in production you'd probably log or email this error to an admin 
        // and then show a special message to the user but for this example 
        // we're going to show the full exception 
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>"); 
    } 
} 
?>
<!-- HTML -->
<html> 
    <head> 
        <!-- Libraries -->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css"> 
        <title>PHP Solr Client Example</title> 
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>  
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">  
        <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>  
        <script src="http://code.jquery.com/jquery-2.1.1.min.js"></script>  
        <script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>  
        <script src="//code.jquery.com/jquery-1.10.2.js"></script>  
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>  
        
        <!-- JS -->
        <script type="text/javascript">
            function auto_complete() {  
                var query = $('input[name=q]').val().trim();
                var query_split = $('input[name=q]').val().toLowerCase().split(" ");  
                var query_backup = query_split;    
                var last_word = query_split.pop().toString(); 
                var query_minus_last_word = query_split.toString() + " ";
                var suggest_data = {'suggestions_for': last_word};  
                
                $("#q").autocomplete({  
                    source : function(request, response){
                        $.ajax({
                            url: 'auto_complete.php',
                            type: "get",
                            dataType: 'json',
                            data: suggest_data,
                            async: true,
                            cache: true,
                            success: function(data) {
                                result = JSON.parse(JSON.stringify(data));
                                var num_of_suggestions = JSON.stringify(result['suggest']['suggest'][last_word]['numFound']);  
                                var suggestions = new Array(); 

                                if(num_of_suggestions < 5) 
                                    count = num_of_suggestions;
                                else
                                    count = 5;
                                
                                for(i = 0; i < count; i++) {
                                    var sug = JSON.stringify(result['suggest']['suggest'][last_word]['suggestions'][i]['term']).replace(/['"',]+/g, ''); 
                                    suggestions.push(query_minus_last_word.replace(/,/g , " ") + sug);
                                }
                                response(suggestions);      
                            }
                        });
                    }
                });  
            }  
            
            
            function resubmit_form() {  
                document.getElementById("q").value = "<?php echo $correct_query; ?>".trim().toLowerCase(); document.getElementById("php_form").submit();  
            }  
            
            $().ready(function() {  
                var original_query = "<?php echo $query ?>".trim().toLowerCase();  
                var corrected_query = "<?php echo $correct_query ?>";  
                
                if(corrected_query!="" && original_query != corrected_query) {  
                    $("#spellCheck").css('visibility', 'visible');  
                }
            });  
        </script>
        
        <!-- CSS -->
        <style type="text/css">
            table {
                width: 100%;
            }
            .title_col {
                width: 10%;
            }
            input[type="radio"] {
                display: inline;
            }
        </style>
    </head> 
    <body> 
        <form id="php_form" accept-charset="utf-8" method="get"> 
            <label for="q">Search:</label> 
            <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?> " onkeyup="auto_complete();"/>
            <br>
            <div>
                <label for="rankingtype">Ranking Strategy:
                <input type="radio" name="rankingtype" value="lucene" <?php if(isset($_GET['rankingtype']) && $_GET['rankingtype'] == 'lucene')  echo ' checked="checked"';?> > Lucene Results
                <input type="radio" name="rankingtype" value="pagerank" <?php if(isset($_GET['rankingtype']) && $_GET['rankingtype'] == 'pagerank')  echo ' checked="checked"';?> > Page Rank Results
                </label>
            </div>
            <input type="submit"/> 
            
            <!-- Spelling Correction Div -->
            <div id="spellCheck" style="visibility:hidden">
                <span>Did you mean <a href="javascript:resubmit_form()"><?php echo $correct_query; ?></a>
                <br>
                </span>Search instead for <a href="javascript:resubmit_form()"><?php echo $query; ?></a>
            </div>
        </form> 
        
        <?php
        //load map.csv
        $csv_file = fopen('/Users/nehapathapati/Sites/Boston_Global_Map.csv', 'r');
        
        //Get text from HTML
        function get_nodes($node) {
            if ($node->isText()) {
                return $node->value;
            } 
            else if ($node->hasChildren()) {
                $child_value = '';
                foreach ($node->child as $child) {
                    $child_value .= get_nodes($child).'.';
                }
                return $child_value;
            }
            return '';
        }    
        
        //Get URLs of HTML files
        function getUrls() {
            $csv_file = fopen('/Users/nehapathapati/Sites/Boston_Global_Map.csv', 'r');
            while (($row = fgetcsv($csv_file, 0, ",")) !== FALSE) {
                $array[$row[0]] = trim($row[1]);
            }
            return $array;
        }
        
        
        //Generate Snippets
        function snippet_generate($snippet_candidates, $query, $doc_title, $url, $title) {
            $query_words = explode(' ', trim($query));
            $number_of_candidates = count($snippet_candidates);
            $number_of_query_words = count($query_words);
    
            //Check for sentence with all query words as a phrase
            for($i = 1; $i < $number_of_candidates; $i++) {
                if (stripos($snippet_candidates[$i], trim($query)) !== false) {
                    
                    $snippet = $snippet_candidates[$i];
                    $snippet_length = strlen($snippet_candidates[$i]);
                    
                    $match_position = stripos($snippet_candidates[$i], trim($query));  
                    $len_query = strlen(trim($query));
                  
                    if($snippet_length > 160) {    
                        $match_plus_len = (int)$match_position + (int)$len_query;
                        
                        if($match_plus_len <= 160)
                            return mb_substr($snippet_candidates[$i], 0, 160, 'utf-8');
                        
                        if($match_plus_len > 160) {
                            if($snippet_length - $match_plus_len > 160)
                                return mb_substr($snippet_candidates[$i], $match_position, 160, 'utf-8');
                            
                            if($snippet_length - $match_plus_len < 160) {
                                $remaining_chars = 160 - ($snippet_length - $match_position);
                                return mb_substr($snippet_candidates[$i], ($match_position - $remaining_chars), 160, 'utf-8');
                            }   
                        }
                        return substr($snippet_candidates[$i], stripos($snippet_candidates[$i], trim($query)), 160, 'utf-8');
                    }
                    else {
                        $diff = 160 - $snippet_length;
                        
                        if($i == 1) { //take next snippet
                            $next_snippet = $snippet_candidates[$i+1];
            
                            if(strlen($next_snippet) <= $diff) 
                                return $snippet_candidates[$i]."...".$next_snippet;
                            else 
                                return $snippet_candidates[$i]."...".mb_substr($next_snippet, 0, $diff, 'utf-8');
                        }
                        else if($i == count($snippet_candidates) - 1) { //take prev snippet
                            $prev_snippet = $snippet_candidates[$i-1];
            
                            if(strlen($prev_snippet) <= $diff) {
                                return $prev_snippet."...".$snippet_candidates[$i];
                            }
                            else {
                                return $snippet_candidates[$i]."...".mb_substr($next_snippet, $diff, strlen($prev_snippet), 'utf-8');
                            }
                        }
                        else { //take next and prev snippet
                            $next_snippet = $snippet_candidates[$i+1];
                            $prev_snippet = $snippet_candidates[$i-1];
                            
                            if(strlen($next_snippet) <= $diff)
                                return $snippet_candidates[$i]."...".mb_substr($next_snippet, 0, strlen($next_snippet), 'utf-8');
                            else if(strlen($prev_snippet) <= $diff)
                                return mb_substr($prev_snippet, 0, strlen($prev_snippet), 'utf-8')."...".$snippet;
                            else
                                return $snippet_candidates[$i]."...".mb_substr($next_snippet, 0, $diff, 'utf-8');  
                        }
                    }
                    return $snippet_candidates[$i];
                }
            }
            
            
            //Check for sentence with all query words
            for($i = 1; $i < $number_of_candidates; $i++) {

                $all_query_words_present = true;
                
                for ($j = 0; $j < $number_of_query_words; $j++) {
                    if (stripos($snippet_candidates[$i], $query_words[$j]) === false){
                        $all_query_words_present = false;
                        break;
                    }
                }
                if($all_query_words_present == true) {
                    $snippet = $snippet_candidates[$i];
                    if(strlen($snippet) > 160) {
                        return substr($snippet, stripos($snippet, $query_words[0]), 160);
                    }
                    else {
                        $diff = 160 - $snippet_length;
                        
                        if($i == 1) { //take next snippet
                            $next_snippet = $snippet_candidates[$i+1];
    
                            if(strlen($next_snippet) <= $diff) 
                                return $snippet."...".$next_snippet;
                            else 
                                return $snippet_candidates[$i]."...".mb_substr($next_snippet, 0, $diff, 'utf-8');
                        }
                        else if($i == count($snippet_candidates) - 1) { //take prev snippet
                            $prev_snippet = $snippet_candidates[$i-1];
                            if(strlen($prev_snippet) <= $diff) {
                                return $prev_snippet."...".$snippet;
                            }
                            else {
                                return $snippet."...".mb_substr($prev_snippet, $diff, strlen($prev_snippet), 'utf-8');
                            }
                        }
                        else {  // prev or next
                            $next_snippet = $snippet_candidates[$i+1];
                            $prev_snippet = $snippet_candidates[$i-1];
                            
                            if(strlen($next_snippet) <= $diff)
                                return $snippet."...".mb_substr($next_snippet, 0, strlen($next_snippet), 'utf-8');
                            else if(strlen($prev_snippet) <= $diff)
                                return mb_substr($prev_snippet, 0, strlen($prev_snippet), 'utf-8')."...".$snippet;
                            else
                                return $snippet."...".mb_substr($next_snippet, 0, $diff, 'utf-8');  
                        }
                    }
                    return $snippet_candidates[$i];
                }
            }      
            
            
            //Check for sentence with query words as a sub-phrase
            if($number_of_query_words > 2) {
                $subphrases = array();
                for ($i = 0; $i <= $number_of_query_words - 2; $i++) {
                    array_push($subphrases, $query_words[$i] . ' ' . $query_words[$i+1]);
                }
            }
            for($i = 1; $i < $number_of_candidates; $i++) {
                for ($j = 0; $j < count($subphrases); $j++) {
                    if (stripos($snippet_candidates[$i], $subphrases[$j]) == true) {
                      
                        $snippet = $snippet_candidates[$i];
                        
                        if(strlen($snippet) > 160) {
                            return substr($snippet, stripos($snippet, $query_words[j]), 160);
                        }
                        else {
                            $diff = 160 - $snippet_length;

                            if($i == 1) {
                                $next_snippet = $snippet_candidates[$i+1];
                                if(strlen($next_snippet) <= $diff) 
                                    return $snippet."...".$next_snippet;
                                else 
                                    return $snippet."...".mb_substr($next_snippet, 0, $diff, 'utf-8');
                            }
                            else if($i == count($snippet_candidates) - 1) {
                                $prev_snippet = $snippet_candidates[$i-1];

                                if(strlen($prev_snippet) <= $diff) 
                                    return $prev_snippet."...".$snippet;
                                else 
                                    return $snippet."...".mb_substr($prev_snippet, $diff, strlen($prev_snippet), 'utf-8');
                            }
                            else {
                                $next_snippet = $snippet_candidates[$i+1];
                                $prev_snippet = $snippet_candidates[$i-1];

                                if(strlen($next_snippet) <= $diff)
                                    return $snippet."...".mb_substr($next_snippet, 0, strlen($next_snippet), 'utf-8');
                                else if(strlen($prev_snippet) <= $diff)
                                    return mb_substr($prev_snippet, 0, strlen($prev_snippet), 'utf-8')."...".$snippet;
                                else
                                    return $snippet."...".mb_substr($next_snippet, 0, $diff, 'utf-8');  
                            }
                            return $snippet_candidates[$i];
                        }
                    }
                }
            }
            
            
            //Check for sentence at least one of the query words
            for($i = 1; $i < $number_of_candidates; $i++) {
                for ($j = 0; $j < $number_of_query_words; $j++) {
                    if (stripos($snippet_candidates[$i], $query_words[$j]) == true) {
                 
                        $snippet = $snippet_candidates[$i];
                        
                        if(strlen($snippet) > 160) {
                            return substr($snippet, stripos($snippet, $query_words[j]), 160);
                        }
                        else {
                            $diff = 160 - $snippet_length;

                            if($i == 1) {
                                $next_snippet = $snippet_candidates[$i+1];
                                if(strlen($next_snippet) <= $diff) 
                                    return $snippet."...".$next_snippet;
                                else 
                                    return $snippet."...".mb_substr($next_snippet, 0, $diff, 'utf-8');
                            }
                            else if($i == count($snippet_candidates) - 1) {
                                $prev_snippet = $snippet_candidates[$i-1];

                                if(strlen($prev_snippet) <= $diff) 
                                    return $prev_snippet."...".$snippet;
                                else 
                                    return $snippet."...".mb_substr($prev_snippet, $diff, strlen($prev_snippet), 'utf-8');
                            }
                            else {
                                $next_snippet = $snippet_candidates[$i+1];
                                $prev_snippet = $snippet_candidates[$i-1];

                                if(strlen($next_snippet) <= $diff)
                                    return $snippet."...".mb_substr($next_snippet, 0, strlen($next_snippet), 'utf-8');
                                else if(strlen($prev_snippet) <= $diff)
                                    return mb_substr($prev_snippet, 0, strlen($prev_snippet), 'utf-8')."...".$snippet;
                                else
                                    return $snippet."...".mb_substr($next_snippet, 0, $diff, 'utf-8');  
                            }
                            return $snippet_candidates[$i];
                        }
                    }
                }
            }
            
            // Meta Description
            $meta = get_meta_tags($url);
            for ($m = 0; $m < $number_of_query_words; $m++) {
                if (stripos($meta["description"], $query_words[$m]) == true) {
                    if(strlen($meta["description"]) > 160)
                        return mb_substr($meta["description"], 0, 160, 'utf-8');
                }
            }
            
            // Title
            for ($t = 0; $t < $number_of_query_words; $t++) {
                if (stripos($title, $query_words[$t]) == true) {
                    return $title;
                }
            }
            
            return "";
        }

        
        //Process Results
        if ($results) 
        { 
            // Load the CSV map file
            $map = array();
            while ($line = fgetcsv($csv_file))
            {
                $key = array_shift($line);
                $map[$key] = $line;
            }
            fclose($csv_file);

            $total = (int) $results->response->numFound; 
            $start = min(1, $total); 
            $end = min($limit, $total); 
            $html_urls = array();
            $html_urls = getUrls();
        ?> 
        <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>   
        <ol> 
            <?php  
            foreach ($results->response->docs as $doc) { 
                $display_data = array();
                
                foreach ($doc as $field => $value) {
                    
                    if ($field == "id" || $field == "description" || $field == "title" || $field == "og_url") {
                        $display_data[$field] = $value;
                    }
                    
                    if ($field == "id") {
                        $display_data[$field] = $value;
                        $id = $display_data["id"];
                        $base_id = substr($id, strrpos($id, '/') + 1);
                        $content = file_get_html($html_urls[$base_id]);
                        $text = "";
                        
                        foreach ($content->find('p') as $para) {
                            $text = $text.$para.PHP_EOL;
                        }
                        
                        $html_split = preg_split( "/[.;:]/", html_entity_decode(strip_tags(trim($text))));
                        $query_split = preg_split( "/ /", $query);
                        
                        $snippet_candidates = array();
                    
                        for($k=1; $k < count($html_split); $k++) {
                            if(trim($html_split[$k])!="" && trim($html_split[$k]) !="Share via e-mail") {
                                array_push($snippet_candidates, $html_split[$k]);
                            }
                        }
           
                        $snippet = snippet_generate(array_filter($snippet_candidates), $query, $doc_title, $html_urls[$base_id], $display_data["title"]);

                        if ($snippet != "") {
                            $display_data["snippet"] = "...".$snippet."...";
                        }
                        else {
                            $display_data["snippet"] = "NO SNIPPET FOUND";
                        }
                    }                  
                }
                ?>
            <li> 
                <table style="border: 1px solid black; text-align: left">  
                    <!-- Title -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("Title", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <td><?php echo htmlspecialchars($display_data["title"], ENT_NOQUOTES, 'utf-8'); ?></td>
                    </tr>
                    <!-- URL -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("URL", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <?php if ($display_data["og_url"]) { ?>
                        <td><a target="_blank" href="<?php echo htmlspecialchars($display_data["og_url"], ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($display_data["og_url"], ENT_NOQUOTES, 'utf-8'); ?></a></td>
                        <?php } 
                        else { 
                            $id = $display_data["id"];
                            $base_id = substr($id, strrpos($id, '/') + 1);
//                            echo('<script>console.log("'.$base_id.'");</script>');
                            $url = $map[$base_id][0];
                            ?>
                           <td><a target="_blank" href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?></a></td>
                        <?php } ?>
                    </tr>
                    <!-- ID -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("ID", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <td><?php echo htmlspecialchars($display_data["id"], ENT_NOQUOTES, 'utf-8'); ?></td>
                    </tr>
                    <!-- DESCRIPTION -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("DESCRIPTION", ENT_NOQUOTES, 'utf-8'); ?></th>   
                        <?php if ($display_data["description"]) { ?>
                        <td><?php echo htmlspecialchars($display_data["description"], ENT_NOQUOTES, 'utf-8'); ?></td>
                        <?php } 
                        else { ?>
                        <td><?php echo htmlspecialchars("NA", ENT_NOQUOTES, 'utf-8'); ?></td>
                        <?php } ?>
                    </tr>
                    <!-- SNIPPET -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("SNIPPET", ENT_NOQUOTES, 'utf-8'); ?></th>  
                        <td><?php echo htmlspecialchars($display_data["snippet"], ENT_NOQUOTES, 'utf-8'); ?></td>
                    </tr>
                </table> 
            </li> 
            <?php } ?>
        </ol> 
        <?php } ?>
    </body> 
</html>