<?php 
// $getOrdination =  DB::connection('system')
//                     ->table("ordination")
//                     ->where('id',$env->Website()->ordination_id)
//                     ->first();
// $menu_bg_color    = $getOrdination->menu_bg_color ; 
// $light_text_color = $getOrdination->light_text_color; 
// $dark_text_color  = $getOrdination->dark_text_color;  
// $screen_bg_color  = $getOrdination->screen_bg_color;  
// $button_colors_code = $getOrdination->button_colors; 
// $menu_header_colors = $getOrdination->menu_header_colors;
?>
<style type="text/css">
[class*='sidebar-dark-'] {
  background-color: {{config('menu_bg_color')}}!important;/*Menu Background Code*/
}
[class*=sidebar-dark-] .sidebar a {
    color: {{config('light_text_color')}}!important;/*Light Text Code*/
}
body{
  color: {{config('dark_text_color')}}!important;/*Dark Text Code*/
}
[class*=sidebar-dark-] .nav-treeview>.nav-item>.nav-link.active, [class*=sidebar-dark-] .nav-treeview>.nav-item>.nav-link.active:focus, [class*=sidebar-dark-] .nav-treeview>.nav-item>.nav-link.active:hover {
    background-color: {{config('screen_bg_color')}}!important; /*Screen Background Code*/
  
}
.card-primary:not(.card-outline)>.card-header, .card-primary:not(.card-outline)>.card-header a {
    color: {{config('light_text_color')}}!important; /*Light Text Code*/
}
.btn-primary {
    color: #fff;
    box-shadow: none; /* Button Code*/
    background-color: <?php echo config('button_colors_code'); ?>;
    border-color: <?php echo config('button_colors_code'); ?>;
}
/*Add new theme color*/
.btn-cnt {
    color: #fff;
    background-color: #F44336!important;
    border-color: #131112;
    box-shadow: none;
    border-radius: 30px;
    height: 24px;
    width: auto;
    padding: 0px;
    margin-right: 10px;
    padding: 0 5px;
}
</style>
