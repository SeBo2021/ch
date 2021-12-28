<?php
/**
 * 大白鲨支付查询
 */

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PayLog;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\PayTrait;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class DBSQuery
 * @package App\Console\Commands
 */
class DBSQuery extends Command
{
    use PayTrait;
    use ApiParamsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbs_query {order_id?} {process=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '大白鲨定时查询订单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return bool
     * @throws GuzzleException
     */
    public function handle(): bool
    {
        $arguments = $this->arguments();
        $orderId = $arguments['order_id'];
        $process = $arguments['process'];
        $this->info(lang('开始查询订单'));
        try {
            $data = PayLog::query()->where(function ($query) use ($orderId) {
                if ($orderId) {
                    $query->where(['id' => $orderId]);
                }
            })->orderBy('id')
                ->get()?->toArray();
            array_map(function ($payInfo) use ($process) {
                if (($payInfo['status'] == 1) && $process) {
                    $this->info("订单已经支付,订单id{$payInfo['order_id']}");
                    return;
                }
                sleep(3);
                $orderInfo = Order::query()->find($payInfo['order_id']);
                if (!$orderInfo) {
                    throw new Exception("订单不存在");
                }
                $mercId = SELF::getPayEnv()['DBS']['merchant_id'];
                $input = [
                    'mercId' => $mercId,
                    'tradeNo' => strval($payInfo['number']),
                ];
                Log::info('dbs_query_params===', [$input]);//三方参数日志
                $response = (new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]))->get(SELF::getPayEnv()['DBS']['query_url'], [
                    'body' => json_encode($input),
                    'verify' => false
                ])->getBody()->getContents();
                Log::info('dbs_query_response===', [$response]);//三方响应日志
                $content = json_decode($response, true);
                if ($process && ($content['msg']['payStatus'] == '已支付')) {
                    DB::beginTransaction();
                    $this->orderUpdate($orderInfo->number ?? '', $response);
                    DB::commit();
                } else {
                    $this->info('查询结果为:' . $response);
                }
            }, $data);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info('dbs_query_error===', [$e]);
        }
        $this->info(lang('操作成功'));
        return true;
    }
}
