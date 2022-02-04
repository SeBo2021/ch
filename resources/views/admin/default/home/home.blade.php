@extends('admin.default.layouts.baseCont')
@section('content')
    <style>
        .grid-demo{
            height: 100px;
            text-align: center;
            padding: 10px 10px;
        }
        .layui-field-title {
            margin: 30px 0px 0px;
            border-width: 1px 0px 0px;
        }
        .layui-elem-field legend {
            margin-left: 0;
            padding: 0px 10px;
            font-size: 20px;
            font-weight: 300;
            color:black;
        }
        .layui-code {
            position: relative;
            margin: 10px 0px;
            padding: 15px;
            line-height: 30px;
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: rgb(221, 221, 221);
            border-image: initial;
            background: none;
            color: rgb(51, 51, 51);
            font-size: 18px;
        }
    </style>

    <div class="layui-card shadow panel" style="display: none">
        <div class="layui-card-header">数据总览
            <div class="panel-action"  >
                <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
            </div>
        </div>
        <div class="layui-card-body ">
            {{--<div class="layui-carousel layadmin-carousel layadmin-dataview">
                @if($channel_type==2)
                <div class="layui-row">
                    <div class="layui-col-md12">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>累计付费</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-blue" id="total_amount"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>本月付费</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="month_amount"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>今日付费</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="today_amount"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>本月订单</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="month_orders"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>今日订单</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="today_orders"></pre>
                        </div>
                    </div>
                </div>
                @else
                <div class="layui-row">
                    <div class="layui-col-md4">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>累计下载</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-blue" id="total_downloads"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md4">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>本月下载</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="month_downloads"></pre>
                        </div>
                    </div>
                    <div class="layui-col-md4">
                        <div class="grid-demo">
                            <fieldset class="layui-elem-field layui-field-title">
                                <legend>今日下载</legend>
                            </fieldset>
                            <pre class="layui-code layui-bg-black" id="today_downloads"></pre>
                        </div>
                    </div>

                </div>
                @endif
            </div>--}}
        </div>
    </div>

    <div class="layui-card shadow panel" style="display: none">
        <div class="layui-card-header">15日下载数据统计图
            <div class="panel-action"  >
                <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
            </div>
        </div>
        <div class="layui-card-body" style="height: 360px">
            <div class="layui-carousel layadmin-carousel layadmin-dataview">
                <div class="layui-col-md12" id="summaryCpsOrCpa" style="height: 100%"></div>
{{--                <div class="layui-col-md6" id="users" style="height: 100%"></div>--}}
            </div>
        </div>
    </div>



    <script src="{{ ___('admin/layui/lay/modules/laydate.js',$res_version??'') }}"></script>
    <script src="{{ ___('admin/layui/layui.js',$res_version??'') }}"></script>
    <script src="{{ ___('admin/jquery/jquery.min.js',$res_version??'') }}"></script>
    <script src="/echarts/echarts.min.js?v={{ $res_version??'' }}"></script>
    <script src="/echarts/shine.js?v={{ $res_version??'' }}"></script>
    {{--        <script src="/echarts/dark.js"></script>--}}
    <script>
        var ajaxParams = {
            /*totalStatistic:{"type":"totalStatistic"},
            activeUsers:{"type":"activeUsers"},
            recharge:{"type":"recharge"},
            increment:{"type":"increment"},
            users:{"type":"users"},*/
            dataOverview:{"type":"dataOverview","channel_type": {{$channel_type}},"channel_id": {{$channel_id}}},
            summaryCpsOrCpa:{"type":"summaryCpsOrCpa","channel_type": {{$channel_type}},"channel_id": {{$channel_id}}}
        };

        //数据总览
        var dataOverview = function (){
                /*$.get('/admin/home/list',ajaxParams.dataOverview,function (jsonRes) {
                    if(ajaxParams.dataOverview.channel_type === 2){
                        $('#total_amount').text(jsonRes.total_amount);
                        $('#month_amount').text(jsonRes.month_amount);
                        $('#today_amount').text(jsonRes.today_amount);
                        $('#month_orders').text(jsonRes.month_orders);
                        $('#today_orders').text(jsonRes.today_orders);
                    }else{
                        //
                        $('#total_downloads').text(jsonRes.total_downloads);
                        $('#month_downloads').text(jsonRes.month_downloads);
                        $('#today_downloads').text(jsonRes.today_downloads);
                    }
                });*/
            };
            // 15日图表
            summaryCpsOrCpa = function () {
                $.get('/admin/home/list',ajaxParams.summaryCpsOrCpa,function (jsonRes) {
                    let option = {};
                    if(ajaxParams.dataOverview.channel_type === 2){
                        option = {
                            legend: {
                                data: ['付费额','订单数']
                            },
                            xAxis: {
                                type: 'category',
                                data: jsonRes.x
                            },
                            yAxis: {
                                //type: 'value'
                            },
                            tooltip: {
                                trigger: 'axis'
                            },
                            toolbox: {
                                left: 'left',
                                feature: {
                                    saveAsImage: {
                                        'title':'下载'
                                    }
                                }
                            },
                            series: [
                                {
                                    name: '付费额',
                                    data: jsonRes.amount,
                                    label: {
                                        show: true
                                    },
                                    type: 'line'
                                },
                                {
                                    name: '订单数',
                                    data: jsonRes.order,
                                    label: {
                                        show: true
                                    },
                                    type: 'line'
                                },
                            ]
                        };
                    }else{
                        option = {
                            legend: {
                                data: ['下载人数']
                            },
                            xAxis: {
                                type: 'category',
                                data: jsonRes.x
                            },
                            yAxis: {
                                //type: 'value'
                            },
                            tooltip: {
                                trigger: 'axis'
                            },
                            toolbox: {
                                left: 'left',
                                feature: {
                                    saveAsImage: {
                                        'title':'下载'
                                    }
                                }
                            },
                            series: [
                                {
                                    name: '下载人数',
                                    data: jsonRes.y,
                                    /*markPoint: {
                                        data: [{
                                            type: "max"
                                        }],
                                        symbolOffset: [0, 0],
                                        symbolRotate: 2,
                                    },*/
                                    label: {
                                        show: true
                                    },
                                    type: 'line'
                                }
                            ]
                        };

                    }
                    const item = echarts.init(document.getElementById('summaryCpsOrCpa'));
                    item.setOption(option);
                });
            };


        dataOverview();
        summaryCpsOrCpa();
        //==============================
    </script>

@endsection




