<?php

namespace App\TraitClass;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait StatisticTrait
{
    public function getDateArr($t=null): array
    {
        $time = $t ?? time();
        $dateArr['at'] = date('Y-m-d H:i:s',$time);
        $dateArr['time'] = $time;
        $dateArr['day'] = date('Y-m-d',$time);
        $dateArr['day_time'] = strtotime($dateArr['day']);
        return $dateArr;
    }

    public function saveStatisticByDay($field,$channel_id,$device_system,$date=null)
    {
        $dateArr = $date ?? $this->getDateArr();
        $queryBuild = DB::table('statistic_day')
            ->where('channel_id',$channel_id)
            ->where('device_system',$device_system)
            ->where('at_time',$dateArr['day_time']);
        $one = $queryBuild->first(['id',$field]);

        if($one){
            $queryBuild->increment($field);
            //更新扣量表
            if($channel_id > 0){
                $deductionValue = DB::table('channels')->where('id',$channel_id)->value('deduction');
                $stepValue = round(1*(1-$deductionValue/10000),2) * 100;
                DB::table('statistic_day_deduction')
                    ->where('channel_id',$channel_id)
                    ->where('device_system',$device_system)
                    ->where('at_time',$dateArr['day_time'])
                    ->increment($field,$stepValue);
            }
        }else{
            $insertData = [
                'channel_id' => $channel_id,
                $field => 1,
                'device_system' => $device_system,
                'at_time' => $dateArr['day_time'],
            ];
            $insertDeductionData = $insertData;
            if($channel_id > 0){
                $deductionValue = DB::table('channels')->where('id',$channel_id)->value('deduction');
                $insertDeductionData[$field] = round(1*(1-$deductionValue/10000),2) * 100;
            }
            DB::beginTransaction();
            DB::table('statistic_day')->insert($insertData);
            DB::table('statistic_day_deduction')->insert($insertDeductionData);
            DB::commit();
        }
    }

    //保存活跃用户数据
    public function saveUsersDay($uid,$channel_id,$device_system)
    {
        $at_time = strtotime(date('Y-m-d'));
        //
        $userHadCome = DB::table('users_day')->where('uid',$uid)->where('at_time',$at_time)->first(['id','uid']);
        //Log::debug('saveUsersDay===user_come:',[$userHadCome]);
        if(!$userHadCome){
            DB::table('users_day')->insert([
                'uid' => $uid,
                'at_time' => $at_time,
                'channel_id' => $channel_id,
                'device_system' => $device_system,
            ]);
        }

        //更新统计扣量的表
        if($channel_id > 0){
            $first = DB::table('statistic_users_day_deduction')
                ->where('at_time',$at_time)
                ->where('channel_id',$channel_id)
                ->where('device_system',$device_system)
                ->first(['id']);
            $deductionValue = DB::table('channels')->where('id',$channel_id)->value('deduction');
            $stepValue = round(1*(1-$deductionValue/10000),2) * 100;
            if(!$first){
                DB::table('statistic_users_day_deduction')->insert([
                        'users' => $stepValue,
                        'at_time' => $at_time,
                        'channel_id' => $channel_id,
                        'device_system' => $device_system,
                    ]);
            }else{
                if(!$userHadCome){
                    DB::table('statistic_users_day_deduction')
                        ->where('at_time',$at_time)
                        ->where('channel_id',$channel_id)
                        ->where('device_system',$device_system)
                        ->increment('users',$stepValue);
                }
            }
        }


    }

    /**
     * 通用用户表修复统计数据
     * @param $channelId 渠道id
     * @param $deviceSystem 设备类型
     * @param $timeRange 时间区域
     * @param $startDate 开始时间
     * @param $endDate 结束时间
     * @return array 返回值
     */
    public function fixDataByUserTable($channelId, $deviceSystem, $timeRange, $startDate, $endDate,$isGroup = false)
    {
        $userModel = DB::table('users')
            ->where(function ($query) use($channelId,$deviceSystem,$timeRange,$startDate,$endDate){
                if($channelId!==null){
                    $query->where('channel_id',$channelId);
                }

                if( $deviceSystem>0 ){
                    $query->where('device_system',$deviceSystem);
                }

                if($timeRange != 0){
                    $query->whereBetween('created_at',[$startDate,$endDate]);
                }
            });
        if ($isGroup) {
            $totalUesrData = $userModel->select('device_system',DB::raw('count(1) as value'))
                ->where('device_system','>',0)
                ->groupBy(['device_system'])->get();
            return $totalUesrData;
        } else {
            $totalUesrData = $userModel->select(DB::raw('count(1) as install'),DB::raw('sum( CASE WHEN phone_number = "0" THEN 0 ELSE 1 END ) AS register'))->first();
        }
        $newInstall = $totalUesrData->install??0;
        $newRegister = $totalUesrData->register??0;
        return [
            'newInstall' => $newInstall,
            'newRegister' => $newRegister,
        ];
    }

}