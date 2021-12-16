<?php header('Content-type: application/json');
    function call($url, $linenum){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, (substr($url, -4) != "0.do" ? "line=" : "lineNumCd=").$linenum);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
    $param = "";
    $linenum = $_GET["line"];

    $smss = json_decode(call("https://smss.seoulmetro.co.kr/api/3010.do", $linenum), true)["ttcVOList"];
    $res = call("https://smapp.seoulmetro.co.kr:58443/traininfo/traininfoUserMap.do", $linenum);
    
    $list = [];
    $data = explode('</div>
    </div>', explode('<div class="'.$linenum.'line_metro">', $res)[1])[0];

    $list = [];
    for($i=0; $i<100; $i++){
        $da = trim(explode('"', explode('title="',explode('data-statnTcd="',$data)[$i])[1])[0]);
        if($da == "") break;
        if(str_contains($da, "K") || str_contains($da, "S"))
            $da = substr($da, 1);
        $list[] = $da;
    }

    $a = [];
    $i = 0;
    foreach($list as $e){
        $a[$i]["trainNo"] = ($linenum != "2" ? substr($smss[$i]["trainY"], 0, 1).substr($e, 0, 4) : "S".substr($e, 0, 4));
        $a[$i]["trainP"] = $smss[$i]["trainP"] ?? null;
        $a[$i]["statnNm"] = explode(" ", $e)[2];
        $a[$i]["statnTnm"] = mb_substr(explode(" ", $e)[4], 0, -1);
        $a[$i]["trainSttus"] = explode(" ", $e)[3];
        $a[$i]["updnLine"] = (int)$smss[$i]["dir"] == 1 ? "상행" : "하행";
        $a[$i]["isExpress"] = (int)$smss[$i]["directAt"];
        if ($smss[$i]["trainP"] == '000' || $smss[$i]["trainP"] == '' || $smss[$i]["trainP"] == '400' || $smss[$i]["trainP"] == "0")
            $a[$i]["trainP"] = null;
        $i++;
    }

    echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
