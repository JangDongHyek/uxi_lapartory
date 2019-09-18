<? include_once "../../common.php"; ?>
<? include_once "../../inc/admin_check.php"; ?>
<? include_once "../../inc/site_info.php"; ?>
<? include_once "../../inc/oper_info.php"; ?>
<? include_once "../../inc/prd_info.php"; ?>
<?
// 페이지 파라메터 (검색조건이 변하지 않도록)
//--------------------------------------------------------------------------------------------------
$param = "page=$page&dep_code=$dep_code&dep2_code=$dep2_code&dep3_code=$dep3_code";
$param .= "&special=$special&display=$display&searchopt=$searchopt&searchkey=$searchkey&stock_opt=$stock_opt&page=$page&shortpage=$shortpage";
if(!empty($prdcode)) $param .= "&prdcode=$prdcode";
//--------------------------------------------------------------------------------------------------

$prdimg_path = "../../data/prdimg";
$prdicon_path = "../../data/prdicon";
//////////////////////////////////////////////////////////////////////////////////////////
// 상품등록
//////////////////////////////////////////////////////////////////////////////////////////
if($mode == "insert"){

	// 상품넘버 만들기
	$sql = "select max(prdcode) as prdcode, max(prior) as prior from wiz_product";
	$result = mysql_query($sql) or error(mysql_error());
	if($row = mysql_fetch_object($result)){

		$datenum = substr($row->prdcode,0,6);
		$prdnum = substr($row->prdcode,6,4);
		$prdnum = substr("000".(++$prdnum),-4);

		if($datenum == date('ymd')) $prdcode = $datenum.$prdnum;
		else $prdcode = date('ymd')."0001";

		// 상품진열 순서
 		// $prior = $row->prior + 1;
		$prior = 100;

	}else{
		$prdcode = date('ymd')."0001";

		// 상품진열 순서
		// $prior = date(ymdHis);
		$prior = 100;

	}

	// 상품아이콘
	for($ii=0; $ii<count($prdicon); $ii++){
		$prdicon_list .= $prdicon[$ii]."/";
	}

	// 상품이미지 저장
	include "./prd_imgup.inc";

	$prdname = str_replace("'","′",$prdname);
	$content = str_replace("'","′",$content);
	$content_m = str_replace("'","′",$content_m);

	// 상품정보 저장
	$sql = "insert into wiz_product
					(prdcode,prdname,prdcom,origin,showset,stock,savestock,prior,viewcnt,deimgcnt,basketcnt,ordercnt,cancelcnt,
					comcnt,sellprice,conprice,strprice,new,best,popular,recom,sale,shortage,coupon_use,coupon_dis,coupon_type,
					coupon_amount,coupon_limit,coupon_sdate,coupon_edate,delivery_type,delivery_price,prdicon,prefer,brand,info_use,info_name1,info_value1,
					info_name2,info_value2,info_name3,info_value3,info_name4,info_value4,info_name5,info_value5,
					info_name6,info_value6,
					select_subjects, supply_subjects,
					prdimg_R,prdimg_L1,prdimg_M1,prdimg_S1,prdimg_L2,prdimg_M2,prdimg_S2,prdimg_L3,prdimg_M3,prdimg_S3,
					prdimg_L4,prdimg_M4,prdimg_S4,prdimg_L5,prdimg_M5,prdimg_S5,searchkey,stortexp,content,content_m,wdate,mdate)
					values('$prdcode','$prdname','$prdcom','$origin','$showset','$stock','$savestock','$prior','$viewcnt',
					'$deimgcnt','$basketcnt','$ordercnt','$cancelcnt','$comcnt','$sellprice','$conprice','$strprice','$new',
					'$best','$popular','$recom','$sale','$shortage','$coupon_use','$coupon_dis','$coupon_type','$coupon_amount',
					'$coupon_limit','$coupon_sdate','$coupon_edate','$delivery_type','$delivery_price','$prdicon_list','$prefer','$brand','$info_use',
					'$info_name1','$info_value1','$info_name2','$info_value2','$info_name3','$info_value3',
					'$info_name4','$info_value4','$info_name5','$info_value5','$info_name6','$info_value6',
					'$select_subjects', '$supply_subjects',
					'$prdimg_R_name','$prdimg_L1_name','$prdimg_M1_name','$prdimg_S1_name',
					'$prdimg_L2_name','$prdimg_M2_name','$prdimg_S2_name','$prdimg_L3_name','$prdimg_M3_name','$prdimg_S3_name',
					'$prdimg_L4_name','$prdimg_M4_name','$prdimg_S4_name','$prdimg_L5_name','$prdimg_M5_name','$prdimg_S5_name',
					'$searchkey','$stortexp','$content','$content_m',now(),now())";


	mysql_query($sql) or die(mysql_error());

	include "./prd_option_save.php";

	// 카테고리정보 저장
	if(!empty($class03)){
	  $catcode = $class03;
	}else{
	  if(!empty($class02)) $catcode = $class02."000";
	  else {
 			if(empty($class01)) $class01 = "000";
  		$catcode = $class01."000000";
    }
	}
	$sql = "insert into wiz_cprelation(idx,prdcode,catcode) values('', '$prdcode', '$catcode')";
	$result = mysql_query($sql) or error(mysql_error());

	complete("상품이 입력되었습니다.","prd_input.php?mode=update&prdcode=$prdcode&$param");

//////////////////////////////////////////////////////////////////////////////////////////
// 상품수정
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "update"){

	// 상품이미지 저장
	include "./prd_imgup.inc";

	$prdname = str_replace("'","′",$prdname);

	// 상품아이콘
	for($ii=0; $ii<count($prdicon); $ii++){
		$prdicon_list .= $prdicon[$ii]."/";
	}

	// 상품이미지 삭제
	for($ii=0; $ii<count($delimg); $ii++){
		if($delimg[$ii] != "") @unlink($prdimg_path."/".$delimg[$ii]);
	}

	//echo $optcode3;
	//exit;

	// 상품정보 저장
	$sql = "update wiz_product set
	            prdcode='$prdcode',prdname='$prdname',prdcom='$prdcom',origin='$origin',showset='$showset',shortage='$shortage',
	            coupon_use='$coupon_use',coupon_dis='$coupon_dis',coupon_type='$coupon_type',coupon_amount='$coupon_amount',coupon_limit='$coupon_limit',coupon_sdate='$coupon_sdate',coupon_edate='$coupon_edate',
	            delivery_type='$delivery_type',delivery_price='$delivery_price',prdicon='$prdicon_list', prefer='$prefer',brand='$brand',stock='$stock',prior='$prior',
	            sellprice='$sellprice', conprice='$conprice', strprice='$strprice', new='$new', best='$best', popular='$popular', recom='$recom', sale='$sale',
	            info_use='$info_use',info_name1='$info_name1',info_value1='$info_value1',info_name2='$info_name2',info_value2='$info_value2',
	            info_name3='$info_name3',info_value3='$info_value3',info_name4='$info_name4',info_value4='$info_value4',
	            info_name5='$info_name5',info_value5='$info_value5',info_name6='$info_name6',info_value6='$info_value6',
							select_subjects='$select_subjects', supply_subjects='$supply_subjects',
							prdimg_R='$prdimg_R_name',prdimg_L1='$prdimg_L1_name', prdimg_M1='$prdimg_M1_name', prdimg_S1='$prdimg_S1_name', prdimg_L2='$prdimg_L2_name', prdimg_M2='$prdimg_M2_name', prdimg_S2='$prdimg_S2_name',
							prdimg_L3='$prdimg_L3_name', prdimg_M3='$prdimg_M3_name', prdimg_S3='$prdimg_S3_name', prdimg_L4='$prdimg_L4_name', prdimg_M4='$prdimg_M4_name', prdimg_S4='$prdimg_S4_name', prdimg_L5='$prdimg_L5_name', prdimg_M5='$prdimg_M5_name', prdimg_S5='$prdimg_S5_name',
							searchkey='$searchkey',stortexp='$stortexp',content='$content',content_m='$content_m',mdate=now() where prdcode = '$prdcode'";

	$result = mysql_query($sql) or error(mysql_error());

	include "./prd_option_save.php";

	// 카테고리 정보 저장
	if(!empty($class03)){
	  $catcode = $class03;
	}else{
	  if(!empty($class02)) $catcode = $class02."000";
	  else {
   		if(empty($class01)) $class01 = "000";
      $catcode = $class01."000000";
    }
	}

	$sql = "update wiz_cprelation set catcode = '$catcode' where prdcode = '$prdcode' and idx = '$relidx'";
	$result = mysql_query($sql) or error(mysql_error());

	complete("상품정보가 수정되었습니다.","prd_input.php?mode=update&prdcode=$prdcode&$param");

//////////////////////////////////////////////////////////////////////////////////////////
// 선택수정
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "change"){
	$array_selprdcode = explode("|",$selprdcode);
	$array_selprior = explode("|",$selprior);
	$i = 0;
	while($array_selprdcode[$i]){
		$tmp_prdcode = $array_selprdcode[$i];
		$tmp_prior = $array_selprior[$i];

		$sql = "Update wiz_product Set prior = '$tmp_prior' Where prdcode = '$tmp_prdcode'";
		$result = mysql_query($sql) or error(mysql_error());
		$i++;
	}

	complete("선택한 상품을 수정하였습니다.","prd_list.php?page=$page&$param");

//////////////////////////////////////////////////////////////////////////////////////////
// 상품삭제
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "delete"){

	if($prdcode){

		// 카테고리 연관 삭제
		$sql = "delete from wiz_cprelation where prdcode = '$prdcode'";
		$result = mysql_query($sql) or error(mysql_error());

		// 관련련상품 연관 삭제
		$sql = "delete from wiz_prdrelation where prdcode = '$prdcode' || relcode = '$prdcode'";
		$result = mysql_query($sql) or error(mysql_error());

		// 상품데이타 삭제
		foreach (glob($prdimg_path."/".$prdcode."*") as $filename) {
   		@unlink($filename);
		}

		// 상품평 삭제
		$sql = "delete from wiz_comment where prdcode = '$prdcode'";
		mysql_query($sql) or error(mysql_error());

		$sql = "delete from wiz_product where prdcode = '$prdcode'";
		$result = mysql_query($sql) or error(mysql_error());

	}else{

		$array_selected = explode("|",$selected);
		$i=0;
		while($array_selected[$i]){

			$tmp_prdcode = $array_selected[$i];

			// 카테고리 연관 삭제
			$sql = "delete from wiz_cprelation where prdcode = '$tmp_prdcode'";
			mysql_query($sql) or error(mysql_error());

			// 관련상품 연관 삭제
			$sql = "delete from wiz_prdrelation where prdcode = '$tmp_prdcode' || relcode = '$tmp_prdcode'";
			mysql_query($sql) or error(mysql_error());

			//상품데이타 삭제
			foreach (glob($prdimg_path."/".$tmp_prdcode."*") as $filename) {
	   		@unlink($filename);

			}

			// 상품평 삭제
			$sql = "delete from wiz_comment where prdcode = '$tmp_prdcode'";
			mysql_query($sql) or error(mysql_error());

			$sql = "delete from wiz_product where prdcode = '$tmp_prdcode'";
			$result = mysql_query($sql) or error(mysql_error());

			$i++;
		}

	}

	complete("선택한 상품을 삭제하였습니다.","prd_list.php?page=$page&$param");

//////////////////////////////////////////////////////////////////////////////////////////
// 상품복사
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "prdcopy"){
	// 기존상품 정보
	$sql = "select * from wiz_product where prdcode='$prdcode'";
	$result = mysql_query($sql) or error(mysql_error());
	$prd_info = mysql_fetch_object($result);

	// 상품넘버 만들기
	$sql = "select max(prdcode) as prdcode, max(prior) as prior from wiz_product";
	$result = mysql_query($sql) or error(mysql_error());
	if($row = mysql_fetch_object($result)){

		$datenum = substr($row->prdcode,0,6);
		$prdnum = substr($row->prdcode,6,4);
		$prdnum = substr("000".(++$prdnum),-4);

		if($datenum == date('ymd')) $prdcode = $datenum.$prdnum;
		else $prdcode = date('ymd')."0001";

		// 상품진열 순서
 		// $prior = $row->prior + 1;
		$prior = 100;

	}else{
		$prdcode = date('ymd')."0001";

		// 상품진열 순서
		// $prior = date(ymdHis);
		$prior = 100;

	}

	// 상품이미지
	$prdimg_path = "../../data/prdimg";
	$prdimg_R_name = $prdcode."_R.".substr($prd_info->prdimg_R,-3);
	$prdimg_L1_name = $prdcode."_L1.".substr($prd_info->prdimg_L1,-3);
	$prdimg_M1_name = $prdcode."_M1.".substr($prd_info->prdimg_M1,-3);
	$prdimg_S1_name = $prdcode."_S1.".substr($prd_info->prdimg_S1,-3);

	if(@file($prdimg_path."/".$prd_info->prdimg_R)) copy($prdimg_path."/".$prd_info->prdimg_R, $prdimg_path."/".$prdimg_R_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_L1)) copy($prdimg_path."/".$prd_info->prdimg_L1, $prdimg_path."/".$prdimg_L1_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_M1)) copy($prdimg_path."/".$prd_info->prdimg_M1, $prdimg_path."/".$prdimg_M1_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_S1)) copy($prdimg_path."/".$prd_info->prdimg_S1, $prdimg_path."/".$prdimg_S1_name);

	$prdimg_L2_name = $prdcode."_L2.".substr($prd_info->prdimg_L2,-3);
	$prdimg_M2_name = $prdcode."_M2.".substr($prd_info->prdimg_M2,-3);
	$prdimg_S2_name = $prdcode."_S2.".substr($prd_info->prdimg_S2,-3);

  if(@file($prdimg_path."/".$prd_info->prdimg_L2)) copy($prdimg_path."/".$prd_info->prdimg_L2, $prdimg_path."/".$prdimg_L2_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_M2)) copy($prdimg_path."/".$prd_info->prdimg_M2, $prdimg_path."/".$prdimg_M2_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_S2)) copy($prdimg_path."/".$prd_info->prdimg_S2, $prdimg_path."/".$prdimg_S2_name);


  $prdimg_L3_name = $prdcode."_L3.".substr($prd_info->prdimg_L3,-3);
	$prdimg_M3_name = $prdcode."_M3.".substr($prd_info->prdimg_M3,-3);
	$prdimg_S3_name = $prdcode."_S3.".substr($prd_info->prdimg_S3,-3);

  if(@file($prdimg_path."/".$prd_info->prdimg_L3)) copy($prdimg_path."/".$prd_info->prdimg_L3, $prdimg_path."/".$prdimg_L3_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_M3)) copy($prdimg_path."/".$prd_info->prdimg_M3, $prdimg_path."/".$prdimg_M3_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_S3)) copy($prdimg_path."/".$prd_info->prdimg_S3, $prdimg_path."/".$prdimg_S3_name);

  $prdimg_L4_name = $prdcode."_L4.".substr($prd_info->prdimg_L4,-3);
	$prdimg_M4_name = $prdcode."_M4.".substr($prd_info->prdimg_M4,-3);
	$prdimg_S4_name = $prdcode."_S4.".substr($prd_info->prdimg_S4,-3);

  if(@file($prdimg_path."/".$prd_info->prdimg_L4)) copy($prdimg_path."/".$prd_info->prdimg_L4, $prdimg_path."/".$prdimg_L4_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_M4)) copy($prdimg_path."/".$prd_info->prdimg_M4, $prdimg_path."/".$prdimg_M4_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_S4)) copy($prdimg_path."/".$prd_info->prdimg_S4, $prdimg_path."/".$prdimg_S4_name);

  $prdimg_L5_name = $prdcode."_L5.".substr($prd_info->prdimg_L5,-3);
	$prdimg_M5_name = $prdcode."_M5.".substr($prd_info->prdimg_M5,-3);
	$prdimg_S5_name = $prdcode."_S5.".substr($prd_info->prdimg_S5,-3);

  if(@file($prdimg_path."/".$prd_info->prdimg_L5)) copy($prdimg_path."/".$prd_info->prdimg_L5, $prdimg_path."/".$prdimg_L5_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_M5)) copy($prdimg_path."/".$prd_info->prdimg_M5, $prdimg_path."/".$prdimg_M5_name);
  if(@file($prdimg_path."/".$prd_info->prdimg_S5)) copy($prdimg_path."/".$prd_info->prdimg_S5, $prdimg_path."/".$prdimg_S5_name);

  $prd_info->content = addslashes($prd_info->content);
	$prd_info->content_m = addslashes($prd_info->content_m);

  $prd_info->prior = $prior;

	// 상품정보 저장
	$sql = "insert into wiz_product(
					prdcode,prdname,prdcom,origin,showset,stock,savestock,prior,viewcnt,deimgcnt,basketcnt,ordercnt,cancelcnt,comcnt,
					sellprice,conprice,strprice,new,best,popular,recom,sale,shortage,delivery_type,delivery_price,prdicon,prefer,brand,
					info_use,info_name1,info_value1,info_name2,info_value2,info_name3,info_value3,info_name4,info_value4,info_name5,info_value5,info_name6,info_value6,
					select_subjects, supply_subjects,
					prdimg_R,prdimg_L1,prdimg_M1,prdimg_S1,prdimg_L2,prdimg_M2,prdimg_S2,prdimg_L3,prdimg_M3,prdimg_S3,
					prdimg_L4,prdimg_M4,prdimg_S4,prdimg_L5,prdimg_M5,prdimg_S5,searchkey,stortexp,content,content_m,wdate,mdate
					)
					values(
					'$prdcode','$prd_info->prdname','$prd_info->prdcom','$prd_info->origin','$prd_info->showset','$prd_info->stock','$prd_info->savestock','$prd_info->prior','0','0', '0', '0', '0', '0',
					'$prd_info->sellprice','$prd_info->conprice','$prd_info->strprice','$prd_info->new','$prd_info->best','$prd_info->popular','$prd_info->recom','$prd_info->sale','$prd_info->shortage',
					'$prd_info->delivery_type','$prd_info->delivery_price','$prd_info->prdicon_list','$prd_info->prefer','$prd_info->brand',
					'$prd_info->info_use','$prd_info->info_name1','$prd_info->info_value1','$prd_info->info_name2','$prd_info->info_value2','$prd_info->info_name3','$prd_info->info_value3','$prd_info->info_name4','$prd_info->info_value4','$prd_info->info_name5','$prd_info->info_value5','$prd_info->info_name6','$prd_info->info_value6',
					'$prd_info->select_subjects', '$prd_info->supply_subjects',
					'$prdimg_R_name', '$prdimg_L1_name','$prdimg_M1_name','$prdimg_S1_name','$prdimg_L2_name','$prdimg_M2_name','$prdimg_S2_name',
					'$prdimg_L3_name','$prdimg_M3_name','$prdimg_S3_name','$prdimg_L4_name','$prdimg_M4_name','$prdimg_S4_name','$prdimg_L5_name','$prdimg_M5_name','$prdimg_S5_name',
					'$prd_info->searchkey','$prd_info->stortexp','$prd_info->content','$prd_info->content_m',now(),now())";

	mysql_query($sql) or error(mysql_error());

	// 옵션 정보 저장
	$sql = "select * from wiz_product_option where prdcode='$prd_info->prdcode' and type='select'";
	$result = mysql_query($sql) or error(mysql_error());
	$uniq_select = uniqid();
	$uniq_supply = uniqid();
	while($row = mysql_fetch_array($result)){
		switch($row['type']){
			case "select": $uniq = $uniq_select; break;
			case "supply": $uniq = $uniq_supply; break;
		}
		$idx = $uniq.substr($row['idx'], 13, 3);
		$sql = "insert into wiz_product_option (idx, prdcode, type, name, price, stock, enabled) values ('{$idx}', '{$prdcode}', '{$row['type']}', '{$row['name']}', '{$row['price']}', '{$row['stock']}', {$row['enabled']})";
		mysql_query($sql) or error(mysql_error());
	}

	// 카테고리정보 저장
	$sql = "select * from wiz_cprelation where prdcode='$prd_info->prdcode'";
	$result = mysql_query($sql) or error(mysql_error());
	while($row = mysql_fetch_object($result)){

		$sql = "insert into wiz_cprelation(idx,prdcode,catcode) values('', '$prdcode', '$row->catcode')";
		mysql_query($sql) or error(mysql_error());

	}

	complete("복사되었습니다.","prd_list.php?$param");


