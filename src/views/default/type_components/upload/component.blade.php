<div class='form-group {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }} {{@$form["groupclass"]}}' id='form-group-{{$name}}' style="{{@$form['style']}}">
    <label class='col-sm-2 control-label'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        @if($value)
            <?php
            if(Storage::exists($value) || file_exists($value)):
            $url = asset($value);
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            $images_type = array('jpg', 'png', 'gif', 'jpeg', 'bmp', 'tiff');
            if(in_array(strtolower($ext), $images_type)):
            ?>
            <p><a id="imgpreviewpro-{{$name}}" data-lightbox='roadtrip' href='{{$url}}'><img style='max-width:400px' title="Image For {{$form['label']}}" src='{{$url}}'/></a></p>
            <?php else:?>
            <p><a href='{{$url}}'>{{trans("crudbooster.button_download_file")}}</a></p>
            <?php endif;
            echo "<input type='hidden' name='_$name' value='$value'/>";
            else:
                echo "<p class='text-danger'><i class='fa fa-exclamation-triangle'></i> ".trans("crudbooster.file_broken")."</p>";
            endif;
            ?>

        @endif

            <img id="imgpreview-{{$name}}" alt="your image" width="400" style="display:none;"/>
            <input type='file' id="{{$name}}" title="{{$form['label']}}" {{$required}} {{$readonly}} {{$disabled}} class='form-control' name="{{$name}}"
onchange="document.getElementById('imgpreviewpro-{{$name}}').style.display = 'none';document.getElementById('imgpreview-{{$name}}').style.display = 'block';document.getElementById('imgpreview-{{$name}}').src = window.URL.createObjectURL(this.files[0]);"
            />
            <p class='help-block'>{{ @$form['help'] }}</p>

        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):"" !!}</div>

    </div>

</div>
