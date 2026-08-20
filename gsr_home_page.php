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
/*
 * New/Classic experience preference handling.
 * This is intentionally kept in this existing page so no other GSR routes are affected.
 */
function gsrExperienceSqlValue($value)
{
    return str_replace("'", "''", (string)$value);
}
function gsrExperienceBaseUrl()
{
    $is_https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https');
    $protocol = $is_https ? 'https' : 'http';
    $host = !empty($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : (!empty($_SERVER['SERVER_NAME']) ? (string)$_SERVER['SERVER_NAME'] : 'localhost');
    // Prevent invalid characters from being used in a redirect URL.
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host);
    return $protocol . '://' . $host . '/';
}
function gsrExperienceUserId()
{
    if (!empty($_SERVER['HTTP_REMOTE_USER'])) {
        return (string)$_SERVER['HTTP_REMOTE_USER'];
    }
    if (!empty($_SERVER['HTTP_REMOTE_ADLOGON'])) {
        return (string)$_SERVER['HTTP_REMOTE_ADLOGON'];
    }
    return '';
}

function gsrExperienceUserName($userId)
{
    if (!empty($_SERVER['HTTP_REMOTE_DISPLAYNAME'])) {
        return (string)$_SERVER['HTTP_REMOTE_DISPLAYNAME'];
    }
    $lastName = !empty($_SESSION['azure_auth_LastName']) ? (string)$_SESSION['azure_auth_LastName'] : '';
    $firstName = !empty($_SESSION['azure_auth_FirstName']) ? (string)$_SESSION['azure_auth_FirstName'] : '';
    $sessionName = trim($lastName . ($lastName !== '' && $firstName !== '' ? ', ' : '') . $firstName);
    if ($sessionName !== '') {
        return $sessionName;
    }
    if (!empty($_SERVER['HTTP_REMOTE_GIVENNAME'])) {
        return (string)$_SERVER['HTTP_REMOTE_GIVENNAME'];
    }
    return (string)$userId;
}

$experience_user_id = gsrExperienceUserId();
$gsr_base_url = gsrExperienceBaseUrl();
$new_experience_url = $gsr_base_url . 'gsr/new';
$experience_switch = isset($_GET['switch_experience']) ? strtolower((string)$_GET['switch_experience']) : '';
/* User explicitly selected the new dashboard. Insert the first time; update on later selections. */
if ($experience_switch === 'new' && $experience_user_id !== '') {
    try {
        $experience_db = new gsr_sqlsrv_db_class('gsr_write');		
        $safe_user_id = gsrExperienceSqlValue($experience_user_id);
        $safe_user_name = gsrExperienceSqlValue(gsrExperienceUserName($experience_user_id));
        $clicked_url = $new_experience_url;
        $safe_clicked_url = gsrExperienceSqlValue($clicked_url);
        /* Check whether this user already has a preference record. */
        $experience_db->run_sql_save_with_exit_on_error("SELECT id FROM [GSR_ADMIN].[dbo].[GSR_USER_EXPERIENCE_PREFERENCE] WHERE user_id = '" . $safe_user_id . "'");
        $existing_user = $experience_db->fetch_array_assoc();
        if (!empty($existing_user)) {
            /* Existing user: update the same record. */
            $experience_db->run_sql_save_with_exit_on_error("UPDATE [GSR_ADMIN].[dbo].[GSR_USER_EXPERIENCE_PREFERENCE] SET user_name = '" . $safe_user_name . "', clicked_datetime = GETDATE(), clicked_url = '" . $safe_clicked_url . "', clicked_status = 1 WHERE user_id = '" . $safe_user_id . "'");
        } else {
            /* New user: create the preference record once. */
            $experience_db->run_sql_save_with_exit_on_error("INSERT INTO [GSR_ADMIN].[dbo].[GSR_USER_EXPERIENCE_PREFERENCE] (user_id, user_name, clicked_datetime, clicked_url, clicked_status) VALUES ('" . $safe_user_id . "', '" . $safe_user_name . "', GETDATE(), '" . $safe_clicked_url . "', 1)");
        }
        unset($experience_db);
    } catch (Exception $e) {
        /* Preference logging must never block normal GSR access. */
    }
    header('Location: ' . $new_experience_url);
    exit;
}
/*
 * On later visits to the classic landing page, users who selected the new
 * experience are sent directly to the new dashboard.
 */
