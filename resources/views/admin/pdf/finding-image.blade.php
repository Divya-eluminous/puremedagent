<!DOCTYPE html>
  <html>
    <head>
      <title>Findings</title>

    </head>
    <body> 
      <?php 
      $cnt = 0;
    
      foreach ($findingImage as $key => $value) 
      {
         //dd(url('storage/app/diagnostic_findings/'.$value));
         if(!empty($value))
         {
            if($cnt !=0)
            { ?>
              <div style="page-break-after: always;"></div>
            <?php } ?> 
                <div style="width: 100%;"> 
                 <!-- <p style="font-size: 12px;font-weight: 200; margin-bottom: 0;">Image Name:{{$value['name']}}</p> -->
                </div> 
                <div style="display: flex; justify-content: center;align-items: center; overflow: hidden;max-height: 70vh;"> 
                 <img src="{{$value['path']}}" width="{{$value['width']}}" height="{{$value['height']}}" style="max-width: 100%;height: auto;width: auto;" />
                </div>
        <?php 
          }
        $cnt++;
      } ?>
    </body>
  </html>
   