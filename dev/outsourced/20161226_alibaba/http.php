<?

function simple_get($url, $referer, $cookies=Array(), $timeout=30, $show=false) {

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);

    $path2 = $path . (isset($query) ? '?' . $query : '');

    $send_header_text = "
    (Request-Line)	GET " . $path2 . " HTTP/1.1
    Host	" . $host . "
    User-Agent	Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1
    Accept	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
    Accept-Language	ko-kr,ko;q=0.8,en-us;q=0.5,en;q=0.3
    Accept-Encoding	gzip, deflate
    Accept-Charset	EUC-KR,utf-8;q=0.7,*;q=0.7
    Connection	Close
    ";

    if (!empty($referer)) {

        $send_header_text .= "Referer	" . $referer  . "
        ";
    }

    $send_body = '';

    return simple_http_request($url, $referer, $send_header_text, $send_body, $cookies, $timeout, $show);
}

function simple_getimage($url, $referer, $cookies=Array(), $timeout=30, $show=false) {

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);

    $path2 = $path . (isset($query) ? '?' . $query : '');

    $send_header_text = "
    (Request-Line)	GET " . $path2 . " HTTP/1.1
    Host	" . $host . "
    User-Agent	Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1
    Accept	image/png,image/*;q=0.8,*/*;q=0.5
    Accept-Language	ko-kr,ko;q=0.8,en-us;q=0.5,en;q=0.3
    Accept-Encoding	gzip, deflate
    Accept-Charset	EUC-KR,utf-8;q=0.7,*;q=0.7
    Connection	Close
    ";

    if (!empty($referer)) {

        $send_header_text .= "Referer	" . $referer  . "
        ";
    }

    $send_body = '';

    return simple_http_request($url, $referer, $send_header_text, $send_body, $cookies, $timeout, $show);
}

function simple_post($url, $referer, $send_body='', $cookies=Array(), $timeout=30, $show=false) {

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);

    $path2 = $path . ($query ? '?' . $query : '');

    $send_header_text = "
    (Request-Line)	POST " . $path2 . " HTTP/1.1
    Host	" . $host . "
    User-Agent	Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1
    Accept	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
    Accept-Language	ko-kr,ko;q=0.8,en-us;q=0.5,en;q=0.3
    Accept-Encoding	gzip, deflate
    Accept-Charset	EUC-KR,utf-8;q=0.7,*;q=0.7
    Connection	Close
    ";

    if (!empty($referer)) {

        $send_header_text .= "Referer	" . $referer  . "
        ";
    }

    $send_header_text .= "Content-Type	application/x-www-form-urlencoded
    Content-Length	" . strlen($send_body) . "
    ";

    return simple_http_request($url, $referer, $send_header_text, $send_body, $cookies, $timeout, $show);
}

