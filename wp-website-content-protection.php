<?php
/*
Plugin Name: WP Website Content Protection (by SiteGuarding.com)
Plugin URI: http://www.siteguarding.com/en/website-extensions
Description: Detects all the changes in your posts and pages. Highlights the changes. Sends the alerts by email.
Version: 1.1
Author: SiteGuarding.com
Author URI: http://www.siteguarding.com
License: GPLv2
TextDomain: plgsgwcp
*/ 
// rev.20200601

define('PLGSGWCP_PLUGIN_VERSION', '1.1');

if (!defined('DIRSEP'))
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') define('DIRSEP', '\\');
    else define('DIRSEP', '/');
}

add_filter( 'cron_schedules', 'cron_add_weekly' );
function cron_add_weekly( $schedules ) {
	$schedules['one_week'] = array(
		'interval' => 60 * 60 * 24 * 7,
		'display' => 'one per week'
	);
	return $schedules;
}



add_action( 'plgsgwcp_check_content', 'plgsgwcp_CRON_job_check_backup' );
function plgsgwcp_CRON_job_check_backup()
{
    
    
}





if( is_admin() ) {
	
	//error_reporting(0);
	function plgsgwcp_admin_init()
	{
		wp_register_style( 'plgsgwcp_LoadStyle', plugins_url('css/wp-website-content-protection.css', __FILE__) );	
	}
    add_action( 'admin_init', 'plgsgwcp_admin_init' );
    
    

    
    
	function register_plgsgwcp_page() 
	{
		add_menu_page('plgsgwcp_protection', 'Content Protection', 'activate_plugins', 'plgsgwcp_protection', 'register_plgsgwcp_page_callback', plugins_url('images/', __FILE__).'website-content-protection-logo.png');
	}
    add_action('admin_menu', 'register_plgsgwcp_page');
    

	function register_plgsgwcp_page_callback() 
	{
        wp_enqueue_style( 'plgsgwcp_LoadStyle' );
        
        $params = PLGSGWCP_class_HTML::Get_Params();
        
        ?>

        <div class="wrap">
        <h1>WordPress Content Protection</h1>
        
        <?php
            if (isset($_REQUEST['refile_o']) && isset($_REQUEST['post_id']))
            {
                ?>
                    <br />
                    <h1 class="wp-heading-inline">Changes in text</h1>
                <?php
                
                $a_arr = PLGSGWCP_class_HTML::Read_Backup_file(sanitize_file_name($_REQUEST['refile_o']).'_backup.gz');
                
                $post_data = array(
                  'ID'           => $a_arr['ID'],
                  'post_title'   => $a_arr['post_title'],
                  'post_content' => $a_arr['post_content']
                );
                wp_update_post( $post_data );
                
                $txt = 'Post '.$a_arr['ID'].' is restored [title: '.$a_arr['post_title'].']';
                PLGSGWCP_class_HTML::ShowMessage($txt);
            }
        ?>
        
        <?php
        
                PLGSGWCP_class_HTML::Backup_content();
                
        ?>
        
                <div style="margin:10px 0">
                	<a target="_blank" href="https://www.siteguarding.com/en/protect-your-website">
                	<img src="<?php echo plugins_url('images/rek3.png', __FILE__); ?>" />
                	</a>
                    
                	<a target="_blank" style="margin:0 10px" href="https://www.siteguarding.com/en/website-extensions">
                	<img src="<?php echo plugins_url('images/rek1.png', __FILE__); ?>" />
                	</a>
                    
                	<a target="_blank" href="https://www.siteguarding.com/en/secure-web-hosting">
                	<img src="<?php echo plugins_url('images/rek4.png', __FILE__); ?>" />
                    </a>
                    
                </div>
                
        
            <div class="welcome-panel sgwcp_box">
            <div class="welcome-panel-content">
            <h2 class="sgwcp_h2">History calendar</h2>
            <p>Calendar shows the dates when your content is changed. Click on the date to see the changes.</p>
            </div>
            <?php
            $date_start = mktime(0, 0, 0, date("m")-$params['keep_history'], 1,   date("Y"));
            $date_end = mktime(0, 0, 0, date("m")+1, 1,   date("Y"));
            
            $history_arr = array();
            
            $date_current = $date_start;
            
            $i = 0;
            while($date_current < $date_end) 
            {
                $tmp_i = $date_start+$i*24*60*60;
                
                $date_current = $tmp_i;
                $month_current = date("m", $tmp_i);
                $day_current = date("d", $tmp_i);
                $year_current = date("Y", $tmp_i);
                
                if ($date_current < $date_end)
                {
                    $history_arr[$year_current.'-'.$month_current.'-01'][$day_current] = 0;
                }
                
                $i++;
            }
            
            $history_arr = PLGSGWCP_class_HTML::Calendar_find_changes($history_arr);
            
            //print_r($history_arr);
            // Print calendar
            echo '<table class="sgwcp_calendar">';
            foreach ($history_arr as $month => $month_arr)
            {
                $txt_Yd = date("Y-m", strtotime($month));
                echo '<tr><td class="sgwcp_month">'.date("F Y", strtotime($month)).'</td><td>';
                foreach ($month_arr as $day => $changes)
                {
                    //if (rand(1,3) == 1) $changes = 1;
                    $changes_class = '';
                    $changes_link = ' href="admin.php?page=plgsgwcp_protection&date='.$txt_Yd.'-'.$day.'"';
                    if ($changes > 0) $changes_class = ' class="changes"';
                    
                    echo '<a'.$changes_class.$changes_link.'>'.$day.'</a>';    
                }
                echo '</td></tr>';
            }
            echo '</table>';
            
            
            if ( isset($_REQUEST['date']) )
            {
                $view_date = sanitize_text_field($_REQUEST['date']);
                
                $post_ids = PLGSGWCP_class_HTML::Get_Backup_files_by_date($view_date);
            }
            
            ?>
            
        </div>
        
        <?php
        if ( isset($_REQUEST['date']) ) 
        {
            $view_date = sanitize_text_field($_REQUEST['date']);
            
            $post_ids_dates = PLGSGWCP_class_HTML::Get_Backup_files_by_date($view_date);
        ?>
        <h1 class="wp-heading-inline">Changes for <?php echo $view_date; ?></h1>
        
        <?php
            if (count($post_ids_dates) == 0) echo '<p><b>No any changes for this date.</b></p>';
            else {
            ?>
                <script>
                function ConfirmRestore(link)
                {
                    var result = confirm("Do you want to restore the post?");
                    if (result) {
                        window.location.href=link;
                    }
                }

                </script>
                <table class="wp-list-table widefat fixed striped users">
                	<thead>
                	<tr>
                		<th>Post title</th><th>Lastest backup date</th><th>Actions</th>
                	</tr>
                	</thead>
                
                	<tbody>
                    <?php
                        foreach ($post_ids_dates as $post_id => $post_dates) 
                        {
                            $link_compare = 'admin.php?page=plgsgwcp_protection&date='.$view_date.'&file_o='.$post_id.'_'.str_replace(" ", "_", $post_dates[0]).'&file_c='.$post_id.'_'.str_replace(" ", "_", $post_dates[1]);
                            $link_restore = 'admin.php?page=plgsgwcp_protection&date='.$view_date.'&refile_o='.$post_id.'_'.str_replace(" ", "_", $post_dates[0]).'&post_id='.$post_id;
                    ?>
                	<tr>
                        <td class="column-primary"><?php echo PLGSGWCP_class_HTML::GetPostTitle_by_ID($post_id); ?></td>
                        <td class="column-primary"><?php echo PLGSGWCP_class_HTML::FileDate_to_Date($post_dates[0]); ?></td>
                        <td class="column-primary"><a href="<?php echo $link_compare; ?>"><span class="dashicons dashicons-admin-page"></span> Compare changes</a>&nbsp;&nbsp;&nbsp;<a onclick="ConfirmRestore('<?php echo $link_restore; ?>');" href="javascript:;"><span class="dashicons dashicons-sos"></span> Restore post</a></td>
                    </tr>
                    <?php
                        }
                    ?>
                </table>
            <?php
            }
        }
        ?>
        
        <?php
        if (isset($_REQUEST['file_o']) && isset($_REQUEST['file_c']))
        {
            ?>
                <br />
                <h1 class="wp-heading-inline">Changes in text</h1>
            <?php
            
            if (!class_exists('Diff')) require_once dirname(__FILE__).'/lib/Diff.php';
            
            $a_arr = PLGSGWCP_class_HTML::Read_Backup_file(sanitize_file_name($_REQUEST['file_o']).'_backup.gz');
            $b_arr = PLGSGWCP_class_HTML::Read_Backup_file(sanitize_file_name($_REQUEST['file_c']).'_backup.gz');
            
            if ($a_arr['post_title'] != $b_arr['post_title'])
            {
                echo '<p class="sgwcp_red"><b>Old title: </b>'.$a_arr['post_title']."<br>";
                echo '<b>New title: </b>'.$b_arr['post_title']."<br></p>";
            }
            
    		$a = explode("\n", $a_arr['post_content']);
    		$b = explode("\n", $b_arr['post_content']);
            
    		$options = array(
    			//'ignoreWhitespace' => true,
    			//'ignoreCase' => true,
    		);
            
            $diff = new Diff($a, $b, $options);
            
    		// Generate a side by side diff
    		if (!class_exists('Diff_Renderer_Html_SideBySide')) require_once dirname(__FILE__).'/lib/Diff/Renderer/Html/SideBySide.php';
            
    		$renderer = new Diff_Renderer_Html_SideBySide;
    		echo $diff->Render($renderer);
            
        }
        
        
        
        ?>
            <p>&nbsp;</p>
            <hr />
            <p>&nbsp;</p>
            <p>
    		If you need help to fix/clean your website and remove it from the blacklists please <a target="_blank" href="https://www.siteguarding.com/en/services/malware-removal-service">click here</a>.<br><br>
    		<a href="http://www.siteguarding.com/livechat/index.html" target="_blank">
    			<img src="<?php echo plugins_url('images/livechat.png', __FILE__); ?>"/>
    		</a><br>
    		For any questions and support please use LiveChat or this <a href="https://www.siteguarding.com/en/contacts" rel="nofollow" target="_blank" title="SiteGuarding.com - Website Security. Professional security services against hacker activity. Daily website file scanning and file changes monitoring. Malware detecting and removal.">contact form</a>.<br>
    		<br>
    		<a href="https://www.siteguarding.com/" target="_blank">SiteGuarding.com</a> - Website Security. Professional security services against hacker activity.<br>
    		</p>
        <?php
    }
	






	

	function register_plgsgwcp_settings_subpage() {
		add_submenu_page( 'plgsgwcp_protection', 'Settings', 'Settings', 'manage_options', 'plgsgwcp_settings_page', 'plgsgwcp_settings_page_callback' ); 
	}
    add_action('admin_menu', 'register_plgsgwcp_settings_subpage');
	
	
	function plgsgwcp_settings_page_callback()
	{
		if (isset($_POST['action']) && $_POST['action'] == 'update' && check_admin_referer( 'name_71B2C76585D9' ))
		{
            $data['keep_history'] = intval($_POST['keep_history']);
            $data['send_notifications'] = intval($_POST['send_notifications']);
            $data['email_for_notifications'] = sanitize_email($_POST['email_for_notifications']);
            if ($data['email_for_notifications'] == '') $data['email_for_notifications'] = get_option( 'admin_email' );
			
			PLGSGWCP_class_HTML::Set_Params($data);
			
			PLGSGWCP_class_HTML::ShowMessage('Settings saved.');
		}
		
		$params = PLGSGWCP_class_HTML::Get_Params();
		
		?>
        <div class="wrap">
		<h1><span class="dashicons dashicons-admin-generic"></span> WordPress Content Protection Settings</h1>
		
            <form method="post" id="plgwpagp_settings_page" action="admin.php?page=plgsgwcp_settings_page">


			<table id="settings_page">


			<tr class="line_4">
			<th scope="row"><?php _e( 'Keep History', 'plgsgwcp' )?></th>
			<td>
                <select name="keep_history" id="keep_history">
                <?php
                $params['keep_history'] = intval($params['keep_history']);
                if ($params['keep_history'] == 0) $params['keep_history'] = 3;
                $txt = 'month';
                for ($i = 1; $i <= 12; $i++)
                {
                    if ($params['keep_history'] == $i) $sel = ' selected="selected"';
                    else $sel = '';
                    
                    if ($i == 2) $txt = $txt.'s';
                    echo '<option value="'.$i.'"'.$sel.'>'.$i.' '.$txt.'</option>';
                }
                ?>
                </select>
	            
	            <span class="description">Select how long you want to keep the information about all the changes</span>
			</td>
			</tr>
            
			<tr class="line_4">
			<th scope="row">&nbsp;</th>
			<td>
	            <hr />
			</td>
			</tr>

            
			<tr class="line_4">
			<th scope="row"><?php _e( 'Send Notifications', 'plgsgwcp' )?></th>
			<td>
                <?php
                if (!isset($params['send_notifications'])) $params['send_notifications'] = 1;
                ?>
	            <input name="send_notifications" type="checkbox" id="send_notifications" value="1" <?php if (intval($params['send_notifications']) == 1) echo 'checked="checked"'; ?>>
                &nbsp;<span class="msg_alert">We will send important notifications by email.</span>
			</td>
			</tr>
            
			<tr class="line_4">
			<th scope="row"><?php _e( 'Email for Notifications', 'plgsgwcp' )?></th>
			<td>
                <?php
                if (trim($params['email_for_notifications']) == '') $params['email_for_notifications'] = get_option( 'admin_email' );
                ?>
	            <input type="text" name="email_for_notifications" id="email_for_notifications" value="<?php echo $params['email_for_notifications']; ?>" class="regular-text">
			</td>
			</tr>

			
			</table>

        <?php
        wp_nonce_field( 'name_71B2C76585D9' );
        ?>			
        <p class="submit">
          <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>
        
        <input type="hidden" name="page" value="plgsgwcp_settings_page"/>
        <input type="hidden" name="action" value="update"/>
        </form>
        </div>

		<?php
	}




    function plgsgwcp_post_updated_action( $post_id ) 
    {
        $post = get_post($post_id);
        
        if ($post->post_status != 'publish') return;
        
        
        $latest_md5 = PLGSGWCP_class_HTML::Read_Latest_json();

        $md5_post_title = md5($post->post_title);
        $md5_post_content = md5($post->post_content);
        
        if ( isset($latest_md5[$post_id]) && $latest_md5[$post_id] == $md5_post_title.$md5_post_content) 
        {
            // Skip backup
            return;  
        }
        
        $post_backup_arr = array(
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
        );
        
        PLGSGWCP_class_HTML::Save_Post_gz($post_backup_arr);
                    
        $latest_md5[$post_id] = $md5_post_title.$md5_post_content;
            
        PLGSGWCP_class_HTML::Save_Latest_json($latest_md5);
        
        
        // Send alert
        $params = PLGSGWCP_class_HTML::Get_Params(array('send_notifications', 'email_for_notifications'));
        if (intval($params['send_notifications']) == 1)
        {
            PLGSGWCP_class_HTML::SendAlert($params['email_for_notifications'], array($post->post_title));
        }
        
    }
    add_action( 'save_post', 'plgsgwcp_post_updated_action' );



    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'plgsgwcp_add_action_link', 10, 2 );
    function plgsgwcp_add_action_link( $links, $file )
    {
  		$faq_link = '<a target="_blank" href="https://www.siteguarding.com/en/protect-your-website">Premium Security</a>';
		array_unshift( $links, $faq_link );
        
  		$faq_link = '<a target="_blank" href="https://www.siteguarding.com/en/contacts">Help</a>';
		array_unshift( $links, $faq_link );

		return $links;
    } 
    


    function plgsgwcp_API_Request($type = '')
    {
        $plugin_code = 8;
        $website_url = get_site_url();
        
        $url = "https://www.siteguarding.com/ext/plugin_api/index.php";
        $response = wp_remote_post( $url, array(
            'method'      => 'POST',
            'timeout'     => 600,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => array(
                'action' => 'inform',
                'website_url' => $website_url,
                'action_code' => $type,
                'plugin_code' => $plugin_code,
            ),
            'cookies'     => array()
            )
        );
    }
    
    
	function plgsgwcp_activation()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgwcp_config';
		if( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name .'"' ) != $table_name ) {
			$sql = 'CREATE TABLE IF NOT EXISTS '. $table_name . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `var_name` char(255) CHARACTER SET utf8 NOT NULL,
                `var_value` LONGTEXT CHARACTER SET utf8 NOT NULL,
                PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql ); // Creation of the new TABLE
            
            $data = array(
                'installation_date' => date("Y-m-d"),
                'keep_history' => 3,
                'send_notifications' => 1,
                'email_for_notifications' => get_option( 'admin_email' )
            );
            PLGSGWCP_class_HTML::Set_Params( $data );
		}
        
        plgsgwcp_API_Request(1);
		wp_clear_scheduled_hook( 'plgsgwcp_check_content' );	
		wp_schedule_event( time(), 'one_week', 'plgsgwcp_check_content');
	}
	register_activation_hook( __FILE__, 'plgsgwcp_activation' );
    


	register_deactivation_hook( __FILE__, 'deactivation_plgsgwcp_check_content');
	function deactivation_plgsgwcp_check_content() {
		plgsgwcp_API_Request(2);
        wp_clear_scheduled_hook('plgsgwcp_check_content');
	}
	
	if( ! wp_next_scheduled( 'plgsgwcp_check_content' ) ) {  
		wp_schedule_event( time(), 'one_week', 'plgsgwcp_check_content');  
	}
    

    
	function plgsgwcp_uninstall()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgwcp_config';
		$wpdb->query( 'DROP TABLE ' . $table_name );
        
        plgsgwcp_API_Request(3);
	}
	register_uninstall_hook( __FILE__, 'plgsgwcp_uninstall' );
	
}


