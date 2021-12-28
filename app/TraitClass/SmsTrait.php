<?php

namespace App\TraitClass;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function MongoDB\BSON\toJSON;

trait SmsTrait
{
    public $smsConfig = [
        'key' => 'd36939bc82d174de8d6cfa897ee3d2e9',
        'tpl_id' => 238861
    ];

    public function validateSmsCode($phone,$code)
    {
        return DB::table('sms_codes')
            ->where('phone',$phone)
            ->where('code',$code)
            ->where('status',0)
            ->where('created_at', '>', date("Y-m-d H:i:s", strtotime("-30 minute"))) //30分钟有效
            ->first();
    }

    public function sendChinaSmsCode($phone, $code)
    {

        $params = [
            'mobile' => $phone,
            'tpl_id' => 238861,
            'vars' => json_encode(['code'=>$code],JSON_UNESCAPED_UNICODE),
            'tpl_value' => urlencode('#code#='.$code),
            'key' => 'd36939bc82d174de8d6cfa897ee3d2e9',
        ];
        $guzzle = new Client();
        $response = $guzzle->request('post','http://v.juhe.cn/sms/send',[
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => $params
        ]);
        $responseBody = (string) $response->getBody();
        $getBody = json_decode($responseBody, true);
        if($getBody['error_code'] > 0){
            Log::debug('==send_sms_code==',$getBody);
        }
        /*
         * 成功返回以下结果
         * {
            "reason":"操作成功",
            "result":{
                "sid":"202110141802358733871524",
                "fee":1,
                "count":1
            },
            "error_code":0
        }*/
    }

    public function sendInternationalSmsCode($areaNum, $phone, $code)
    {
        $params = [
            'mobile' => $phone,
            'tplId' => 12072,
            'areaNum' => $areaNum,
            'tplValue' => urlencode('#code#='.$code),
            'key' => '6b00e9b68e48669b6d5f0f3373499ba4',
        ];
        $guzzle = new Client();
        $response = $guzzle->request('post','http://v.juhe.cn/smsInternational/send.php',[
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => $params
        ]);
        $responseBody = (string) $response->getBody();
        $getBody = json_decode($responseBody, true);
        if($getBody['error_code'] > 0){
            Log::debug('==send_sms_code==',$getBody);
        }
        /*
         * 成功返回以下结果
         * {
            "reason":"操作成功",
            "result":{
                "sid":"202110141842264665977",
                "fee":7
            },
            "error_code":0
        }*/
    }

