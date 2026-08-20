<?php
/**
*
* Filename: gsr_home_page.php
*
* $Revision:$
*
* $Date:$
*
* $Id:$
*
* @package
* @author Otmar Nytra
* @version $Revision:$  $Id:$
*/
// get the GSR main configuration file   
require_once 'gsr_environment.php';
require_once ( GSR_DOCUMENT_ROOT.'/gsr/common/class/gsr_sqlsrv_db_class.php');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow, noimageindex">
<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />

<title>Global Service Reports (GSR)</title>
<link rel="stylesheet" type="text/css" href="/gsr/common/css/gsr_cms_menu.css?v=20220426" />
<link rel="stylesheet" type="text/css" href="/gsr/common/css/gsr_slideout.css?v=20220426" />
<script src="/gsr/common/javascript/jquery-1.7.1.min.js" type="text/javascript"></script> 
<script src="/gsr/common/javascript/jquery.tabSlideOut.js" type="text/javascript"></script> 
<script>
    $(function(){
        $('#references').tabSlideOut({
            tabHandle: '.handle',                     //class of the element that will become your tab
            tabLocation: 'left',                      //side of screen where tab lives, top, right, bottom, or left
            speed: 300,                               //speed of animation
            action: 'click',                          //options: 'click' or 'hover', action to trigger animation
            topPos: '200px',                          //position from the top/ use if tabLocation is left or right
            fixedPosition: true                      //options: true makes it stick(fixed position) on scroll
        });
        $('#secondary').tabSlideOut({
            tabHandle: '.handle',                     //class of the element that will become your tab
            tabLocation: 'right',                      //side of screen where tab lives, top, right, bottom, or left
            speed: 300,                               //speed of animation
            action: 'click',                          //options: 'click' or 'hover', action to trigger animation
            topPos: '50px',                          //position from the top/ use if tabLocation is left or right
            fixedPosition: true                     //options: true makes it stick(fixed position) on scroll
        });

    });
    </script>
