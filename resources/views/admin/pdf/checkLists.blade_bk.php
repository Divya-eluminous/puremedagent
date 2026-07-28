

<!DOCTYPE html>
        <html>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <body> 
             <?php 
              foreach ($data as $key => $value) 
              {
              ?>   
              <div> 
                <table width="100%" style="margin: 0 auto;font-family: 'arial';">
                <tbody><tr style="border-bottom: 1px solid #e6e6e6;">
                    <td colspan="2" style="width: 50%;padding: 0 16px;">
                    <p style="font-size: 16px;font-weight: 600; margin-bottom: 0;">Checklists Name:</p>
                    <p style="font-size: 14px;font-style: italic; margin: 1px;">{{$value['check_list_name']}}</p>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid #e6e6e6;">
                    <td colspan="2" style="width: 50%;padding: 0 16px;">
                    <p style="font-size: 16px;font-weight: 600; margin-bottom: 0;">Introduction text:</p>
                    <p style="font-size: 14px;font-style: italic; margin: 1px;">
                     {!!$value['introduction_text']!!}
                    </p>
                    </td>
                </tr>
                <tr style=";border-bottom: 1px solid #e6e6e6;">
                    <td colspan="2" style="width: 50%;padding: 0 16px;">
                    <p style="font-size: 16px;font-weight: 600; margin-bottom: 0;">Final text:</p>
                    <p style="font-size: 14px;font-style: italic;margin: 1px;">
                      {!!$value['final_name']!!}
                    </p>
                    </td>
                </tr>
                <!-- Heading section -->
                <?php 
                //dd($value['heading']);
                foreach ($value['heading'] as $keyheading => $heading) 
                {
                ?>
                  <tr style=";border-bottom: 2px solid #e6e6e6;">
                      <td style="width: 100%;padding: 18px;" colspan="2"> 
                      <table style="width: 100%;border-collapse: collapse;border: 2px solid #f0f0f0;">
                          <tbody>
                              <tr>
                                  <td style="width: 100%;">
                                      <table style="width: 100%;">
                                          <tbody>
                                              <tr style="
                                                  background-color: #e91e63;
                                                  color: #fff;
                                                  font-size: 14px;
                                              ">
                                                  <td colspan="2" style="padding: 15px 14px;">{{$heading['heading']}}</td>
                                              </tr>
                                              <!-- question -->

                                              <?php 

                                              $cccnt = 1;

                                              foreach($heading['question'] as $keyq=>$question){
                                                foreach ($question as $keyquu => $ques) {
                                                ?>
                                                <tr>
                                                    <td style="width: 10%;padding: 6px 11px;border-bottom: 2px solid #e6e6e6; font-size: 14px;border-right:2px solid #e6e6e6;">
                                                      @if($ques['flag'] == 1)
                                                      <img style="width: 10px;height: 10px;" src="{{ asset('assets/admin/images/checkbox.png') }}"/>
                                                      @else
                                                      <img style="width: 10px;height: 10px;" src="{{ asset('assets/admin/images/uncheck-box-icon.jpg') }}"/>
                                                      @endif
                                                    </td>
                                                    <td style="width: 75%;padding: 6px 11px; border-bottom: 2px solid #e6e6e6;font-size: 14px;">{{$ques['question']}}</td>
                                                </tr>
                                                <?php $cccnt++;
                                              }
                                              } ?>
                                              <!-- end question -->
                                          </tbody>
                                      </table>
                                  </td>
                              </tr>
                          </tbody>
                      </table>
                  </td>
                  </tr>
                 
                <?php 
                  }
                ?> 
                </tbody>
        </table> 
      </div>
      <?php
      if($key != count($data)-1 )
        {?>
                <div style="page-break-after: always;"></div>
              <?php } ?>
                <!-- end heading section -->
        <?php 
        }
        ?> <div> 
        <?php 
        $sign =0;
        foreach ($data as $key => $value) 
        { 
          //dd(url('storage/app/public/'.$value['signature']));
          if($value['signature']!='' && $sign == 0)
          {
        ?> 
        <table width="100%" style="margin: 0 auto;font-family: 'arial';">
        <tbody>
        <tr style="border-bottom: 2px solid #e6e6e6;">
        <td style="width: 100%;" colspan="2">
        <p style="padding: 0 25px; font-size: 14px; text-align: right;"><strong>Signatures<br /></strong></p>
        <p style="padding: 0 35px; font-size: 14px; text-align: right;"><img style="width: 40px;height: 55px;" src="{{url('storage/app/public/'.$value['signature'])}}"/></p> 
        </td>
        </tr> 
        </tbody>
        </table>
      <?php 
    }
    $sign++;
      } ?>
      </div>
        </body>
        </html>
      