@if($form['datatable'])



    @if($form['relationship_table'])
        @push('bottom')
            <script type="text/javascript">
                $(function () {
                    $('#{{$name}}').select2();
                })
            </script>
        @endpush
    @else
        @if($form['datatable_ajax'] == true)

            <?php
            $datatable = @$form['datatable'];
            $where = @$form['datatable_where'];
            $format = @$form['datatable_format'];

            $raw = explode(',', $datatable);
            $url = CRUDBooster::mainpath("find-data");

            $table1 = $raw[0];
            $column1 = $raw[1];

            @$table2 = $raw[2];
            @$column2 = $raw[3];

            @$table3 = $raw[4];
            @$column3 = $raw[5];
            ?>

            @push('bottom')
                <script>
                    $(function () {
                        $('#{{$name}}').select2({
                            placeholder: {
                                id: '-1',
                                text: '{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}'
                            },
                            allowClear: true,
                            ajax: {
                                url: '{!! $url !!}',
                                delay: 250,
                                data: function (params) {
                                    var query = {
                                        q: params.term,
                                        format: "{{$format}}",
                                        table1: "{{$table1}}",
                                        column1: "{{$column1}}",
                                        table2: "{{$table2}}",
                                        column2: "{{$column2}}",
                                        table3: "{{$table3}}",
                                        column3: "{{$column3}}",
                                        where: "{!! addslashes($where) !!}"
                                    }
                                    return query;
                                },
                                processResults: function (data) {
                                    return {
                                        results: data.items
                                    };
                                }
                            },
                            escapeMarkup: function (markup) {
                                return markup;
                            },
                            minimumInputLength: 1,
                            @if($value)
                            initSelection: function (element, callback) {
                                var id = $(element).val() ? $(element).val() : "{{$value}}";
                                if (id !== '') {
                                    $.ajax('{{$url}}', {
                                        data: {
                                            id: id,
                                            format: "{{$format}}",
                                            table1: "{{$table1}}",
                                            column1: "{{$column1}}",
                                            table2: "{{$table2}}",
                                            column2: "{{$column2}}",
                                            table3: "{{$table3}}",
                                            column3: "{{$column3}}"
                                        },
                                        dataType: "json"
                                    }).done(function (data) {
                                        callback(data.items[0]);
                                        $('#<?php echo $name?>').html("<option value='" + data.items[0].id + "' selected >" + data.items[0].text + "</option>");
                                    });
                                }
                            }

                            @endif
                        });

                    })
                </script>
            @endpush

        @else
            @push('bottom')
                <script type="text/javascript">
                    $(function () {
                        $('#{{$name}}').select2();
                    })
                </script>
            @endpush
        @endif
    @endif
@else

    @push('bottom')
        <script type="text/javascript">
            $(function () {
                $('#{{$name}}').select2();
            })
        </script>
    @endpush

@endif

@if ($form['lockchange']==true)    
  <?php
    //$disabled = "readonly='readonly'";
    if ($col_width)
    {
      $array_of_piece = explode('-', $col_width);
      //dd($array_of_piece);
      $col_width = $array_of_piece[0]."-".$array_of_piece[1]."-".(intval($array_of_piece[2])-2);
    }
    else {
      $col_width = 'col-sm-8';
    }
   ?>
  @push('bottom')
      <script type="text/javascript">
          $(function () {
          $("#{{$name}}").prop("disabled", "true");            
            $('#lockchange-{{$name}}').click(function() {
                var r = confirm("You are changing your dealing assistant from the dealing assistant assigned to you, please confirm");
                if (r == true) {                    
                    $('#{{$name}}').prop('disabled', false);
                    $("#{{$name}}").removeAttr("disabled");                    
                    @if (isset($form['lockchangeconfirmapi']))
                        var link = "{{$form['lockchangeconfirmapi']}}"+"/"+$("#{{$name}}").val();
                        $.get(link, function(data, status){
                            console.log("Sent confirm ID");
                        });
                    @endif
                    $("#lockchange-{{$name}}").off('click');
                }
            
            });
            $('#form').on('submit', function() {
                $('#{{$name}}').prop('disabled', false);
                $("#{{$name}}").removeAttr("disabled");                    
            });
          })
      </script>
  @endpush
@endif

