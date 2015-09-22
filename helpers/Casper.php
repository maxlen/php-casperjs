<?php

namespace maxlen\proxy\helpers;

use Yii;
use maxlen\proxy\models\Proxies;
use maxlen\proxy\models\ProxyLog;
use maxlen\proxy\models\CaptchaLog;
use maxlen\proxy\helpers\AntiCaptcha;

use yii\data\ActiveDataProvider;

class Casper
{
    public static $proxy = [];
    public static $proxyIndex = 0;
    public static $captchaFileName = null;
    public static $curlSession = null;
    public static $antiCaptchaKey = null;
    public static $cookieFileName = null;
    public static $tmpFilePrefix = 'prefix';
    public static $captcha = false;
    public static $dirInTmp = '/php-parsing';
    public static $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive'
        ];
    public static $forPosition = false;
    public static $mobile = false;
    
    const GOOGLE = 'google';
    const YAHOO = 'yahoo';
    const BING = 'bing';
    const YANDEX = 'yandex';
    
    /**
     * Set spool for proxies
     * 
     * @param type $cronId
     * @param type $searchEngine
     * @return type
     */
    public static function setProxySpool($cronId, $searchEngine = self::GOOGLE)
    {   
        switch($cronId){
            case 1:
                self::getProxy(5, $searchEngine);
                break;
            case 2:
                self::getProxy(6, $searchEngine);
                break;
            case 3:
                self::getProxy(7, $searchEngine);
                break;
            case 4:
                self::getProxy(8, $searchEngine);
                break;
            case 5:
                self::getProxy(9, $searchEngine);
                break;
            case 6:
                self::getProxy(10, $searchEngine);
                break;
            case 7:
                self::getProxy(11, $searchEngine);
                break;
            case 8:
                self::getProxy(12, $searchEngine);
                break;
            case 9:
                self::getProxy(13, $searchEngine);
                break;
            case 10:
                self::getProxy(2, $searchEngine);
                break;
            case 11:
                self::getProxy(4, $searchEngine);
                break;
            default:
                self::getProxy(5, $searchEngine);
                break;
        }
    }

    public static function getPrefixFile(){
        $prefix = '';
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($backtrace[1]['file'])){
            $path = $backtrace[1]['file'];
            $method = (isset($backtrace[2]['function']) ? $backtrace[2]['function'] : ''); 
            $prefix = md5($path . $method);
        }
        
        return $prefix;
    }
    
    /**
     * Get scope of proxies
     * 
     * @param int $scope_id - number of proxies scope 
     * @param string $searchEngine - search engine ('google', 'yandex')
     * @return array
     */
    public static function getProxy($scope_id, $searchEngine = self::GOOGLE)
    {
        if ($searchEngine == self::GOOGLE){
            static::$antiCaptchaKey = \Yii::$app->params['proxy.settings']['AntiCaptchaKey'];
            static::$tmpFilePrefix = self::getPrefixFile();
        }
        
        if(count(static::$proxy) > 0)
            return static::$proxy;
        else
            static::$proxy = Proxies::getProxyPull($scope_id, $searchEngine);
        
        return static::$proxy;
    }
    
    /**
     * Get all of proxies
     * 
     * @param string $searchEngine - search engine ('google', 'yandex')
     * @return array
     */
    public static function getAllProxies($searchEngine = false)
    {
        static::$proxy = Proxies::getAllProxies($searchEngine);
        return static::$proxy;
    }
    
    /**
     * Get random proxy
     * @return array
     */
    public static function getRandomProxy()
    {
        static::$proxy = [Proxies::getRandomProxy()];
        return static::$proxy;
    }
    
    public static function getGoogleSearchResPage($query, $proxy)
    {
        $url = 'https://www.google.com.ua/search?q='.urlencode($query);
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $proxy['login'] . ':' . $proxy['password']);
        curl_setopt($curlSession, CURLOPT_PROXY, $proxy['host'] . ':' . $proxy['port']);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 5);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        

        $output = curl_exec($curlSession);
        
        if(!self::checkProxyResponseCode($proxy['host'], curl_getinfo($curlSession))) {
            return false;
        }
        
        curl_close($curlSession);
        
        return $output;
    }
    
    public static function getGoogleDropDownList($word, $host, $port, $login, $password, $lang = '', $isMap = false)
    {      
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);
        $url = "https://www.google.com/s?sclient=psy-ab&site=&source=hp&q=" . urlencode($word);
        
        $curlSession = curl_init();
        
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login . ':' . $password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host . ':' . $port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 5);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        if (!is_null(static::$antiCaptchaKey)) {
            
            static::$cookieFileName = self::getCookieFileName($host);

            curl_setopt($curlSession, CURLOPT_HEADER, 1);
            curl_setopt($curlSession, CURLOPT_COOKIEJAR, static::$cookieFileName);
            curl_setopt($curlSession, CURLOPT_COOKIEFILE, static::$cookieFileName);
        }

        $res = curl_exec($curlSession);

        print_r($res);
        
        $patterns[0] = '/{(.*)}/Us';
        $replacements[0] = '';
        $data = preg_replace($patterns, $replacements, $res);
        
        preg_match_all('/"(.*)"/Us', $data, $data2);
        
        $query = [];
        if (isset($data2[1][0])){
            unset($data2[1][0]);
            foreach ($data2[1] as $item){
                $query[] = str_replace(['\u003cb\u003e', '\u003c\/b\u003e'],'',urldecode($item));
            }
        }
        
        self::$curlSession = $curlSession;

        $info = curl_getinfo($curlSession);
        
        if (!is_null(static::$antiCaptchaKey) && $info['http_code'] == 302 && isset($info['redirect_url']) && strlen($info['redirect_url']) > 0){
            $dataAfterCaptcha = self::captcha($info['redirect_url'], $host, $port, $login, $password, $url);
            
            $code = 0;
            if (!is_null($dataAfterCaptcha['res']) && !is_null($dataAfterCaptcha['info'])) {
                $res = $dataAfterCaptcha['res'];
                $info = $dataAfterCaptcha['info'];
                $code = $info['http_code'];
            }
            
            $captchaLog = new CaptchaLog();
            $captchaLog->ip = $host;
            $captchaLog->code = $code;
            $captchaLog->create_date = date('Y-m-d H:i:s');
            $captchaLog->save();
            
        } else {
            curl_close($curlSession);
        }        
        
        if(!self::checkProxyResponseCode($host, $info)) {
            return false;
        }
        
        return array_unique($query);
    }
    
    public static function getGoogleResults($word, $host, $port, $login, $password, $start = 0, $lang = '', $isMap = false)
    {
        
        $useragent = self::getRandomUserAgent();
        $url = "http://www.google.com/search?q=" . urlencode($word) . '&start=' . $start . $lang.'&gws_rd=cr';
        if (self::$mobile){
            $useragent = self::getMobileUserAgent();
            $url = "https://www.google.com/search?site=&source=hp&q=" . urlencode($word) . '&start=' . $start ."&gs_l=mobile-gws-";
        }
        
        ini_set('user_agent', $useragent);
        
        $curlSession = curl_init();
        
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login . ':' . $password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host . ':' . $port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 120);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        if (!is_null(static::$antiCaptchaKey)) {
            
            static::$cookieFileName = self::getCookieFileName($host);

            curl_setopt($curlSession, CURLOPT_HEADER, 1);
            curl_setopt($curlSession, CURLOPT_COOKIEJAR, static::$cookieFileName);
            curl_setopt($curlSession, CURLOPT_COOKIEFILE, static::$cookieFileName);
        }

        $res = curl_exec($curlSession);

        self::$curlSession = $curlSession;

        $info = curl_getinfo($curlSession);
        
        if (!is_null(static::$antiCaptchaKey) && $info['http_code'] == 302 && isset($info['redirect_url']) && strlen($info['redirect_url']) > 0){
            $dataAfterCaptcha = self::captcha($info['redirect_url'], $host, $port, $login, $password, $url);
            
            $code = 0;
            if (!is_null($dataAfterCaptcha['res']) && !is_null($dataAfterCaptcha['info'])) {
                $res = $dataAfterCaptcha['res'];
                $info = $dataAfterCaptcha['info'];
                $code = $info['http_code'];
            }
            
            $captchaLog = new CaptchaLog();
            $captchaLog->ip = $host;
            $captchaLog->code = $code;
            $captchaLog->create_date = date('Y-m-d H:i:s');
            $captchaLog->save();
            
        } else {
            curl_close($curlSession);
        }        
        
        if(!self::checkProxyResponseCode($host, $info)) {
            return false;
        }
        if(!$isMap)
            return static::getGooglePageResults($res);
        else
            return static::getGooglePageResultsMap($res);
    }
    
    public static function captcha($redirectUrl, $host, $port, $login, $password, $url){
        static::$captcha = true;
        
        $res = $info = null;
        
        $capchaData = self::getCaptchaData($redirectUrl, $host, $port, $login, $password);
        
        if (!is_null($capchaData)) {
            $form = $capchaData['form'];
            
            $captchaFileName = self::saveCaptcha($capchaData['link'], $form);

            $form['captcha'] = AntiCaptcha::recognize($captchaFileName, static::$antiCaptchaKey);

            self::sendCaptchaToGoogle($form, $host, $port, $login, $password, $redirectUrl);

            if (is_file(static::$captchaFileName)){
                unlink(static::$captchaFileName);
                static::$captchaFileName = null;
            }
            
            $proxy = [
                'host' => $host,
                'port' => $port,
                'login' => $login,
                'password' => $password
            ];
            
            $data = self::getHTML($url, ['proxy' => $proxy, 'getInfo' => true]);
            $res = $data['page'];
            $info = $data['info'];
        
            static::$captcha = false;
        }

        return ['res' => $res, 'info' => $info];
    }
    
    public static function getCookieFileName($host){
        return sys_get_temp_dir() . static::$dirInTmp . '/cookie_' . static::$tmpFilePrefix . '_' . str_replace('.', '_', $host);
    }

    public static function getCaptchaFileName($form){
        return sys_get_temp_dir() . static::$dirInTmp . '/' . md5($form['id'] . time()) . '.jpg';
    }

    public static function getTmpFileName($form){
        return sys_get_temp_dir() . static::$dirInTmp . '/tmp_' . md5($form['id'] . time()) . '.tmp';
    }
    
    public static function sendCaptchaToGoogle($form, $host, $port, $login, $password, $referer){
        
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);
        $url =  $form['action'] . '?continue=' . $form['continue'] . '&id=' . $form['id'] . '&captcha=' . $form['captcha'] . '&submit=Отправить';
        
        $curlSession = self::$curlSession;
        
        $tmpFile = self::getTmpFileName($form);
        if (is_file($tmpFile)){
            unlink($tmpFile);
        }
        $file = fopen($tmpFile,'w+');
        
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login . ':' . $password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host . ':' . $port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 5);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_HEADER, 1);
        curl_setopt($curlSession, CURLOPT_COOKIEJAR, static::$cookieFileName);
        curl_setopt($curlSession, CURLOPT_COOKIEFILE, static::$cookieFileName);
        curl_setopt($curlSession, CURLOPT_FILE, $file);
        curl_setopt($curlSession, CURLOPT_REFERER, $referer);
        $headers = static::$headers; 

        curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
        
        
        $res = curl_exec($curlSession);
        
        fclose($file);
        if (is_file($tmpFile)){
            unlink($tmpFile);
        }
        
        curl_close($curlSession);
        
        return true;
    }
    
    public static function saveCaptcha($captchaLink, $form) {
        $result = null;


        $curlSession = self::$curlSession;
        static::$captchaFileName = self::getCaptchaFileName($form);
        $file = fopen(static::$captchaFileName, 'w+');

        curl_setopt($curlSession, CURLOPT_URL, $captchaLink);
        curl_setopt($curlSession, CURLOPT_FILE, $file);
        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt($curlSession, CURLOPT_COOKIEJAR, static::$cookieFileName);
        curl_setopt($curlSession, CURLOPT_COOKIEFILE, static::$cookieFileName);
        curl_exec($curlSession);
        fclose($file);

        self::$curlSession = $curlSession;

        if (is_file(static::$captchaFileName)) {
            echo(static::$captchaFileName . PHP_EOL);

            $type = mime_content_type(static::$captchaFileName);
//                    echo(mime_content_type(static::$captchaFileName) . PHP_EOL);

            if ($type == 'image/jpeg') {
                $result = static::$captchaFileName;
            } else {
                if (is_file(static::$captchaFileName)) {
                    unlink(static::$captchaFileName);
                    static::$captchaFileName = null;
                }
            }
        } else {
            echo ('not jpg');
        }

        return $result;
    }

    public static function getCaptchaData($redirectUrl, $host, $port, $login, $password){
        $result = null;
        $captchaLink = null;
        $form = [];
        
        $proxy = [
            'host' => $host,
            'port' => $port,
            'login' => $login,
            'password' => $password
        ];
        
        $html = self::getCaptchaHTML($redirectUrl, $proxy);
        
        preg_match_all('/<img src="(.*)"/Us', $html, $img);
        
        if (isset($img[1])){
            foreach($img[1] as $link){
                $pos = strpos($link, 'sorry');
                if ($pos !== false){
                    $captchaLink = 'http://google.com' . $link;
                    break;
                }
            } 
        }
        
        preg_match_all('/<input type="hidden" name="continue" value="(.*)"/Us', $html, $continue);
        if (isset($continue[1][0]) && strlen($continue[1][0]) > 0){
            $form['continue'] = $continue[1][0];
        }
        
        preg_match_all('/<input type="hidden" name="id" value="(.*)"/Us', $html, $id);
        if (isset($id[1][0]) && strlen($id[1][0]) > 0){
            $form['id'] = $id[1][0];
        }
        
        preg_match_all('/<form action="(.*)"/Us', $html, $action);
        if (isset($action[1][0]) && strlen($action[1][0]) > 0){
            $form['action'] = 'http://ipv4.google.com/sorry/' . $action[1][0];
        }
        
        if (!is_null($captchaLink)){
            $result = ['link' => $captchaLink, 'form' => $form];
        }
        return $result;
    }
    
    public static function getGooglePageResults($googlePage){
        
        $res = $googlePage;
        
        $relevant = true;
        $relevantPhrase = 'In order to show you the most relevant results, we have omitted some entries';
        $relevantNext = '<span style="display:block;margin-left:53px">Next</span></a></td></tr></table>';
        if(strpos($res, $relevantPhrase) !== false || strpos($res, $relevantNext) === false){
            $relevant = false;
        }
        
        $findResult = true;
        $findResultPhrase = 'No results found for';
        if(strpos($res, $findResultPhrase) !== false){
            $findResult = false;
        }
        
        if (!$res || $res == '' || strlen($res) < 10) {
            echo '-1';
            return -1;
        }

        //  ---  find number of results
        preg_match_all('/<div.*id="resultStats">(.*) results<\/div>/Us', $res, $main);
        if (!isset($main[1][0])) $main[1][0] = 0;
        $return = array();
        $return['pages'] = str_replace('About ', '', str_replace(',', '', $main[1][0]));
        //echo $main[1][0];exit;

        preg_match_all('/<div.*id="resultStats"(.*)<\/div>/Us', $res, $stats);

        if(isset($stats[1][0]) && !empty($stats[1][0])) {
            $stats = explode('(', $stats[1][0]);
            $stats = explode('nobr', $stats[0]);
            $stats = explode(';', $stats[0]);
            $googleResCount = preg_replace('~[^0-9]+~','',$stats[0]);
            /*
            $googleResCount = (int)$stats[1][0];
            $googleResCount = preg_replace("/\D/","", $stats[1][0]);
             */
        }
        else
            $googleResCount = 0;
            
        $resOr = $res;

        $res = substr($res, strpos($res, '<div id="ires">'));
        $pos = strpos($res, 'id="foot"');
        if ($pos > 0)
            $res = substr($res, 0, $pos);
        //echo $res;exit;
        
        if (self::$forPosition){
            $main = self::withGooglePosition($res);
        } else {
            preg_match_all('/<li class="g">.*<h3 class="r"><a href="\/url\?q=(.*)&amp;sa=U.*">(.*)<\/a><\/h3>.*<span class="st">(.*)<\/span>.*<\/li>/Us', $res, $main);
        }
        
        //'<li class="g"><h3 class="r"><a href="\/url?q=(.*)&amp;sa=U.*">(.*)<\/a><\/h3>.*<span class="st">(.*)<\/span>.*<\/li>';
        //<li class="g"><span style="float:left"><span class="mime">[PDF]</span>&nbsp;</span><h3 class="r"><a href="/url?q=http://www.crestwoodmedcenter.com/Documents/The_Heart_Of_The_Matter.pdf&amp;sa=U&amp;ei=SsFKUbLhAciOtQb6tICwBA&amp;ved=0CBgQFjAA&amp;usg=AFQjCNEOBEX6AX-cVdei-nJo8fl-rkmCdw">The_Heart_Of_The_Matter - Crestwood Medical Center</a></h3><div class="s"><div class="kv" style="margin-bottom:2px"><cite>www.crestwoodmedcenter.com/Documents/The_Heart_Of_The_Matter.pdf</cite><span class="flc"> - <a href="/url?q=http://webcache.googleusercontent.com/search%3Fq%3Dcache:zS1YjSgDAuwJ:http://www.crestwoodmedcenter.com/Documents/The_Heart_Of_The_Matter.pdf%252Bfiletype:pdf%2Bsite:www.crestwoodmedcenter.com%26hl%3Den%26ct%3Dclnk&amp;sa=U&amp;ei=SsFKUbLhAciOtQb6tICwBA&amp;ved=0CBkQIDAA&amp;usg=AFQjCNHTJ1LMFBueE6pyIc2v8pDOlYvjng">Cached</a></span></div><span class="st">PREMIER PATIENT EXPERIENCE. Heart of the Matter. Hospital proves cardiac <br>  procedure is safe and is now fighting to keep the service available <b>...</b></span><br></div></li>
        //print_r($main);exit;
        preg_match_all('/<p class="_Bmc" style="margin:3px 8px"><a href="(.*)">(.*)<\/a><\/p>/Us', $res, $offenSeek);
        
        preg_match_all('/id="tads.*<li.*>.*<ol.*>(.*)<\/ol><\/div>/Us', $resOr, $linksAds);
        if(!empty($linksAds)) {
            preg_match_all('/id="tads.*<ol.*>(.*)<\/ol><\/div>/Us', $resOr, $linksAds);
            if(isset($linksAds[1][0]))
                preg_match_all('/.*<li.*><h3><a.*href="(.*)">(.*)<\/a><\/h3>.*<cite>(.*)<\/cite>.*<span class="ac">(.*)<\/span>.*<\/li>.*/Us', $linksAds[1][0], $linksAdsRes);
        }

        $linksAdsResDomains = [];
        if(isset($linksAdsRes[3]) && !empty($linksAdsRes[3])) {
            foreach($linksAdsRes[3] as $domain) {
                $linksAdsResDomains[] = strip_tags($domain);
            }
        }
        
        $return['links'] = $main[1];
        $return['titles'] = $main[2];
        $return['descriptions'] = $main[3];
        $return['google_res_count'] = $googleResCount;
        $return['offen_seek_links'] = $offenSeek[1];
        $return['offen_seek_text'] = preg_replace('~(<b>|</b>)~','',$offenSeek[2]);
        $return['ads_top_links'] = (isset($linksAdsRes[1])) ? $linksAdsRes[1] : [];
        $return['ads_top_titles'] = (isset($linksAdsRes[2])) ? $linksAdsRes[2] : [];
        $return['ads_top_domains'] = isset($linksAdsResDomains) ? $linksAdsResDomains : [];
        $return['ads_top_desc'] = isset($linksAdsRes[4]) ? $linksAdsRes[4] : [];
        $return['relevant'] = $relevant;
        $return['findResult'] = $findResult;
//        
//        var_dump($return);
//        echo $resOr;
//        die();

        if (count($return['links']) == 0 && strpos($resOr, '302 Moved') !== false && strpos($resOr, 'The document has moved') !== false)
            return -2;

        return $return;
    }
    
    public static function withGooglePosition($res) {
        
        $main = [
            1 => [],
            2 => [],
            3 => [],
        ];

        preg_match_all('/<li class="g">.*<h3 class="r"><a href="(.*)">(.*)<\/a><\/h3>.*<\/li>/Us', $res, $main);
        
        foreach ($main[1] as $linkKey => $link) {
            preg_match('/url\?q=(.*)&amp;sa=U.*/', $link, $linkResult);
            if (isset($linkResult[1])) {
                $main[1][$linkKey] = $linkResult[1];
            }
        }

        preg_match_all('/<li class="g">.*<div class="s">(.*)<\/div><\/li>/Us', $res, $mainDescriptions);
        foreach ($mainDescriptions[0] as $descKey => $desc) {
            preg_match('/.*<span class="st">(.*)<\/span>.*/Us', $desc, $descResult);
            if (isset($descResult[0])) {
                $main[3][$descKey] = $descResult[1];
            } else {
                $main[3][$descKey] = $desc;
            }
        }

        if (!isset($main[1])){
            $main[1] = [];
        }
        if (!isset($main[2])){
            $main[2] = [];
        }
        if (!isset($main[3])){
            $main[3] = [];
        }
        
        return $main;
    }

    public static function getGooglePageResultsMap($res){
        
        if (!$res || $res == '' || strlen($res) < 10) {
            echo '-1';
            return -1;
        }
        
        preg_match_all('/<table class="ts"(.*)>(.*)<\/table>/Us', $res, $main);
        
        $return = [];
        if(isset($main[2][0]) && !empty($main[2][0])) {
            $gmaps = $main[2][0];
            preg_match_all('/<h4 class="r"(.*)><a(.*)href="\/url\?q=http:\/\/(.*)\/&amp;.*">(.*)<\/a><\/h4>/Us', $gmaps, $main);
            if(isset($main[3])) {
                $return['sites'] = $main[3];
                $return['titles'] = $main[4];
            }
        }
        else
            return -1;

        if (count($return['sites']) == 0 && strpos($res, '302 Moved') !== false && strpos($res, 'The document has moved') !== false)
            return -2;

        return $return;
    }
    
    public static function getYahooResults($word, $host, $port, $login, $password, $start)
    {
        //global $agents;
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);
        $url = "http://search.yahoo.com/search;_ylt=Apg.fQFBzxhPrDgbbsxmnm2bvZx4?p=" . urlencode($word) . '&b=' . $start;
        //$url = "http://search.yahoo.com/search;_ylt=ApLYy1iuTRLVmjcbCmMs4u2bvZx4?p=filetype%3Apdf&toggle=1&cop=mss&ei=UTF-8&fr=yfp-t-900";
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login . ':' . $password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host . ':' . $port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curlSession);
        
        if(!self::checkProxyResponseCode($host, curl_getinfo($curlSession), 'yahoo', 3600)) {
            return false;
        }
        
        curl_close($curlSession);

        if (!$res || $res == '' || strlen($res) < 10) {
            echo '-1';
            return -1;
        }
        
        $return = [];
        
        \phpQuery::newDocument($res);
        $paginator = pq('div.compPagination span')->text();
        $return['pages'] = ($paginator != '') ? (int)$paginator : 0;

        $res = substr($res, strpos($res, '<h2>Search results'));
        $pos = strpos($res, 'id="pg"');
        if ($pos > 0)
            $res = substr($res, 0, $pos);
        
        preg_match_all('/<li.*><div.*><div.*><h3 class="title"><a.*href=".*http%3a%2f%2f(.*)" target="_blank".*>(.*)<\/a><\/h3> <div><span class=".*">.*<div class="compText aAbs" ><p.*>(.*)<\/p><\/div><\/div><\/li>/Us', $res, $main);

        for($i=0;$i<count($main[1]);$i++){
            $pos=strpos($main[1][$i],'.pdf');
            if($pos>0)$main[1][$i]=substr($main[1][$i],0,$pos+4);
            $main[1][$i]='http://'.$main[1][$i];
            $main[2][$i]=strip_tags($main[2][$i]);
            $main[3][$i]=strip_tags($main[3][$i]);
        }