//////////////////////////////////////////////////////////////////////////////////////////
// 상품평 삭제
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "delesti"){

	// 1개 상품평 삭제
	if($estiidx){

		$sql = "delete from wiz_bbs where idx = '$estiidx'";
		$result = mysql_query($sql) or error(mysql_error());

	// 선택 상품평 삭제
	}else{

		$array_selected = explode("|",$selected);
		$i=0;
		while($array_selected[$i]){

			$tmp_estiidx = $array_selected[$i];

			$sql = "delete from wiz_bbs where idx = '$tmp_estiidx'";
			$result = mysql_query($sql) or error(mysql_error());

			$i++;
		}

	}

	complete("선택한 상품평을 삭제하였습니다.","prd_estimate.php?page=$page");

//////////////////////////////////////////////////////////////////////////////////////////
// 재고관리
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "stock"){

	$sql = "update wiz_product set stock='$stock', savestock='$savestock' where prdcode='$prdcode'";
	$result = mysql_query($sql) or error(mysql_error());

	complete("선택한 상품재고를 수정하였습니다.","prd_shortage.php?$param");

// 분류추가
}else if($mode == "catlist"){
	if($submode == "insert"){
		if(!empty($class03)){
			$catcode = $class03;
		}
		else{
			if(!empty($class02)) $catcode = $class02."000";
			else {
				if(empty($class01)) $class01 = "000";
				$catcode = $class01."000000";
			}
		}
		$sql = "select * from wiz_cprelation where prdcode = '$prdcode' and catcode = '$catcode'";
		$result = mysql_query($sql) or error(mysql_error());

		if($row = mysql_fetch_object($result)){
			error('이미등록된 분류입니다.');
		}
		else{
			$sql = "select * from wiz_cprelation where prdcode='$prdcode'";
			$result = mysql_query($sql) or error(mysql_error());
			$row = mysql_fetch_object($result);
			$catcode_total = mysql_num_rows($result);

			if($catcode_total == "1" && $row->catcode == "000000000"){
				$sql = "update wiz_cprelation set catcode='$catcode' where prdcode='$prdcode'";
				$result = mysql_query($sql) or error(mysql_error());
			}
			else{
				$sql = "insert into wiz_cprelation(idx,prdcode,catcode) values('', '$prdcode', '$catcode')";
				$result = mysql_query($sql) or error(mysql_error());
			}
			complete('분류를 추가하였습니다.','prd_catlist.php?prdcode='.$prdcode);
		}
	}
	else if($submode == "delete"){
		$sql = "select prdcode from wiz_cprelation where prdcode = '$prdcode'";
		$result = mysql_query($sql) or error(mysql_error());
		$catcode_total = mysql_num_rows($result);

		if($catcode_total == "1"){
			$sql = "update wiz_cprelation set catcode='000000000' where prdcode='$prdcode'";
			$result = mysql_query($sql) or error(mysql_error());
		}
		else{
			$sql = "delete from wiz_cprelation where prdcode = '$prdcode' and catcode = '$catcode'";
			$result = mysql_query($sql) or error(mysql_error());
		}
		complete('선택한 분류를 삭제하였습니다.','prd_catlist.php?prdcode='.$prdcode);
	}

//////////////////////////////////////////////////////////////////////////////////////////
// 상품아이콘 등록
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "prdicon"){

	if($upfile[size] > 0){
		file_check($upfile[name]);
    copy($upfile[tmp_name], $prdicon_path."/".$upfile[name]);
    chmod($prdicon_path."/".$upfile[name], 0707);
	}

	complete('등록되었습니다.','prd_icon.php');

//////////////////////////////////////////////////////////////////////////////////////////
// 상품아이콘 삭제
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "icondel"){

	@unlink($prdicon_path."/".$prdicon);
	complete('삭제되었습니다.','prd_icon.php');

//////////////////////////////////////////////////////////////////////////////////////////
// 관련상품 삭제
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "reldel"){

	for($ii=0;$ii<count($idx);$ii++){
		$sql = "delete from wiz_prdrelation where idx = '".$idx[$ii]."'";
		//echo $sql."<br>";
		mysql_query($sql);
	}

	complete("삭제되었습니다.","prd_relation.php?prdcode=".$prdcode);

//////////////////////////////////////////////////////////////////////////////////////////
// 관련상품 등록
//////////////////////////////////////////////////////////////////////////////////////////
}else if($mode == "reladd"){

	$array_selected = explode("|",$selected);
	$i=0;
	while($array_selected[$i]){

		$tmp_prdcode = $array_selected[$i];

		$sql = "insert into wiz_prdrelation(idx,prdcode,relcode) values('','$prdcode','$tmp_prdcode')";
		mysql_query($sql) or error(mysql_error());

		$i++;
	}

	echo "<script>opener.document.location.reload();</script>";
	complete("등록되었습니다.","prd_rellist.php?$param");

}
?>
