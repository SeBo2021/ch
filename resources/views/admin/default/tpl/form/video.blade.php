<script src="/player/spark-md5.min.js"></script>
<script src="/player/dash.all.min.js"></script>
{{--<script src="https://cdn.dashjs.org/latest/dash.all.min.js"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/spark-md5@3.0.1/spark-md5.min.js"></script>--}}
<div class="upload-area" id="aetherupload-wrapper">
    @php
        $form_item['type'] = 'hidden';
    @endphp
    @include('admin.default.tpl.form.text',['form_item'=>$form_item])
    <div class="mb-10">
{{--        <iframe id="videoIframe" width="345" height="200" src="/player/dash.html" frameborder="0" allowfullscreen></iframe>--}}
        <video width="345" height="200" id="dashjs" controls></video>
{{--        <button class="layui-btn layui-btn-white layui-btn-sm iupload-area-img-show-btn {{ $form_item['value']?'':'none' }}" type="button">删除</button>--}}
    </div>
    <div class="controls">
        <label class="layui-btn layui-btn-sm">
            <i class="layui-icon layui-icon-upload-circle"></i> {{ lang('点击上传') }}
            <input type="file" style="display: none" id="aetherupload-resource" onchange="aetherupload(this).setGroup('file').setSavedPathField('#aetherupload-savedpath').setPreprocessRoute('/aetherupload/preprocess').setUploadingRoute('/aetherupload/uploading').setLaxMode(false).success(someCallback).upload()"/>
        </label>
        <div class="progress" style="height: 6px;margin-bottom: 2px;margin-top: 10px;width: 200px;">
            <div id="aetherupload-progressbar" style="background:#2F4056;height:6px;width:0;"></div><!--需要一个名为aetherupload-progressbar的id，用以标识进度条-->
        </div>
        <span style="font-size:12px;color:#aaa;" id="aetherupload-output"></span><!--需要一个名为aetherupload-output的id，用以标识提示信息-->
        <input type="hidden" name="{{ $form_item['field'] }}" value="{{ $form_item['value'] }}" id="aetherupload-savedpath"><!--需要一个自定义名称的id，以及一个自定义名称的name值, 用以标识资源储存路径自动填充位置，默认id为aetherupload-savedpath，可根据setSavedPathField(...)设置为其它任意值-->
        <input type="hidden" id="callback_upload" name="callback_upload" value="0">
    </div>
    {{ storage_host_field() }} <!--（可选）需要标识资源服务器host地址的field，用以支持分布式部署-->
    {{ csrf_field() }} <!--需要标识csrf token的field-->
    <div id="result"></div>
</div>
<script src="{{ \Illuminate\Support\Facades\URL::asset('vendor/aetherupload/js/aetherupload-all.js') }}"></script><!--需引入aetherupload-core.js、zepto.min.js（类似jquery，更轻量化，可与jquery互相代替）、spark-md5.min.js，此文件已包含上述全部，也可分别单独引入 -->

@if(!empty($form_item['value']))
<script>
    var real_use_url = "{{ \App\Jobs\VideoSlice::get_slice_url($form_item['value'],'dash',$form_item['sync']) }}";
    console.log(real_use_url);
    // $("#videoIframe").attr('src',real_use_url);
    //=====================以上打印出同步资源地址======================
    var url = "{{ \App\Jobs\VideoSlice::get_slice_url($form_item['value']) }}";
    var playerElement = $('#dashjs');
    playerElement.show();
    var player = dashjs.MediaPlayer().create();
    player.initialize(document.querySelector('#dashjs'), real_use_url, false); //true 为自动播放
</script>
@endif
<script>
    // success(someCallback)中声名的回调方法需在此定义，参数someCallback可为任意名称，此方法将会在上传完成后被调用
    // 可使用this对象获得resourceName,resourceSize,resourceTempBaseName,resourceExt,groupSubdir,group,savedPath等属性的值
    someCallback = function () {
        let previewUrl = '{{ env('APP_URL') }}/aetherupload/display/'+this.savedPath; //预览
        $('#dashjs').attr('src',previewUrl);
        // $('#dashjs')[0].play();
        // Example
        $('#result').append(
            '<p>执行回调 - 文件已上传，原名：<span >' + this.resourceName + '</span> | 大小：<span >' + parseFloat(this.resourceSize / (1024 * 1024)).toFixed(2) + 'MB（按1kb=1024b换算得出）' + '</span> | 储存名：<span >' + this.savedPath.substr(this.savedPath.lastIndexOf('_') + 1) + '</span></p>'
        );
        $('#callback_upload').val(1);
        parent.$('.layui-layer-btn0').click();
    }
</script>