function simple_post_file_flash($url, $referer, $send_body='', $boundary='', $cookies=Array(), $timeout=30, $show=false) {

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);

    $path2 = $path . ($query ? '?' . $query : '');

    $send_header_text = "
    (Request-Line)	POST " . $path2 . " HTTP/1.1
    Host	" . $host . "
    User-Agent	Shockwave Flash
    Accept	text/*
    Cache-Control	no-cache
    Connection	Close
    ";

    if (!empty($referer)) {

        $send_header_text .= "Referer	" . $referer  . "
        ";
    }

    $send_header_text .= "Content-Type	multipart/form-data; boundary=" . $boundary . "
    Content-Length	" . strlen($send_body) . "
    ";

    return simple_http_request($url, $referer, $send_header_text, $send_body, $cookies, $timeout, $show);
}

function simple_post_file_script($url, $referer, $send_body='', $cookies=Array(), $timeout=30, $show=false) {

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);

    $path2 = $path . ($query ? '?' . $query : '');

    $send_header_text = "
    (Request-Line)	POST " . $path2 . " HTTP/1.1
    Host	" . $host . "
    User-Agent	Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1
    Accept	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
    Accept-Language	ko-kr,ko;q=0.8,en-us;q=0.5,en;q=0.3
    Accept-Encoding	gzip, deflate
    Connection	keep-alive
    Content-Type	text/plain; charset=UTF-8
    Pragma	no-cache
    Cache-Control	no-cache
    ";

    if (!empty($referer)) {

        $send_header_text .= "Referer	" . $referer  . "
        ";
    }

    $send_header_text .= "Content-Length	" . strlen($send_body) . "
    ";

    return simple_http_request($url, $referer, $send_header_text, $send_body, $cookies, $timeout, $show);
}

function simple_http_request($url, $referer,$send_header_text, $send_body='', $cookies=Array(), $timeout=30, $show=false){

    $chunked = $gzip = false;

    $url = trim($url);
    $temp = @parse_url($url);
    @extract($temp);
    $scheme = @strtolower($scheme);

    if (empty($host)) {

        //die('������ ȣ��Ʈ ������ �����ϴ�.');
        return Array();
    }

    if (empty($scheme)) {

        $scheme = 'http';
    }
    else if ($scheme != 'http' && $scheme != 'https') {

        die('�������� �ʴ� ����Դϴ�.');
    }

    if (empty($port))
        $port = ($scheme == 'http') ? 80 : 443;

    if (empty($path))
        $path = '/';

    if ($scheme == 'http') {

        $chost = $host;
    }
    else {

        $chost = 'ssl://' . $host;
    }



    $send_header = '';
    $temp = explode("\n", str_replace("(Request-Line)\t", '', $send_header_text));
    foreach($temp as $v){

        $v = trim($v);
        if (empty($v)) continue;

        $send_header .= str_replace("\t", ': ', $v) . "\r\n";
    }

    if (!empty($send_body)) {

        if (!preg_match("`(Content-Length:.+\r\n)`i", $send_header, $m)) {

            $send_header .= "Content-Length: " . strlen($send_body) . "\r\n";
        }
        else {

            $send_header = str_replace($m[1], "Content-Length: " . strlen($send_body) . "\r\n", $send_header);
        }
    }

    if ($scheme == 'https') {

        $send_header = preg_replace("`Connection:\s+keep-alive`i", "Connection: Close", $send_header);
    }

    $send_header .= get_cookie_header($host, $cookies);

    $send_header .= "\r\n";

    $fp = @fsockopen($chost, $port, $errno, $errstr, 30);
    if (empty($fp)) return array();

    fwrite($fp, $send_header . $send_body, strlen($send_header . $send_body));

    if ($show == true) echo $send_header . $send_body . "<br>\n<br>\n";

    if ($timeout > 0) stream_set_timeout($fp, $timeout);

    $receive_header = '';
    $receive_headers = Array();
    while(!feof($fp) && $receive_text = @fgets($fp, 1024)){

        $receive_header .= $receive_text;

        if($receive_text == "\r\n")
            break;

        if (preg_match("`:\s+`", $receive_text)) {

            list($hk, $hv) = preg_split("`:\s+`", $receive_text);
            if (empty($hk)) {

                continue;
            }

            $hk = trim($hk);
            $hk2 = strtolower($hk);
            $hv = trim($hv);
            if (!empty($hk) && !empty($hv)) {

                if (isset($receive_headers[$hk]) && !is_array($receive_headers[$hk]))
                    settype($receive_headers[$hk], 'array');

                if (isset($receive_headers[$hk]) && is_array($receive_headers[$hk])) {

                    $receive_headers[$hk][] = $hv;
                    $receive_headers[$hk] = array_unique($receive_headers[$hk]);
                    if (count($receive_headers[$hk]) == 1) $receive_headers[$hk] = array_shift($receive_headers[$hk]);
                }
                else {

                    $receive_headers[$hk] = $hv;
                }

                if ($hk != $hk2) $receive_headers[$hk2] = $receive_headers[$hk];
            }
        }
        else if (preg_match("`^HTTP.+`i", $receive_text)){

            $receive_headers['status-line'] = $receive_headers['Status-Line'] = $receive_text;
        }

        if (preg_match("`^Transfer-Encoding:\s+chunked`i", trim($receive_text))) $chunked = true;
        else if (preg_match("`^Content-Encoding:\s+gzip`i", trim($receive_text))) $gzip = true;
    }

    if ($show == true) {

        echo $receive_header . "<br>\n<br>\n";

        print_r($receive_headers);
        echo "<br>\n<br>\n";

    }

    $receive_contents = '';

    while(!feof($fp) && $receive_text = @fgets($fp, 1024)){

        if (strlen($receive_text) == 0)
            break;

        $receive_info = stream_get_meta_data($fp);

        if ($receive_info['timed_out'])
            break;

        $receive_contents .= $receive_text;
    }

    if ($show == true) {

        print_r($receive_info);
        echo "<br>\n<br>\n";
    }

    @fclose($fp);

    if ($chunked) $receive_contents = http_chunked_decode($receive_contents);
    if ($gzip) $receive_contents = gzinflate(substr($receive_contents, 10, -8));

    if (empty($receive_headers['set-cookie']))
        $receive_headers['set-cookie'] = array();

    $receive_cookies = set_cookies($host, $cookies, $receive_headers['set-cookie']);

    if ($show == true) {

        print_r($receive_cookies);
        echo "<br>\n<br>\n";

        echo nl2br(str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace(' ', '&nbsp;', htmlspecialchars($receive_contents)))) . "<br>\n<br>\n<br>\n<br>\n";
    }

    if (!empty($receive_headers['location'])) {

        if (!preg_match('`^http`i', $receive_headers['location']) && substr($receive_headers['location'], 0, 1) == '/') {

            $receive_headers['location'] = $scheme . '://' . $host . $receive_headers['location'];
        }
        else if (!preg_match('`^http`i', $receive_headers['location']) && preg_match("`^(\./|[^.])`", $receive_headers['location'])) {

            $receive_headers['location'] = $scheme . '://' . $host . preg_replace("`/+`", '/', dirname($path) . '/' . preg_replace("`^\./`", '', $receive_headers['location']));
        }
        else if (!preg_match('`^http`i', $receive_headers['location']) && preg_match("`^(\.\./)`", $receive_headers['location'], $tm)) {

            $temp_path = dirname($path);
            for($ti = 0; $ti < count($tm[1]); $ti++){

                $temp_path = dirname($temp_path);
            }

            $receive_headers['location'] = $scheme . '://' . $host . preg_replace("`/+`", '/', $temp_path . '/' . preg_replace("`^\./`", '', $receive_headers['location']));
        }

        return simple_get($receive_headers['location'], $referer, $receive_cookies, $timeout, $show);
    }
    else {

        return Array($receive_headers, $receive_contents, $receive_cookies, $receive_info);
    }
}


function get_image($url, $referer){

    global $base_path, $data_path;

    list($receive_headers, $receive_contents, $receive_cookies, $receive_info) = simple_get($url, $referer, $cookies=Array(), 100, false);

    $file = '';
    if (strlen($receive_contents) > 100) {

        $ext = substr(strrchr($url, "."), 1);
        $ext = strtolower($ext);

        $file = $base_path . '/' . $data_path . '/' . date('YmdHis') . '_' . uniqid('') . '.' . $ext;
        file_put_contents($file, $receive_contents);
        @chmod($file, 0777);

        $file = basename($file);
    }

    return $file;
}


if (!function_exists('http_chunked_decode')) {

    /**
     * dechunk an http 'transfer-encoding: chunked' message
     *
     * @param string $chunk the encoded message
     * @return string the decoded message.  If $chunk wasn't encoded properly it will be returned unmodified.
     */
    function http_chunked_decode($chunk) {

        $pos = 0;
        $len = strlen($chunk);
        $dechunk = null;

        while(($pos < $len)
            && ($chunkLenHex = substr($chunk,$pos, ($newlineAt = strpos($chunk,"\n",$pos+1))-$pos))) {

            if (!is_hex($chunkLenHex)) {

                trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
                return $chunk;
            }

            $pos = $newlineAt + 1;
            $chunkLen = hexdec(rtrim($chunkLenHex,"\r\n"));
            $dechunk .= substr($chunk, $pos, $chunkLen);
            $pos = strpos($chunk, "\n", $pos + $chunkLen) + 1;
        }
        return $dechunk;
    }
}