/**
 * Functions
 */
class PLGSGWCP_class_HTML
{
	public static function ShowMessage($txt)
	{
		echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong><span class="dashicons dashicons-yes"></span>'.$txt.'</strong></p></div>';
	}


    public static function Get_Backup_Folder()
    {
        return WP_CONTENT_DIR.DIRSEP.'backup-website-content';
    }


    public static function GetPostTitle_by_ID($post_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'posts';
        
        $rows = $wpdb->get_results( 
        	"
        	SELECT post_title
        	FROM ".$table_name."
            WHERE ID = ".$post_id."
            LIMIT 1;
        	"
        );
        
        if (count($rows)) return $rows[0]->post_title;
        else return false;
    }
    
    public static function GetPostTitles_by_IDs($post_ids = array())
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'posts';
        
        $rows = $wpdb->get_results( 
        	"
        	SELECT ID, post_title
        	FROM ".$table_name."
            WHERE ID IN (".implode(",", $post_ids).")
        	"
        );
        
        if (count($rows)) 
        {
            $a = array();
            foreach ($rows as $row)
            {
                $a[$row->ID] = $row->post_title;
            }
            return $a;
        }
        else return false;
    }



    public static function GetAllPosts_IDs()
    {
        global $wpdb;
        
        // post_status = publish
        // post_type = page, post
        $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_type='page' OR post_type='post') AND post_status = 'publish'" ) );
        
        return $post_ids;
    }
    
    
    public static function Read_Latest_json()
    {
        self::Prepare_Backup_folder();
        
        $filename = self::Get_Backup_Folder().DIRSEP.'latest.json';
        if(!file_exists($filename)) return array();
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        
        $latest_json = (array)json_decode($contents, true);
        if ($latest_json === false) return array();
        
        return $latest_json;
    }

    
    public static function Save_Latest_json($a)
    {
        self::Prepare_Backup_folder();
        
        $filename = self::Get_Backup_Folder().DIRSEP.'latest.json';
        $fp = fopen($filename, 'w');
        fwrite($fp, json_encode($a));
        fclose($fp);
    }
    
    
    public static function Read_Backup_folder()
    {
        self::Prepare_Backup_folder();
        
        $filename = self::Get_Backup_Folder().DIRSEP;
        
        $a = array();
        foreach (glob(self::Get_Backup_Folder().DIRSEP.'*_backup.gz') as $filename) 
        {
            $filename = basename($filename);
            $filename = explode("_", $filename);
            $a[intval($filename[0])][] = trim($filename[1]);
        }

        return $a;
    }

    
    public static function Prepare_Backup_folder()
    {
        $filename = self::Get_Backup_Folder().DIRSEP;
        if(!file_exists($filename)) mkdir($filename);
        
        $filename = $filename.'.htaccess';
        if(!file_exists($filename)) 
        {
            $fp = fopen($filename, 'w');
            fwrite($fp, '<Limit GET POST>'."\n".'order deny,allow'."\n".'deny from all'."\n".'</Limit>');
            fclose($fp);
        }
    }
    
    public static function Save_Post_gz($post_arr)
    {
        self::Prepare_Backup_folder();
        
        $filename = self::Get_Backup_Folder().DIRSEP.$post_arr['ID'].'_'.date("Y-m-d_His").''.'_backup.gz';
        
        $gz = gzopen($filename,'w9');
        gzwrite($gz, json_encode($post_arr));
        gzclose($gz);
    }
    
    
    
    public static function Read_Backup_file($file)
    {
        $filename = self::Get_Backup_Folder().DIRSEP.$file;
        $zd = gzopen($filename, "r");
        $contents = gzread($zd, self::gzfilesize($filename));
        gzclose($zd);
        
        return (array)json_decode($contents, true);
    }
    
    public static function gzfilesize($filename) {
      $gzfs = FALSE;
      if(($zp = fopen($filename, 'r'))!==FALSE) {
        if(@fread($zp, 2) == "\x1F\x8B") { // this is a gzip'd file
          fseek($zp, -4, SEEK_END);
          if(strlen($datum = @fread($zp, 4))==4)
            extract(unpack('Vgzfs', $datum));
        }
        else // not a gzip'd file, revert to regular filesize function
          $gzfs = filesize($filename);
        fclose($zp);
      }
      return($gzfs);
    }
    
    
    public static function Calendar_find_changes($history_arr)
    {
        $changes = self::Read_Backup_folder();

        foreach ($changes as $posd_id => $change_dates)
        {
            if (count($change_dates) == 1) continue;
            else unset($change_dates[0]);
            
            foreach ($change_dates as $change_date)
            {
                $time = strtotime($change_date);
                $history_arr[date("Y-m", $time).'-01'][date("d", $time)] = 1;
            }
        }
        
        return $history_arr;
    }



    public static function Backup_content()
    {
        self::Prepare_Backup_folder();
        
        $post_arr_with_changes = array();
        
        $post_ids = self::GetAllPosts_IDs();
        
        $latest_md5 = self::Read_Latest_json();
        $latest_md5_new = array();
        
        if (count($post_ids))
        {
            // Backup by 10
            $max = 10;
            for ($i = 0; $i < ceil(count($post_ids) / $max); $i++)
            {
                $post_ids_block = array_slice($post_ids, $i * $max, $max);
                
                foreach ($post_ids_block as $post_id)
                {
                    $post = get_post($post_id);
                    
                    $md5_post_title = md5($post->post_title);
                    $md5_post_content = md5($post->post_content);
                    
                    if ( isset($latest_md5[$post_id]) && $latest_md5[$post_id] == $md5_post_title.$md5_post_content) 
                    {
                        // Skip backup
                        $latest_md5_new[$post_id] = $latest_md5[$post_id];
                        continue;  
                    }
                    
                    $post_backup_arr = array(
                        'ID' => $post->ID,
                        'post_title' => $post->post_title,
                        'post_content' => $post->post_content,
                    );
                    
                    self::Save_Post_gz($post_backup_arr);
                    
                    $post_arr_with_changes[$post->ID] = $post->post_title;
                    
                    $latest_md5_new[$post_id] = $md5_post_title.$md5_post_content;
                }
            }
            
            self::Save_Latest_json($latest_md5_new);
        }


        if (count($post_arr_with_changes))
        {
            // Send alert by email
            $params = self::Get_Params(array('send_notifications', 'email_for_notifications'));
            
            if (intval($params['send_notifications']) == 1)
            {
                self::SendAlert($params['email_for_notifications'], $post_arr_with_changes);
            }
        }
    }
    

    public static function SendAlert($email_for_notifications, $post_titles_array = array())
    {
        if (count($post_titles_array) == 0) return;
        
        $i = 1;
        foreach ($post_titles_array as $k => $title)
        {
            $post_titles_array[$k] = $i.'. '.$title;
            $i++;
        }

        $result_txt = 'Date: '.date('Y-m-d H:i:s')."<br><br>".'<span style="color:#cf3622">We have noticed the changes in content of <b>'.get_site_url().'</b></span> '."<br><br><b>".implode("<br>", $post_titles_array)."</b><br><br>".'If you did not edit your content, please login to your website and compare the changes.'."<br><br>";
        $email_subject = 'We have noticed the changed on your website '.get_site_url();
        self::SendEmail($email_for_notifications, $result_txt, $email_subject);
    }


    
    
    public static function Get_Backup_files_by_date($view_date)
    {
        $post_ids = array();
        $post_ids_all = array();
        foreach (glob(self::Get_Backup_Folder().DIRSEP.'*_backup.gz') as $filename) 
        {
            $filename = basename($filename);

            $filename = explode("_", $filename);
            $filename[1] = trim($filename[1]);
            if ($filename[1] <= $view_date) $post_ids_all[intval($filename[0])][] = $filename[1].' '.$filename[2];
        }
        
        
        foreach (glob(self::Get_Backup_Folder().DIRSEP.'*_'.$view_date.'_*_backup.gz') as $filename) 
        {
            $filename = basename($filename);

            $filename = explode("_", $filename);
            $post_ids[intval($filename[0])] = 1;
        }

        $post_data = array();
        if (count($post_ids))
        {
            foreach ($post_ids as $post_id => $tmp_val)
            {
                $post_data[$post_id] = $post_ids_all[$post_id];
            }

            foreach ($post_data as $post_id => $post_dates)
            {
                if (count($post_dates) == 1) 
                {
                    unset($post_data[$post_id]);
                    continue;
                }
                
                sort($post_dates);
                
                $post_data[$post_id] = array_slice($post_dates, -2, 2);
            }
        }
        //print_r($post_data);
        return $post_data;
    }
    


    public static function FileDate_to_Date($fdate)
    {
        return substr($fdate, 0, 13).":".substr($fdate, 13, 2).":".substr($fdate, 15, 2);
    }



    public static function Get_Params($vars = array())
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'plgsgwcp_config';
        
        $ppbv_table = $wpdb->get_results("SHOW TABLES LIKE '".$table_name."'" , ARRAY_N);
        if(!isset($ppbv_table[0])) return false;
        
        if (count($vars) == 0)
        {
            $rows = $wpdb->get_results( 
            	"
            	SELECT *
            	FROM ".$table_name."
            	"
            );
        }
        else {
            foreach ($vars as $k => $v) $vars[$k] = "'".$v."'";
            
            $rows = $wpdb->get_results( 
            	"
            	SELECT * 
            	FROM ".$table_name."
                WHERE var_name IN (".implode(',',$vars).")
            	"
            );
        }
        
        $a = array();
        if (count($rows))
        {
            foreach ( $rows as $row ) 
            {
            	$a[trim($row->var_name)] = trim($row->var_value);
            }
        }
    
        return $a;
    }
    
    
    public static function Set_Params($data = array())
    {
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgwcp_config';
    
        if (count($data) == 0) return;   
        
        foreach ($data as $k => $v)
        {
            $tmp = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE var_name = %s LIMIT 1;', $k ) );
            
            if ($tmp == 0)
            {
                // Insert    
                $wpdb->insert( $table_name, array( 'var_name' => $k, 'var_value' => $v ) ); 
            }
            else {
                // Update
                $data = array('var_value'=>$v);
                $where = array('var_name' => $k);
                $wpdb->update( $table_name, $data, $where );
            }
        } 
    }


	public static function PrepareDomain($domain)
	{
	    $host_info = parse_url($domain);
	    if ($host_info == NULL) return false;
	    $domain = $host_info['host'];
	    if ($domain[0] == "w" && $domain[1] == "w" && $domain[2] == "w" && $domain[3] == ".") $domain = str_replace("www.", "", $domain);
	    //$domain = str_replace("www.", "", $domain);
	    
	    return $domain;
	}

    
    public static function CheckAntivirusInstallation()
    {
        $avp_path = dirname(__FILE__);
		$avp_path = str_replace('wp-website-content-protection', 'wp-antivirus-site-protection', $avp_path);
        return file_exists($avp_path);
    }
    
    
    
    
	public static function SendEmail($email, $result, $subject = '')
	{
		$to  = $email; // note the comma
		
		// message
        $body_message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SiteGuarding - Professional Web Security Services!</title>
</head>
<body bgcolor="#ECECEC" style="background-color:#ECECEC;">
<table cellpadding="0" cellspacing="0" width="100%" align="center" border="0" bgcolor="#ECECEC" style="background-color: #fff;">
  <tr>
    <td width="100%" align="center" bgcolor="#ECECEC" style="padding: 5px 30px 20px 30px;">
      <table width="750" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#fff" style="background-color: #fff;">
        <tr>
          <td width="750" bgcolor="#fff"><table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color: #fff;">
            <tr>
              <td width="350" height="60" bgcolor="#fff" style="padding: 5px; background-color: #fff;"><a href="http://www.siteguarding.com/" target="_blank"><img src="data:image/png;base64,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" alt="SiteGuarding - Protect your website from unathorized access, malware and other threat" height="60" border="0" style="display:block" /></a></td>
              <td width="400" height="60" align="right" bgcolor="#fff" style="background-color: #fff;">
              <table border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color: #fff;">
                <tr>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/login" target="_blank" style="color:#656565; text-decoration: none;">Login</a></td>
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/prices" target="_blank" style="color:#656565; text-decoration: none;">Services</a></td>
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/what-to-do-if-your-website-has-been-hacked" target="_blank" style="color:#656565; text-decoration: none;">Security Tips</a></td>            
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif;  font-size:11px;"><a href="http://www.siteguarding.com/en/contacts" target="_blank" style="color:#656565; text-decoration: none;">Contacts</a></td>
                  <td width="30"></td>
                </tr>
              </table>
              </td>
            </tr>
          </table></td>
        </tr>

        <tr>
          <td width="750" height="2" bgcolor="#D9D9D9"></td>
        </tr>
        <tr>
          <td width="750" bgcolor="#fff" ><table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color:#fff;">
            <tr>
              <td width="750" height="30"></td>
            </tr>
            <tr>
              <td width="750">
                <table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color:#fff;">
                <tr>
                  <td width="30"></td>
                  <td width="690" bgcolor="#fff" align="left" style="background-color:#fff; font-family:Arial, Helvetica, sans-serif; color:#000000; font-size:12px;">
                    {MESSAGE_CONTENT}
                    <br>
                    <b>URGENT SUPPORT</b><br>
                    Not sure in the report details? Need urgent help and support. Please contact us <a href="https://www.siteguarding.com/en/contacts" target="_blank">https://www.siteguarding.com/en/contacts</a>
                  </td>
                  <td width="30"></td>
                </tr>
              </table></td>
            </tr>
            <tr>
              <td width="750" height="15"></td>
            </tr>
            <tr>
              <td width="750" height="15"></td>
            </tr>
            <tr>
              <td width="750"><table width="750" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="30"></td>
                  <td width="690" align="left" style="font-family:Arial, Helvetica, sans-serif; color:#000000; font-size:12px;"><strong>How can we help?</strong><br />
                    If you have any questions please dont hesitate to contact us. Our support team will be happy to answer your questions 24 hours a day, 7 days a week. You can contact us at <a href="mailto:support@siteguarding.com" style="color:#2C8D2C;"><strong>support@siteguarding.com</strong></a>.<br />
                    <br />
                    Thanks again for choosing SiteGuarding as your security partner!<br />
                    <br />
                    <span style="color:#2C8D2C;"><strong>SiteGuarding Team</strong></span><br />
                    <span style="font-family:Arial, Helvetica, sans-serif; color:#000; font-size:11px;"><strong>We will help you to protect your website from unauthorized access, malware and other threats.</strong></span></td>
                  <td width="30"></td>
                </tr>
              </table></td>
            </tr>
            <tr>
              <td width="750" height="30"></td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td width="750" height="2" bgcolor="#D9D9D9"></td>
        </tr>
      </table>
      <table width="750" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="750" height="10"></td>
        </tr>
        <tr>
          <td width="750" align="center"><table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/website-daily-scanning-and-analysis" target="_blank" style="color:#656565; text-decoration: none;">Website Daily Scanning</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/malware-backdoor-removal" target="_blank" style="color:#656565; text-decoration: none;">Malware & Backdoor Removal</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/update-scripts-on-your-website" target="_blank" style="color:#656565; text-decoration: none;">Security Analyze & Update</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/website-development-and-promotion" target="_blank" style="color:#656565; text-decoration: none;">Website Development</a></td>
            </tr>
          </table></td>
        </tr>

        <tr>
          <td width="750" height="10"></td>
        </tr>
        <tr>
          <td width="750" align="center" style="font-family: Arial,Helvetica,sans-serif; font-size: 10px; color: #656565;">Add <a href="mailto:support@siteguarding.com" style="color:#656565">support@siteguarding.com</a> to the trusted senders list.</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
';

		$body_message = str_replace("{MESSAGE_CONTENT}", $result, $body_message);
		
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		
		// Additional headers
		$headers .= 'From: '. $to . "\r\n";
		
		// Mail it
		return wp_mail($to, $subject, $body_message, $headers);
	}
    

}



/* Dont remove this code: SiteGuarding_Block_25BCC4C0BF5C */
