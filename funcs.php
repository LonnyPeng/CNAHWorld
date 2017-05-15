<?php

/**
 * Use the curl virtual browser
 *
 * @param array $urlInfo = array('url' => "https://www.baidu.com/", 'params' => array('key' => 'test'), 'cookie' => 'cookie')
 * @param string $type = 'GET|POST'
 * @param boolean $info = false|true
 * @return string|array
 */
function curl($urlInfo, $type = "GET", $info = false) {
    $type = strtoupper(trim($type));

    if (isset($urlInfo['cookie'])) {
        $cookie = $urlInfo['cookie'];
        unset($urlInfo['cookie']);
    }

    if ($type == "POST") {
        $url = $urlInfo['url'];
        $data = $urlInfo['params'];
    } else {
        $urlArr = parse_url($urlInfo['url']);

        if (isset($urlInfo['params'])) {
            $params = "";
            foreach ($urlInfo['params'] as $key => $row) {
                if (is_array($row)) {
                    foreach ($row as $value) {
                        if ($params) {
                            $params .= "&" . $key . "=" . $value;
                        } else {
                            $params .= $key . "=" . $value;
                        }
                    }
                } else {
                    if ($params) {
                        $params .= "&" . $key . "=" . $row;
                    } else {
                        $params .= $key . "=" . $row;
                    }
                }
            }
            
            if (isset($urlArr['query'])) {
                if (preg_match("/&$/", $urlArr['query'])) {
                    $urlArr['query'] .= $params;
                } else {
                    $urlArr['query'] .= "&" . $params;
                }
            } else {
                $urlArr['query'] = $params;
            }
        }

        if (isset($urlArr['host'])) {
            if (isset($urlArr['scheme'])) {
                $url = $urlArr['scheme'] . "://" . $urlArr['host'];
            } else {
                $url = $urlArr['host'];
            }

            if (isset($urlArr['port'])) {
                $url .= ":" . $urlArr['port'];
            }
            if (isset($urlArr['path'])) {
                $url .= $urlArr['path'];
            }
            if (isset($urlArr['query'])) {
                $url .= "?" . $urlArr['query'];
            }
            if (isset($urlArr['fragment'])) {
                $url .= "#" . $urlArr['fragment'];
            }
        } else {
            $url = $urlInfo['url'];
        }
    }
    
    $httpHead = array(
        "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        "Cache-Control:no-cache",
        "Connection:keep-alive",
        "Pragma:no-cache",
        "Upgrade-Insecure-Requests:1",
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (isset($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE , $cookie);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHead);
    curl_setopt($ch, CURLOPT_ENCODING , "gzip");
    if ($type == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    $result = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch); 
    
    if ($info) {
        return $curlInfo;
    } else {
        return $result;
    }
}

/**
 * Regular match HTML
 *
 * @param string $html = ""
 * @param string $preg = ""
 * @param boolean $status = true|false
 * @return string
 */
function pregHtml($html = "", $preg = "", $status = true)
{
    $pregInit = array(
        'clear' => "/\f|\n|\r|\t|\v/",
        'spaces' => "/[ ]{2,}/",
        'css' => "/<style[^>]*>.+?<\/style>/i",
        'js' => "/<script[^>]*>.+?<\/script>/i",
        'nojs' => "/<noscript[^>]*>.+?<\/noscript>/i",
        'notes' => "/<!.*?>/",
    );

    //init
    $html = trim($html);
    foreach ($pregInit as $key => $value) {
        switch ($key) {
            case 'clear':
                $html = preg_replace($value, "", $html);
                break;
            case 'spaces':
                $html = preg_replace($value, " ", $html);
                break;
            default:
                if ($status) {
                    $src = pregHtml($html, $value, false);
                    if (is_array($src)) {
                        foreach ($src as $val) {
                            $html = str_replace($val, "", $html);
                        }
                    } else {
                        $html = str_replace($src, "", $html);
                    }
                }
                break;
        }
    }

    if (!$preg) {
        return $html;
    }

    //action
    preg_match_all($preg, $html, $pregArr);

    if (isset($pregArr[1])) {
        if (count($pregArr[1]) == 1) {
            $pregArr = $pregArr[1][0];
        } else {
            $pregArr = $pregArr[1];
        }
    } else {
        if (count($pregArr[0]) == 1) {
            $pregArr = $pregArr[0][0];
        } else {
            $pregArr = $pregArr[0];
        }
    }

    return is_array($pregArr) ? array_unique($pregArr) : $pregArr;
}

/**
 * Get the HTML tag
 *
 * @param string $str = ""
 * @return array
 */
function getTags($str = "")
{
    $oneTag = array(
        "meta", "link", "input", "img", "br", "hr", "param",
    );
    $data = $tagArr = array();

    for($i=0;;$i++) {
        $str = trim($str);
        if (preg_match("/^</", $str)) {
            if (preg_match("/^<\//", $str)) {
                //right tag
                $lastStatus = stripos($str, ">");
                $rightTag = substr($str, 0, $lastStatus + 1);
                $tag = pregHtml($rightTag, "/^<\/([a-z1-6]+)>/i");

                foreach ($tagArr as $key => $value) {
                    $valTag = pregHtml($value, "/^<([a-z1-6]+)[\s]?.*[\/]?>/i");
                    if ($tag == $valTag) {
                        $leftTag = $value;
                        $tagKey = $key;
                    }
                }

                $lastDataKey = array_search(end($data[$leftTag]), $data[$leftTag]);
                foreach ($data[$leftTag] as $key => $value) {
                    if (!isset($value['right'])) {
                        $lastDataKey = $key;
                    }
                }

                $data[$leftTag][$lastDataKey]['right'] = $rightTag;
                unset($tagArr[$tagKey]);

                $str = substr($str, $lastStatus + 1, strlen($str) - $lastStatus);
            } else {
                //left tag
                $lastStatus = stripos($str, ">");
                $leftTag = substr($str, 0, $lastStatus + 1);
                $tag = pregHtml($leftTag, "/^<([a-z1-6]+)[\s]?.*[\/]?>/i");

                $data[$leftTag][$i]['tags'] = $tagArr;
                $data[$leftTag][$i]['left'] = $leftTag;
                if (preg_match("/\/>$/", $leftTag) || in_array($tag, $oneTag)) {
                    //no right tag
                    $data[$leftTag][$i]['right'] = "";
                } else {
                    //have right tag
                    $tagArr[] = $leftTag;
                }

                $str = substr($str, $lastStatus + 1, strlen($str) - $lastStatus);
            }
        } else {
            //content
            $startStatus = stripos($str, "<");
            $content = substr($str, 0, $startStatus);
            $lastTagKey = end($tagArr);
            $currentData= $data[$lastTagKey];
            $data[$lastTagKey][array_search(end($currentData), $currentData)]['content'] = $content;

            $str = substr($str, $startStatus, strlen($str) - $startStatus);
        }

        if (!$str) {
            break;
        }
    }

    return $data;
}

/**
 * Google translation 01
 *
 * @param array $tranInfo = array('tl' => 'zh-CN', 'text' => "Hello World")
 * @return array
 */
function translation($tranInfo = array('tl' => 'en', 'text' => 'Hello World'))
{
    if (!isset($tranInfo['tl']) || !isset($tranInfo['text'])) {
        return false;
    }

    $urlInfo = array(
        'url' => 'http://translate.google.com/translate_t',
        'params' => array(
            'client' => 't',
            'sl' => 'auto',
            'tl' => $tranInfo['tl'],
            'ie' => 'UTF-8',
            'text' => $tranInfo['text'],
        ),
    );
    $html = curl($urlInfo);

    $title = urldecode($tranInfo['text']);
    $title = preg_replace("/[ ]+/", " ", $title);
    $pregArr = array(
        'key_1' => "/<span[\s]?title[\s]*=[\s]*[\"|']?{$title}[\"|']?[^>]*>/i",
        'key_2' => "/<span[\s]?id[\s]*=[\s]*[\"|']?result_box[\"|']?[^>]*>/i",
    );

    $html = pregHtml($html);
    $html = preg_replace("/&#39;/", "'", $html);
    $data = getTags($html);
    $content = "";
    foreach ($data as $key => $value) {
        if (preg_match($pregArr['key_1'], $key) || preg_match($pregArr['key_2'], $key)) {
            $row = reset($value);
            $content = isset($row['content']) ? $row['content'] : "";
        }
    }
    
    return $content;
}