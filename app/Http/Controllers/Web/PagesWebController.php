<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ExaminationsModel;
use Session;

class PagesWebController extends Controller
{
    public function __construct(
       
        ExaminationsModel $ExaminationsModel
    )
    {
        $this->BaseModel = $ExaminationsModel;  
        $this->ViewData = [];
        $this->JsonData = []; 

      
        $this->ModulePath   = 'web.pages.'; 
    }
    

    /*---------------------------------
    |   Web Pages
    ------------------------------------------*/

    public function index($slug)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['url'] = url('/');
        $this->JsonData['msg'] = 'Incorrect details';
        if(!empty($slug))
        {
            $url = url('/')."/".$slug;
            $pageData = $this->BaseModel->where('url',$url)->first();
            
            if(!empty($pageData))
            {
                $this->ViewData['page_data'] = $pageData; 
                return view($this->ModulePath.'index', $this->ViewData);
            }else
            {
                return abort(404);
            }
        }
        else
        {
            return abort(404);
        }
    }
}
