<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\WhiteList;
use App\TraitClass\WhiteListTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TestController extends Controller
{

    use WhiteListTrait;
    public function aes_en(Request $request)
    {
        if(!$this->whitelistPolice()){
            return response()->json(['code' => 1, 'msg' => lang('不在白名单')]);
        }
        $input  = $request->except('s');
        $inputStr = json_encode($input,JSON_UNESCAPED_UNICODE);
        $result = Crypt::encryptString($inputStr);
        return response()->json([
            'params' => $result,
            'originParamsString' => $inputStr
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function aes_de(Request $request)
    {
        if(!$this->whitelistPolice()){
            return response()->json(['code' => 1, 'msg' => lang('不在白名单')]);
        }
        if (isset($request->params)){
            $inputStr = $request->params;
            $result = Crypt::decryptString($inputStr);
            $result = json_decode($result,true);
            return response()->json($result)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }
        dd($request->except('s'));
    }

}
