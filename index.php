<pre>
<meta http-equiv="content-type" content="text/html; charset=<?$charset?>"></meta>
<?php
function abi_get_url_object($url, $user_agent=null)
{
    define('ABI_URL_STATUS_UNSUPPORTED', 100);
    define('ABI_URL_STATUS_OK', 200);
    define('ABI_URL_STATUS_REDIRECT_301', 301);
    define('ABI_URL_STATUS_REDIRECT_302', 302);
    define('ABI_URL_STATUS_NOT_FOUND', 404);
    define('MAX_REDIRECTS_NUM', 4);
    $TIME_START = explode(' ', microtime());
    $TRY_ID = 0;
    $URL_RESULT = false;
    do
    {
        //--- parse URL ---
        $URL_PARTS = @parse_url($url);
        if( !is_array($URL_PARTS))
        {
            break;
        };
        $URL_SCHEME = ( isset($URL_PARTS['scheme']))?$URL_PARTS['scheme']:'http';
        $URL_HOST = ( isset($URL_PARTS['host']))?$URL_PARTS['host']:'';
        $URL_PATH = ( isset($URL_PARTS['path']))?$URL_PARTS['path']:'/';
        $URL_PORT = ( isset($URL_PARTS['port']))?intval($URL_PARTS['port']):80;
        if( isset($URL_PARTS['query']) && $URL_PARTS['query']!='' )
        {
            $URL_PATH .= '?'.$URL_PARTS['query'];
        };
        $URL_PORT_REQUEST = ( $URL_PORT == 80 )?'':":$URL_PORT";
        //--- build GET request ---
        $USER_AGENT = ( $user_agent == null )?'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)':strval($user_agent);
        $GET_REQUEST = "GET $URL_PATH HTTP/1.0\r\n"
            ."Host: $URL_HOST$URL_PORT_REQUEST\r\n"
            ."Accept: text/plain\r\n"
            ."Accept-Encoding: identity\r\n"
            ."User-Agent: $USER_AGENT\r\n\r\n";
        //--- open socket ---
        $SOCKET_TIME_OUT = 30;
        $SOCKET = @fsockopen($URL_HOST, $URL_PORT, $ERROR_NO, $ERROR_STR, $SOCKET_TIME_OUT);
        if( $SOCKET )
        {
            if( fputs($SOCKET, $GET_REQUEST))
            {
                socket_set_timeout($SOCKET, $SOCKET_TIME_OUT);
                //--- read header ---
                $header = '';
                $SOCKET_STATUS = socket_get_status($SOCKET);
                while( !feof($SOCKET) && !$SOCKET_STATUS['timed_out'] )
                {
                    $temp = fgets($SOCKET, 128);
                    if( trim($temp) == '' ) break;
                    $header .= $temp;
                    $SOCKET_STATUS = socket_get_status($SOCKET);
                };
                //--- get server code ---
                if( preg_match('~HTTP\/(\d+\.\d+)\s+(\d+)\s+(.*)\s*\\r\\n~si', $header, $res))
                    $SERVER_CODE = $res[2];
                else
                    break;
                if( $SERVER_CODE == ABI_URL_STATUS_OK )
                {
                    //--- read content ---
                    $content = '';
                    $SOCKET_STATUS = socket_get_status($SOCKET);
                    while( !feof($SOCKET) && !$SOCKET_STATUS['timed_out'] )
                    {
                        $content .= fgets($SOCKET, 1024*8);
                        $SOCKET_STATUS = socket_get_status($SOCKET);
                    };
                    //--- time results ---
                    $TIME_END = explode(' ', microtime());
                    $TIME_TOTAL = ($TIME_END[0]+$TIME_END[1])-($TIME_START[0]+$TIME_START[1]);
                    //--- output ---
                    $URL_RESULT['header'] = $header;
                    $URL_RESULT['content'] = $content;
                    $URL_RESULT['time'] = $TIME_TOTAL;
                    $URL_RESULT['description'] = '';
                    $URL_RESULT['keywords'] = '';
                    //--- title ---
                    $URL_RESULT['title'] =( preg_match('~<title>(.*)<\/title>~U', $content, $res))?strval($res[1]):'';
                    //--- meta tags ---
                    if( preg_match_all('~<meta\s+name\s*=\s*["\']?([^"\']+)["\']?\s+content\s*=["\']?([^"\']+)["\']?[^>]+>~', $content, $res, PREG_SET_ORDER) > 0 )
                    {
                        foreach($res as $meta)
                            $URL_RESULT[strtolower($meta[1])] = $meta[2];
                    };
                }
                elseif( $SERVER_CODE == ABI_URL_STATUS_REDIRECT_301 || $SERVER_CODE == ABI_URL_STATUS_REDIRECT_302 )
                {
                    if( preg_match('~location\:\s*(.*?)\\r\\n~si', $header, $res))
                    {
                        $REDIRECT_URL = rtrim($res[1]);
                        $URL_PARTS = @parse_url($REDIRECT_URL);
                        if( isset($URL_PARTS['scheme'])&& isset($URL_PARTS['host']))
                            $url = $REDIRECT_URL;
                        else
                            $url = $URL_SCHEME.'://'.$URL_HOST.'/'.ltrim($REDIRECT_URL, '/');
                    }
                    else
                    {
                        break;
                    };
                };
            };// GET request is OK
            fclose($SOCKET);
        }// socket open is OK
        else
        {
            break;
        };
        $TRY_ID++;
    }
    while( $TRY_ID <= MAX_REDIRECTS_NUM && $URL_RESULT === false );
    return $URL_RESULT;
};
?>
<?php
function use_abi_get_url_object($url,$user_agent)
{
    global $URL_OBJ,$CONTENT,$HEADER,$TITLE,$DESCRIPTION,$KEYWORDS,$TIME_REQUEST;
    $URL_OBJ = abi_get_url_object($url, $user_agent);
    if ($URL_OBJ) {
//            /*Сохраним контент в файле =) */
//        @mkdir('C:\GetContentFrom\\'.$URL_OBJ['description'], 0777, true);
//        @$handle = fopen('C:\GetContentFrom\\'.$URL_OBJ['description'].'\file.txt', "w");   // @ - для маскирования ошибок появляющихся при попытке создать уже существующую папку.
//        flock($handle, LOCK_EX);
//        fputs($handle,$CONTENT);
//        fclose($handle);
            /*Разложим контент по константам для удобства*/
        $CONTENT = $URL_OBJ['content'];
        $HEADER = $URL_OBJ['header'];
        $TITLE = $URL_OBJ['title'];
        $DESCRIPTION = $URL_OBJ['description'];
        $KEYWORDS = $URL_OBJ['keywords'];
        $TIME_REQUEST = $URL_OBJ['time'];

        return $URL_OBJ;
    } else {
        print 'Запрашиваемая страница недоступна.';
    }
}
?>
 <?                                              /*Главный контент Архива судебных актов за 2010 год*/
