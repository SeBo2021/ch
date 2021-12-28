<?php

namespace App\Http\Controllers\Admin;

use App\Models\Comment;

class CommentController extends BaseCurlIndexController
{
    public $pageName = '评论';

    public function setModel()
    {
        return $this->model = new Comment();
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
                'field' => 'reply_cid',
                'minWidth' => 100,
                'title' => '回复编号',
                'sort' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'vid',
                'minWidth' => 80,
                'title' => '视频ID',
                'sort' => 1,
                'align' => 'center',
                'edit' => 1
            ],
            [
                'field' => 'uid',
                'minWidth' => 80,
                'title' => '用户ID',
                'align' => 'center',
            ],
            [
                'field' => 'content',
                'minWidth' => 150,
                'title' => '评论内容',
                'align' => 'center',
            ],
            [
                'field' => 'reply_at',
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
}