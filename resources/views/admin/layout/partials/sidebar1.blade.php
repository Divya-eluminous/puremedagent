 <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <!-- <div class="user-panel">
        <div class="pull-left image">
          <img src="{{ asset('assets/adminLte/dist/img/user2-160x160.jpg') }}" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Alexander Pierce</p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div> -->
      
      <!-- sidebar menu: : style can be found in sidebar.less -->
     
      <ul class="sidebar-menu" data-widget="tree">
       <!--  <li class="header">MAIN NAVIGATION</li> -->
        
        <li class="{{ active(['admin/dashboard','admin/dashboard/*']) }}">
          <a href="{{ route('admin.dashboard') }}">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
            <!-- <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span> -->
          </a>
          <!-- <ul class="treeview-menu">
            <li class="active"><a href="index.html"><i class="fa fa-circle-o"></i> Dashboard v1</a></li>
            <li><a href="index2.html"><i class="fa fa-circle-o"></i> Dashboard v2</a></li>
          </ul> -->
        </li>

        <!-- Manage Users -->
        <li class="treeview {{ active(['admin/users','admin/roles','admin/permissions', 'admin/users/*']) }}">
          <a href="#">
            <i class="fa fa-user-plus"></i>
            <span>Manage Users</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="{{ active('admin/users/create') }}">
              <a href="{{ route('admin.users.create') }}"><i class="fa fa-hand-o-right"></i> Add User</a>
            </li>
            <li class="{{ active('admin/users') }}">
              <a href="{{ route('admin.users.index') }}"><i class="fa fa-hand-o-right"></i> View Users</a>
            </li>
            
            <li class="{{ active('admin/roles') }}">
              <a href="{{ route('admin.roles.index') }}"><i class="fa fa-hand-o-right"></i> Manage Roles</a>
            </li>

            <li class="{{ active('admin/permissions') }}">
              <a href="{{ route('admin.permissions.index') }}"><i class="fa fa-hand-o-right"></i> Manage Permissions</a>
            </li>
          </ul>
        </li>

        <!-- Manage Material -->   
        @can('store-manage-materials')     
        <li class="treeview {{ active(['admin/materials', 'admin/materials/*']) }}">
          <a href="#">
            <i class="fa fa-list-alt"></i>
            <span>Manage Material</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">  
            @can('store-material-listing')          
            <li class="{{ active('admin/materials') }}">
              <a href="{{ route('admin.materials.index') }}"><i class="fa fa-hand-o-right"></i> View materials</a>
            </li> 
            @endcan             
            @can('store-material-add') 
            <li class="{{ active('admin/materials/create') }}">
              <a href="{{ route('admin.materials.create') }}"><i class="fa fa-hand-o-right"></i> Add material</a>
            </li>
            @endcan  
           
          </ul>
        </li>   
        @endcan   
		<!-- End Manage Material -->
		
        <!-- Manage Material In -->   
        @can('store-manage-material-in')     
        <li class="treeview {{ active(['admin/materials-in', 'admin/materials-in/*']) }}">
          <a href="#">
            <i class="fa fa-inbox"></i>
            <span>Manage Material In</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">    
            @can('store-material-in-listing')           
            <li class="{{ active('admin/materials-in') }}">
              <a href="{{ route('admin.materials-in.index') }}"><i class="fa fa-hand-o-right"></i> View Material In</a>
            </li> 
            @endcan 
            @can('store-material-in-add')              
            <li class="{{ active('admin/materials-in/create') }}">
              <a href="{{ route('admin.materials-in.create') }}"><i class="fa fa-hand-o-right"></i> Add Material In</a>
            </li> 
            @endcan           
          </ul>
        </li>
        @endcan
        <!-- End Manage Material In -->  

        <!-- Manage RMS -->
        @can('store-manage-batches') 
        <li class="treeview {{ active(['admin/rms-store', 'admin/rms-store/*']) }}">
          <a href="#">
            <i class="fa fa-user-plus"></i>
            <span>Manage Batches</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @can('store-batches-listing') 
            <li class="{{ active('admin/rms-store') }}">
              <a href="{{ route('admin.rms-store.index') }}"><i class="fa fa-hand-o-right"></i> View Batches</a>
            </li>
           @endcan   
            @can('store-batches-add') 
            <li class="{{ active('admin/rms-store/create') }}">
              <a href="{{ route('admin.rms-store.create') }}"><i class="fa fa-hand-o-right"></i> Add Batch</a>
            </li>
            @endcan   
          </ul>
        </li>
        @endcan 
        <!-- End Manage RMS -->
        
        <!-- production -->
        @can('store-manage-material-plans') 
        <li class="treeview {{ active(['admin/production', 'admin/production/*']) }}">
          <a href="#">
            <i class="fa fa-gear"></i>
            <span>Manage Material Plans</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
           @can('store-material-plan-listing') 
            <li class="{{ active('admin/production') }}">
              <a href="{{ route('admin.production.index') }}"><i class="fa fa-hand-o-right"></i> View Material Plan</a>
            </li>
           @endcan   
           @can('store-material-plan-add') 
            <li class="{{ active('admin/production/create') }}">
              <a href="{{ route('admin.production.create') }}"><i class="fa fa-hand-o-right"></i> Add Material Plan</a>
            </li>
           @endcan   
          </ul>
        </li>
        @endcan   
        <!-- end production -->

       
		
		<!-- return material -->
       @can('store-manage-returned-material') 
        <li class="treeview {{ active(['admin/return', 'admin/return/*']) }}">
          <a href="#">
            <i class="fa fa-share"></i>
            <span>Manage Returned Material</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
           @can('store-returned-material-listing') 
            <li class="{{ active('admin/return') }}">
              <a href="{{ route('admin.return.index') }}"><i class="fa fa-hand-o-right"></i> View Returned Material</a>
            </li>
            @endcan  
           @can('store-returned-material-add') 
            <li class="{{ active('admin/return/create') }}">
              <a href="{{ route('admin.return.create') }}"><i class="fa fa-hand-o-right"></i> Add Returned Material</a>
            </li>
            @endcan  
          </ul>
        </li>
         @endcan  
        <!-- End return material -->
        
        <!-- output material -->
        @can('store-manage-material-output') 
        <li class="treeview {{ active(['admin/materials-out', 'admin/materials-out/*']) }}">
          <a href="#">
            <i class="fa fa-user-secret"></i>
            <span>Manage Material Output</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @can('store-material-output-listing') 
            <li class="{{ active('admin/materials-out') }}">
              <a href="{{ route('admin.materials-out.index') }}"><i class="fa fa-hand-o-right"></i> View Material Output</a>
            </li>
            @endcan 
           @can('store-material-output-add') 
            <li class="{{ active('admin/materials-out/create') }}">
              <a href="{{ route('admin.materials-out.create') }}"><i class="fa fa-hand-o-right"></i> Add Material Output</a>
            </li>
            @endcan 
          </ul>
        </li>
         @endcan 
        <!-- end output material -->
        <!-- wastage material -->
        @can('store-manage-wastage-material')
        <li class="treeview {{ active(['admin/wastage-material', 'admin/wastage-material/*']) }}">
          <a href="#">
            <i class="fa fa-inbox"></i>
            <span>Manage Wastage Material</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @can('store-wastage-material-listing') 
            <li class="{{ active('admin/wastage-material') }}">
              <a href="{{ route('admin.wastage-material.index') }}"><i class="fa fa-hand-o-right"></i> View Wastage Material</a>
            </li>  
            @endcan           
          </ul>
        </li>
         @endcan 
        <!-- end wastage material -->
        
        <!-- stock -->
        @can('store-manage-stock')
        <li class="treeview {{ active(['admin/sale-stock', 'admin/sale-stock/*']) }}">
          <a href="#">
            <i class="fa fa-inbox"></i>
            <span>Manage Stock</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
           @can('store-stock-listing')
            <li class="{{ active('admin/sale-stock') }}">
              <a href="{{ route('admin.sale-stock.index') }}"><i class="fa fa-hand-o-right"></i> View Stock</a>
            </li>            
            @endcan 
            @can('store-stock-add')
            <li class="{{ active('admin/sale-stock/create') }}">
              <a href="{{ route('admin.sale-stock.create') }}"><i class="fa fa-hand-o-right"></i> Add Opening Stock</a>
            </li>            
            @endcan 
          </ul>
        </li>
         @endcan 
        <!-- end stock -->
        
        <!-- Review Batch card -->
        
       <!--  <li class="{{ active(['admin/review-batch-card','admin/review-batch-card/*']) }}">
          <a href="{{ url('admin/review-batch-card') }}">
            <i class="fa fa-user-plus"></i> <span>Review Batch Cards</span>
          </a>
          
        </li> -->
        

        @can('store-manage-returned-sales')
        <li class="treeview {{ active(['admin/return-sale', 'admin/return-sale/*']) }}">
          <a href="#">
            <i class="fa fa-share"></i>
            <span>Manage Returned Sale</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @can('store-returned-sales-listing')
            <li class="{{ active('admin/return-sale') }}">
              <a href="{{ route('admin.return-sale.index') }}"><i class="fa fa-hand-o-right"></i> View Returned Sale</a>
            </li>
            @endcan 
            @can('store-sales-returned-add')
            <li class="{{ active('admin/return-sale/create') }}">
              <a href="{{ route('admin.return-sale.create') }}"><i class="fa fa-hand-o-right"></i> Add Returned Sale</a>
            </li>
            @endcan 
          </ul>
        </li>
        @endcan 

        <!-- Manage Reports --> 
        @can('store-manage-reports')       
        <li class="treeview {{ active(['admin/batch-summary','admin/batch-summary/*', 'admin/aged-materials', 'admin/contribution-report','admin/aged-products','admin/deviation-material','admin/deviation-material/*','admin/stock-deviation','admin/stock-deviation/*']) }}">
          <a href="#">
            <i class="fa fa-file"></i>
            <span>Manage Reports</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @can('store-batch-wise-report')  
            <li class="{{ active(['admin/batch-summary', 'admin/batch-summary/*']) }}">
              <a href="{{ route('admin.report.batch') }}"><i class="fa fa-hand-o-right"></i> Batch-Wise Report</a>
            </li>
            @endcan 
            @can('store-aged-material-report')  
            <li class="{{ active('admin/aged-materials') }}">
              <a href="{{ route('admin.report.agedMaterials') }}"><i class="fa fa-hand-o-right"></i> Aged Material Report</a>
            </li>
            @endcan 
            @can('store-material-deviation-report')  
            <li class="{{ active(['admin/deviation-material','admin/deviation-material/*']) }}">
              <a href="{{ route('admin.report.deviationMaterial') }}"><i class="fa fa-hand-o-right"></i> Material Deviation Report</a>
            </li>
            @endcan 
            @can('store-contribution-report')  
            <li class="{{ active('admin/contribution-report') }}">
              <a href="{{ route('admin.report.contribution') }}"><i class="fa fa-hand-o-right"></i> Contribution Report</a>
            </li>  
            @endcan 
            @can('store-aged-product-report')  
            <li class="{{ active('admin/aged-products') }}">
              <a href="{{ route('admin.report.agedProducts') }}"><i class="fa fa-hand-o-right"></i> Aged Product Report</a>
            </li>
            @endcan 
            @can('store-stock-deviation-report')  
            <li class="{{ active(['admin/stock-deviation','admin/stock-deviation/*']) }}">
              <a href="{{ route('admin.report.stockDeviation') }}"><i class="fa fa-hand-o-right"></i> Stock Deviation Report</a>
            </li> 
            @endcan         
          </ul>
        </li>
        @endcan 
        <!-- End Manage Reports -->
      
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>