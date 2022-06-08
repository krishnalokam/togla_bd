<?
require_once $w['root_directory']."/excel/Classes/PHPExcel.php";
global $w;

//if(isset($_POST['execute']) && ($_POST['execute']==1)) {
if(isset($_GET['execute']) && ($_GET['execute']==1)) {	
	
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING );
	ignore_user_abort(1);
	require_once $w['root_directory']."/excel/Classes/PHPExcel.php";
	global $w;
	
	set_time_limit(0);
	ini_set("memory_limit","-1");
	
	function prepareColumn($v)
	{	
		if ((strpos($v,"{") !== false) && (strpos($v,"}") !== false )){
			$v = str_replace("{","",$v);
			$v = str_replace("}","",$v);
		}					
		return "'".mysql_real_escape_string($v)."'";
	}
	
	function prepareColumn2($v)
	{		
	  return "'".mysql_real_escape_string($v)."'";
	}
	
	function displaylog($message) {
		//echo "<br/> ************************************************* <br/>";
		//echo $message;
		//echo "<br />";
	}
	
	function uploadImage($img_url) {
		global $w;	
		$ch = curl_init($img_url);
		$tmp_file = explode("/",$img_url);	
		$c = count($tmp_file)-1;
		$file = $tmp_file[$c];
		
		//echo $w['root_directory']."/pictures/profile/".$file;
		$fp = fopen($w['root_directory']."/pictures/profile/".$file,"wb");	
			
		if(file_exists($w['root_directory']."/pictures/profile/".$file)) { 
			//echo "Added File <br />";
		} else { 
			//echo "File Not Found".$img_url."<br />";
		}		
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}	
		
	function resizeImage($base_path,$picname,$newpicname){
		try{ 						
						
			$new_width = 400;
			$new_height = 400; 		
			list($orig_width,$orig_height) = getimagesize( $base_path.$picname);
			
			$ratio_orig = $orig_width/$orig_height;
                if ($new_width/$new_height > $ratio_orig) {
                    $new_width = $new_height*$ratio_orig;
                } else {
                    $new_height = $new_width/$ratio_orig;
                }
			
			$currentImgPath = $base_path.$picname;
			$newImgPath = $base_path.$newpicname;
			$img = new Imagick($currentImgPath);
			$img->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1,true);
			$reWriteImg = $img->writeImage($newImgPath);
			//echo $currentImgPath."<br />".$newImgPath;
			return $newpicname;		
		} catch(Exception $e) {
			//echo "File Not Found   <br />";	
		}
	}
	/************************************ Fetch data from file  *****************************************/	
	echo "process started ";	
	displaylog("Data being fetched from file ");
	try {	
			$base_path = $w['root_directory']."/excel/";
			$web_filename = "http://mrmoonlight.com/providerimages/fieldsforbrilliant1.xlsx";
			$local_filename=$base_path."fieldsforbrilliant1.xls";
			//file_put_contents($local_filename, file_get_contents($web_filename));
	
			$reader= PHPExcel_IOFactory::load($local_filename);
			$d=$reader->getSheet('0')->toArray();
			$tot_rows =  count($d)-1;	
			$columns =  implode(",",$d[0]);
	
			$data_rows=array();
			//echo "Data being inserted for the ".$tot_rows." rows <br />";
			$rs = mysql($w['database'], "SELECT iucount  FROM `imported_users_count`;");			
			$row = mysql_fetch_assoc($rs);
			//print_r($row );			 			
			$start_index = $row['iucount']+1;
			
			mysql($w['database'], "truncate table imported_users ;");
			for($i=$start_index; $i <= $tot_rows; $i++){
				$escaped_values  = array_map('prepareColumn2', array_values($d[$i]));		
				$insert_data = implode(",",$escaped_values);		
				$data_rows[]="(".$insert_data.")";
				if($i%100==0){
					 mysql($w['database'], "insert into imported_users ($columns) Values ".implode(",",$data_rows));
					 $data_rows = array();
					//echo "Data inserted successfully for  ".$i." rows <br />";
				}		
			}
			if(sizeof($data_rows)>0) mysql($w['database'], "insert into imported_users ($columns) Values ".implode(",",$data_rows));
			//echo "Data inserted successfully for  ".$tot_rows." rows <br />";	
		}catch(Exception $e) {
			//echo $e;
			//print_r($e);
	}
	
		mysql($w['database'],'update imported_users_count set iucount='.$tot_rows);
	die();
	/*************************************** Data merging **************************************************/
	
	displaylog("Data being merged  <br />");
	try {
		
		$profession_id 		= 	1;
		$active				=	2;				
		$type				=	'Individual';
		$subscription_id 	= 	3;				
		$password 			= 	"abcd@1234";
		
		$columns = "provider_id,first_name,last_name,email,phone_number,address1,address2,city,zip_code,state_code,affiliation,about_me,subscription_id,password,active,signup_date,
		position,profession_id,listing_type,lat,lon,imported_img_url,gender,accepting_new_patients,age_groups_seen,location_name,fax,commercial_entity_name,
		board_certifications,medical_school,languages,insurances,provider_network";
		
		$photo_columns="user_id,original,type,date_added,resized";
		
		$provider_ids = array();
		$udrs = mysql($w['database'],"select * from users_data");
		
		while($ud_row 	= mysql_fetch_assoc($udrs)){
			 $provider_ids[] = $ud_row['provider_id']; 
		}	
		
		//print_r($provider_ids);
		$iurs = mysql($w['database'],"select * from imported_users ;");	
			
		$data_rows = array();
		$update_rows= array();
		
		$i=0;	
		while($row = mysql_fetch_assoc($iurs)){	
		
			if(!in_array($row['provider_id'],$provider_ids)){
				
				echo "inserting";
							
				$email = $row['provider_id']."@testmail.com";
							
				$data_row = array( $row['provider_id'],$row['first_name']." ".$row['middle_name'],$row['last_name'],$email,$row['phone'],$row['address1'],
								$row['address2']." ".$row['suite'],$row['city'],$row['zip'],$row['state'],$row['hospital_affiliations'],$row['professional_statement'],
								$subscription_id,$password,$active,$row['create_date'],$row['degrees'],$profession_id,$type,$row['lat'],$row['lon'],$row['image_url'],$row['gender'],
								$row['accepting_new_patients'],$row['age_groups_seen'],$row['location_name'],$row['fax'],$row['commercial_entity_name'],
								$row['board_certifications'],$row['medical_school'],$row['languages'],$row['insurances'],$row['provider_network']
							  );						
				
				$escaped_values  = array_map('prepareColumn', $data_row);		
				$insert_data = implode(",",$escaped_values);			
				$data_rows[] = 	"(".$insert_data.")";
				
				if(++$i%100==0){															
					mysql($w['database'], "insert into users_data ($columns) Values ".implode(",",$data_rows))." ON DUPLICATE KEY UPDATE ";				
					$data_rows = array();				
					//echo "Data merged successfully for  ".$i." rows <br />";					
				}	
				
			} else {
				// update users_data
					if( false ) {						
						$update_sql = "update users_data set ".
									"provider_id= ".$row['provider_id'].",".
									"first_name= ".$row['first_name']." ".$row['middle_name'].",".
									"last_name= ".$row['last_name'].",".
									"email= ".$email.",".
									"phone_number= ".$row['phone'].",".
									"address1= ".$row['address1'].",".
									"address2= ".$row['address2']." ".$row['suite'].",".
									"city= ".$row['city'].",".
									"zip_code= ".$row['zip'].",".
									"state_code= ".$row['state'].",".
									"affiliation= ".$row['hospital_affiliations'].",".
									"about_me= ".$row['professional_statement'].",".
									"subscription_id= ".$subscription_id.",".
									"password= ".$password.",".
									"active= ".$active.",".
									"signup_date= ".$row['create_date'].",".
									"position= ".$row['degrees'].",".
									"profession_id= ".$profession_id.",".
									"listing_type= ".$type.",".
									"lat= ".$row['lat'].",".
									"lon= ".$row['lon'].",".
									"imported_img_url= ".$row['image_url'].",".
									"gender= ".$row['gender'].",".
									"accepting_new_patients= ".$row['accepting_new_patients'].",".
									"age_groups_seen= ".$row['age_groups_seen'].",".
									"location_name= ".$row['location_name'].",".
									"fax= ".$row['fax'].",".
									"commercial_entity_name= ".$row['commercial_entity_name'].",".
									"board_certifications= ".$row['board_certifications'].",".
									"medical_school= ".$row['medical_school'].",".
									"languages= ".$row['languages'].",".
									"insurances= ".$row['insurances'].",".
									"provider_network= ".$row['provider_network']
									." where provider_id=".$provider_ids[$row['provider_id']].";";
									
									echo $update_sql;
									die();
					}
						
			}						 									
		}		
	
		if(sizeof($data_rows)>0) mysql($w['database'], "insert into users_data ($columns) Values ".implode(",",$data_rows));		
		//echo "Data merged successful for ".($i)." rows! <br />";
		
	} catch(Exception $e) {
		//echo $e;
		//print_r($e);	
	}
	/******************************     file name update      *****************************************************/
		
	//sleep(1);
	displaylog("Updating the slag ");
		
		$udrs2 = mysql($w['database'],"select * from users_data where user_id not in (1,2)");
		mysqli_data_seek($udrs2,0);
		$i = 0;
		while($row = mysql_fetch_assoc($udrs2)) {			
			createFilename($row['user_id'],'profile',$row['user_id'],'update',$w);					
			//if(++$i % 100 == 0) //echo " ".($i)." rows processed successfully <br />";
		}
		mysql($w['database'],"update users_data set token=md5(filename)");	
	
	/******************************************** list services ***********************************************************/
	
	//sleep(1);
		displaylog("Updating services");
	
		$lsrs = mysql($w['database'],"select provider_id,specialties from imported_users");
		$rows = array();
		while($row = mysqli_fetch_assoc($lsrs)) { 
				$v=$row['specialties'];
				if ((strpos($v,"{") !== false) && (strpos($v,"}") !== false )){
					$v = str_replace("{","",$v);
					$v = str_replace("}","",$v);
				}
				$services = explode(",",$v);			
				$rows = array_merge($rows,$services);
		}
		$unique_services = array_unique($rows);
		$columns = "name,profession_id,filename,revision_timestamp";
		//echo count($unique_services)." services available <br />";
		$i=0;
		$data_rows = array();
		foreach($unique_services as $service) {
			$filename = texttourl($service);
			$data_rows[] = "(".prepareColumn($service).",".prepareColumn(1).",".prepareColumn($filename).",".CURRENT_TIMESTAMP.")";
			if( ++$i%100==0) {
				//echo "inserting 100 records";
				//echo "insert into list_services ($columns) values ".implode(',',$data_rows);		
				mysql($w['database']," insert into list_services ($columns) values ".implode(',',$data_rows));
				//echo $i." services are inserted successfully <br />";
				$data_rows = array();		
			}		
		}
			if(sizeof($data_rows) > 0) mysql($w['database']," insert into list_services ($columns) values".implode(',',$data_rows));
			//echo $i." services are inserted successfully <br />";	
	
	/******************************************** rel services ***********************************************************/	
	
	//sleep(1);
	displaylog("Updating relation among services");
		$lsrs = mysql($w['database'],"select service_id,name from list_services ");
		$i = 0;
		$list_services = array();
		while($row = mysql_fetch_assoc($lsrs)) {	
			$list_services[$row['name']] = $row['service_id'];	
		}
	//echo "There are ".count($list_services)." uniques services in the system <br />";
	
	$relrs = mysql($w['database'],"SELECT ud.user_id,iu.provider_id,iu.specialties FROM `users_data` ud left join imported_users iu on ud.provider_id = iu.provider_id 
									where user_id not in (1,2) order by provider_id");								
	$i = 0;							
	$columns = "user_id,service_id,date";
	$data_rows	= array();
	while($row = mysql_fetch_assoc($relrs)) {	
				$v=$row['specialties'];
				if ((strpos($v,"{") !== false) && (strpos($v,"}") !== false )){
					$v = str_replace("{","",$v);
					$v = str_replace("}","",$v);
				}
				$services = explode(",",$v);
				foreach($services as $service) {
					if(array_key_exists($service,$list_services)) {
						$data_rows[] = "(".$row['user_id'].",".$list_services[$service].","."date_Format(CURRENT_TIMESTAMP,'%Y%m%d%H%i%s')".")";					
						if(++$i%100==0) {		
								
							mysql($w['database']," insert into rel_services ($columns) values ".implode(',',$data_rows));
							$data_rows = array();
							//echo "data inserted successfully into rel_services for ".$i." rows";
						}					
					}				
				}
	}
		if(sizeof($data_rows) > 0) {
			 mysql($w['database']," insert into rel_services ($columns) values ".implode(',',$data_rows));
			//echo "data inserted successfully into rel_services for ".$i." rows";
		}
	
	
	/************************************ Process Images ****************************************************************/
	
	//sleep(1);
	displaylog("Processing the images");
	$udrs2 = mysql($w['database'],"select distinct imported_img_url from users_data where user_id not in (1,2);");
	mysqli_data_seek($udrs2,0);
	while($row = mysql_fetch_assoc($udrs2)) {		
		$img_url = $row['imported_img_url'];
		uploadImage($img_url);
	}
		//echo "Processed the images successfully <br /> ";
	
	/********************************** users_photos ************************************************/
	
	//sleep(1);
	displaylog("Updating users_photos");
	
	$udrs2 = mysql($w['database'],"select `user_id`,`imported_img_url` from users_data where user_id not in (1,2)");
	mysqli_data_seek($udrs2,0);
	$columns="user_id,file,original,type,date_added,resized";
	$type='photo';
	$resized=1;
	$i=0;
	$data_rows = array();
		while($row = mysql_fetch_assoc($udrs2)){			
			$base_path = $w['root_directory']."/pictures/profile/";
			$picname= end(explode("/",$row['imported_img_url']));		
			$newpicname = "pimage-".$row['user_id'].".jpg";
			$newpicname  = resizeImage($base_path,$picname,$newpicname);	
			$data_rows[] = "(".prepareColumn($row['user_id']).",".prepareColumn($newpicname).",".prepareColumn($row['imported_img_url']).",".prepareColumn($type).","."date_Format(CURRENT_TIMESTAMP,'%Y%m%d%H%i%s')".",".prepareColumn($resized).")";
			if(++$i%100 == 0) {			
					//echo "insert into users_photo ($columns) values ".implode(',',$data_rows)."<br />";
					mysql($w['database'],"insert into users_photo ($columns) values". implode(',',$data_rows));
					//echo "Data inserted successfully for ".$i." rows "."<br />";
					$data_rows = array();				
			}
		}
		if(sizeof($data_rows)>0)  {
			mysql($w['database'],"insert into users_photo ($columns) values ". implode(',',$data_rows));
			//echo "Data inserted successfully for ".$i." rows "."<br />";	
		}		
	/********************************** users_photos ************************************************/	
	


	echo "process finished ";
} else {
	echo "Data import not started, you should pass execute=1 as query param for data import to start";	
}
?>