    public function getSmsAreaNum()
    {
        $data = json_decode('[{"cname":"印度","ename":"India","areanum":91,"fee":1},{"cname":"阿鲁巴","ename":"Aruba","areanum":297,"fee":10},{"cname":"马绍尔群岛","ename":"Marshall Islands","areanum":692,"fee":6},{"cname":"圭亚那","ename":"Guyana","areanum":592,"fee":2},{"cname":"多哥","ename":"Togo","areanum":228,"fee":1},{"cname":"东帝汶民主共和国","ename":"DEMOCRATIC REPUBLIC OF TIMORLESTE","areanum":670,"fee":1},{"cname":"土耳其","ename":"Turkey","areanum":90,"fee":5},{"cname":"菲律宾","ename":"Philippines","areanum":63,"fee":1},{"cname":"冈比亚","ename":"The Gambia","areanum":220,"fee":1},{"cname":"格陵兰","ename":"Greenland","areanum":299,"fee":2},{"cname":"格鲁吉亚","ename":"Georgia","areanum":995,"fee":1},{"cname":"吉布提","ename":"Djibouti","areanum":253,"fee":6},{"cname":"库克群岛","ename":"Cook Islands","areanum":682,"fee":3},{"cname":"法属圭亚那","ename":"French Guiana","areanum":594,"fee":2},{"cname":"也门","ename":"Yemen","areanum":967,"fee":2},{"cname":"加拿大","ename":"Canada","areanum":1,"fee":1},{"cname":"美国","ename":"United States of America","areanum":1,"fee":1},{"cname":"赤道几内亚","ename":"Equatorial Guinea","areanum":240,"fee":1},{"cname":"马达加斯加","ename":"Madagascar","areanum":261,"fee":2},{"cname":"萨摩亚","ename":"Samoa","areanum":685,"fee":1},{"cname":"加蓬","ename":"Gabon","areanum":241,"fee":2},{"cname":"列支敦士登","ename":"Liechtenstein","areanum":423,"fee":3},{"cname":"法罗群岛","ename":"Faroe Islands","areanum":298,"fee":1},{"cname":"汤加","ename":"Tonga","areanum":676,"fee":2},{"cname":"科摩罗和马约特","ename":"Comoros","areanum":269,"fee":1},{"cname":"塞舌尔","ename":"Seychelles","areanum":248,"fee":4},{"cname":"刚果共和国","ename":"The Republic of Congo?","areanum":242,"fee":2},{"cname":"塞拉利昂","ename":"Sierra Leone","areanum":232,"fee":1},{"cname":"几内亚","ename":"Guinea","areanum":224,"fee":2},{"cname":"斯威士兰王国","ename":"The Kingdom of Swaziland","areanum":268,"fee":2},{"cname":"莱索托","ename":"Lesotho","areanum":266,"fee":2},{"cname":"科威特","ename":"Kuwait","areanum":965,"fee":3},{"cname":"刚果民主共和国","ename":"Democratic Republic of the Congo","areanum":243,"fee":2},{"cname":"赞比亚","ename":"Zambia","areanum":260,"fee":1},{"cname":"南非","ename":"South Africa","areanum":27,"fee":1},{"cname":"泰国","ename":"Thailand","areanum":66,"fee":1},{"cname":"韩国","ename":"Korea, South)","areanum":82,"fee":1},{"cname":"圣多美和普林西比","ename":"Sao Tome and Principe","areanum":239,"fee":1},{"cname":"尼日尔","ename":"Niger","areanum":227,"fee":2},{"cname":"土库曼斯坦","ename":"Turkmenistan?","areanum":993,"fee":2},{"cname":"巴布亚新几内亚","ename":"Papua New Guinea","areanum":675,"fee":2},{"cname":"瓦努阿图","ename":"Vanuatu","areanum":678,"fee":2},{"cname":"安哥拉","ename":"Angola","areanum":244,"fee":3},{"cname":"纳米比亚","ename":"Namibia","areanum":264,"fee":2},{"cname":"利比里亚","ename":"Liberia","areanum":231,"fee":1},{"cname":"巴基斯坦","ename":"Pakistan","areanum":92,"fee":2},{"cname":"马其顿","ename":"Macedonia","areanum":389,"fee":2},{"cname":"博茨瓦纳","ename":"Botswana","areanum":267,"fee":1},{"cname":"马尔代夫??","ename":"Maldives??","areanum":960,"fee":2},{"cname":"法属波利尼西亚","ename":"French Polynesia","areanum":689,"fee":4},{"cname":"加纳","ename":"Ghana","areanum":233,"fee":1},{"cname":"所罗门群岛","ename":"Solomon Islands","areanum":677,"fee":2},{"cname":"喀麦隆","ename":"Cameroon","areanum":237,"fee":1},{"cname":"卢旺达","ename":"Rwanda","areanum":250,"fee":1},{"cname":"圣皮埃尔和密克隆群岛","ename":"Saint Pierre and Miquelon","areanum":508,"fee":8},{"cname":"老挝","ename":"Laos","areanum":856,"fee":2},{"cname":"黎巴嫩","ename":"Lebanon","areanum":961,"fee":2},{"cname":"乌干达","ename":"Uganda","areanum":256,"fee":1},{"cname":"以色列","ename":"Israel","areanum":972,"fee":2},{"cname":"洪都拉斯","ename":"Honduras","areanum":504,"fee":3},{"cname":"阿塞拜疆","ename":"Azerbaijan","areanum":994,"fee":1},{"cname":"密克罗尼西亚","ename":"Federated States of Micronesia","areanum":691,"fee":1},{"cname":"贝宁","ename":"Benin","areanum":229,"fee":2},{"cname":"基里巴斯","ename":"Kiribati","areanum":686,"fee":2},{"cname":"帕劳","ename":"Palau","areanum":680,"fee":2},{"cname":"冰岛","ename":"Iceland","areanum":354,"fee":6},{"cname":"苏里南","ename":"Suriname","areanum":597,"fee":2},{"cname":"利比亚","ename":"Libya","areanum":218,"fee":2},{"cname":"约旦","ename":"Jordan","areanum":962,"fee":2},{"cname":"文莱","ename":"Brunei Darussalam","areanum":673,"fee":2},{"cname":"特克斯和凯科斯群岛","ename":"Turks and Caicos Islands","areanum":1649,"fee":2},{"cname":"古巴","ename":"Cuba","areanum":53,"fee":3},{"cname":"斯洛文尼亚","ename":"Slovenia","areanum":386,"fee":3},{"cname":"斯里兰卡","ename":"Sri Lanka","areanum":94,"fee":2},{"cname":"乌克兰","ename":"Ukraine","areanum":380,"fee":3},{"cname":"蒙古","ename":"Mongolia","areanum":976,"fee":4},{"cname":"圣基茨和尼维斯","ename":"Saint Kitts and Nevis","areanum":1869,"fee":2},{"cname":"布隆迪","ename":"Burundi","areanum":257,"fee":2},{"cname":"爱沙尼亚","ename":"Estonia","areanum":372,"fee":2},{"cname":"智利","ename":"Chile","areanum":56,"fee":2},{"cname":"伯利兹","ename":"Belize","areanum":501,"fee":2},{"cname":"苏丹","ename":"Sudan","areanum":249,"fee":2},{"cname":"尼日利亚","ename":"Nigeria","areanum":234,"fee":2},{"cname":"柬埔寨","ename":"Cambodia","areanum":855,"fee":7},{"cname":"阿尔及利亚","ename":"Algeria","areanum":213,"fee":2},{"cname":"黑山共和国","ename":"The Republic of Montenegro","areanum":382,"fee":2},{"cname":"玻利维亚","ename":"Bolivia","areanum":591,"fee":2},{"cname":"厄立特里亚","ename":"Eritrea","areanum":291,"fee":2},{"cname":"印尼","ename":"Indonesia","areanum":62,"fee":3},{"cname":"中国澳门","ename":"Macao","areanum":853,"fee":2},{"cname":"马来西亚","ename":"Malaysia","areanum":60,"fee":3},{"cname":"摩尔多瓦","ename":"Moldova","areanum":373,"fee":2},{"cname":"马耳他","ename":"Malta","areanum":356,"fee":2},{"cname":"佛得角","ename":"Cape Verde","areanum":238,"fee":2},{"cname":"塔吉克斯坦","ename":"Tajikistan","areanum":992,"fee":2},{"cname":"摩纳哥","ename":"Monaco","areanum":377,"fee":2},{"cname":"索马里","ename":"Somalia","areanum":252,"fee":2},{"cname":"尼加拉瓜","ename":"Nicaragua","areanum":505,"fee":2},{"cname":"乍得","ename":"Chad","areanum":235,"fee":2},{"cname":"肯尼亚","ename":"Kenya","areanum":254,"fee":2},{"cname":"留尼汪","ename":"Reunion","areanum":262,"fee":2},{"cname":"立陶宛","ename":"Lithuania","areanum":370,"fee":2},{"cname":"阿拉伯联合酋长国","ename":"United Arab Emirates","areanum":971,"fee":3},{"cname":"委内瑞拉","ename":"Venezuela","areanum":58,"fee":2},{"cname":"埃塞俄比亚","ename":"Ethiopia","areanum":251,"fee":2},{"cname":"布基纳法索","ename":"Burkina Faso","areanum":226,"fee":2},{"cname":"沙特阿拉伯","ename":"Saudi Arabia","areanum":966,"fee":2},{"cname":"秘鲁","ename":"Peru","areanum":51,"fee":2},{"cname":"阿根廷","ename":"Argentina","areanum":54,"fee":2},{"cname":"巴巴多斯","ename":"Barbados","areanum":1246,"fee":2},{"cname":"科特迪瓦","ename":"Cote d’Ivoire","areanum":225,"fee":2},{"cname":"格林纳达","ename":"Grenada","areanum":1473,"fee":2},{"cname":"直布罗陀","ename":"Gibraltar","areanum":350,"fee":2},{"cname":"几内亚比绍","ename":"Guinea-Bissau","areanum":245,"fee":2},{"cname":"津巴布韦","ename":"Zimbabwe","areanum":263,"fee":2},{"cname":"开曼群岛","ename":"Cayman Islands","areanum":1345,"fee":2},{"cname":"叙利亚","ename":"Syria","areanum":963,"fee":2},{"cname":"安道尔","ename":"Andorra","areanum":376,"fee":3},{"cname":"中国香港","ename":"Hong Kong (SAR)","areanum":852,"fee":4},{"cname":"巴拉圭","ename":"Paraguay","areanum":595,"fee":3},{"cname":"乌兹别克斯坦","ename":"Uzbekistan","areanum":998,"fee":3},{"cname":"塞浦路斯","ename":"Cyprus","areanum":357,"fee":2},{"cname":"白俄罗斯","ename":"Belarus","areanum":375,"fee":2},{"cname":"阿曼","ename":"Oman","areanum":968,"fee":2},{"cname":"中国台湾","ename":"Taiwan","areanum":886,"fee":2},{"cname":"巴哈马","ename":"The Bahamas","areanum":1242,"fee":2},{"cname":"坦桑尼亚","ename":"Tanzania","areanum":255,"fee":2},{"cname":"埃及","ename":"Egypt","areanum":20,"fee":2},{"cname":"新加坡","ename":"Singapore?","areanum":65,"fee":2},{"cname":"英国","ename":"United Kingdom","areanum":44,"fee":2},{"cname":"克罗地亚","ename":"Croatia","areanum":385,"fee":3},{"cname":"中非共和国","ename":"Central African Republic","areanum":236,"fee":5},{"cname":"伊朗","ename":"Iran","areanum":98,"fee":3},{"cname":"巴林","ename":"Bahrain","areanum":973,"fee":3},{"cname":"拉脱维亚","ename":"Latvia","areanum":371,"fee":3},{"cname":"斐济","ename":"Fiji","areanum":679,"fee":3},{"cname":"毛里求斯","ename":"Mauritius","areanum":230,"fee":3},{"cname":"莫桑比克","ename":"Mozambique","areanum":258,"fee":2},{"cname":"牙买加","ename":"Jamaica","areanum":1876,"fee":3},{"cname":"越南","ename":"Vietnam","areanum":84,"fee":4},{"cname":"多米尼克","ename":"Dominica","areanum":1767,"fee":3},{"cname":"丹麦","ename":"Denmark","areanum":45,"fee":3},{"cname":"安圭拉岛","ename":"Anguilla","areanum":1264,"fee":3},{"cname":"毛里塔尼亚","ename":"Mauritania","areanum":222,"fee":3},{"cname":"哥斯达黎加","ename":"Costa Rica","areanum":506,"fee":3},{"cname":"墨西哥","ename":"Mexico","areanum":52,"fee":3},{"cname":"福克兰群岛","ename":"Falkland","areanum":500,"fee":3},{"cname":"伊拉克","ename":"Iraq","areanum":964,"fee":3},{"cname":"瑞士","ename":"Switzerland","areanum":41,"fee":3},{"cname":"挪威","ename":"Norway","areanum":47,"fee":3},{"cname":"波黑","ename":"Bosnia and Herzegovina?","areanum":387,"fee":3},{"cname":"特立尼达和多巴哥","ename":"Trinidad and Tobago","areanum":1868,"fee":3},{"cname":"亚美尼亚","ename":"Armenia","areanum":374,"fee":3},{"cname":"日本","ename":"Japan","areanum":81,"fee":3},{"cname":"瑞典","ename":"Sweden","areanum":46,"fee":3},{"cname":"塞尔维亚","ename":"Serbia and Montenegro","areanum":381,"fee":3},{"cname":"卡塔尔","ename":"Qatar?","areanum":974,"fee":3},{"cname":"俄罗斯","ename":"Russia","areanum":7,"fee":2},{"cname":"吉尔吉斯斯坦","ename":"Kyrgyzstan","areanum":996,"fee":4},{"cname":"意大利","ename":"Italy","areanum":39,"fee":3},{"cname":"波多黎各","ename":"The Commonwealth of Puerto Rico","areanum":1,"fee":3},{"cname":"法国","ename":"France","areanum":33,"fee":3},{"cname":"摩洛哥","ename":"Morocco","areanum":212,"fee":3},{"cname":"圣卢西亚","ename":"Saint Lucia","areanum":1758,"fee":3},{"cname":"新西兰","ename":"New Zealand","areanum":64,"fee":3},{"cname":"波兰","ename":"Poland","areanum":48,"fee":3},{"cname":"圣文森特和格林纳丁斯","ename":"Saint Vincent and the Grenadines","areanum":1784,"fee":4},{"cname":"塞内加尔","ename":"Senegal","areanum":221,"fee":4},{"cname":"危地马拉","ename":"Guatemala","areanum":502,"fee":3},{"cname":"萨尔瓦多","ename":"El Salvador","areanum":503,"fee":4},{"cname":"保加利亚","ename":"Bulgaria","areanum":359,"fee":4},{"cname":"澳大利亚","ename":"Australia","areanum":61,"fee":3},{"cname":"百慕大","ename":"Bermuda","areanum":1441,"fee":3},{"cname":"美属萨摩亚","ename":"American Samoa","areanum":1684,"fee":6},{"cname":"捷克共和国","ename":"Czech Republic","areanum":420,"fee":4},{"cname":"英属维京群岛","ename":"British Virgin Islands","areanum":1284,"fee":3},{"cname":"爱尔兰","ename":"Ireland","areanum":353,"fee":4},{"cname":"芬兰","ename":"Finland","areanum":358,"fee":4},{"cname":"乌拉圭","ename":"Uruguay","areanum":598,"fee":4},{"cname":"新喀里多尼亚","ename":"New Caledonia","areanum":687,"fee":4},{"cname":"希腊","ename":"Greece","areanum":30,"fee":5},{"cname":"马拉维","ename":"Malawi","areanum":265,"fee":4},{"cname":"阿尔巴尼亚","ename":"Albania","areanum":355,"fee":4},{"cname":"葡萄牙","ename":"Portugal","areanum":351,"fee":3},{"cname":"斯洛伐克","ename":"Slovakia","areanum":421,"fee":4},{"cname":"突尼斯","ename":"Tunisia","areanum":216,"fee":4},{"cname":"巴西","ename":"Brazil","areanum":55,"fee":4},{"cname":"瑙鲁","ename":"Nauru","areanum":674,"fee":4},{"cname":"关岛","ename":"Guam","areanum":1671,"fee":5},{"cname":"阿富汗","ename":"Afghanistan","areanum":93,"fee":4},{"cname":"荷属安的列斯","ename":"Netherlands Antilles","areanum":599,"fee":5},{"cname":"卢森堡","ename":"Luxembourg","areanum":352,"fee":5},{"cname":"哥伦比亚","ename":"Colombia","areanum":57,"fee":5},{"cname":"圣马力诺","ename":"San Marino","areanum":378,"fee":5},{"cname":"缅甸","ename":"Burma","areanum":95,"fee":4},{"cname":"海地","ename":"Haiti","areanum":509,"fee":5},{"cname":"巴拿马","ename":"Panama","areanum":507,"fee":5},{"cname":"罗马尼亚","ename":"Romania","areanum":40,"fee":4},{"cname":"奥地利","ename":"Austria","areanum":43,"fee":5},{"cname":"匈牙利","ename":"Hungary","areanum":36,"fee":4},{"cname":"马里","ename":"Mali","areanum":223,"fee":2},{"cname":"西班牙","ename":"Spain","areanum":34,"fee":3},{"cname":"孟加拉","ename":"Bangladesh","areanum":880,"fee":5},{"cname":"厄瓜多尔","ename":"Ecuador","areanum":593,"fee":5},{"cname":"德国","ename":"Germany","areanum":49,"fee":6},{"cname":"尼泊尔","ename":"Nepal","areanum":977,"fee":6},{"cname":"荷兰","ename":"Netherlands","areanum":31,"fee":6},{"cname":"比利时","ename":"Belgium","areanum":32,"fee":7}]',true);
        $china = [
            'cname' => '中国',
            'ename' => 'china',
            'areanum' => '86',
            'fee' => 1,
        ];
        array_unshift($data, $china);
        return $data;
    }
}