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
        <div class="layui-card shadow panel ">
            <div class="layui-card-header ">
                搜索
                <div class="panel-action">
                    <a href="#" data-perform="panel-collapse"><i title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>

            <div class="layui-card-body" id="collapseSearch">

                <div class="layui-form layui-form-pane layui-search-warp ">

                    <div class="layui-form-item ">
                        <div class="layui-form-label">
                            <span class="layui-item-text">选择渠道</span>
                        </div>
                        <div class="layui-input-block">
                            <select id="channelList" name="channelList" lay-verify="" lay-search>
                                @foreach($channels as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item ">
                        <div class="layui-form-label">
                            <span class="layui-item-text">选择日期</span>
                        </div>
                        <div class="layui-input-block">
                            <input class="layui-input" type="text" id="range_date" >
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-input-block " style="margin-left: 0;">
                            <button class="layui-btn layuiadmin-btn-admin" id="searchSubmitBtn" style="margin-top: -3px">
                                <i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
                            </button>
                        </div>
                    </div>


                </div>

            </div>

        </div>

        <div class="layui-card shadow panel">
            <div class="layui-card-header">总量统计(近一月)
                <div class="panel-action"  >
                    <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>
            <div class="layui-card-body ">
                <div class="layui-carousel layadmin-carousel layadmin-dataview">
                    <div class="layui-input-block" style="text-align: center;margin-left: 0">
                        <div class="layui-btn-group demoTest" style="margin-top: 5px;margin-bottom: 10px;">
                            <button class="layui-btn layui-btn-sm" style="background-color: #5FB878;" data-type="set" data-key="totalStatistic" data-value="0">All</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="totalStatistic" data-value="2">Android</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="totalStatistic" data-value="1">IOS</button>
                        </div>
                    </div>
                    <div class="layui-row">
                        <div class="layui-col-md3">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>访问量</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-blue" id="total_access"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md3">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>点击量</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-green" id="total_hits"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md3">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>安装量</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-cyan" id="total_install"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md3">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>注册量</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-black" id="total_register"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>平均1日后留存率</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-black" id="avg_keep_day_rate"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>平均7日后留存率</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-orange" id="avg_keep_week_rate"></pre>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="grid-demo">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>平均30日后留存率</legend>
                                </fieldset>
                                <pre class="layui-code layui-bg-red" id="avg_keep_month_rate"></pre>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="layui-card shadow panel" >
            <div class="layui-card-header">日活用户
                <div class="panel-action"  >
                    <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>
            <div class="layui-card-body" style="height: 360px">

                <div class="layui-carousel layadmin-carousel layadmin-dataview">
                    <div class="layui-input-block" style="text-align: center;margin-left: 0;">
                        <div class="layui-btn-group demoTest" style="margin-top: 5px;margin-bottom: 10px;">
                            <button class="layui-btn layui-btn-sm" style="background-color: #5FB878;" data-type="set" data-key="activeUsers" data-value="0">All</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="activeUsers" data-value="2">Android</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="activeUsers" data-value="1">IOS</button>
                        </div>
                    </div>
                    <div class="layui-col-md6" id="activeUsers" style="height: 100%"></div>
                    <div class="layui-col-md6" id="users" style="height: 100%"></div>
                </div>
            </div>
        </div>

        <div class="layui-card shadow panel" >
            <div class="layui-card-header">增长趋势
                <div class="panel-action"  >
                    <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>
            <div class="layui-card-body" style="height: 450px">
                <div class="layui-carousel layadmin-carousel layadmin-dataview">
                    <div class="layui-input-block" style="text-align: center;margin-left: 0;height: 50px">
                        <div class="layui-btn-group demoTest" style="margin-top: 5px;margin-bottom: 10px;">
                            <button class="layui-btn layui-btn-sm" style="background-color: #5FB878;" data-type="set" data-key="increment" data-value="0">All</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="increment" data-value="2">Android</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="increment" data-value="1">IOS</button>
                        </div>
                    </div>
                    <div class="" id="increment" style="height: 400px"></div>
                </div>
            </div>
        </div>

        <div class="layui-card shadow panel">
            <div class="layui-card-header">充值图表
                <div class="panel-action"  >
                    <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>
            <div class="layui-card-body ">
                <div class="layui-carousel layadmin-carousel layadmin-dataview">
                    <div class="layui-input-block" style="text-align: center;margin-left: 0;">
                        <div class="layui-btn-group demoTest" style="margin-top: 5px;margin-bottom: 10px;">
                            <button class="layui-btn layui-btn-sm" style="background-color: #5FB878;" data-type="set" data-key="recharge" data-value="0">All</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="recharge" data-value="2">Android</button>
                            <button class="layui-btn layui-btn-primary layui-btn-sm" data-type="set" data-key="recharge" data-value="1">IOS</button>
                        </div>
                    </div>
                    <div class="" id="recharge" style="height: 100%"></div>
                </div>
            </div>
        </div>

        <div class="layui-card shadow panel">
            <div class="layui-card-header">IP分布
                <div class="panel-action"  >
                    <a href="#" data-perform="panel-collapse"><i  title="点击可折叠" class="layui-icon layui-icon-subtraction"></i></a>
                </div>
            </div>
            <div class="layui-card-body " style="height: 600px">
                <div class="layui-carousel layadmin-carousel layadmin-dataview">
                    <div class="" id="IPDistribution" style="height: 600px"></div>
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
                totalStatistic:{"type":"totalStatistic"},
                activeUsers:{"type":"activeUsers"},
                recharge:{"type":"recharge"},
                increment:{"type":"increment"},
                users:{"type":"users"},
                IPDistribution:{"type":"IPDistribution"}
            };

            //总量统计
            var totalStatistic = function (){
                $.get('/admin/statistics/list',ajaxParams.totalStatistic,function (jsonRes) {
                    $('#total_access').text(jsonRes.access);
                    $('#total_hits').text(jsonRes.hits);
                    $('#total_install').text(jsonRes.install);
                    $('#total_register').text(jsonRes.register);
                    $('#avg_keep_day_rate').text(jsonRes.keep1AG);
                    $('#avg_keep_week_rate').text(jsonRes.keep7AG);
                    $('#avg_keep_month_rate').text(jsonRes.keep30AG);
                });
            },//日活
            activeUsers = function () {
                $.get('/admin/statistics/list',ajaxParams.activeUsers,function (jsonRes) {
                    var option = {
                        legend: {
                            data: ['日活数']
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
                                name: '日活数',
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
                    var  item = echarts.init(document.getElementById('activeUsers'));
                    item.setOption(option);
                });
            },//充值订单
            recharge = function () {
                $.get('/admin/statistics/list',ajaxParams.recharge,function (jsonRes) {
                    var option = {
                        legend: {
                            data: ['充值金额']
                        },
                        aria: {
                            show: true
                        },
                        tooltip: {
                            trigger: 'axis'
                        },
                        xAxis: {
                            type: 'category',
                            data: jsonRes.x
                        },
                        yAxis: {
                            type: 'value'
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
                                name: '充值金额',
                                data: jsonRes.y,
                                type: 'line'
                            }
                        ]
                    };
                    var  item = echarts.init(document.getElementById('recharge'));
                    item.setOption(option);
                });
            },//用户
            users = function () {
                $.get('/admin/statistics/list',ajaxParams.users,function (jsonRes) {
                var option = {
                    aria: {
                        show: true
                    },
                    title: {
                        text: '设备系统',
                        x: 'center'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    toolbox: {
                        feature: {
                            saveAsImage: {
                                'title':'下载'
                            }
                        }
                    },
                    series: [
                        {
                            id: 'pie',
                            label: {
                                formatter: '{b}: {@2012} ({d}%)'
                            },
                            name: '手机系统',
                            type: 'pie',
                            data: jsonRes
                        }
                    ]
                };
                var  item = echarts.init(document.getElementById('users'));
                //console.log(jsonRes);
                item.setOption(option);
            });
            },
            increment = function () {
                $.get('/admin/statistics/list',ajaxParams.increment,function (jsonRes) {
                    var option = {
                        aria: {
                            show: true
                        },
                        tooltip:{
                            trigger:"axis",
                            formatter:function(a){
                                if(!a.length)return null;
                                var e=a[0].name;
                                    // var n=i[a[0].dataIndex];
                                jsonRes.series.total_keep_day_rate
                                a.forEach(
                                    function(a){
                                        var keepValue = a.value;
                                        switch (a.seriesIndex) {
                                            case 4:
                                                keepValue = keepValue+'('+jsonRes.series.total_keep_day_rate[a.dataIndex]+'%)';
                                                break;
                                            case 5:
                                                keepValue = keepValue+'('+jsonRes.series.total_keep_week_rate[a.dataIndex]+'%)';
                                                break;
                                            case 6:
                                                keepValue = keepValue+'('+jsonRes.series.total_keep_month_rate[a.dataIndex]+'%)';
                                                break;
                                        }
                                        e+="<br/>"+a.marker+a.seriesName+" : "+keepValue.toLocaleString();
                                        // console.log(a.dataIndex);
                                        // "1日后留存数"!=a.seriesName&&"7日后留存数"!=a.seriesName&&"30日后留存数"!=a.seriesName||(e+=" ("+(0==n?0:100*a.value/n).toFixed(2)+"%)")
                                    })
                                return e;
                            }
                        },
                        legend: {
                            data: ['访问量', '点击量', '安装量', '注册量', '1日后留存数', '7日后留存数', '30日后留存数']
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        toolbox: {
                            feature: {
                                saveAsImage: {
                                    'title':'下载'
                                }
                            }
                        },
                        xAxis: {
                            type: 'category',
                            boundaryGap: false,
                            data: jsonRes.x,

                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: [
                            {
                                name: '访问量',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_access
                            },
                            {
                                name: '点击量',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_hits
                            },
                            {
                                name: '安装量',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_install
                            },
                            {
                                name: '注册量',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_register
                            },
                            {
                                name: '1日后留存数',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_keep_day_users
                            },
                            {
                                name: '7日后留存数',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_keep_week_users
                            },
                            {
                                name: '30日后留存数',
                                type: 'line',
                                smooth: true,
                                data: jsonRes.series.total_keep_month_users
                            }
                        ]
                    };
                    var item = echarts.init(document.getElementById('increment'));
                    item.setOption(option);
                });
            },
            IPDistribution = function () {
                $.get('/admin/statistics/list',ajaxParams.IPDistribution,function (jsonRes) {
                    var jsonData = {
                        android:jsonRes.android,
                        ios:jsonRes.ios,
                        min:jsonRes.min,
                        max:jsonRes.max
                    };
                    $.get('/echarts/china.json', function (geoJson) {
                        echarts.registerMap('china', {geoJSON: geoJson});
                        var item = echarts.init(document.getElementById('IPDistribution'));
                        item.setOption({
                            title: {
                                text : 'IP分布',
                                textAlign: 'center',
                                left:'40%',
                                /*textStyle:{
                                    fontSize:'16'
                                }*/
                            },
                            legend:{

                            },
                            tooltip: {
                                trigger: 'item'
                            },
                            visualMap: {
                                left: 'right',
                                min: jsonData.min,
                                max: jsonData.max,
                                inRange: {
                                    color: [
                                        '#313695',
                                        '#4575b4',
                                        '#74add1',
                                        '#abd9e9',
                                        '#e0f3f8',
                                        '#ffffbf',
                                        '#fee090',
                                        '#fdae61',
                                        '#f46d43',
                                        '#d73027',
                                        '#a50026'
                                    ]
                                },
                                text: ['高', '低'],
                                calculable: true
                            },
                            toolbox: {
                                show: true,
                                //orient: 'vertical',
                                left: 'left',
                                top: 'top',
                                feature: {
                                    dataView: {
                                        'title':'视图',
                                        lang:['数据视图', '关闭', '刷新'],
                                        readOnly: false
                                    },
                                    restore: {
                                        'title':'还原'
                                    },
                                    saveAsImage: {
                                        'title':'下载'
                                    }
                                }
                            },
                            series: [{
                                name: 'Android',
                                type: 'map',
                                map: 'china',
                                roam: true,
                                showLegendSymbol:false,
                                data: jsonData.android
                            },{
                                name: 'IOS',
                                type: 'map',
                                map: 'china',
                                roam: true,
                                showLegendSymbol:false,
                                data: jsonData.ios
                            }
                            ]
                        });
                    });
                });
            };

            var func = {
                totalStatistic:totalStatistic,
                activeUsers:activeUsers,
                recharge:recharge,
                increment:increment,
                IPDistribution:IPDistribution,
                users:users
            };

            var active = {
                set: function(othis){
                    var THIS = 'layui-btn-primary'
                        ,key = othis.data('key')
                        ,options = {};
                    othis.removeClass('layui-btn-primary').css('background-color', '#5FB878').css('border-left', 0).siblings().removeClass('layui-btn-primary').removeAttr('style').addClass('layui-btn-primary');
                    options[key] = othis.data('value');
                    ajaxParams[key].deviceSystem = othis.data('value');
                    func[key]();
                    console.log(ajaxParams);
                    //todo
                }
            };
            //选择日期
            //console.log(layui.laydate);
            laydate.render({
                elem: '#range_date'
                ,type: 'datetime' //默认，可不填
                ,range:'~'
            });
            //搜索
            $('#searchSubmitBtn').click(function () {
                var channelId = $('#channelList').val();
                var rangDate = $('#range_date').val();
                for (let key in ajaxParams) {
                    //console.log(key) // name age
                    ajaxParams[key].channel_id = channelId;
                    ajaxParams[key].range_date = rangDate;
                    func[key]();
                }
            })

            //=======
            $('.demoTest .layui-btn').on('click', function(){
                var othis = $(this), type = othis.data('type');
                active[type] ? active[type].call(this, othis) : '';
            });
            totalStatistic();
            activeUsers();
            users();
            recharge();
            increment();
            IPDistribution();
            //==============================
        </script>

    @endsection




