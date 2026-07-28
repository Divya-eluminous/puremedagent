<!DOCTYPE html>
<html>
<head>
    <title></title> 
</head>
<style type="text/css">  
    th, td {
      padding: 10px;
      text-align: left; 
    }
    table {
      border-collapse: collapse;
    }

    table, th, td {
      border: 1px solid black;
    }
</style>
<body>
    <div class="container-fluid">   
        <div class="row">
            <div class="col-md-9">
                <div class="panel-heading"><h1>Tabellen Felder Details</h1></div>
                <div class="panel-body">
                    <table border="1" class="table table-striped table-bordered" style="width: 100%; text-align:center;" id="myTable">
                        <thead>
                            <tr> 
                                   
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tables as $table)
                                <?php
                                $data = $table->Tables_in_schillermed;
                                $columns = DB::connection('ganymed-mysql')->select(DB::connection('ganymed-mysql')->raw("DESCRIBE ${data}"));
                                ?>
                                <tr>
                                    <td style="font-weight: bold;">{{ $data }}</td>
                                </tr>
                                <tr>
                                    @foreach ($columns as $column)
                                        <td class="w-100-px">{{$column->Field}}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>