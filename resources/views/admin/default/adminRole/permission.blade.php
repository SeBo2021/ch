        @forelse($permissions as $first)
        {{--<div id="treeList" class=""></div>
        @section('foot_js')

            <script>
                layui.use(['tree'], function(){
                    let tree = layui.tree
                        ,layer = layui.layer
                        //数据
                        ,data = @json($permissions);
                    console.log(data);
                    //基本演示
                    tree.render({
                        elem: '#treeList'
                        ,data: data
                        ,showCheckbox: true  //是否显示复选框
                        ,id: 'demoId1'
                        ,isJump: true //是否允许点击节点时弹出新窗口跳转
                        ,click: function(obj){
                            var data = obj.data;  //获取当前点击的节点数据
                            layer.msg('状态：'+ obj.state + '<br>节点数据：' + JSON.stringify(data));
                        }
                    });
                });

            </script>
        @endsection--}}
            <dl class="cate-box card shadow mb-4" >
                <dt class="card-header ">
                    <div class="cate-first"><input id="menu{{$first['id']}}" type="checkbox" name="permissions[]"
                                                   value="{{$first['id']}}" title=" {{ lang($first['cn_name']) }} "
                                                   lay-skin="primary" {{$first['own']??''}} ></div>
                </dt>
                @if(isset($first['_child']))
                    @foreach($first['_child'] as $second)
                        <dd class="border-bottom">
                            <div class="cate-second"><input id="menu{{$first['id']}}-{{$second['id']}}" type="checkbox"
                                                            name="permissions[]" value="{{$second['id']}}"
                                                            title="{{ lang($second['cn_name']) }}"
                                                            lay-skin="primary" {{$second['own']??''}}></div>
                            @if(isset($second['_child']))
                                <div class="cate-third">
                                    @foreach($second['_child'] as $thild)
                                        <input type="checkbox"
                                               id="menu{{$first['id']}}-{{$second['id']}}-{{$thild['id']}}"
                                               name="permissions[]" value="{{$thild['id']}}"
                                               title="{{ lang($thild['cn_name']) }}"
                                               lay-skin="primary" {{$thild['own']??''}}>
                                    @endforeach
                                </div>
                            @endif
                        </dd>
                    @endforeach
                @endif
            </dl>
        @empty
            {{ lang('请添加权限规则') }}
        @endforelse