<div class='form-group {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }} {{@$form["groupclass"]}}' id='form-group-{{$name}}' style="{{@$form['style']}}">
    <label class='control-label col-sm-2'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <select style='width:100%' class='form-control {{@$form["formclass"]}}' id="{{$name}}"
                {{$required}} {!!$placeholder!!} name="{{$name}}{{($form['relationship_table'])?'[]':''}}" {{ ($form['relationship_table'])?'multiple="multiple"':'' }} >
            @if($form['dataenum'])
                <option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}</option>
                <?php
                $dataenum = $form['dataenum'];
                $dataenum = (is_array($dataenum)) ? $dataenum : explode(";", $dataenum);
                ?>
                @foreach($dataenum as $enum)
                    <?php
                    $val = $lab = '';
                    if (strpos($enum, '|') !== FALSE) {
                        $draw = explode("|", $enum);
                        $val = $draw[0];
                        $lab = $draw[1];
                    } else {
                        $val = $lab = $enum;
                    }

                    $select = ($value == $val) ? "selected" : "";
                    ?>
                    <option {{$select}} value='{{$val}}'>{{$lab}}</option>
                @endforeach
            @endif

            @if($form['datatable'])
                @if($form['relationship_table'])
                    <?php
                    $select_table = explode(',', $form['datatable'])[0];
                    $select_title = explode(',', $form['datatable'])[1];
                    $select_where = $form['datatable_where'];
                    $pk = CRUDBooster::findPrimaryKey($select_table);

                    $result = DB::table($select_table)->select($pk, $select_title);
                    if ($select_where) {
                        $result->whereraw($select_where);
                    }
                    $result = $result->orderby($select_title, 'asc')->get();


                    $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
                    $foreignKey2 = CRUDBooster::getForeignKey($select_table, $form['relationship_table']);

                    $value = DB::table($form['relationship_table'])->where($foreignKey, $id);
                    $value = $value->pluck($foreignKey2)->toArray();

                    foreach ($result as $r) {
                        $option_label = $r->{$select_title};
                        $option_value = $r->id;
                        $selected = (is_array($value) && in_array($r->$pk, $value)) ? "selected" : "";

                        if ($data1!=null)
                        {
                            $option_data1 = $r->{$data1};
                            echo "<option $selected value='$option_value' data-data1='$option_data1'>$option_label</option>";
                        }
                        else
                            echo "<option $selected value='$option_value'>$option_label</option>";
                    }
                    ?>
                @else
                    @if($form['datatable_ajax'] == false)
                        <option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}</option>
                        <?php
                        $select_table = explode(',', $form['datatable'])[0];
                        $select_title = explode(',', $form['datatable'])[1];
                        $data1 = $form['data1'];
                        $select_where = $form['datatable_where'];
                        $datatable_format = $form['datatable_format'];
                        $select_table_pk = CRUDBooster::findPrimaryKey($select_table);                        
                        if($form['join_table']) {
                            $jn_table = explode(',', $form['join_table'])[0];
                            $jn_field = explode(',', $form['join_table'])[1];
                            $id_field = explode(',', $form['join_table'])[2];
                            
                            //$result = $result->join($jn_table,$jn_table.".".$jn_field,'=',$select_table.".".$id_field);
                            $select_table_pk2 = $select_table.".".$select_table_pk;

                            if ($data1!=null)
                                $result = DB::table($select_table)->select($select_table_pk2, $select_title,$data1)->join($jn_table,$jn_table.".".$jn_field,'=',$select_table.".".$select_table_pk);
                            else
                                $result = DB::table($select_table)->select($select_table_pk2, $select_title)->join($jn_table,$jn_table.".".$jn_field,'=',$select_table.".".$select_table_pk);
                        }
                        else
                        {
                            if ($data1!=null)
                                $result = DB::table($select_table)->select($select_table_pk, $select_title,$data1);
                            else
                                $result = DB::table($select_table)->select($select_table_pk, $select_title);
                        }
                        

                        if ($datatable_format) {
                            $result->addSelect(DB::raw("CONCAT(".$datatable_format.") as $select_title"));
                        }

                        

                        if ($select_where) {
                            $result->whereraw($select_where);
                        }
                        if (CRUDBooster::isColumnExists($select_table, 'deleted_at')) {
                            $result->whereNull('deleted_at');
                        }
                        $result = $result->orderby($select_title, 'asc')->get();
                        

                        foreach ($result as $r) {
                            $option_label = $r->{$select_title};
                            $option_value = $r->$select_table_pk;
                            $selected = ($option_value == $value) ? "selected" : "";

                            if ($data1!=null)
                            {
                                $option_data1 = $r->{$data1};
                                echo "<option $selected value='$option_value' data-data1='$option_data1'>$option_label</option>";
                            }
                            else
                                echo "<option $selected value='$option_value'>$option_label</option>";
                        }

                        
                        ?>
                    <!--end-datatable-ajax-->
                    @endif

                <!--end-relationship-table-->
                @endif

            <!--end-datatable-->
            @endif
        </select>

        <div class="text-danger">
            {!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):"" !!}
        </div><!--end-text-danger-->
        <p class='help-block'>{{ @$form['help'] }}</p>

    </div>
    @if ($form['lockchange']==true)
    <div class="col-sm-2">
        <a class="btn btn-danger" id="lockchange-{{$name}}" href="#" role="button">Change</a>
    </div>
    @endif

</div>
