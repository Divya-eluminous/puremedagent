
<!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo $data['name']; ?></title>
        <style type="text/css">
            footer {
                position: fixed; 
                bottom: 0cm;                
                right: 0cm;                
              }
        </style>

      </head>
        <body> 
          <footer>
              <div align="right"> {{ date('d-m-Y') }} </div>
          </footer> 

         <?php  //dd($data['header_image_path'],$data['footer_image_path']);?>
            <br/><br/>
            <div style="width: 100%;" align="right">
              @if(isset($data['patientFullName']) && !empty($data['patientFullName']))
               <label> Patientenname : </label> {{ $data['patientFullName'] }}
              @endif
            </div> 

            <div style="width: 100%;" align="right">
              @if(isset($data['patientDob']) && !empty($data['patientDob']))
               <label> Geburtsdatum : </label> {{ $data['patientDob'] }}
              @endif
            </div>
            <br/><br/>

            <div style="width: 100%;"> 
              <div  style="background-color: <?php echo $data['background_color'];?>">
                  <?php
                   if(isset($data['header_image']) && !empty($data['header_image']))
                   {
                  ?>
                 <!--  <img style="width: 100%;height: 100px;" src="<?php //echo $data['header_image_path'] ?>" > -->
                  <img style="width: 100%;height: auto;" src="<?php echo $data['header_image_path'] ?>" >
                  <?php
                    }
                  ?>
                  
                  <div style="margin-left: 52px;margin-right: 20px;">
                    <h4>
                      <?php echo ucfirst($data['name']);?></h4>
                    <p>{!! html_entity_decode($data['html_text']) !!}</p>
                  </div>

                   <!-- commented  by swapnil pawar 14-10-2022--> 
                 <!--  <?php
                  // if(isset($data['footer_image']) && !empty($data['footer_image']))
                   {
                  ?>
                  <img style="width: 100%;height: 70px;" src="<?php //echo  $data['footer_image_path'] ?>" >
                  <?php
                    }
                  ?> -->
                   <!-- commented  by swapnil pawar 14-10-2022-->
                  
              </div>
              <div style="margin-left: 50px;">
               <!--  <p>Herzlichen Dank fur lhre Unterstutzung</p> -->
                <p>Ihr pureGyn Team</p>
              </div>
            </div>
             
            <?php 
              if(isset($data['signature']) && $data['signature']!='')
              { 
            ?> 
            <table width="100%" style="margin: 0 auto;font-family: 'arial';">
            <tbody>
            <tr style="border-bottom: 2px solid #e6e6e6;">
            <td style="width: 100%;" colspan="2">
            <p style="padding: 0 25px; font-size: 14px; text-align: right;"><img style="width: 400px;height: 150px;" src="{{$data['signature']}}"/></p> 
            <p style="padding: 0 25px; font-size: 14px; text-align: right;"><strong>Unterschrift<br /></strong></p>
            </td>
            </tr> 
            </tbody>
            </table>
            <?php 
          }
          ?>
          <?php 
              if(isset($data['notes']) && $data['notes']!='')
              { 
            ?> 
            <table width="100%" style="margin: 0 auto;font-family: 'arial';">
            <tbody>
            <tr style="border-bottom: 2px solid #e6e6e6;">
            <td style="width: 100%;" colspan="2">

           <!--  <p style="padding: 0 25px; font-size: 14px; text-align: left;"><strong>Notizen: <br /></strong>  {{ $data['notes'] }}</p> -->
            <p style="padding: 0 25px; font-size: 14px; text-align: left;"><strong>Notizen<br /></strong></p>

             <p style="padding: 0 25px; font-size: 14px; text-align: left;"><img style="width: 400px;height: 150px;" src="{{$data['notes']}}"/></p> 
            </td>
            </tr> 
            </tbody>
            </table>
            <?php 
          }
          ?>
          
          <!-- code by swapnil pawar 14-10-2022-->
          <div>
           <?php
           if(isset($data['footer_image']) && !empty($data['footer_image']))
           {
           ?>
           <img style="width: 100%;height:auto;" src="<?php echo  $data['footer_image_path'] ?>" >
           <?php
           }
           ?>
        </div>
        <!-- code by swapnil pawar 14-10-2022-->

          
        </body>
        </html>
<?php
//dd($data['signature']);
//dump(url('storage/sign/image_1628088868.png'));
//dump(url('storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/20210726125408-1627296837092.jpg'));
//dd(url('storage/sign/'.$data['signature']));
?>