/**
 * determine if a string can represent a number in hexadecimal
 *
 * @param string $hex
 * @return boolean true if the string is a hex, otherwise false
 */
function is_hex($hex) {

    // regex is for weenies
    $hex = strtolower(trim(ltrim($hex,"0")));
    if (empty($hex)) { $hex = 0; };
    $dec = hexdec($hex);
    return ($hex == dechex($dec));
}



function set_cookies($domain, $cookies, $cookie_header){

    if (empty($cookies)) $cookies = Array();

    $cookie_header_cnt = count($cookie_header);
    for($x = 0; $x < $cookie_header_cnt; $x++){

        if(preg_match('/^([^=]+)=([^;]*)/i', $cookie_header[$x], $match)) {

            $cookie_name = $match[1];
            $cookie_value = $match[2];
            $cookie_path = '/';
            $cookie_domain = $domain;
            $cookie_expires = '0';

            if (!isset($cookies[$cookie_name]) || !is_array($cookies[$cookie_name]))
                $cookies[$cookie_name] = Array();

            $cookies[$cookie_name]['value'] = $cookie_value;
            $cookies[$cookie_name]['path'] = $cookie_path;
            $cookies[$cookie_name]['domain'] = $cookie_domain;
            $cookies[$cookie_name]['expires'] = $cookie_expires;

            if (preg_match('/Path=([^;]+)/i', $cookie_header[$x],$match))
                $cookies[$cookie_name]['path'] = $match[1];

            if (preg_match('/Domain=([^;]+)/i', $cookie_header[$x],$match))
                $cookies[$cookie_name]['domain'] = $match[1];

            if (preg_match('/Expires=([^;]+)/i', $cookie_header[$x],$match)){

                $cookie_expires = strtotime(trim($match[1]));

                if ($cookie_expires < time()) {

                    unset($cookies[$cookie_name]);
                    continue;
                }
                else {

                    $cookies[$cookie_name]['expires'] = $cookie_expires;
                }
            }

            if ($cookie_value == 'expired' || $cookie_value == 'deleted'){

                unset($cookies[$cookie_name]);
                continue;
            }
        }
    }

    return $cookies;
}