$url = 'http://kirovsky.tms.sudrf.ru/modules.php?name=docum_sud&rid=14';
$user_agent = '';
use_abi_get_url_object($url,$user_agent);

                                                /*Пробежимся по контенту и найдем RIDs*/
 //$array = new SplFixedArray(100000);
preg_match_all ("@href=['\"].*?(modules.*?rid=\\d{1,})['\"].?>(.*?)<@", $CONTENT, $res);
 $testArray=array(''=>array(''=>array(''=>'')));
 $Anominal2=$nominal=$Anominal=$combine=array(''=>array(''=>array('')));
 $nominal2=$all=$getURLName=array(''=>'');
$epolete = array_combine($res[1], $res[2]);         //  Создадим рабочий массив с ключами=URL и значениями=NameOfURL
 echo 'Структура запрошенной страницы (все RIDs): '.'<br>'; print_r($epolete);

foreach ($epolete as $key=>$value) {
    /*Дунем содержимое с полученной URL*/
    $url = 'http://kirovsky.tms.sudrf.ru/'.$key;$user_agent = '';use_abi_get_url_object($url,$user_agent);
    /*Пробежимя по содержимому в поисках RIDs*/
    preg_match_all ("@href=['\"].*?(modules.*?id=\\d{1,})['\"].?>(.*?)<@", $CONTENT, $nom);
    $nominal=array_combine($nom[1], $nom[2]);                   // Сохраним URL  из ключей-адресов указанных в epolete
    $nominal = array_diff_key($nominal, $epolete) ;             // Удалим уже записанные адреса, оставив только новые
    unset($nominal['modules.php?name=docum_sud&id=822']);       // {КОСТЫЛЬ} Удаляем пустую ссылку, которую выявили в ручную
    $Anominal2+=array($value=>$nominal);
    foreach($nominal as $keyNominal=>$valueNominal){
        preg_match("@modules.*?rid=\\d{1,}@", $keyNominal, $rid);
        if($rid){
            $url = 'http://kirovsky.tms.sudrf.ru/'.$keyNominal;$user_agent = '';use_abi_get_url_object($url,$user_agent);
//            echo '<br>УРЛ _=_ <br>'.$url;
            /*Пробежимя по содержимому в поисках IDs*/
            preg_match_all ("@href=['\"].*?(modules.*?id=\\d{1,})['\"].?>(.*?)<@", $CONTENT, $res);
            $content=array_combine($res[1], $res[2]);
            $content = array_diff_key($content, $epolete) ;
//            echo 'Контент массив = <BR>';
//            print_r($content);
            $nominal2+=array($valueNominal=>$content);
//            $Anominal+=array($valueNominal=>$content);
//            echo '<BR> $Anominal2 = <BR>'.$i.'<br>';
//            print_r($Anominal2);
        }
    }
    $Anominal+=array($value=>$nominal2);
    $nominal2 =array(''=>'');
}




echo 'Аноминальный массив: <br>';
 print_r($Anominal);
 echo 'Аноминальный2 массив: <br>';
 print_r($Anominal2);
//asort($epolete, SORT_NATURAL  );
// echo 'Смердженный массив: <br>';
// print_r($testArray);




///*Раскладываем весь сайт по полочкам*/
//@mkdir('C:\openserver\domains\PARSER\ContentFromSite\\', 0777, true);
//for($i=0; $i<count($tableOfContets[1]); ++$i){
//    /* Проверим наличие содержимого*/
//    if(!is_null($tableOfContets[2])){
//        $pathToFolder = 'C:\GetContentFrom\ContentFromSite\\' .$tableOfContets[2][$i];  // Указываем путь
//        @mkdir($pathToFolder, 0777, true);                                              // Создаем папку под каждый интересующий раздел
//        $url = 'http://kirovsky.tms.sudrf.ru/'.$tableOfContets[1][$i];                             // Парсим новый URL Он состоит из http://kirovsky.tms.sudrf.ru/  +  modules.php?name=docum_sud&?id=???
//        $user_agent = '';
//        use_abi_get_url_object($url, $user_agent);
//        /*Создаем файлик с контентом выбранного раздела*/
//        @$handle = fopen($pathToFolder.'\file.txt', "w");
//        flock($handle, LOCK_EX);
//        fputs($handle,$CONTENT);
//        fclose($handle);
//    }


?>
<?php
/*Определение кодировки для корректного отображения странички*/
preg_match ("/charset=(.*?)\"/is", $CONTENT, $char);
$charset=$char[1];
?>

</pre>


