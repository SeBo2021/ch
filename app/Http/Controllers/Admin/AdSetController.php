<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdSet;
use App\Services\UiService;
use Illuminate\Http\Request;

class AdSetController extends BaseCurlController
{

    public $pageName = '广告设置';

    public function setModel()
    {
        return $this->model = new AdSet();
    }

    public function indexCols()
    {
        $cols = [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'id',
                'width' => 80,
                'title' => '编号',
                'sort' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'name',
                'minWidth' => 150,
                'title' => '广告位置名称',
                'align' => 'center',
            ],
            [
                'field' => 'flag',
                'width' => 150,
                'title' => '广告位标识',
                'align' => 'center',
//                'edit' => 1
            ],
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '是否启用',
                'align' => 'center',
            ],
            [
                'field' => 'position',
                'minWidth' => 80,
                'title' => '随机位置',
                'align' => 'center',
            ],
            [
                'field' => 'created_at',
                'minWidth' => 150,
                'title' => '创建时间',
                'align' => 'center'
            ],
            [
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];
        return $cols;
    }

    public function setListOutputItemExtend($item)
    {
        $item->status = UiService::switchTpl('status', $item);
        return $item;
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'name',
                'type' => 'text',
                'name' => '广告位置名称',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'flag',
                'type' => 'text',
                'name' => '广告位标识',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'status',
                'type' => 'radio',
                'name' => '是否启用',
                'verify' => '',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ],
            [
                'field' => 'position',
                'type' => 'text',
                'name' => '随机位置',
                'verify' => '',
                'tips' => '"整数"表示每隔几个出现广告,0表示不启用随机功能,区间"例如3:5 表示 每隔3到5个随机出现广告',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ],

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;

    }

    //表单验证
    public function checkRule($id = '')
    {
        $data = [
            'name'=>'required',
            'flag'=>'required|unique:ad_set,flag',
        ];
        //$id值存在表示编辑的验证
        if ($id) {
            $data['flag'] = 'required|unique:ad_set,name,' . $id;
        }
        return $data;
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'广告位名称',
            'flag'=>'广告位标识',
        ];
    }

    /**
     * 清理缓存
     * @param \App\TraitClass\入库 $model
     * @param string $id
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function afterSaveSuccessEvent($model, $id = '')
    {
        //清除缓存
        cache()->delete('ad_set');
        return $model;
    }

}