{{--<script src="/player/dash.all.min.js"></script>--}}
{{--<script src="/player/spark-md5.min.js"></script>--}}
<script src="https://cdn.jsdelivr.net/npm/spark-md5@3.0.1/spark-md5.min.js"></script>
<script src="https://cdn.dashjs.org/latest/dash.all.min.js"></script>
<div class="upload-area" id="aetherupload-wrapper">
    @php
        $form_item['type'] = 'hidden';
    @endphp
    @include('admin.default.tpl.form.text',['form_item'=>$form_item])
    <div class="mb-10">
        <video width="345" height="200" id="dashjs" controls oncanplaythrough="playthrough(this)"></video>
{{--        <button class="layui-btn layui-btn-white layui-btn-sm iupload-area-img-show-btn {{ $form_item['value']?'':'none' }}" type="button">删除</button>--}}
    </div>
    <div class="controls">

        <div class="progress" style="height: 6px;margin-bottom: 2px;margin-top: 10px;width: 200px;">
            <div id="aetherupload-progressbar" style="background:#2F4056;height:6px;width:0;"></div><!--需要一个名为aetherupload-progressbar的id，用以标识进度条-->
        </div>
        <span style="font-size:12px;color:#aaa;" id="aetherupload-output"></span><!--需要一个名为aetherupload-output的id，用以标识提示信息-->
        <input type="hidden" name="{{ $form_item['field'] }}" value="{{ $form_item['value'] }}" ><!--需要一个自定义名称的id，以及一个自定义名称的name值, 用以标识资源储存路径自动填充位置，默认id为aetherupload-savedpath，可根据setSavedPathField(...)设置为其它任意值-->
        <input type="hidden" id="video_duration" name="video_duration" value="0">
        {{ csrf_field() }} <!--需要标识csrf token的field-->
    </div>
</div>
<script src="{{ URL::asset('vendor/aetherupload/js/aetherupload-all.js') }}"></script>

@if(!empty($form_item['value']))
<script>
    var url = "{{ \App\Http\Controllers\Api\FileUploadController::getUrl($form_item['value']) }}";
    console.log(url);
    $('#dashjs').attr('src',url);
    /*var playerElement = $('#dashjs');
    playerElement.show();
    var player = dashjs.MediaPlayer().create();
    player.initialize(document.querySelector('#dashjs'), url, false); //true 为自动播放*/
</script>
@endif
<script>
    function playthrough(ele) {
        let duration = formatSeconds(ele.duration);
        console.log(duration);
        $('#video_duration').val(duration);
    }

    function formatSeconds(value) {
        var secondTime = parseInt(value);// 秒
        var minuteTime = 0;// 分
        var hourTime = 0;// 小时
        if(secondTime > 60) {//如果秒数大于60，将秒数转换成整数
            //获取分钟，除以60取整数，得到整数分钟
            minuteTime = parseInt(secondTime / 60);
            //获取秒数，秒数取佘，得到整数秒数
            secondTime = parseInt(secondTime % 60);
            //如果分钟大于60，将分钟转换成小时
            if(minuteTime > 60) {
                //获取小时，获取分钟除以60，得到整数小时
                hourTime = parseInt(minuteTime / 60);
                //获取小时后取佘的分，获取分钟除以60取佘的分
                minuteTime = parseInt(minuteTime % 60);
            }
        }
        let second = parseInt(secondTime);
        if(second<10){
            second = "0" +second;
        }

        minuteTime = parseInt(minuteTime);
        if(minuteTime<10){
            minuteTime = "0" +minuteTime;
        }
        /*if(minuteTime > 0) {
            return [parseInt(minuteTime), parseInt(secondTime)];
            return minuteTime + ':' + second;
        }*/
        hourTime = parseInt(hourTime);
        if(hourTime<10){
            hourTime = "0" + hourTime;
        }
        /*if(hourTime > 0) {
            return [parseInt(hourTime), parseInt(minuteTime), parseInt(secondTime)];
            return hourTime+":"+minuteTime+":"+second;
        }*/
        // return [parseInt(secondTime)];
        return hourTime+":"+minuteTime+":"+second;
    }

</script>