if ($experience_switch === '' && $experience_user_id !== '') {
    try {
        $experience_db = new sqlsrv_db_class();
        $safe_user_id = gsrExperienceSqlValue($experience_user_id);
        $experience_db->run_sql_save_with_exit_on_error("SELECT TOP 1 clicked_status FROM [GSR_ADMIN].[dbo].[GSR_USER_EXPERIENCE_PREFERENCE] WHERE user_id = '" . $safe_user_id . "'  ORDER BY id DESC ");
        $experience_row = $experience_db->fetch_array_assoc();
        unset($experience_db);
        if (!empty($experience_row) && (int)$experience_row[0]['clicked_status'] === 1) {
            header('Location: ' . $new_experience_url);
            exit;
        }
    } catch (Exception $e) {
        /* If preference lookup fails, continue showing the existing classic page. */
    }
}
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
<style type="text/css">
    /* New GSR experience banner */
    #new-gsr-experience {
        margin: 10px 14px 14px 14px;
        padding: 14px 18px;
        border: 1px solid #c7dcf3;
        border-left: 5px solid #1976c9;
        border-radius: 8px;
        background: linear-gradient(90deg, #f7fbff 0%, #eef7ff 100%);
        box-shadow: 0 2px 8px rgba(0, 64, 128, 0.10);
        font-family: Arial, Helvetica, sans-serif;
        overflow: hidden;
    }

    #new-gsr-experience .experience-content {
        float: left;
        width: 72%;
        padding-top: 1px;
    }

    #new-gsr-experience .experience-title {
        margin: 0 0 5px 0;
        color: #0d5f9f;
        font-size: 16px;
        line-height: 20px;
        font-weight: bold;
    }

    #new-gsr-experience .experience-title:before {
        content: "\2605";
        display: inline-block;
        margin-right: 7px;
        color: #f2a100;
        font-size: 15px;
    }

    #new-gsr-experience .experience-text {
        margin: 0;
        color: #4b5f70;
        font-size: 12px;
        line-height: 18px;
    }

    #new-gsr-experience .experience-highlight {
        color: #154f7b;
        font-weight: bold;
    }

    #new-gsr-experience .experience-action {
        float: right;
        width: 25%;
        padding-top: 9px;
        text-align: right;
    }

    #new-gsr-experience .experience-button {
        display: inline-block;
        padding: 10px 20px;
        background: #1976c9;
        border: 1px solid #0f5f9f;
        border-radius: 5px;
        color: #ffffff;
        font-size: 12px;
        line-height: 16px;
        font-weight: bold;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0, 75, 145, 0.22);
        transition: background 0.2s ease, box-shadow 0.2s ease;
    }

    #new-gsr-experience .experience-button:hover {
        background: #0f65ad;
        color: #ffffff;
        text-decoration: none;
        box-shadow: 0 3px 7px rgba(0, 75, 145, 0.28);
    }

    #new-gsr-experience:after {
        content: "";
        display: table;
        clear: both;
    }

    @media (max-width: 800px) {
        #new-gsr-experience .experience-content,
        #new-gsr-experience .experience-action {
            float: none;
            width: auto;
            text-align: left;
        }

        #new-gsr-experience .experience-action {
            padding-top: 10px;
        }
    }
</style>

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

    


  // New GSR experience banner
  
  $new_experience_html  = '<div id="new-gsr-experience">';
  $new_experience_html .= '  <div class="experience-content">';
  $new_experience_html .= '    <div class="experience-title">A New GSR Experience Is Here!</div>';
  $new_experience_html .= '    <p class="experience-text">Explore faster search, improved navigation, personalized favorites, and a modern dashboard.
The new experience becomes the default on <strong>September 18th.</strong><br>';
  $new_experience_html .= '    <span class="experience-text">Any feedback or suggestions, please email GSR team at: <strong>Global_Service_Systems_GSR@abbott.com<strong></span></p>';
  $new_experience_html .= '  </div>';
  $new_experience_html .= '  <div class="experience-action">';
  $new_experience_html .= '    <a class="experience-button" href="?switch_experience=new" title="Open the new GSR dashboard">Switch to New Experience</a>';
  $new_experience_html .= '  </div>';
  $new_experience_html .= '</div>';

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
  echo $new_experience_html;
  echo $gsr_menu_html;   
?>

</body></html>