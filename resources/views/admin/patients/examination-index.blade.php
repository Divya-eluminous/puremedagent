@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Patients' }} 
@endsection

@section('styles')   
@endsection
 
@section('content')
<section class="content">   
<div class="row"> 
    <div class="col-12"> 
        <div class="card">
            <div class="card-header">  
                <!-- @can('patients-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan -->
            </div>

            <!-- /.card-header -->
            <div class="card-body">          
                <table id="listingExamTable" class="table table-bordered table-striped" style="width:100%" > 

                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th>
                            <th class="w-140-px">@lang('admin.TITLE_PATIENT_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_EXAMINATION_NAME')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_EXAMINATION_URL')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_REMINDER_APPOINTMENT_DATE')</th> 
                            <!-- <th class="w-200-px">@lang('admin.TITLE_PATIENT_BIRTH_DATE')</th> -->
                            <!-- <th class="w-200-px">@lang('admin.TITLE_PATIENT_PLACE')</th> -->
                            <!-- <th class="w-100-px">@lang('admin.TITLE_PATIENT_STATUS')</th>
                            <th class="text-center w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th> -->
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</section>  
@endsection

@section('scripts')
<script type="text/javascript">
   var patientId = <?php echo $id; ?>
</script>
    <script type="text/javascript" src="{{ asset('/assets/admin/js/patients/examination-index.js') }}"></script> 
@endsection 