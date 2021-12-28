<?php

namespace App\TraitClass;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait ApiParamsTrait
{
    /**
     * @param $params
     * @param false $combine 是否合并成字符串
     * @return array|mixed|string
     */
    public static function parse($params,$combine = false)
    {
        if(\Illuminate\Support\Env::get('MIDDLEWARE_SECRET')) {
            $p = Crypt::decryptString($params);
            $p = json_decode($p,true);
            if ($combine) {
                return implode(',',($p['params'] ?? $p));
            }
            return $p['params'] ?? $p;
        }else{
            if(is_array($params)){
                if ($combine) {
                    return implode(',',$params);
                }
                return $params;
            }else{
                return json_decode($params,true);
            }
        }
    }

    /**
     * 统一输出格式
     * @param int $status
     * @param array $data
     * @param string $message
     * @return array
     */
    public function format($status = 0,$data = [],$message = ''): array
    {
        return [
            'state' => $status,
            'data' => $data,
            'msg' => $message,
        ];
    }
}