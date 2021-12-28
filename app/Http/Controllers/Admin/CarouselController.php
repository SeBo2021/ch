<?php


namespace App\Http\Controllers\Admin;


use App\Models\Carousel;
use App\Models\Category;
use App\Services\UiService;

class CarouselController extends BaseCurlController
{
    public $pageName = '轮播图管理';

    public function setModel()
    {
        return $this->model = new Carousel();
    }

    public function getCateGoryData()
    {
        return array_merge($this->uiService->allDataArr('请选择分类'), $this->uiService->treeData(Category::checked()->get()->toArray(), 0));//树形select
    }

    public function indexCols()
    {
        $data = [
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
                'field' => 'category_name',
                'width' => 150,
                'title' => '分类',
                'align' => 'center',
//                'edit' => 1
            ],
            [
                'field' => 'sort',
                'width' => 80,
                'title' => '排序',
                'sort' => 1,
                'align' => 'center',
                'edit' => 1
            ],
            [
                'field' => 'title',
                'minWidth' => 150,
                'title' => '标题',
                'align' => 'center',

            ],
            [
                'field' => 'img',
                'minWidth' => 100,
                'title' => '图片',
                'align' => 'center',

            ],
            [
                'field' => 'url',
                'minWidth' => 100,
                'title' => '链接地址',
                'align' => 'center',

            ],
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '状态',
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
        //要返回给数组
        return $data;
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'cid',
                'type' => 'select',
                'name' => '分类',
                'must' => 1,
                'verify' => 'rq',
                'default' => 0,
                'data' => $this->getCateGoryData()
            ],
            [
                'field' => 'title',
                'type' => 'text',
                'name' => '标题',
                'must' => 0,
                'default' => '',
            ],
            [
                'field' => 'img',
                'type' => 'img',
                'name' => '图片',
                'must' => 1,
            ],
            [
                'field' => 'url',
                'type' => 'text',
                'name' => '链接地址',
                'must' => 0,
                'default' => '',
            ],
            [
                'field' => 'sort',
                'type' => 'text',
                'name' => '排序',
                'must' => 0,
                'default' => '',
            ],

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }

    public function setListOutputItemExtend($item)
    {
        $item->category_name = $item->category['name'] ?? '';
        $item->status = UiService::switchTpl('status', $item,'');
        return $item;
    }
    //表单验证
    public function checkRule($id = '')
    {
        return [
//            'title'=>'required',
            'img'=>'required',
//            'url'=>'required',
        ];
    }

    public function checkRuleFieldName($id = '')
    {
        return [
//            'title'=>'标题',
            'img'=>'图片',
        ];
    }

}
