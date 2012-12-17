<?php 
function mailing_addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}

function wdnl_truncate($str, $length, $breakWords = TRUE, $append = ' ...') {
  $strLength = mb_strlen($str);

  if ($strLength <= $length) {
     return $str;
  }

  if ( ! $breakWords) {
       while ($length < $strLength AND preg_match('/^\pL$/', mb_substr($str, $length, 1))) {
           $length++;
       }
  }

  return mb_substr($str, 0, $length) . $append;
}