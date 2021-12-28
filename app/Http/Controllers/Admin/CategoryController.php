<?php
// +----------------------------------------------------------------------
// | KQAdmin [ 基于Laravel后台快速开发后台 ]
// | 快速laravel后台管理系统，集成了，图片上传，多图上传，批量Excel导入，批量插入，修改，添加，搜索，权限管理RBAC,验证码，助你开发快人一步。
// +----------------------------------------------------------------------
// | Copyright (c) 2012~2019 www.haoxuekeji.cn All rights reserved.
// +----------------------------------------------------------------------
// | Laravel 原创视频教程，文档教程请关注 www.heibaiketang.com
// +----------------------------------------------------------------------
// | Author: kongqi <531833998@qq.com>`
// +----------------------------------------------------------------------

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Services\UiService;
use App\TraitClass\PHPRedisTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends BaseCurlController
{
    use PHPRedisTrait;
    //那些页面不共享，需要单独设置的方法
    //设置页面的名称
    public $pageName = '分类栏目';

    public $denyCommonBladePathActionName = ['index','create','edit'];

    public $appModuleStyle = [
        0 => [
            'id' => 0,
            'name' => '无'
        ],
        1 => [
            'id' => 1,
            'name' => '横中'
        ],
        2 => [
            'id' => 2,
            'name' => '横中大'
        ],
        3 => [
            'id' => 3,
            'name' => '横小'
        ],
        4 => [
            'id' => 4,
            'name' => '横大二'
        ],
        5 => [
            'id' => 5,
            'name' => '横大一'
        ],
        6 => [
            'id' => 6,
            'name' => '双连'
        ],
        7 => [
            'id' => 7,
            'name' => '大一偶小'
        ],
        8 => [
            'id' => 8,
            'name' => '新横大二'
        ],
    ];

    //1.设置模型
    public function setModel()
    {
        return $this->model = new Category();
    }

    //2.首页的数据表格数组
    public function indexCols()
    {
        //这里99%跟layui的表格设置参数一样
        $data = [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'id',
                'minWidth' => 80,
                'title' => '编号',
                'sort' => 1,
                'align' => 'center'
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
                'field' => 'name',
                'minWidth' => 150,
                'title' => '名称',
                'align' => 'center',

            ],
            [
                'field' => 'seo_title',
                'minWidth' => 150,
                'title' => '标题',
                'align' => 'center',
                'hide'=>true
            ],
            [
                'field' => 'group_type',
                'minWidth' => 100,
                'title' => '版块类型',
                'align' => 'center'
            ],
            [
                'field' => 'limit_display_num',
                'minWidth' => 80,
                'title' => '显示数量',
                'edit' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'group_bg_img',
                'minWidth' => 150,
                'title' => '背景图',
                'align' => 'center',
                'hide'=>true
            ],
            [
                'field' => 'local_bg_img',
                'minWidth' => 150,
                'title' => '本地背景图',
                'align' => 'center',
                'hide'=>true
            ],
            [
                'field' => 'path_level',
                'minWidth' => 100,
                'title' => '层级',
                'align' => 'center',
                'hide'=>true
            ],
            [
                'field' => 'is_free',
                'minWidth' => 80,
                'title' => '是否免费',
                'align' => 'center',
                'hide'=>true
            ],
            [
                'field' => 'is_rand',
                'minWidth' => 80,
                'title' => '是否随机显示',
                'align' => 'center'
            ],
            [
                'field' => 'is_checked_html',
                'minWidth' => 80,
                'title' => '状态',
                'align' => 'center',
            ],
            [
                'field' => 'created_at',
                'minWidth' => 150,
                'title' => '创建时间',
                'align' => 'center',
                'hide'=>true
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

    public function setListOutputItemExtend($item)
    {
        $appModuleStyle = $this->appModuleStyle;
        $item->is_free = UiService::switchTpl('is_free', $item,'','是|否');
        $item->is_rand = UiService::switchTpl('is_rand', $item,'','是|否');
        $item->group_type = $appModuleStyle[$item->group_type]['name'];
        return $item;
    }

    //首页按钮设置
    public function setOutputHandleBtnTpl($shareData)
    {
        $default = $this->defaultHandleBtnAddTpl($shareData);
        $data = [

            [
                'class' => 'btn-success',
                'name' => '全部展开',
                'id' => 'btn-expand',

            ],
            [
                'class' => 'btn-dark',
                'name' => '全部折叠',
                'id' => 'btn-fold',
            ],

        ];
        //是否具有批量添加权限
        if (($this->isCanBatch())) {
            /*$data[] = [
                'class' => 'btn-info',
                'name' => '批量添加',
                'data' => [
                    'data-type' => "custormAdd",
                    'data-url' => $this->batchIndexData()['all_create_url'],
                    'data-post_url' => $this->batchIndexData()['all_post_url'],
                    'data-title' => '批量添加' . $this->getPageName(),
                    'data-w' => $this->layuiOpenWidth(),
                    'data-h' => $this->layuiOpenHeight()
                ]
            ];*/
            //下载模板
            $data[]=[
                'class'=>'btn-secondary',
                'name' => '下载导入模板',
                'data'=>[
                    'data-event'=>"link",
                    'data-url'=>$this->batchIndexData()['import_tpl_url'],

                ]
            ];
            //导入数据
            $data[]=[
                'class'=>'btn-dark',
                'name' => '导入Excel',
                'data'=>[
                    'data-type'=>"import",
                    'data-post_url'=>$this->batchIndexData()['import_post_url'],
                    'data-title'=>'导入添加'.$this->getPageName(),
                    'data-w'=>$this->layuiOpenWidth(),
                    'data-h'=>$this->layuiOpenHeight()
                ]
            ];
        }
        $data = array_merge($default, $data);
        //赋值到ui数组里面必须是`btn`的key值
        $this->uiBlade['btn'] = $data;

    }

    //3.设置搜索数据表单
    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            [
                'field' => 'id',
                'type' => 'text',
                'name' => 'ID',
            ],
            [
                'field' => 'query_like_name',//这个搜索写的查询条件在app/TraitClass/QueryWhereTrait.php 里面写
                'type' => 'text',
                'name' => '名称',
            ],
            [
                'field' => 'query_is_checked',
                'type' => 'select',
                'name' => '是否启用',
                'default' => '',
                'data' => $this->uiService->trueFalseData(1)
            ]

        ];
        //赋值给UI数组里面，必须是search这个key
        $this->uiBlade['search'] = $data;
    }

    //4.编辑和添加页面表单数据
    public function setOutputUiCreateEditForm($show = '')
    {
        //如果是批量添加位置，需要把name转换成textarea
        $name = [];
        if ($this->createFormCurrent == 'batch') {
            $name['type'] = 'textarea';
            $name['mark'] = '一行一条记录';
        } else {
            $name['type'] = 'text';
        }
        $data = [
            [
                'field' => 'name',
                'type' => $name['type'],
                'name' => '名称',
                'must' => 1,
                'verify' => 'rq',
                'mark' => $name['mark'] ?? ''
            ],
            [
                'field' => 'parent_id',
                'name' => '上级',
                'must' => 1,
                'verify' => 'rq',
                'default' => 0,
                'blade_name'=>'adminPermission.parent',
                'type'=>'blade',
            ],
            [
                'field' => 'seo_title',
                'type' => 'text',
                'name' => '标题',
                'default' => '',
            ],
            [
                'field' => 'group_type',
                'type' => 'radio',
                'name' => '版块类型',
                'must' => 0,
                'default' => 0,
                'verify' => 'rq',
                'data' => $this->appModuleStyle
            ],
            [
                'field' => 'group_bg_img',
                'type' => 'img',
                'name' => '背景图',
            ],
            [
                'field' => 'local_bg_img',
                'type' => 'number',
                'name' => '本地背景图',
                'default' => 1,
            ],
            [
                'field' => 'limit_display_num',
                'type' => 'number',
                'name' => '版块显示数量',
                'default' => 8,
            ],
            [
                'field' => 'sort',
                'type' => 'text',
                'name' => '排序',
                'must' => 1,
                'default' => 0,
                'verify' => 'rq'
            ],
            [
                'field' => 'is_free',
                'type' => 'radio',
                'name' => '是否免费',
                'verify' => '',
                'default' => 0,
                'data' => $this->uiService->trueFalseData()
            ],
            [
                'field' => 'is_rand',
                'type' => 'radio',
                'name' => '是否随机',
                'verify' => '',
                'default' => 0,
                'data' => $this->uiService->trueFalseData()
            ],
            [
                'field' => 'is_checked',
                'type' => 'radio',
                'name' => '是否启用',
                'verify' => '',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ]

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;

    }
    //编辑和添加页面共享数据
    public function createEditShareData($id = '')
    {
        $cate = $this->getModel()->checked()->get()->toArray();
        $cate=array_merge([['id'=>0,'name'=>'根级','parent_id'=>0]],$cate);
        $cate = tree($cate,'id','parent_id','children');
        return [ 'category' => $cate];
    }

    //表单验证
    public function checkRule($id = '')
    {
        return [
            'name'=>'required',
            'parent_id'=>'required',
        ];
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'名称',
            'parent_id'=>'上级',
        ];
    }

    protected function afterSaveSuccessEvent($model, $id = '')
    {
        $redis = $this->redis();
        if ($model->parent_id != 0) {
            //上级
            $parent = $this->model->find($model->parent_id);
            $next = $parent->path_level;
            $next = $next ? $next . '-' : '';
            $model->path_level = $next . $model->id;
            //清除缓存
            if($model->parent_id==2){
                $keys = $redis->keys(($this->apiRedisKey['home_lists']).$model->id.'-*');
            }else{
                $keys = $redis->keys(($this->apiRedisKey['home_lists']).$parent->id.'-*');
            }
            $this->redisBatchDel($keys,$redis);
            $model->save();
        } else {
            $model->path_level = $model->id;
            $model->save();
        }

        return $model;
    }


    /**
     * 批量写入数据的数据
     * @param Request $request
     */
    public function batchCreateSetData(Request $request)
    {
        $name = $request->input('name');
        if (empty($name)) {
            return [];
        }
        $name = explode("\n", $name);

        $data = [];
        foreach ($name as $k => $v) {
            if($v===''){
                continue;
            }
            $data[] = [
                'name' => $v,
                'parent_id' => $request->input('parent_id'),
                'sort' => $request->input('sort'),
                'is_checked' => $request->input('is_checked')
            ];

        }
        return $data;
    }

    /**
     * 批量成功添加之后 需要设置层级
     * @param array $data 数据
     */
    public function afterBatchSuccessCreateEvent(array $data)
    {
        foreach ($data as $k=>$v){
            $model=Category::where('name',$v['name'])->first();
            if ($model->parent_id != 0) {
                //上级
                $next = $this->model->find($model->parent_id)->path_level;
                $next = $next ? $next . '-' : '';
                $model->path_level = $next . $model->id;
                $model->save();
            } else {
                $model->path_level = $model->id;
                $model->save();
            }

        }
    }
    //批量导入之后的层级修改
    public function afterImportSuccessEvent($insert_data)
    {
        foreach ($insert_data as $k=>$v){
            $model=Category::where('name',$v['name'])->first();
            if ($model->parent_id != 0) {
                //上级
                $next = $this->model->find($model->parent_id)->path_level;
                $next = $next ? $next . '-' : '';
                $model->path_level = $next . $model->id;
                $model->save();
            } else {
                $model->path_level = $model->id;
                $model->save();
            }

        }
    }

    //刪除检查
    public function checkDelet($id_arr)
    {
        $childs = $this->getModel()->whereIn('parent_id', $id_arr)->count();
        if ($childs) {
            return lang('存在子级，请先删除子级再删除');
        }
    }

    //弹窗大小
    public function layuiOpenWidth()
    {
        return '55%'; // TODO: Change the autogenerated stub
    }

    public function layuiOpenHeight()
    {
        return '70%'; // TODO: Change the autogenerated stub
    }

}