<?php
    // A MODIFIER SELON TES PREFERENCES
    define('OPTIONS', array("MAX_RECORDS" => 3, "TIME" => 1000 * 60 * 60 * 24));

    $timestamp = time();
    $trace = trace();

    $blacklist = fetch();
    $blacklist = unban($timestamp, $blacklist);

    // ENDIF
    if(isBanned($trace)){
        return "ERROR: VOUS ENVOYEZ TROP DE MESSAGE. MERCI D'ATTENDRE ".(OPTIONS["TIME"] / 1000 / 60)." MINUTES ENTRE ".OPTIONS["MAX_RECORDS"]." MESSAGES";
    }

    $blacklist["recorded"][$trace]["last_timestamp"] = $timestamp;
    $blacklist["recorded"][$trace]["recorded"] = $blacklist["recorded"][$trace]["recorded"] ?? array();
    $recorded = array_push($blacklist["recorded"][$trace]["recorded"], $timestamp);
    append($blacklist);

    if($recorded > OPTIONS["MAX_RECORDS"]){
        $records = $blacklist["recorded"][$trace]["recorded"];
        if(OPTIONS["TIME"] > ($records[2] - $records[0])){
            // BAN POUR LE PROCHAIN MESSAGE
            unset($blacklist["recorded"][$trace]);
            $blacklist["banned"][$trace] = $timestamp;
            append($blacklist);
        } else {
            // PAS DE BAN, ON DECALE
            array_shift($blacklist["recorded"][$trace]["recorded"]);
            append($blacklist);
        }
    }

    // C'EST BON LE MESSAGE PEUX ETRE ENVOYE
    return true;
?>

<?php 
function unban($timestamp, $blacklist){
    $good_to_go = array_filter($blacklist["banned"], function($banned) use($timestamp){
        return ($timestamp - $banned) > OPTIONS["TIME"];
    });
    var_dump($good_to_go);
    foreach ($good_to_go as $key => $free) {
        echo $key;
        unset($blacklist["banned"][$key]);
        append($blacklist);
    }
    return $blacklist;
}
function isBanned($trace){
    $blacklist = fetch();
    return $blacklist["banned"][$trace] ?? false;
}
function append($data){
    file_put_contents('./blacklist.json', json_encode($data, JSON_PRETTY_PRINT), LOCK_EX || FILE_APPEND);
}
function fetch(){
    return json_decode(file_get_contents('./blacklist.json'), JSON_PRETTY_PRINT, JSON_FORCE_OBJECT);
}
function trace(){
    $ip=null; if (!empty($_SERVER['HTTP_CLIENT_IP'])){ $ip=$_SERVER['HTTP_CLIENT_IP'];} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];} else{ $ip=$_SERVER['REMOTE_ADDR'];} return sha1($ip);
}
?>