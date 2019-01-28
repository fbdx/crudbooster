@if($command=='layout')
    <style>
        tr.dtrg-group,
        tr.dtrg-group:hover {
            font-weight: bold;
            background-color: #ddd !important;
        }
    </style>
    <div id='{{$componentID}}' class='border-box'>

        <div class="panel panel-default">
            <div class="panel-heading">
                [name]
            </div>
            <div class="panel-body table-responsive no-padding">
                [sql]
            </div>
        </div>

        <div class='action pull-right'>
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Small Box' class='btn-edit-component'><i class='fa fa-pencil'></i></a>
            &nbsp;
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i class='fa fa-trash'></i></a>
        </div>
    </div>
@elseif($command=='configuration')
    <form method='post'>
        <input type='hidden' name='_token' value='{{csrf_token()}}'/>
        <input type='hidden' name='componentid' value='{{$componentID}}'/>
        <div class="form-group">
            <label>Name</label>
            <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}'/>
        </div>

        <div class="form-group">
            <label>SQL Query</label>
            <textarea name='config[sql]' rows="5" placeholder="E.g : select column_id,column_name from view_table_name"
                      class='form-control'>{{@$config->sql}}</textarea>
            <div class='help-block'>
                Make sure the sql query are correct unless the widget will be broken. Mak sure give the alias name each column. You may use alias [SESSION_NAME]
                to get the session. We strongly recommend that you use a <a href='http://www.w3schools.com/sql/sql_view.asp' target='_blank'>view table</a>
            </div>
        </div>

         <div class="form-group">
            <label>Group Column No</label>
            <input class="form-control" name='config[groupcolno]' type='number' value='{{@$config->groupcolno}}'/>
            <div class='help-block'>
                Column number that it's made for grouping the table
            </div>
        </div>

    </form>
@elseif($command=='showFunction')
    <?php
    if($key == 'sql') {
    try {
        $sessions = Session::all();
        foreach ($sessions as $key => $val) {
            $value = str_replace("[".$key."]", $val, $value);
        }
        $sql = DB::select(DB::raw($value));
    } catch (\Exception $e) {
        die($e);
    }
    ?>

    @if($sql)
        <table id="table-{{$componentID}}" class='grouptable table-striped'>
            <thead>
            <tr>
                @foreach($sql[0] as $key=>$val)
                    <th>{{$key}}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($sql as $row)
                <tr>
                    @foreach($row as $key=>$val)
                        <td>{{$val}}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <script type="text/javascript">
            var collapsedGroups = {};

            $('#table-{{$componentID}}').DataTable({
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",
                rowGroup: {                    
                    startRender: function (rows, group) {
                        var collapsed = !!collapsedGroups[group];

                        rows.nodes().each(function (r) {
                            r.style.display = collapsed ? 'none' : '';
                        });

                        var dealsCreated = rows
                            .data()
                            .pluck(2)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);

                        var dealsPending = rows
                            .data()
                            .pluck(3)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);

                        var dealsCompleted = rows
                            .data()
                            .pluck(4)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);

                        var dealsReceived = rows
                            .data()
                            .pluck(5)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);

                        var dealsReceivedPending = rows
                            .data()
                            .pluck(6)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);

                        var dealsReceivedCompleted = rows
                            .data()
                            .pluck(7)
                            .reduce( function (a, b) {
                                return a + b.replace(/[^\d]/g, '')*1;
                            }, 0);
                        //dealsCreated = $.fn.dataTable.render.number(',', '.', 0, '$').display( dealsCreated );

                        // Add category name to the <tr>. NOTE: Hardcoded colspan
                        return $('<tr/>')
                            .append('<td colspan=2">' + group + ' (' + rows.count() + ')</td>')
                            .append( '<td>'+dealsCreated+'</td>' )
                            .append( '<td>'+dealsPending+'</td>' )
                            .append( '<td>'+dealsCompleted+'</td>' )
                            .append( '<td>'+dealsReceived+'</td>' )
                            .append( '<td>'+dealsReceivedPending+'</td>' )
                            .append( '<td>'+dealsReceivedCompleted+'</td>' )
                            .attr('data-name', group)
                            .toggleClass('collapsed', collapsed);
                    },
                    dataSrc: 0
                },         
                @if($config->groupcolno)
                    rowGroup: {
                        dataSrc: {{$config->groupcolno}}
                    },                    
                @endif
                lengthMenu: [[-1], ["All"]]                
            });

            $('#table-{{$componentID}} > tbody').on('click', 'tr.dtrg-start', function () {
                var name = $(this).data('name');
                collapsedGroups[name] = !collapsedGroups[name];
                $('#table-{{$componentID}}').DataTable().draw();
            });
        </script>
    @endif
    <?php
    }else {
        echo $value;
    }
    ?>
@endif  