function get_cookie_header($domain, $cookies){

    $cookie_headers = '';

    if(!is_array($cookies))
        $cookies = (array) $cookies;

    reset($cookies);
    if ( count($cookies) > 0 ) {

        $cookie_headers .= 'Cookie: ';
        foreach ( $cookies as $cookie_name => $array ) {

            if (!empty($array['expires']) && $array['expires'] < time())
                continue;

            if (!($array['domain'] == $domain || preg_match("`" . preg_quote(preg_replace("`^\.`", '', $array['domain'])) . "$`", $domain)))
                continue;

            $cookie_headers .= $cookie_name . "=" . $array['value'] . "; ";
        }

        if ($cookie_headers == 'Cookie: ')
            $cookie_headers = '';
        else
            $cookie_headers = substr($cookie_headers, 0, -2) . "\r\n";
    }

    return $cookie_headers;
}

function get_post_send_body($data){

    $send_body = '';

    while(list($key,$val) = each($data)) {

        if (is_array($val) || is_object($val)) {

            while (list($cur_key, $cur_val) = each($val)) {

                $send_body .= rawurlencode($key) . '=' . @rawurlencode($cur_val) . '&';
            }
        }
        else {

            $send_body .= rawurlencode($key) . '=' . @rawurlencode($val) . '&';
        }
    }

    $send_body = preg_replace("`&+$`", '', $send_body);

    return $send_body;
}

function get_post_file_send_body($data, $files, $boundary){

    $send_body = '';

    while(list($key,$val) = each($data)) {

        if (is_array($val) || is_object($val)) {

            while (list($cur_key, $cur_val) = each($val)) {

                $send_body .= '--' . $boundary . "\r\n";
                $send_body .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n";
                $send_body .= $cur_val . "\r\n";
            }
        }
        else {

            $send_body .= '--' . $boundary . "\r\n";
            $send_body .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n";
            $send_body .= $val . "\r\n";
        }
    }

    while (list($field_name, $file_names) = each($files)) {

        while (list(, $file_name) = each($file_names)) {

            if (!is_array($file_names)) settype($file_names, "array");
            if (is_readable($file_name)) {

                $fp = fopen($file_name, "rb");
                $file_content = fread($fp, filesize($file_name));
                fclose($fp);
                $base_name = basename($file_name);

                $send_body .= '--' . $boundary . "\r\n";

                $send_body .= "Content-Disposition: form-data; name=\"" . rawurlencode($field_name) . "\"; filename=\"" . $base_name . "\"\r\n";

                $size = @getimagesize($file_name);
                if ($size[2] == '1') $send_body .= "Content-Type: image/gif\r\n\r\n";
                else if ($size[2] == '2') $send_body .= "Content-Type: image/pjpeg\r\n\r\n";
                else $send_body .= "Content-Type: application/octet-stream\r\n\r\n";

                $send_body .= "$file_content\r\n";
            }
        }
    }

    $send_body .= '--' . $boundary . "--\r\n";

    return $send_body;
}

function get_post_boundary(){

    return '----------' . get_random_string2(30);
}

//�������� ����
function get_random_string2($len){

    $str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $strlen = strlen($str) -1;
    $return = '';

    for ($i = 0; $i < $len; $i++){

        $rand = rand(0, $strlen);
        $return .= $str[$rand];
    }

    return $return;
}

?>