//        echo '-'.count($main[1]).'-';
        $return['links'] = $main[1];
        $return['titles'] = $main[2];
        $return['descriptions'] = $main[3];
        return $return;
    }
    
    public static function getBingResults($word, $host, $port, $login, $password, $start)
    {
        //global $agents;
        //$useragent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:23.0) Gecko/20100101 Firefox/23.0";
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);
        $url = "http://www.bing.com/search?q=" . urlencode($word) . '&first=' . $start;
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login.':'.$password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host.':'.$port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curlSession);
        
        if(!self::checkProxyResponseCode($host, curl_getinfo($curlSession), 'bing', 3600)) {
            return false;
        }
        
        curl_close($curlSession);

        if (!$res || $res == '' || strlen($res) < 10) {
            echo '-1';
            return -1;
        }

        preg_match_all('/<span class="sb_count".*>(.*) results<\/span>/Us',$res,$main);
        if(!isset($main[1][0])||empty($main[1][0])){
            preg_match_all('/<span class="sb_count".*>результаты: (.*)<\/span>/Us',$res,$main);

            if(!isset($main[1][0])){
                if(!isset($main[1][0])||empty($main[1][0])){
                    preg_match_all('/<span class="sb_count".*>(.*) результати<\/span>/Us',$res,$main);

                    if(!isset($main[1][0]))
                        $main[1][0]=0;
                }

            }
        }

        $return = [];
        $return['pages']=intval(str_replace([',', ' ', '&#160;'],'',$main[1][0]));

        $resOr=$res;

        if(strpos($res,'ul id="wg0"') > 0){
            $res=substr($res,strpos($res,'ul id="wg0"'));
        }else{
            $res=substr($res,strpos($res,'<li class="b_algo"'));
        }

        $pos=strpos($res,'Pagination');
        if($pos>0){
            $res=substr($res,0,$pos);

            preg_match_all('/<li.*><div.*<h3><a href="(.*)".*>(.*)<\/a><\/h3><\/div>.*<p>(.*)<\/p><\/div><\/div><\/li>/Us',$res,$main);
            if(count($main[1]) == 0){
                preg_match_all('/<li.*><div.*<h2><a href="(.*)".*>(.*)<\/a><\/h2><\/div>.*<p>(.*)<\/p><\/div><\/li>/Us',$res,$main);
            }
        }else{
            $pos=strpos($res,'Разбиение на страницы');
            if($pos>0){
                $res=substr($res,0,$pos);
            }

            preg_match_all('/<li.*><h2><a href="(.*)".*>(.*)<\/a><\/h2><div.*>.*<\/div><p>(.*)<\/p><\/div><\/li>/Us',$res,$main);
        }

        for($i=0;$i<count($main[1]);$i++){
            $pos=strpos($main[1][$i],'.pdf');
            if($pos>0)$main[1][$i]=substr($main[1][$i],0,$pos+4);
            $main[1][$i]=$main[1][$i];
            $main[2][$i]=strip_tags($main[2][$i]);
            $main[3][$i]=strip_tags($main[3][$i]);
        }

        $return['links'] = $main[1];
        $return['titles'] = $main[2];
        $return['descriptions'] = $main[3];

        return $return;
    }

    public static function getYandexResults($word, $host, $port, $login, $password, $start)
    {
        $useragent = self::getRandomUserAgent();
        ini_set('user_agent', $useragent);

    	$query_txt = $word[1].'&'.$word[2].'&'.$word[3].'&p='.$start;
    	if($start != 0) $word[] = 'p='.$start;
        shuffle($word);

        $url = "http://yandex.com/yandsearch?".implode("&", $word);

        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $login.':'.$password);
        curl_setopt($curlSession, CURLOPT_PROXY, $host.':'.$port);
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_COOKIEFILE, "ya_cookies/{$host}-{$port}.txt");
		curl_setopt($curlSession, CURLOPT_COOKIEJAR, "ya_cookies/{$host}-{$port}.txt");
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curlSession);
        
        if(!self::checkProxyResponseCode($host, curl_getinfo($curlSession), 'yandex', 3600)) {
            return false;
        }
        
        curl_close($curlSession);

        $fp_page = fopen("data/yandex_".$query_txt.".html", 'a');
        fputs($fp_page, "{$login} / {$password} / {$host} / {$port} / {$url}<br>".$res);

        sleep(rand(240, 420));

        if (!$res || $res == '' || strlen($res) < 10) {
            echo '-1';
            return -1;
        }

        $return['pages']=100;

        $resOr=$res;

        $res=substr($res,strpos($res,'ul id="wg0"'));
        $pos=strpos($res,'Pagination');
        if($pos>0)
            $res=substr($res,0,$pos);
        
        preg_match_all('/<div.*><h2.*><a.*href="(.*)"><i.*><\/i><\/a><a.*>(.*)<\/a><\/h2><div.*><div.*><a.*><div.*><img.*\/><span.*>.*<\/span><\/div><\/a><\/div><div.*><div.*><span.*><a.*>.*<\/a><span.*>.*<\/span><a.*>.*<\/a><\/span><span.*><\/span><\/div><div.*>(.*)<\/div><div.*><a.*><span.*>.*<\/span><\/a><a.*><span.*>.*<\/span><\/a><\/div><\/div><\/div><\/div><\/div>/Us',$res,$main);

        for($i=0;$i<count($main[1]);$i++){
            $pos=strpos($main[1][$i],'.pdf');
            if($pos>0)$main[1][$i]=substr($main[1][$i],0,$pos+4);
            $main[1][$i]=urldecode($main[1][$i]);
            $main[2][$i]=strip_tags($main[2][$i]);
            $main[3][$i]=strip_tags($main[3][$i]);
        }
        
        $return['links'] = $main[1];
        $return['titles'] = $main[2];
        $return['descriptions'] = $main[3];

        return $return;
    }
    
    public static function proxyResults($word, $start, $yahoo =0)
    {   
        $search = true;
        $p = 0;
        $step = 0;
            
        while ($search) {
            while(static::$proxyIndex < count(static::$proxy) && !isset(static::$proxy[static::$proxyIndex])) {
                static::$proxyIndex++;
            }
            
            if (!isset(static::$proxy[static::$proxyIndex])) {
                static::$proxyIndex = 0;
                return false;
            }
            
            if (static::$proxy[static::$proxyIndex]['active'] == 0) {
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    $p++;
                    if ($p > 1) {
                        return false;
                    }
                }
            }
            if ($yahoo==3){
                $title = self::getYandexResults($word, static::$proxy[static::$proxyIndex]['host'], static::$proxy[static::$proxyIndex]['port'], static::$proxy[static::$proxyIndex]['login'], static::$proxy[static::$proxyIndex]['password'], $start);
            }elseif ($yahoo==2)
                $title = self::getBingResults($word, static::$proxy[static::$proxyIndex]['host'], static::$proxy[static::$proxyIndex]['port'], static::$proxy[static::$proxyIndex]['login'], static::$proxy[static::$proxyIndex]['password'], $start);
            elseif ($yahoo==1)
                $title = self::getYahooResults($word, static::$proxy[static::$proxyIndex]['host'], static::$proxy[static::$proxyIndex]['port'], static::$proxy[static::$proxyIndex]['login'], static::$proxy[static::$proxyIndex]['password'], $start);
            else
                $title = self::getGoogleResults($word, static::$proxy[static::$proxyIndex]['host'], static::$proxy[static::$proxyIndex]['port'], static::$proxy[static::$proxyIndex]['login'], static::$proxy[static::$proxyIndex]['password'], $start);
            if ($title == -1 || $title == -2) {
                static::$proxy[static::$proxyIndex]['active'] = 0;
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    $p++;
                    if ($p > 1) {
                        return false;
                    }
                }
            } else {
                $search = false;
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    if($yahoo == 0){
                    	//sleep(60 - count($proxy) + 20);
                        sleep(30);
                    }elseif($yahoo == 3){
                    	//sleep in function getYandexResults
                    }else{
                    	sleep(60);
                    }
                }
                return $title;
            }
        }
    }
    
    public static function proxyResultsGMap($query)
    {
        $search = true;
        $p = 0;
        $step = 0;
            
        while ($search) {
            while(static::$proxyIndex < count(static::$proxy) && !isset(static::$proxy[static::$proxyIndex])) {
                static::$proxyIndex++;
            }
            
            if (!isset(static::$proxy[static::$proxyIndex])) {
                static::$proxyIndex = 0;
                return false;
            }
            
            if (static::$proxy[static::$proxyIndex]['active'] == 0) {
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    $p++;
                    if ($p > 1) {
                        return false;
                    }
                }
            }
            
            $title = self::getGoogleResults($query, static::$proxy[static::$proxyIndex]['host'], static::$proxy[static::$proxyIndex]['port'], $proxy[static::$proxyIndex]['login'], static::$proxy[static::$proxyIndex]['password'], 0, '', true);
            return $title;
            if ($title == -1 || $title == -2) {
                static::$proxy[static::$proxyIndex]['active'] = 0;
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    $p++;
                    if ($p > 1) {
                        return false;
                    }
                }
            } else {
                $search = false;
                static::$proxyIndex++;
                if (static::$proxyIndex >= count(static::$proxy)) {
                    static::$proxyIndex = 0;
                    if($yahoo == 0){
                    	//sleep(60 - count($proxy) + 20);
                        sleep(30);
                    }elseif($yahoo == 3){
                    	//sleep in function getYandexResults
                    }else{
                    	sleep(120);
                    }
                }
                return $title;
            }
        }
    }
    
    /**
     *
     * @param string $url
     * @param array $params - [  'proxy' => [],
     *                           'getInfo' => false,
     *                           'autoRedirect' => false,
     *                           'content_type' => ['html'/'pdf'/etc]
     *                           'post' => []
     *                           'auth' => ['username' => *, 'password' => *]
     * @param string $agent
     * @return string 
     */
    public static function getHTML($url, $params = []){
        $curlSession = curl_init();
        
        curl_setopt($curlSession, CURLOPT_USERAGENT, static::getRandomUserAgent());
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 120);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 0);
        
        if($params) {
            if (isset($params['proxy']) && !empty($params['proxy'])){
                curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $params['proxy']['login'] . ':' . $params['proxy']['password']);
                curl_setopt($curlSession, CURLOPT_PROXY, $params['proxy']['host'] . ':' . $params['proxy']['port']);
            }

            if (isset($params['autoRedirect']) && $params['autoRedirect']){
                curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
            }
            
            if (isset($params['post']) && !empty($params['post'])){
                curl_setopt($curlSession, CURLOPT_POST, count($params['post']));
                $postFields = '';
                foreach ($params['post'] as $key => $value) {
                    $postFields .= "$key=$value&";
                }
                rtrim($postFields,'&');
                curl_setopt($curlSession, CURLOPT_POSTFIELDS,$postFields);
            }
            
            if (isset($params['auth']) && isset($params['auth']['username']) && isset($params['auth']['password'])){
                curl_setopt($curlSession, CURLOPT_USERPWD, $params['auth']['username'] . ":" . $params['auth']['password']);
            }
            
            if (isset($params['ssl'])) {
                curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 2);
            }
        }
        else {
            curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        
        $res = curl_exec($curlSession);
        
        if($params) {
            $info = curl_getinfo($curlSession); 

            if(isset($params['getInfo']) && $params['getInfo']){
                $infoRes = $res;
                $res = ['page' => $infoRes, 'info' => $info];
            }
            if(isset($params['content_type']) && isset($info['content_type']) && !is_null($info['content_type'])) {
                $contentType = self::getContentType($info['content_type']);
                if(!in_array($contentType, $params['content_type'])) {
                    $res = false;
                }
            }
        }
        curl_close($curlSession);

        return $res;
    }
    
    public static function downloadFile($url, $destination, $params = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        $data = curl_exec ($ch);
        $error = curl_error($ch); 
        curl_close ($ch);
        
        $file = fopen($destination, "w");
        fputs($file, $data);
        fclose($file);
    }
    
    /**
     *
     * @param string $url
     * @param array $proxy
     * @param string $agent
     * @return string 
     */
    public static function getCaptchaHTML(
            $url, 
            $proxy = [],
            $getInfo = false,
            $autoRedirect = false,
            $referer = false
            ){

        $curlSession = self::$curlSession;
        
        curl_setopt($curlSession, CURLOPT_USERAGENT, static::getRandomUserAgent());
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 10);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_VERBOSE, 1);
        
        if ($proxy){
            curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $proxy['login'] . ':' . $proxy['password']);
            curl_setopt($curlSession, CURLOPT_PROXY, $proxy['host'] . ':' . $proxy['port']);
        }
        
        curl_setopt($curlSession, CURLOPT_COOKIEJAR, static::$cookieFileName);
        curl_setopt($curlSession, CURLOPT_COOKIEFILE, static::$cookieFileName);
        curl_setopt($curlSession, CURLOPT_HEADER, 1);
            
        $headers = static::$headers;

        curl_setopt($curlSession, CURLOPT_HTTPHEADER,$headers);

        if ($referer){
            curl_setopt($curlSession, CURLOPT_REFERER, $referer);
        }
        
        if ($autoRedirect){
            curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
        }

        $res = curl_exec($curlSession);
        
        if($getInfo){
            $info = curl_getinfo($curlSession); 
            $infoRes = $res;
            $res = ['page' => $infoRes, 'info' => $info];
        }
        
        self::$curlSession = $curlSession;
        
        return $res;
    }
    
    public static function getMobileUserAgent() {
        return 'Mozilla/5.0 (iPhone; U; XXXXX like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A477c Safari/419.3';        
    }
    
    public static function getRandomUserAgent() {
        return "Mozilla/5.0 (Windows; U; Windows NT 5.1; de-DE; rv:1.7.6) Gecko/20050226 Firefox/1.0.1";
        /*
        $browser_strings = array (
                "Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0",
                "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)",
                "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)",
                "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/5.0)",
                "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/4.0; InfoPath.2; SV1; .NET CLR 2.0.50727; WOW64)",
                "Mozilla/5.0 (compatible; MSIE 10.0; Macintosh; Intel Mac OS X 10_7_3; Trident/6.0)",
                "Mozilla/4.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/5.0)",
                "Mozilla/1.22 (compatible; MSIE 10.0; Windows 3.1)",
                "Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))",
                "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; InfoPath.3; MS-RTC LM 8; .NET4.0C; .NET4.0E)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; chromeframe/12.0.742.112)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; Tablet PC 2.0; InfoPath.3; .NET4.0C; .NET4.0E)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; yie8)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET CLR 1.1.4322; .NET4.0C; Tablet PC 2.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; FunWebProducts)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/13.0.782.215)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/11.0.696.57)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0) chromeframe/10.0.648.205",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.1; SV1; .NET CLR 2.8.52393; WOW64; en-US)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; chromeframe/11.0.696.57)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/4.0; GTB7.4; InfoPath.3; SV1; .NET CLR 3.1.76908; WOW64; en-US)",
                "Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))",
                "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; InfoPath.3; MS-RTC LM 8; .NET4.0C; .NET4.0E)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; chromeframe/12.0.742.112)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; Tablet PC 2.0; InfoPath.3; .NET4.0C; .NET4.0E)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; yie8)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET CLR 1.1.4322; .NET4.0C; Tablet PC 2.0)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; FunWebProducts)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/13.0.782.215)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/11.0.696.57)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0) chromeframe/10.0.648.205",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.1; SV1; .NET CLR 2.8.52393; WOW64; en-US)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; chromeframe/11.0.696.57)",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/4.0; GTB7.4; InfoPath.3; SV1; .NET CLR 3.1.76908; WOW64; en-US)",
                "Mozilla/5.0 ( ; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 4.4.58799; WOW64; en-US)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/4.0; FDM; MSIECrawler; Media Center PC 5.0)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/4.0; GTB7.4; InfoPath.3; SV1; .NET CLR 3.4.53360; WOW64; en-US)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 5.1; Trident/5.0)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; OfficeLiveConnector.1.4; OfficeLivePatch.1.3; .NET4.0C; .NE",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows 98; .NET CLR 3.0.04506.30)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 7.1; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; AskTB5.5)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; InfoPath.2; .NET4.0C; .NET4.0E)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET4.0C; .NET4.0E; InfoPath.3)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET4.0C)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; FDM; .NET CLR 1.1.4322; .NET4.0C; .NET4.0E; Tablet PC 2.0)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; Tablet PC 2.0; InfoPath.3; .NET4.0E)",
                "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Trident/5.0; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; FDM; .NET4.0C; .NET4.0E; chromeframe/11.0.696.57)",
                "Mozilla/4.0 (compatible; U; MSIE 9.0; WIndows NT 9.0; en-US)",
                "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; FunWebProducts)",
                "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/25.0",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:25.0) Gecko/20100101 Firefox/25.0",
                "Mozilla/5.0 (Windows NT 6.0; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0",
                "Mozilla/5.0 (Windows NT 6.2; rv:22.0) Gecko/20130405 Firefox/23.0",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20130406 Firefox/23.0",
                "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:23.0) Gecko/20131011 Firefox/23.0",
                "Mozilla/5.0 (Windows NT 6.2; rv:22.0) Gecko/20130405 Firefox/22.0",
                "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:22.0) Gecko/20130328 Firefox/22.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:22.0) Gecko/20130405 Firefox/22.0",
                "Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:16.0.1) Gecko/20121011 Firefox/21.0.1",
                "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:16.0.1) Gecko/20121011 Firefox/21.0.1",
                "Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:21.0.0) Gecko/20121011 Firefox/21.0.0",
                "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20130331 Firefox/21.0",
                "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (X11; Linux i686; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.2; rv:21.0) Gecko/20130326 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20130401 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20130331 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20130330 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20130401 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20130328 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20130401 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20130331 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 5.0; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0",
                "Mozilla/5.0 (Windows NT 6.2; Win64; x64;) Gecko/20100101 Firefox/20.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20100101 Firefox/19.0",
                "Mozilla/5.0 (Windows NT 6.1; rv:14.0) Gecko/20100101 Firefox/18.0.1",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0",
                "Mozilla/5.0 (X11; Ubuntu; Linux armv7l; rv:17.0) Gecko/20100101 Firefox/17.0",
                "Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1",
                "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1",
                "Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1",
                "Mozilla/5.0 (Windows NT 6.1; rv:15.0) Gecko/20120716 Firefox/15.0a2",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.16) Gecko/20120427 Firefox/15.0a1",
                "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:15.0) Gecko/20120427 Firefox/15.0a1",
                "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:15.0) Gecko/20120910144328 Firefox/15.0.2",
                "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:15.0) Gecko/20100101 Firefox/15.0.1",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:15.0) Gecko/20121011 Firefox/15.0.1",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.17 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.62 Safari/537.36",
                "Mozilla/5.0 (X11; CrOS i686 4319.74.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.2 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1468.0 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1467.0 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1464.0 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
                "Mozilla/5.0 (X11; CrOS i686 3912.101.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.60 Safari/537.17",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1309.0 Safari/537.17",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.15 (KHTML, like Gecko) Chrome/24.0.1295.0 Safari/537.15",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.14 (KHTML, like Gecko) Chrome/24.0.1292.0 Safari/537.14",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1290.1 Safari/537.13",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1290.1 Safari/537.13",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1290.1 Safari/537.13",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1290.1 Safari/537.13",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1284.0 Safari/537.13",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.6 Safari/537.11",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.26 Safari/537.11",
                "Mozilla/5.0 (Windows NT 6.0) yi; AppleWebKit/345667.12221 (KHTML, like Gecko) Chrome/23.0.1271.26 Safari/453667.1221",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.17 Safari/537.11",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_0) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/537.4",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1",
                "Mozilla/5.0 (X11; CrOS i686 2268.111.0) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.6 (KHTML, like Gecko) Chrome/20.0.1092.0 Safari/536.6",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.6 (KHTML, like Gecko) Chrome/20.0.1090.0 Safari/536.6",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/19.77.34.5 Safari/537.1",
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.9 Safari/536.5",
                "Mozilla/5.0 (Windows NT 6.0) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.36 Safari/536.5",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1063.0 Safari/536.3",
                "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1063.0 Safari/536.3",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_0) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1063.0 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1062.0 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1062.0 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1061.1 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1061.1 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1061.1 Safari/536.3",
                "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1061.0 Safari/536.3",
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.24 (KHTML, like Gecko) Chrome/19.0.1055.1 Safari/535.24",
                "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/535.24 (KHTML, like Gecko) Chrome/19.0.1055.1 Safari/535.24",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.24 (KHTML, like Gecko) Chrome/19.0.1055.1 Safari/535.24",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.22 (KHTML, like Gecko) Chrome/19.0.1047.0 Safari/535.22",
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.21 (KHTML, like Gecko) Chrome/19.0.1042.0 Safari/535.21",
                "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.21 (KHTML, like Gecko) Chrome/19.0.1041.0 Safari/535.21",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
                "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/18.6.872.0 Safari/535.2 UNTRUSTED/1.0 3gpp-gba UNTRUSTED/1.0",
                "Mozilla/5.0 (Macintosh; AMD Mac OS X 10_8_2) AppleWebKit/535.22 (KHTML, like Gecko) Chrome/18.6.872",
                "Mozilla/5.0 (X11; CrOS i686 1660.57.0) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.46 Safari/535.19",
                "Mozilla/5.0 (Windows NT 6.0; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.45 Safari/535.19",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.45 Safari/535.19",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.45 Safari/535.19",
                "Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/534.55.3 (KHTML, like Gecko) Version/5.1.3 Safari/534.53.10",
                "Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko ) Version/5.1 Mobile/9B176 Safari/7534.48.3",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; tr-TR) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; ko-KR) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; fr-FR) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; cs-CZ) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; ja-JP) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_8; zh-cn) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_8; ja-jp) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; ja-jp) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; zh-cn) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; sv-se) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; ko-kr) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; ja-jp) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; it-it) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; fr-fr) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; es-es) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-us) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-gb) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; de-de) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; sv-SE) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; de-DE) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; hu-HU) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; de-DE) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; ja-JP) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; it-IT) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; en-us) AppleWebKit/534.16+ (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; fr-ch) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; de-de) AppleWebKit/534.15+ (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; ar) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Android 2.2; Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; zh-HK) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; tr-TR) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; nb-NO) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; fr-FR) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-TW) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; zh-cn) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5",
                "Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; ja-jp) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5",
                "Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_1 like Mac OS X; zh-cn) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8G4 Safari/6533.18.5",
                "Mozilla/5.0 (iPod; U; CPU iPhone OS 4_2_1 like Mac OS X; he-il) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; ru; CPU iPhone OS 4_2_1 like Mac OS X; ru) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; ru; CPU iPhone OS 4_2_1 like Mac OS X; fr) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; fr; CPU iPhone OS 4_2_1 like Mac OS X; fr) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_1 like Mac OS X; zh-tw) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8G4 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3 like Mac OS X; pl-pl) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8F190 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3 like Mac OS X; fr-fr) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8F190 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3 like Mac OS X; en-gb) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8F190 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_2_1 like Mac OS X; ru-ru) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5",
                "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_2_1 like Mac OS X; nb-no) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5",
                "Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US) AppleWebKit/533.17.8 (KHTML, like Gecko) Version/5.0.1 Safari/533.17.8",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; th-th) AppleWebKit/533.17.8 (KHTML, like Gecko) Version/5.0.1 Safari/533.17.8",
                "Mozilla/5.0 (X11; U; Linux x86_64; en-us) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+",
                "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; es-ES) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.18.1 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Windows; U; Windows NT 6.0; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_8; ja-jp) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_4_11; fr) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; zh-cn) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; ru-ru) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; ko-kr) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; it-it) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; HTC-P715a; en-ca) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-us) AppleWebKit/534.1+ (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-au) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; el-gr) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; ca-es) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; zh-tw) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; ja-jp) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; it-it) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
                "Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14",
                "Mozilla/5.0 (Windows NT 6.0; rv:2.0) Gecko/20100101 Firefox/4.0 Opera 12.14",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0) Opera 12.14",
                "Opera/12.80 (Windows NT 5.1; U; en) Presto/2.10.289 Version/12.02",
                "Opera/9.80 (Windows NT 6.1; U; es-ES) Presto/2.9.181 Version/12.00",
                "Opera/9.80 (Windows NT 5.1; U; zh-sg) Presto/2.9.181 Version/12.00",
                "Opera/12.0(Windows NT 5.2;U;en)Presto/22.9.168 Version/12.00",
                "Opera/12.0(Windows NT 5.1;U;en)Presto/22.9.168 Version/12.00",
                "Mozilla/5.0 (Windows NT 5.1) Gecko/20100101 Firefox/14.0 Opera/12.0",
                "Opera/9.80 (Windows NT 6.1; WOW64; U; pt) Presto/2.10.229 Version/11.62",
                "Opera/9.80 (Windows NT 6.0; U; pl) Presto/2.10.229 Version/11.62",
                "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
                "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; de) Presto/2.9.168 Version/11.52",
                "Opera/9.80 (Windows NT 5.1; U; en) Presto/2.9.168 Version/11.51",
                "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; de) Opera 11.51",
                "Opera/9.80 (X11; Linux x86_64; U; fr) Presto/2.9.168 Version/11.50",
                "Opera/9.80 (X11; Linux i686; U; hu) Presto/2.9.168 Version/11.50",
                "Opera/9.80 (X11; Linux i686; U; ru) Presto/2.8.131 Version/11.11",
                "Opera/9.80 (X11; Linux i686; U; es-ES) Presto/2.8.131 Version/11.11",
                "Mozilla/5.0 (Windows NT 5.1; U; en; rv:1.8.1) Gecko/20061208 Firefox/5.0 Opera 11.11",
                "Opera/9.80 (X11; Linux x86_64; U; bg) Presto/2.8.131 Version/11.10",
                "Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10",
                "Opera/9.80 (Windows NT 5.1; U; zh-tw) Presto/2.8.131 Version/11.10",
                "Opera/9.80 (Windows NT 6.1; Opera Tablet/15165; U; en) Presto/2.8.149 Version/11.1",
                "Opera/9.80 (X11; Linux x86_64; U; Ubuntu/10.10 (maverick); pl) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (X11; Linux i686; U; ja) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (X11; Linux i686; U; fr) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.1; U; zh-tw) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.1; U; zh-cn) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.1; U; sv) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.1; U; en-US) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.1; U; cs) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 6.0; U; pl) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 5.2; U; ru) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 5.1; U;) Presto/2.7.62 Version/11.01",
                "Opera/9.80 (Windows NT 5.1; U; cs) Presto/2.7.62 Version/11.01",
                "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.13) Gecko/20101213 Opera/9.80 (Windows NT 6.1; U; zh-tw) Presto/2.7.62 Version/11.01",
                "Mozilla/5.0 (Windows NT 6.1; U; nl; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 11.01",
                "Mozilla/5.0 (Windows NT 6.1; U; de; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 11.01",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; de) Opera 11.01",
                "Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (X11; Linux i686; U; it) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.1; U; zh-cn) Presto/2.6.37 Version/11.00",
                "Opera/9.80 (Windows NT 6.1; U; pl) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.1; U; ko) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.1; U; fi) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.1; U; en-GB) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.1 x64; U; en) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 6.0; U; en) Presto/2.7.39 Version/11.00",
                "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.7.39 Version/11.00",
                "Opera/9.80 (Windows NT 5.1; U; MRA 5.5 (build 02842); ru) Presto/2.7.62 Version/11.00",
                "Opera/9.80 (Windows NT 5.1; U; it) Presto/2.7.62 Version/11.00",
                "Mozilla/5.0 (Windows NT 6.0; U; ja; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 11.00",
                "Mozilla/5.0 (Windows NT 5.1; U; pl; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 11.00",
                "Mozilla/5.0 (Windows NT 5.1; U; de; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 11.00",
                "Mozilla/4.0 (compatible; MSIE 8.0; X11; Linux x86_64; pl) Opera 11.00",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; fr) Opera 11.00",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; ja) Opera 11.00",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; en) Opera 11.00",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; pl) Opera 11.00",
                "Opera/9.80 (Windows NT 6.1; U; pl) Presto/2.6.31 Version/10.70",
                "Mozilla/5.0 (Windows NT 5.2; U; ru; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.70",
                "Mozilla/5.0 (Windows NT 5.1; U; zh-cn; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.70",
                "Opera/9.80 (Windows NT 5.2; U; zh-cn) Presto/2.6.30 Version/10.63",
                "Opera/9.80 (Windows NT 5.2; U; en) Presto/2.6.30 Version/10.63",
                "Opera/9.80 (Windows NT 5.1; U; MRA 5.6 (build 03278); ru) Presto/2.6.30 Version/10.63",
                "Opera/9.80 (Windows NT 5.1; U; pl) Presto/2.6.30 Version/10.62",
                "Mozilla/5.0 (X11; Linux x86_64; U; de; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.62",
                "Mozilla/4.0 (compatible; MSIE 8.0; X11; Linux x86_64; de) Opera 10.62",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; en) Opera 10.62",
                "Opera/9.80 (X11; Linux i686; U; pl) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (X11; Linux i686; U; es-ES) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Windows NT 6.1; U; zh-cn) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Windows NT 6.1; U; en) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Windows NT 6.0; U; it) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Windows NT 5.2; U; ru) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Windows 98; U; de) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (Macintosh; Intel Mac OS X; U; nl) Presto/2.6.30 Version/10.61",
                "Opera/9.80 (X11; Linux i686; U; en) Presto/2.5.27 Version/10.60",
                "Opera/9.80 (Windows NT 6.0; U; nl) Presto/2.6.30 Version/10.60",
                "Opera/10.60 (Windows NT 5.1; U; zh-cn) Presto/2.6.30 Version/10.60",
                "Opera/10.60 (Windows NT 5.1; U; en-US) Presto/2.6.30 Version/10.60",
                "Opera/9.80 (X11; Linux i686; U; it) Presto/2.5.24 Version/10.54",
                "Opera/9.80 (X11; Linux i686; U; en-GB) Presto/2.5.24 Version/10.53",
                "Mozilla/5.0 (Windows NT 5.1; U; zh-cn; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.53",
                "Mozilla/5.0 (Windows NT 5.1; U; Firefox/5.0; en; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.53",
                "Mozilla/5.0 (Windows NT 5.1; U; Firefox/4.5; en; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.53",
                "Mozilla/5.0 (Windows NT 5.1; U; Firefox/3.5; en; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.53",
                "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; ko) Opera 10.53",
                "Opera/9.80 (Windows NT 6.1; U; fr) Presto/2.5.24 Version/10.52",
                "Opera/9.80 (Windows NT 6.1; U; en) Presto/2.5.22 Version/10.51",
                "Opera/9.80 (Windows NT 6.0; U; cs) Presto/2.5.22 Version/10.51",
                "Opera/9.80 (Windows NT 5.2; U; ru) Presto/2.5.22 Version/10.51",
                "Opera/9.80 (Linux i686; U; en) Presto/2.5.22 Version/10.51",
                "Mozilla/5.0 (Windows NT 6.1; U; en-GB; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.51",
                "Mozilla/5.0 (Linux i686; U; en; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 Opera 10.51",
                "Mozilla/4.0 (compatible; MSIE 8.0; Linux i686; en) Opera 10.51",
                "Opera/9.80 (Windows NT 6.1; U; zh-tw) Presto/2.5.22 Version/10.50",
                "Opera/9.80 (Windows NT 6.1; U; zh-cn) Presto/2.5.22 Version/10.50",
                "Opera/9.80 (Windows NT 6.1; U; sk) Presto/2.6.22 Version/10.50",
                "Opera/9.80 (Windows NT 6.1; U; ja) Presto/2.5.22 Version/10.50",
                "Opera/9.80 (Windows NT 6.0; U; zh-cn) Presto/2.5.22 Version/10.50",
                "Opera/9.80 (Windows NT 5.1; U; sk) Presto/2.5.22 Version/10.50",
                "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.5.22 Version/10.50",
                "Opera/10.50 (Windows NT 6.1; U; en-GB) Presto/2.2.2",
                "Opera/9.80 (S60; SymbOS; Opera Tablet/9174; U; en) Presto/2.7.81 Version/10.5",
                "Opera/9.80 (X11; U; Linux i686; en-US; rv:1.9.2.3) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (X11; Linux x86_64; U; it) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (Windows NT 6.1; U; de) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (Windows NT 6.0; U; Gecko/20100115; pl) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (Windows NT 6.0; U; en) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (Windows NT 5.1; U; de) Presto/2.2.15 Version/10.10",
                "Opera/9.80 (Windows NT 5.1; U; cs) Presto/2.2.15 Version/10.10",
                "Mozilla/5.0 (Windows NT 6.0; U; tr; rv:1.8.1) Gecko/20061208 Firefox/2.0.0 Opera 10.10",
                "Mozilla/4.0 (compatible; MSIE 6.0; X11; Linux i686; de) Opera 10.10",
                "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 6.0; tr) Opera 10.10",
                "Opera/9.80 (X11; Linux x86_64; U; en-GB) Presto/2.2.15 Version/10.01",
                "Opera/9.80 (X11; Linux x86_64; U; en) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux x86_64; U; de) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; ru) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; pt-BR) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; pl) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; nb) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; en-GB) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; en) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; Debian; pl) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (X11; Linux i686; U; de) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.1; U; zh-cn) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.1; U; fi) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.1; U; en) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.1; U; de) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.1; U; cs) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.0; U; en) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 6.0; U; de) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 5.2; U; en) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 5.1; U; zh-cn) Presto/2.2.15 Version/10.00",
                "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.2.15 Version/10.00"
        );

        $rand_index = array_rand($browser_strings);
        return $browser_strings[$rand_index];
         */
    }
    
    /**
     * Check end wright to log proxy reply
     * 
     * @param array $proxy
     * @param array $curl_info - information about curl reply (included type: 302, 500 etc.)
     * @param int $timeout - pause in seconds (default 1 hour)
     * @return boolean
     */
    public static function checkProxyResponseCode($proxyIp, $curlInfo, $searchEngine = self::GOOGLE, $timeout = 3600) {
        $proxyIp = trim($proxyIp);
        
        if($curlInfo['http_code'] == 302 && $proxyIp != '') {
            $model = new ProxyLog();
            $model->ip = $proxyIp;
            $model->search_engine = $searchEngine;
            $model->code = $curlInfo['http_code'];
            $model->dt = date('Y-m-d H:i:s');
            $model->dt_unblock = date('Y-m-d H:i:s', (time() + $timeout));
            
            foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $step) {
                if($step['function'] == 'proxyResults') {
                    $model->script = $step['file'];
                    break;
                }
            }
            
            if(!isset($model->script))
                $model->script = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        
            $model->save();
                        
            foreach (static::$proxy as $key => $val) {
                if($val['host'] == $proxyIp/* && $curlInfo['http_code'] != 0*/) {
                    unset(static::$proxy[$key]);
                }
            }
                        
            sleep(1);
            return false;
        }

        return true;
    }
    
    public static function getContentType($contentType) {
        $contentType = explode('/', $contentType);
        $contentType = explode(';', $contentType[1]);
        return $contentType[0];
    }
}
