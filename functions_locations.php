<?php

add_action('wp_head','pluginname_ajaxurl');
function pluginname_ajaxurl(){
	?>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	</script>
	<?php
}

add_action( 'init', 'codex_locations_init' );
function codex_locations_init() {
    $labels = array(
        'name'               => _x( 'Locations', 'post type general name', 'your-plugin-textdomain' ),
        'singular_name'      => _x( 'Location', 'post type singular name', 'your-plugin-textdomain' ),
        'menu_name'          => _x( 'Locations', 'admin menu', 'your-plugin-textdomain' ),
        'name_admin_bar'     => _x( 'Location', 'add new on admin bar', 'your-plugin-textdomain' ),
        'add_new'            => _x( 'Add New', 'location', 'your-plugin-textdomain' ),
        'add_new_item'       => __( 'Add New Location', 'your-plugin-textdomain' ),
        'new_item'           => __( 'New Location', 'your-plugin-textdomain' ),
        'edit_item'          => __( 'Edit Location', 'your-plugin-textdomain' ),
        'view_item'          => __( 'View Location', 'your-plugin-textdomain' ),
        'all_items'          => __( 'All Locations', 'your-plugin-textdomain' ),
        'search_items'       => __( 'Search Locations', 'your-plugin-textdomain' ),
        'parent_item_colon'  => __( 'Parent Locations:', 'your-plugin-textdomain' ),
        'not_found'          => __( 'No locations found.', 'your-plugin-textdomain' ),
        'not_found_in_trash' => __( 'No locations found in Trash.', 'your-plugin-textdomain' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => true,
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', /*'editor', 'thumbnail',*/ 'custom-fields')
    );
    register_post_type( 'location', $args );
}
if( function_exists('acf_add_local_field_group') ){
	acf_add_local_field_group(array (
		'key' => 'group_55924f2d60be7',
		'title' => 'Locations',
		'fields' => array (
			array (
				'key' => 'field_55924f3d5a310',
				'label' => 'Map',
				'name' => 'map',
				'type' => 'google_map',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'center_lat' => '',
				'center_lng' => '',
				'zoom' => '',
				'height' => '',
			),
			array (
				'key' => 'field_55924f725a311',
				'label' => 'Tell',
				'name' => 'tell',
				'type' => 'text',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => '',
				'readonly' => 0,
				'disabled' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'location',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
	));
}

add_action( 'save_post', 'post_published_save_address', 10, 2 );
function post_published_save_address($post_id, $post){
	if($post->post_type=='location'){
		$map = get_field('map', $post_id);
		if($map) $address = geocode($map['address'], FALSE, FALSE, 'en');
		if($address) foreach($address as $address_key => $address_val){
			if(!update_post_meta( $post_id, 'address_'.$address_key, $address_val)) add_post_meta( $post_id, 'address_'.$address_key, $address_val, true);
		}
	}
}

function get_location_custom_template(){
	location_filter();
	location_map();
	location_list();
	location_scripts();
}
function location_filter(){
	$radius = array(10, 25, 50, 100, 200, 500);
	$views = array(25, 50, 75, 100);
	?>
	<form class="location_filter" data-autoload="<?php echo (isset($_REQUEST['zip']))?TRUE:FALSE;?>">
        <input type="text" class="zip_code" placeholder="Zip Code here" name="zip" value="<?php echo $_REQUEST['zip'];?>">
        <select name="radius" class="radius">            
            <?php foreach($radius as $radiu):?>
            	<option value="<?php echo $radiu;?>" <?php if($radiu==$_REQUEST['radius'])echo' selected';?>><?php echo $radiu;?> miles</option>
            <?php endforeach;?>
        </select>
        <select name="views">            
            <?php foreach($views as $view):?>
            	<option value="<?php echo $view;?>" <?php if($view==$_REQUEST['views'])echo' selected';?>><?php echo $view;?></option>
            <?php endforeach;?>
        </select>
        <input type="submit" value="Find Locations" class="btn">
    </form>
	<?php
}
function location_map(){
	?>
	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places"></script>
    <script src="<?php echo get_template_directory_uri(); ;?>/js/gmap3.min.js"></script>
	<div class="map location_map" style="height: 300px;"></div>
	<?php
}
function location_list(){
	?>
	<div class="result">
        <h2><span>Results</span></h2>
        <ul class="location_result"><?php location_load_default();?></ul>
    </div>
	<?php
}
function location_scripts(){
	?>
	<script type="text/javascript">
		jQuery(function($){
			function submit_locator_filter(){
				$.post(ajaxurl+'?action=location_find', $('.location_filter').serializeArray(), function(response){
					$('.location_result').html(response.html);					
				}, 'json');
			}
			$('.location_map').gmap3({
	            map:
	            {
	                options:
	                {
	                    center: new google.maps.LatLng(25.7975082,-80.2117969),
	                    zoom: 15,
	                    panControl: false,
	                    panControlOptions: {
	                        position: google.maps.ControlPosition.RIGHT_BOTTOM
	                    },
	                    zoomControl: true,
	                    zoomControlOptions: {
	                        position: google.maps.ControlPosition.RIGHT_BOTTOM
	                    },
	                    scaleControl: false,
	                    scaleControlOptions: {
	                        position: google.maps.ControlPosition.RIGHT_BOTTOM
	                    },
	                    mapTypeControl: false,
	                    streetViewControl: false,
	                    streetViewControlOptions: {
	                        position: google.maps.ControlPosition.RIGHT_BOTTOM
	                    },
	                    scrollwheel: false,
	                }
	            }
        	});
			if($('.location_filter').attr('data-autoload')) submit_locator_filter();
			$('.location_filter').submit(function(){				
				submit_locator_filter();
				return false;
			});
			
		});
	</script>
	<?php
}

add_action( 'wp_ajax_location_find', 'location_load_ajax' );
add_action( 'wp_ajax_nopriv_location_find', 'location_load_ajax' );
function location_load_ajax(){	
	$result = location_load_results($_POST);
	echo json_encode($result);
	wp_die();
}
function location_load_default(){
	$result = location_load_results(array('zip'=>33127,'radius'=>10));
	echo $result['html'];
	
}
function location_load_results($data){
	global $wpdb;
	extract($data);
	$radius = ($radius)?$radius:10;
	$paged = ($paged)?$paged:1;
    $posts_per_page = ($views)?$views:25;
    $limstart = $paged*$posts_per_page-$posts_per_page;
    $latlon = ($zip)?geocode($zip):FALSE;    
    $mapcenter = 'map:{options:{center: new google.maps.LatLng('.$latlon['lat'].','.$latlon['lng'].'), zoom: 10}}';

    $where_sql = $having_by_radius_sql = $order_by_sql = $data_sql = $join_sql = '';

    $order_by_sql = "ORDER BY distance";

    if($radius){
        $having_by_radius_sql = "HAVING distance <= '$radius'";
    }
    $query_sql_count = "
        SELECT  p.*,
                $data_sql
                pm1.meta_value as address_lat,
                pm2.meta_value as address_lng,
                pm3.meta_value as address_full,
                pm4.meta_value as tell,
                (6371*acos(cos(radians('$latlon[lat]'))*cos(radians(pm1.meta_value))*cos(radians(pm2.meta_value) - radians('$latlon[lng]')) + sin(radians('$latlon[lat]'))*sin(radians(pm1.meta_value)))) AS distance
        FROM    $wpdb->posts p
        LEFT JOIN $wpdb->postmeta pm1 ON (
            pm1.post_id = p.ID  AND
            pm1.meta_key    = 'address_lat'
        )
         LEFT JOIN $wpdb->postmeta pm2 ON (
            pm2.post_id = p.ID  AND
            pm2.meta_key    = 'address_lng'
        )
        LEFT JOIN $wpdb->postmeta pm3 ON (
            pm3.post_id = p.ID  AND
            pm3.meta_key    = 'address_formatted_address'
        )
        LEFT JOIN $wpdb->postmeta pm4 ON (
            pm4.post_id = p.ID  AND
            pm4.meta_key    = 'tell'
        )
        $join_sql
        WHERE    post_status = 'publish'
        AND        post_type = 'location'
        $where_sql
        $having_by_radius_sql
        $order_by_sql
    ";
    $rows_count = $wpdb->query($query_sql_count);
    $query_sql = "
        SELECT  p.*,
                $data_sql
                pm1.meta_value as address_lat,
                pm2.meta_value as address_lng,
                pm3.meta_value as address_full,
                pm4.meta_value as tell,
                (6371*acos(cos(radians('$latlon[lat]'))*cos(radians(pm1.meta_value))*cos(radians(pm2.meta_value) - radians('$latlon[lng]')) + sin(radians('$latlon[lat]'))*sin(radians(pm1.meta_value)))) AS distance
        FROM    $wpdb->posts p
        LEFT JOIN $wpdb->postmeta pm1 ON (
            pm1.post_id = p.ID  AND
            pm1.meta_key    = 'address_lat'
        )
        LEFT JOIN $wpdb->postmeta pm2 ON (
            pm2.post_id = p.ID  AND
            pm2.meta_key    = 'address_lng'
        )
        LEFT JOIN $wpdb->postmeta pm3 ON (
            pm3.post_id = p.ID  AND
            pm3.meta_key    = 'address_formatted_address'
        )
        LEFT JOIN $wpdb->postmeta pm4 ON (
            pm4.post_id = p.ID  AND
            pm4.meta_key    = 'tell'
        )
        $join_sql
        WHERE    post_status = 'publish'
        AND        post_type = 'location'
        $where_sql
        $having_by_radius_sql
        $order_by_sql
        LIMIT    $limstart, $posts_per_page
    ";
    $query_getposts = $wpdb->get_results($query_sql);
    $result = array('html'=>'');
    $result['pages'] = ceil($rows_count/$posts_per_page);
    $result['page'] = $paged;
    $result['limstart'] = $limstart;
    $els = $markers = array();
    if ($query_getposts){
        foreach($query_getposts as $query_getpost){
            $post_id = $query_getpost->ID;
            
            $miles = round($query_getpost->distance, 1);
            $marker = '{latLng:['.$query_getpost->address_lat.', '.$query_getpost->address_lng.'], data:"'.$query_getpost->post_title.' | '.$miles.' miles"}';
            $el = '<li class="cfx">
		        <div class="box name">
		            <span class="title">'.$query_getpost->post_title.'</span>
		            <span class="desc">'.$miles.' miles</span>
		        </div>
		        <div class="box adress">
		            <span class="title">Store Address:</span>
		            <span class="desc">'.$query_getpost->address_full.'</span>
		            <a href="tel:'.str_replace(array(' ','-'), '', $query_getpost->tell).'" class="tel">'.$query_getpost->tell.'</a>
		        </div>
		        <div class="box distance">
		            <span class="title">Distance:</span>
		            <span class="desc">'.$miles.' miles</span>
		        </div>
		    </li>';
            array_push($markers, $marker);
            array_push($els, $el);
        }
    }
    else{
        $result['html'] .= '<h2 style="padding:0 0 30px; text-align:center">We\'re sorry, there are no stores near that location.</h2><style>.loading.nextpage {display: none}</style><script>$(".location_map").gmap3("clear", "markers");</script>';
        $result['res'] = 0;
    }
    if(!empty($els)){
    	$clear_markers = ($paged<=1) ? '$(".location_map").gmap3("clear", "markers");' : '';			
    	$result['res'] = 1;
    	$result['html'] .= implode('',$els);
    	$result['html'] .= '
	    <script>
	    	'.$clear_markers.'
	        $(".location_map").gmap3({
	            action: "addMarkers",
	            marker:{
	                values:['.implode(',',$markers).'],
	                options:
	                {
	                    draggable: false,
	                    animation: google.maps.Animation.DROP
	                },
	                events:{
	                    mouseover: function(marker, event, context){
	                        var map = $(this).gmap3("get"),
	                        infowindow = $(this).gmap3({get:{name:"infowindow"}});
	                        if (infowindow){
	                            infowindow.open(map, marker);
	                            infowindow.setContent(context.data);
	                        }
	                        else {
	                            $(this).gmap3({
	                                infowindow:{
	                                    anchor:marker,
	                                    options:{content: context.data}
	                                }
	                            });
	                        }
	                    },
	                    mouseout: function(){
	                        var infowindow = $(this).gmap3({get:{name:"infowindow"}});
	                        if (infowindow){
	                            infowindow.close();
	                        }
	                    }
	                }
	            }
	        });
	        $(".location_map").gmap3({'.$mapcenter.'});
	    </script>';
    }
    	$result['html'] .= '
    	<script>	        
	        $(".location_map").gmap3({'.$mapcenter.'});
	    </script>';
    return $result;
}


/*Location manager setting*/
//add_action('admin_menu', 'location_manager_page');
function location_manager_page(){
	add_submenu_page( 'edit.php?post_type=location', 'Location manager settings', 'Location manager settings', 'manage_options', 'location-manager', 'location_manager_callback');
}
function location_manager_callback() {
	?>
	<div class="wrap"><div id="icon-tools" class="icon32"></div>
		<h2>Location manager settings</h2>	
		<p>Can delete, import and parse location geocode</p>	
		<h3>Choose what to do</h3>
		<form id="location_form_manager">	
			<p><label><input type="radio" name="mode" value="delete"> Delete</label></p>
			<p class="description">Delete all location post and postmeta</p>
			<p><label><input type="radio" name="mode" value="import"> Import</label></p>
			<p class="description">Impost all location with file</p>
			<p><label><input type="radio" name="mode" value="parse"> Parse geocode</label></p>
			<p class="description">Parse geocode in all location and publish it</p>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Start process">
				<input type="hidden" value="0" name="start"/>
				<input type="hidden" value="0" name="max"/>
			</p>
			<p class="description result"></p>
		</form>
	</div>
	<script>
		jQuery(function($){
			function location_form_manager_function(){
				var form = $('#location_form_manager');
				$.post(ajaxurl+'?action=location_manager', form.serializeArray(), function(response){
					if(response.continue){
						form.find('input[name="start"]').val(response.start);
						form.submit();
					}
					form.find('.result').html(response.message);
				},'json');
			}
			$('#location_form_manager').submit(function(){
				location_form_manager_function();
				return false;
			});
					
		});
	</script>
	<?php

}
add_action( 'wp_ajax_location_manager', 'location_manager_controller' );
add_action( 'wp_ajax_nopriv_location_manager', 'location_manager_controller' );
function location_manager_controller(){
	extract($_POST);
	$result = array(
		'message'	=>	'',
		'continue'	=>	FALSE
	);
	if($mode=='delete'){
		$result['continue'] = delete_location();
		$result['message'] = $result['continue'] ? 'Deleting location, wait...' : 'Delete location, ended';
	}
	elseif($mode=='import'){
		$imp_res = import_locations($start);
		$result['continue'] = ($imp_res['max'] > $imp_res['current']) ? TRUE : FALSE;
		$result['start'] = $result['continue'] ? $imp_res['current'] : 0;
		$result['message'] = $result['continue'] ? 'Import location (<strong>'.$imp_res['current'].'</strong>/'.$imp_res['max'].'), wait...' : 'Import location, ended. Find '.($imp_res['max'] - wp_count_posts('location')->draft).' duplicates';
	}
	elseif($mode=='parse'){
		$result['continue'] = parse_latlon();
		$result['message'] = $result['continue'] ? 'Parsing location geocode, wait...' : 'Parsing location geocode, ended';		
	}
	else $result['message'] = 'Choose something!';
	
	echo json_encode($result);
	wp_die();
}

/**
* 
* 
* @return bool
*/
function delete_location(){
    global $wpdb;
    $wpdb->show_errors();
    $allposts = get_posts('numberposts=250&post_type=location&post_status=any' );
    foreach( $allposts as $mypost ) {
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE post_id = %d", $mypost->ID));
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->term_relationships WHERE object_id = %d", $mypost->ID));
        wp_delete_post($mypost->ID, true);
    }
    $count_posts = wp_count_posts('location');
    $count_posts = (array)$count_posts;
    unset($count_posts['auto-draft']);
    $count_posts = array_sum($count_posts);
    return ($count_posts>0)?TRUE:FALSE;
}
/**
* 
* @param int $start
* 
* @return array
*/
function import_locations($start){
	$result = array();
    $get_template_directory = get_template_directory();
    $locationcontents = file($get_template_directory.'/storefinder.csv');
    $i = 0;
    $cust_fields = array(
        'field_55924f725a311'    =>    5,//tell
    );
    $result['max'] = count($locationcontents);
    $result['start'] = $start+250;
    $start = $start+1;
    $end = $start+250;
    foreach($locationcontents as $locationcontent){
        if($start <= $i && $i <= $end){
            $loc = str_getcsv($locationcontent, ';');
            $name = $loc[0];           
            $name = convert_to($name);
            $custom_address = $loc[1].' '.$loc[2].' '.$loc[3].' '.$loc[4];
            $custom_address = convert_to($custom_address);
            
            $get_post = get_posts(array(
              'post_title'		=>	$name,
              'post_type'		=>	'location',             
              'post_status'		=>	'draft',
              'numberposts'		=>	1,   
              'meta_key'		=>	'custom_address_tmp',
			  'meta_value'		=>	$custom_address,          
            ));
            if(!$get_post){
                $my_post = array(
                    'post_type'		=>	'location',
                    'post_title'	=>	$name,
                    'post_content'	=>	'',
                    /*'post_status'   =>    'publish',*/
                    'post_status'   =>    'draft',
                    'post_author'   =>    1,
                );
                $post_id = wp_insert_post($my_post);
                if(is_numeric($post_id)){
                    foreach($cust_fields as $key => $num) update_field( $key, $loc[$num], $post_id);                    
                    
                    if(!update_post_meta( $post_id, 'custom_address_tmp', $custom_address)) add_post_meta( $post_id, 'custom_address_tmp', $custom_address, true);
                    
                }
            }
        }
        $i++;
    }
    $result['current'] = $end;    
    return $result;

}
function parse_latlon(){
	global $wpdb;
    $locations = get_posts(array(
        'post_type'            =>    'location',
        'post_status'        =>    'draft',
        'posts_per_page'    =>    100,
        'offset'            =>    0,
        /*'meta_query' => array(
                array(
                    'key' => 'address_lat',
                    'value' => '',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'address_lng',
                    'value' => '',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'is_latlon',
                    'value' => '0',
                    'compare' => '='
                )
        )*/        
    ));
    $i = $q = $r = 0;
    foreach($locations as $location){  
    	$post_id = $location->ID;    	
    	$i++;        
        $custom_address_tmp = get_post_meta( $post_id, 'custom_address_tmp', true);
        if($custom_address_tmp){        	
			$address = geocode($custom_address_tmp);
			$q++;		
			if($address){
				$r++;
				$map = array(
					'address'	=>	$address['formatted_address'],
					'lat'		=>	$address['lat'],
					'lng'		=>	$address['lng'],
				);
				update_field('field_55924f3d5a310', $map, $post_id);
				if($address) foreach($address as $address_key => $address_val){
					if(!update_post_meta( $post_id, 'address_'.$address_key, $address_val)) add_post_meta( $post_id, 'address_'.$address_key, $address_val, true);
				}
				$wpdb->update(
					$wpdb->posts,
					array( 'post_status' => 'publish' ),
					array( 'ID' => $post_id )
				);				
//				wp_update_post(array('ID'=>$post_id,'post_status'=>'publish'));
			}
			else{
//				wp_update_post(array('ID'=>$post_id,'post_status'=>'badaddress'));
			}
		}
    }
    $count_posts = wp_count_posts('location');
    $count_posts = $count_posts->draft;
    return ($count_posts > 0) ? TRUE : FALSE;
}
/**
* 
* @param string $url
* 
* @return string
*/
function curl_get_content($url) {
	$ch = curl_init();
	$timeout = 5;
	$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
/**
* 
* @param string $address
* @param string $latlng
* @param bool $sensor
* 
* @return array|bool
*/
function geocode($address = FALSE, $latlng = FALSE, $sensor = FALSE, $language = FALSE){
	$key0 = 'AIzaSyB8PokYk_Z1i9VMUCasAi1cuzPRmKvWIvo';
	$key1 = 'AIzaSyBfibM9dd6gl_N-_O7jb13KaMDXwrEKTCM';
	$key2 = 'AIzaSyAG4u9OzP7RK8eEkzuEbXbxF51FEqIT610';
	$key3 = 'AIzaSyCGVyZFyH6PqL0DB0L8a58liucq7bUK-Ho';
	
	$key = $key2;
	$ssl = TRUE;
	
	$str_ssl = ($ssl)?'s':'';
    $address = urlencode($address);
    $latlng = urlencode($latlng);
    $url = "http{$str_ssl}://maps.google.com/maps/api/geocode/json";
    $url .= '?sensor='.(($sensor)?'TRUE':'FALSE');
    if($address) $url .= "&address={$address}";
    if($latlng) $url .= "&latlng={$latlng}";
    if($language) $url .= "&language={$language}";
    if($key && $ssl) $url .= "&key={$key}";
    $resp_json = curl_get_content($url);
    $response = json_decode($resp_json, true);
	$result = array();
    if($response['status']=='OK'){
        $result['lat'] = $response['results'][0]['geometry']['location']['lat'];
        $result['lng'] = $response['results'][0]['geometry']['location']['lng'];
        $result['formatted_address'] = $response['results'][0]['formatted_address'];
        if(isset($response['results'][0]['address_components']) && is_array($response['results'][0]['address_components'])){
        	
        	foreach($response['results'][0]['address_components'] as $address_component){
				if(in_array('administrative_area_level_1', $address_component['types']) && in_array('political', $address_component['types'])){
					$result['state'] = $address_component['long_name'];
				}	
				elseif(in_array('locality', $address_component['types']) && in_array('political', $address_component['types'])){
					$result['city'] = $address_component['long_name'];
				}
				elseif(in_array('country', $address_component['types']) && in_array('political', $address_component['types'])){
					$result['country'] = $address_component['long_name'];
				}
				elseif(in_array('street_number', $address_component['types'])){
					$result['street_number'] = $address_component['long_name'];
				}
				elseif(in_array('route', $address_component['types'])){
					$result['street'] = $address_component['long_name'];
				}
				elseif(in_array('postal_code', $address_component['types'])){
					$result['zip'] = $address_component['long_name'];
				}
			}
		}        
    }
    return (!empty($result))?$result:FALSE;
}
/**
* 
* @param string $source
* @param string $target_encoding
* 
* @return string
*/
function convert_to($source, $target_encoding = 'UTF-8'){
    $encoding = @mb_detect_encoding( $source, "auto" );
    $target = str_replace( "?", "[question_mark]", $source );
    $target = @mb_convert_encoding( $target, $target_encoding, $encoding);
    $target = str_replace( "?", " ", $target );   
    $target = str_replace( "[question_mark]", "?", $target );
    $target = str_replace( "?", " ", $target );    
    $target = wp_strip_all_tags($target);    
    $target = preg_replace('/[\t\n\r\0\x0B]/', '', $target);    
    $target = preg_replace('/([\s])\1+/', ' ', $target);    
    $target = trim($target);    
    return $target;
}
?>