</head>
<body>
<?php  
  $db_obj = new sqlsrv_db_class();
  switch (SERVER_ENVIRONMENT){
    case 'DEV':
      $env_sql = 'dev_active = 1';
      $link_sql = 'dev_link AS link';
      break;
    case 'TEST':
      $env_sql = 'test_active = 1';
      $link_sql = 'test_link AS link';
      break;
    case 'QA':
      $env_sql = 'qa_active = 1';
      $link_sql = 'qa_link AS link';
      break;
    case 'PROD':
      $env_sql = 'prod_active = 1';
      $link_sql = 'prod_link AS link';
      break;
  }
  // read menu items from the DB
  //$db_obj = new sqlsrv_db_class();
  $db_obj->run_sql_save_with_exit_on_error("SELECT
                         coalesce(slide_out_side,cast([L1] as varchar)) as L1
                        ,coalesce(slide_out_order,[L2]) as L2
                        ,[web_app_icon]
                        ,[title]
                        ,[validation_icon]
                        ,[validated_for_prod]
                        ,".$link_sql."
                        ,[target]
                        ,[description]
                        ,[submit_request]
                        ,slide_out_side
                        ,slide_out_order
                        FROM [GSR_ADMIN].[dbo].[GSR_ADMIN_GUI_MENUS]
                        WHERE ".$env_sql."
                        ORDER BY slide_out_side, slide_out_order,[L1],[L2]");
  $menu_array = $db_obj->fetch_array_assoc();
  //echo "<pre>";
  // prepare menu array
  $l1 = 0;$gsr_menu_items = array();
  foreach($menu_array as $item){
      //print_r($item);
    if($item['L1'] == '0' AND $item['L2'] == 0) {
      $gsr_menu_items['main_title'] = $item['title'];
      continue;
    }
    if($item['L1'] != $l1) $l1 = $item['L1'];

    if($item['L2'] == 0) {
      $gsr_menu_items['items'][$l1]['title'] = $item['title'];
      continue;
    }

    
    $gsr_menu_items['items'][$l1]['items'][$item['L2']]['title'] = $item['title'];
    // $gsr_menu_items['items'][$l1]['items'][$item['L2']]['link'] = str_replace("https://gsr-d.abbott.com/","https://gsr-d2.oneabbott.com/",$item['link']);
    $gsr_menu_items['items'][$l1]['items'][$item['L2']]['link'] = $item['link'];
    $gsr_menu_items['items'][$l1]['items'][$item['L2']]['desc'] = $item['description'];
    $gsr_menu_items['items'][$l1]['items'][$item['L2']]['validation_icon'] = $item['validation_icon'];
    $gsr_menu_items['items'][$l1]['items'][$item['L2']]['validated_for_prod'] = $item['validated_for_prod'];

  }
  //echo "<pre>";
  //print_r($gsr_menu_items);

  // Start Generating HTML pieces now.
  
  
  $gsr_title_html = '<h1 id="banner">';
  $gsr_title_html .= '<img style="width:118px; height:105px; padding:0; margin: 0; border: 0;" src="/gsr/common/images/a_logo_1c_w_png.png" \>'; 
  //$gsr_title_html .= ' &nbsp;&nbsp;&nbsp; Global Service Reports (GSR) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; PHP Version: ' . phpversion();
  $gsr_title_html .= ' &nbsp;&nbsp;&nbsp; Global Service Reports (GSR) ';
  $gsr_title_html .= '</h1>';

  // Add help icon and click thru
  $gsr_title_html .= '<a href="'.constant('GSR_HELP_LINK').'" target="_blank" id="help_link">';
  $gsr_title_html .= '<img id="help_icon" border="0" src="/gsr/common/images/icons/help_small.gif" title="Help" \>';
  $gsr_title_html .= '</a>';
  
  $gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home">';
  $gsr_title_html .= '<img id="gs_pbi_home_icon" style="height:77px; width:77px; padding:0;" border="0" src="/gsr/common/images/gsr_PowerBI_white.png" title="Link to Global Service PowerBI Clearinghouse" \></a>';

  
  
  
  
  //$gsr_title_html .= '<h1 id="banner"><img src="/gsr/common/images/gsr_home_banner_1024x125.png" \></h1>';

  // Add help icon and click thru
  //$gsr_title_html .= '<a href="'.constant('GSR_HELP_LINK').'" target="_blank" id="help_link">';
  //$gsr_title_html .= '<img id="help_icon" border="0" src="/gsr/common/images/icons/help_small.gif" title="Help" \>';
  //$gsr_title_html .= '</a>';
  
    // Link to GS PBI (Clearinghouse) Home Page
  //$gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home">';
  //$gsr_title_html .= '<img id="gs_pbi_home_icon" style="height:32px; width:80px; padding:0;"  border="0" src="/gsr/common/images/pbi_1_150x47.png" title="Link to Global Service PBI Clearinghouse" \>' . 
  //$gsr_title_html .= '</a>';

  // gsr_PowerBI_trans.png,   gsr_PowerBI_white.png
  //$gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home">';
  //$gsr_title_html .= '<img id="gs_pbi_home_icon" style="height:77px; width:77px; padding:0;" border="0" src="/gsr/common/images/gsr_PowerBI_white.png" title="Link to Global Service PBI Clearinghouse" \>'; 
  //$gsr_title_html .= '</a>';

  
  
  //$gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home2">';
  //$gsr_title_html .= '<img id="gs_pbi_home_icon" style="height:65px; width:65px; padding:0;" border="0" src="/gsr/common/images/gsr_PowerBI_white.png" title="Link to Global Service PBI Clearinghouse" \>'; 
  //$gsr_title_html .= '</a>';

  //$gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home2">';
  //$gsr_title_html .= '<img id="gs_pbi_home_icon2" border="0" src="/gsr/common/images/gsr_PowerBI_white.png" title="Link to Global Service PBI Clearinghouse" \>'; 
  //$gsr_title_html .= '</a>';
  
  //$gsr_title_html .= '<a href="/gs_pbi.php" target="_blank" id="link_to_pbi_home2">';
  //$gsr_title_html .= '<img id="gs_pbi_home_icon2" border="0" src="/gsr/common/images/abbott_logo_only_white.png" title="Link to Abbott Home" \>'; 
  //$gsr_title_html .= '</a>';

    

  $end_line = "\r\n";

  $gsr_menu_html = ''.$end_line;

  $gsr_menu_html .= '<div id="cms-menu-wrapper">'.$end_line;

  $count = 1;
  //echo "<pre>";
  foreach ($gsr_menu_items['items'] as $_L1=>$menu_group)
  {
    //print_r($menu_group);
    // Lets put reference items in a div that will be a left side slide out.
    switch ($_L1){
        case 'left':
            $gsr_menu_html .= '<div id="references" class="menu-wrapper">';
            $gsr_menu_html .= '<a class="handle ui-slideouttab-handle-rounded">References</a>';
            $gsr_menu_html .= '<div id="sub_panel">';    // This to make the content vertical scrolling
            $gsr_menu_html .= '<div class="section-title">'.$menu_group['title'].'</div>';
        
            break;
        case 'right':
            $gsr_menu_html .= '<div id="secondary" class="menu-wrapper">';
            $gsr_menu_html .= '<a class="handle ui-slideouttab-handle-rounded">Secondary</a>';
            $gsr_menu_html .= '<div id="sub_panel">';    // This to make the content vertical scrolling
            if(isset($menu_group['title'])) {
                $gsr_menu_html .= '<div class="section-title">'.$menu_group['title'].'</div>';
            } else {
                $gsr_menu_html .= '<div class="section-title">Secondary Reports</div>';
            }
            break;
            
        default:
            $gsr_menu_html .= '<div class="menu-wrapper">';
            $gsr_menu_html .= '<div class="section-title">'.$menu_group['title'].'</div>'; 
        
    }
    
    

    foreach ($menu_group['items'] as $menu_level1)
    {
      $validated_icon = ($menu_level1["validated_for_prod"] == "Y") ? $menu_level1["validation_icon"] : '';
      $on_click = $menu_level1["link"];
      $title = htmlentities($menu_level1['title'],ENT_QUOTES);
      $desc = htmlentities($menu_level1['desc'],ENT_QUOTES);
      $class = (strlen($title) < 30) ? 'title norm' : 'title';
      $gsr_menu_html .= '<div class="link-wrapper">
                           <a href='.$on_click.' title="'.$desc.'" target="_blank">
                             <div class="'.$class.'">'.$title.'</div>
                           </a>'.$validated_icon.'
                           <div class="desc">'.$desc.'</div><div class="cleaner"></div>
                         </div>';
    }
    $gsr_menu_html .= '</div>'.$end_line;
    // Add closing DIV for slideout so we have a vertical scroll in the slide out.
    if($_L1 == 'left' OR $_L1 == 'right'){
       $gsr_menu_html .= '</div>'.$end_line; 
    } else {
      $count++;  
    }
    
    if ($count % 2)
      $gsr_menu_html .= '<div class="cleaner"></div>';
  }
  $gsr_menu_html .= '</div>'.$end_line;
  unset($db_obj);
  echo $gsr_title_html;
  echo $gsr_menu_html;   
?>

</body></html>
