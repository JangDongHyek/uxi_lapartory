<? include_once "../../common.php"; ?>
<? include_once "../../inc/admin_check.php"; ?>
<? include_once "../../inc/site_info.php"; ?>
<? include_once "../../inc/oper_info.php"; ?>
<?

// 페이지 파라메터 (검색조건이 변하지 않도록)
//--------------------------------------------------------------------------------------------------
$param = "s_status=$s_status&prev_year=$prev_year&prev_month=$prev_month&prev_day=$prev_day&next_year=$next_year&next_month=$next_month&next_day=$next_day";
$param .= "&searchopt=$searchopt&searchkey=$searchkey";
if($reason != "") $param .= "&reason=$reason";
if($tax_type != "") $param .= "&tax_type=$tax_type";
//--------------------------------------------------------------------------------------------------

function changeStock($orderid, $prdcode){

	// 주문취소, 주문완료 수량적용
	$sql = "SELECT wb.option_idx, wb.amount, wb.prdcode, wp.shortage
					FROM wiz_basket wb
					LEFT JOIN wiz_product wp
					ON wb.prdcode = wp.prdcode
					WHERE wb.orderid = '$orderid'";

	if($prdcode != "") $sql .= " and wb.prdcode='$prdcode' ";

	$result = mysql_query($sql) or error(mysql_error());
	while($row = mysql_fetch_array($result)){
		if(empty($row['option_idx'])){
			switch($row['shortage']){
				case "Y": // 품절
				case "S": //수량
					$sql = "update wiz_product set stock=stock+{$row[amount]} where prdcode='{$row[prdcode]}'";
					mysql_query($sql);
					break;
				case "N": // 무제한
					break;
			}
		}
		else{
			$option_idx = $row['option_idx'];
			$sql = "update wiz_product_option set stock=stock+{$row[amount]} where idx='{$option_idx}'";
			mysql_query($sql);
		}
	}

}
function changeStatus($orderid, $status, $delsno="", $deldate=""){

	global $DOCUMENT_ROOT, $HTTP_HOST, $connect, $oper_info, $order_info;


	// 운송장 번호가 있는 경우 update
	if(!empty($delsno)) {
		$sql = "update wiz_order set deliver_num='$delsno', deliver_date='$deldate' where orderid='$orderid'";
		mysql_query($sql) or error(mysql_error());
	}

	$sql = "select * from wiz_order where orderid = '$orderid'";
	$result = mysql_query($sql,$connect) or error(mysql_error());
	$order_info = mysql_fetch_object($result);

	$re_info[name] = $order_info->send_name;
	$re_info[email] = $order_info->send_email;
	$re_info[hphone] = $order_info->send_hphone;

	$del_com = $oper_info[del_com];
	//if($order_info->status != $status ){
	if($order_info->status){

		// 배송완료 → 다른 진행상태로 변경 시 배송완료수 -1
		if(!strcmp($order_info->status, "DC") && strcmp($status, "DC")) {

			$sql = "select wb.prdcode, wp.comcnt
			from wiz_basket as wb left join wiz_product as wp on wb.prdcode = wp.prdcode
			where wb.orderid = '$order_info->orderid'";
			$result = mysql_query($sql,$connect) or error(mysql_error());

			while($row = mysql_fetch_object($result)){

				if($row->comcnt > 0) {
					$sql = "update wiz_product set comcnt = comcnt - 1 where prdcode = '$row->prdcode'";
					mysql_query($sql) or error(mysql_error());
				}

			}

		}

		// 주문취소, 환불완료 → 다른 진행상태로 변경 시 주문취소수 -1
		if((!strcmp($order_info->status, "OC") && strcmp($status, "OC")) || (!strcmp($order_info->status, "RC") && strcmp($status, "RC"))){

			$sql = "select wb.prdcode, wp.cancelcnt
			from wiz_basket as wb left join wiz_product as wp on wb.prdcode = wp.prdcode
			where wb.orderid = '$order_info->orderid'";
			$result = mysql_query($sql,$connect) or error(mysql_error());

			while($row = mysql_fetch_object($result)){

				if($row->cancelcnt > 0) {
					$sql = "update wiz_product set cancelcnt = cancelcnt - 1 where prdcode = '$row->prdcode'";
					mysql_query($sql) or error(mysql_error());
				}

			}

		}

		// 입금확인시
		if($status == "OY"){

			// 이전의 상태와 변경상태가 다른 경우에만
			if(strcmp($status, $order_info->status)) {
				try{
					Exe_stock();
					Exe_point($orderid);
				}
				catch(Exception $e){
					error($e->getMessage());
				}

				$oper_time = ", pay_date = now()";

				include "$_SERVER[DOCUMENT_ROOT]/adm/product/order_mail.inc";
				send_mailsms("order_pay", $re_info, $ordmail);

			}

			// 배송완료
		}
		else if(!strcmp($status, "DC")) {

			// 마케팅분석 > 상품통계분석 > 배송완료 증가
			$sql = "select wb.prdcode, wp.comcnt
			from wiz_basket as wb left join wiz_product as wp on wb.prdcode = wp.prdcode
			where wb.orderid = '$order_info->orderid'";
			$result = mysql_query($sql,$connect) or error(mysql_error());

			while($row = mysql_fetch_object($result)){

				if(strcmp($order_info->status, $status)) {
					$sql = "update wiz_product set comcnt = comcnt + 1 where prdcode = '$row->prdcode'";
					mysql_query($sql) or error(mysql_error());
				}
			}

			$oper_time = ", send_date = now()";

			// 주문취소시, 환불완료시
		}
		else if($status == "OC" || $status == "RC"){

			// 주문취소 시 주문접수일 경우를 제외하고 재고 증가 -> 주문접수인 경우에도 재고 증가
			changeStock($order_info->orderid);

			$oper_time = ", cancel_date = now()";
			include "$_SERVER[DOCUMENT_ROOT]/adm/product/order_mail.inc";
			send_mailsms("order_cancel", $re_info, $ordmail);

			// 배송처리 시
		}
		else if(!strcmp($status, "DI")) {

			include "$_SERVER[DOCUMENT_ROOT]/adm/product/order_mail.inc";
			send_mailsms("order_deliver", $re_info, $ordmail);

		}

		$sql = "update wiz_order set status = '$status' $oper_time where orderid = '$orderid'";
		mysql_query($sql,$connect);


	}


	// 배송처리, 배송완료인 경우 배송정보 전송
	if((!strcmp($status, "DI") || !strcmp($status, "DC")) && !empty($delsno)) {
		if($oper_info[pay_agent]=="KCP"){
			// 배송정보 전송
			include "../../product/kcp/escw_update.php";
		}
		if($oper_info[pay_agent]=="DACOM"){
			// 배송정보 전송

			escrow_delivery($order_info, $oper_info, $order_info[deliver_num], $order_info[deliver_date]);
		}
	}

}

// 주문상태 변경
if($mode == "chgstatus"){

	changeStatus($orderid, $chg_status, $deliver_num, $deliver_date);

	complete("주문정보가 수정되었습니다.","order_list.php?page=$page&$param");

	// 주문정보 수정
}
else if($mode == "update"){

	if(!empty($chg_status)) {
		changeStatus($orderid, $chg_status, $deliver_num, $deliver_date);
		$chg_status_sql = " status = '$chg_status', ";
	}

	$sql = "update wiz_order set $chg_status_sql send_name = '$send_name', send_tphone = '$send_tphone', send_hphone = '$send_hphone', send_email = '$send_email',
	send_post = '$send_post', send_address = '$send_address', rece_name =' $rece_name', rece_tphone = '$rece_tphone',
	rece_hphone = '$rece_hphone', rece_post = '$rece_post', rece_address = '$rece_address', demand = '$demand', message = '$message', cancelmsg='$cancelmsg', descript = '$descript',
	deliver_num = '$deliver_num', deliver_date = '$deliver_date', tax_type = '$tax_type', id_info='$id_info', bill_yn='$bill_yn', authno='$authno' where orderid = '$orderid'";

	$result = mysql_query($sql,$connect) or error(mysql_error());




	$sql = "select orderid from wiz_tax where orderid = '$orderid'";
	$result = mysql_query($sql) or error(mysql_error());
	$row = mysql_fetch_array($result);

	include_once "../../inc/site_info.php";

	$shop_name 		= $site_info[com_name];
	$shop_owner 		= $site_info[com_owner];
	$shop_num			= $site_info[com_num];
	$shop_address	= $site_info[com_address];
	$shop_kind 		= $site_info[com_kind];
	$shop_class		= $site_info[com_class];
	$shop_tel			= $site_info[com_tel];
	$shop_email		= $site_info[site_email];

	if(!strcmp($tax_pub, "Y") && strcmp($tmp_tax_pub, "Y")) $tax_pub_sql = ", wdate = now(), shop_name='$shop_name', shop_owner='$shop_owner', shop_num='$shop_num', shop_address='$shop_address', shop_kind='$shop_kind', shop_class='$shop_class', shop_tel='$shop_tel', shop_email='$shop_email' ";

	if(!empty($row[orderid])) {
		$sql = "update wiz_tax set com_num='$com_num', com_name='$com_name', com_owner='$com_owner', com_address='$com_address', com_kind='$com_kind', com_class='$com_class', com_tel='$com_tel', com_email='$com_email'
		, tax_pub='$tax_pub',tax_type = '$tax_type',cash_type='$cash_type',cash_type2='$cash_type2',cash_info='$cash_info',cash_name='$cash_name' $tax_pub_sql where orderid = '$orderid'";
	}
	else {

		include_once "$_SERVER[DOCUMENT_ROOT]/adm/inc/site_info.php";

		$shop_name 		= $site_info[com_name];
		$shop_owner 	= $site_info[com_owner];
		$shop_num			= $site_info[com_num];
		$shop_address	= $site_info[com_address];
		$shop_kind 		= $site_info[com_kind];
		$shop_class		= $site_info[com_class];
		$shop_tel			= $site_info[com_tel];
		$shop_email		= $site_info[shop_email];

		$supp_price = intval($total_price/1.1);
		$tax_price = $total_price - $supp_price;

		$sql = "INSERT INTO wiz_tax(orderid,com_num,com_name,com_owner,com_address,com_kind,com_class,com_tel,com_email,shop_num,shop_name,shop_owner,shop_address,shop_kind,shop_class,shop_tel,shop_email,prd_info,supp_price,tax_price,tax_pub,tax_date,tax_type,cash_type,cash_type2,cash_info,cash_name) VALUES ('".$orderid."','".$com_num."','".$com_name."','".$com_owner."','".$com_address."','".$com_kind."','".$com_class."','".$com_tel."','".$com_email."','".$shop_num."','".$shop_name."','".$shop_owner."','".$shop_address."','".$shop_kind."','".$shop_class."','".$shop_tel."','".$shop_email."','".$prd_info."','".$supp_price."','".$tax_price."','".$tax_pub."',now(),'".$tax_type."','".$cash_type."','".$cash_type2."','".$cash_info."','".$cash_name."')";

	}

	mysql_query($sql) or error(mysql_error());

	complete("주문정보가 수정되었습니다.","order_info.php?orderid=$orderid&page=$page&$param");


	// 주문삭제
}
else if($mode == "delete"){

	$i=0;
	$array_selorder = explode("|",$selorder);
	while($array_selorder[$i]){
		$orderid = $array_selorder[$i];
		$sql = "delete from wiz_order where orderid = '$orderid'";
		$result = mysql_query($sql,$connect) or error(mysql_error());

		$sql = "delete from wiz_basket where orderid = '$orderid'";
		$result = mysql_query($sql,$connect) or error(mysql_error());

		$sql = "delete from wiz_tax where orderid = '$orderid'";
		mysql_query($sql) or error(mysql_error());

		$i++;
	}

	complete("주문을 삭제하였습니다.","order_list.php?page=$page&$param");


	// 주문상태 일괄변경
}
else if($mode == "batchStatus"){

	$i=0;
	$array_selorder = explode("|",$selorder);
	while($array_selorder[$i]){
		list($orderid, $old_status) = explode(":",$array_selorder[$i]);

		if(strcmp($old_status, "OC") && strcmp($old_status, "RC")) {
			changeStatus($orderid, $chg_status,$deliveryno[$i], $deliver_date[$i]);
		}

		$i++;
	}

	echo "<script>alert('주문상태를 변경하였습니다.');opener.document.location.reload();self.close();</script>";

	// 상품 취소
}
else if($mode == "cancel"){

	if(!strcmp($orderstatus, "OR")) {

		$sql = "select wb.*, wo.orderid, wo.deliver_price, wo.prd_price, wo.prd_price, wm.level
		from wiz_basket as wb LEFT JOIN wiz_order as wo ON wb.orderid = wo.orderid
		LEFT JOIN wiz_user AS wm ON wo.send_id = wm.id
		LEFT JOIN wiz_product AS wp ON wb.prdcode = wp.prdcode
		where wb.idx = '$idx'";
		$result = mysql_query($sql,$connect) or error(mysql_error());
		$row = mysql_fetch_array($result);

		$orderid = $row[orderid];
		$prdcode = $row[prdcode];
		$prd_price 		 = $row[prd_price] - ($row[prdprice] * $row[amount]);

		$discount_price = level_discount($row[level],$prd_price);			// 회원할인 [$discount_msg 메세지 생성]
		$deliver_price = deliver_price($prd_price, $oper_info);				// 배송비
		$total_price = $prd_price + $deliver_price - $discount_price; // 전체결제금액

		// 주문 정보에서 해당 금액, 배송비, 회원할인비 가감
		$sql = "update wiz_order set deliver_price = '$deliver_price',
		discount_price = '$discount_price', prd_price = '$prd_price', total_price = '$total_price'
		where orderid = '$row[orderid]'";
		mysql_query($sql,$connect) or error(mysql_error());

		// basket 업데이트
		$sql = "update wiz_basket set status = 'CC', admin = '$wiz_admin[id]', bank = '$bank', account = '$account',
		acc_name = '$acc_name', reason = '$reason', memo = '$memo', repay = '$repay', ca_date = now(), cc_date = now()
		where idx = '$idx'";
		mysql_query($sql,$connect) or error(mysql_error());

		changeStock($orderid, $prdcode);

		complete("상품이 취소되었습니다.","order_info.php?orderid=$row[orderid]&page=$page&$param");

	}
	else {

		// basket 업데이트
		$sql = "update wiz_basket set status = 'CA', admin = '$wiz_admin[id]', bank = '$bank', account = '$account',
		acc_name = '$acc_name', reason = '$reason', memo = '$memo', repay = '$repay', ca_date = now()
		where idx = '$idx'";
		mysql_query($sql,$connect) or error(mysql_error());

		complete("상품이 취소요청이 되었습니다. 상품취소목록에서 확인하실 수 있습니다.","order_info.php?orderid=$orderid&page=$page&$param");

	}

	// 개별취소 목록
}
else if(!strcmp($mode, "cancel_status")){

	if(!strcmp($chg_status, "CC")) {

		$sql = "select wb.*, wo.orderid, wo.deliver_price, wo.prd_price, wo.prd_price, wo.send_id, wo.status as o_status, wm.level, wp.shortage
		from wiz_basket as wb LEFT JOIN wiz_order as wo ON wb.orderid = wo.orderid
		LEFT JOIN wiz_user AS wm ON wo.send_id = wm.id
		LEFT JOIN wiz_product AS wp ON wb.prdcode = wp.prdcode
		where wb.idx = '$idx'";
		$result = mysql_query($sql,$connect) or error(mysql_error());
		$row = mysql_fetch_array($result);

		if(!strcmp($row[status], "CC")) {
			error("이미 취소처리된 상품입니다.");
		}
		else {

			$orderid = $row[orderid];
			$prdcode = $row[prdcode];
			$prd_price 		 = $row[prd_price] - ($row[prdprice] * $row[amount]);

			$discount_price = level_discount($row[level],$prd_price);			// 회원할인 [$discount_msg 메세지 생성]
			$deliver_price = deliver_price($prd_price, $oper_info);				// 배송비
			$total_price = $prd_price + $deliver_price - $discount_price; // 전체결제금액

			// 주문 정보에서 해당 금액, 배송비, 회원할인비 가감
			$sql = "update wiz_order set deliver_price = '$deliver_price',
			discount_price = '$discount_price', prd_price = '$prd_price', total_price = '$total_price'
			where orderid = '$row[orderid]'";
			mysql_query($sql,$connect) or error(mysql_error());

			// 상품 재고
			// 주문접수일 경우를 제외하고 재고증가
			if(strcmp($row[o_status], "OR")) {
				// 옵션별 재고관리 없는 제품이라면 전체 재고 증가
				changeStock($orderid, $prdcode);
			}
		}

		$cc_date_sql = ", cc_date = now() ";

	}

	$sql = "update wiz_basket set status = '$chg_status' $cc_date_sql where idx = '$idx'";
	mysql_query($sql,$connect) or error(mysql_error());

	// 세금계산서 금액 수정
	$supp_price = intval($total_price/1.1);
	$tax_price = $total_price - $supp_price;

	$prd_info = "";

	$b_sql = "select prdname, prdprice, amount from wiz_basket where orderid = '$row[orderid]' and status != 'CC' order by idx asc";
	$b_result = mysql_query($b_sql,$connect) or error(mysql_error());
	while($b_row = mysql_fetch_array($b_result)) {
		$prd_info .= $b_row[prdname]."^".$b_row[prdprice]."^".$b_row[amount]."^^";
	}

	$sql = "update wiz_tax set supp_price='$supp_price', tax_price='$tax_price', prd_info='$prd_info' where orderid = '$row[orderid]'";
	mysql_query($sql,$connect) or error(mysql_error());


	complete("적용되었습니다.","cancel_list.php?page=$page&$param");

	// 개별취소 삭제
}
else if(!strcmp($mode, "delete_basket")) {

	$idx_list = explode("|", $selbasket);
	for($ii = 0; $ii < count($idx_list); $ii++) {
		$idx = $idx_list[$ii];

		$sql = "delete from wiz_basket where idx = '$idx'";
		mysql_query($sql,$connect) or error(mysql_error());
	}

	complete("삭제되었습니다.","cancel_list.php?page=$page&$param");

	// 취소상태 일괄변경
}
else if($mode == "batchStatusBasket"){

	$i=0;
	$array_selbasket = explode("|",$selbasket);
	while($array_selbasket[$i]){
		$idx = $array_selbasket[$i];

		$sql = "select wb.*, wo.orderid, wo.deliver_price, wo.prd_price, wo.send_id, wm.level
		from wiz_basket as wb LEFT JOIN wiz_order as wo ON wb.orderid = wo.orderid
		LEFT JOIN wiz_user AS wm ON wo.send_id = wm.id
		LEFT JOIN wiz_product AS wp ON wb.prdcode = wp.prdcode
		where wb.idx = '$idx'";
		$result = mysql_query($sql,$connect) or error(mysql_error());
		$row = mysql_fetch_array($result);

		if(!strcmp($row[status], "CC")) {
		}
		else {
			if(!strcmp($chg_status, "CC")) {
				$orderid = $row[orderid];
				$prd_price 		 = $row[prd_price] - ($row[prdprice] * $row[amount]);

				$discount_price = level_discount($row[level],$prd_price);			// 회원할인 [$discount_msg 메세지 생성]
				$deliver_price = deliver_price($prd_price, $oper_info);				// 배송비
				$total_price = $prd_price + $deliver_price - $discount_price; // 전체결제금액

				// 주문 정보에서 해당 금액, 배송비, 회원할인비 가감
				$sql = "update wiz_order set deliver_price = '$deliver_price',
				discount_price = '$discount_price', prd_price = '$prd_price', total_price = '$total_price'
				where orderid = '$row[orderid]'";
				mysql_query($sql,$connect) or error(mysql_error());

				// 상품 재고
				// 옵션별 재고관리 없는 제품이라면 전체재고 증가
				changeStock($orderid);

				$cc_date_sql = ", cc_date = now() ";
			}

			$sql = "update wiz_basket set status = '$chg_status' $cc_date_sql where idx = '$idx'";
			mysql_query($sql,$connect) or error(mysql_error());

			// 세금계산서 금액 수정
			$supp_price = intval($total_price/1.1);
			$tax_price = $total_price - $supp_price;

			$prd_info = "";

			$b_sql = "select prdname, prdprice, amount from wiz_basket where orderid = '$row[orderid]' and status != 'CC' order by idx asc";
			$b_result = mysql_query($b_sql) or error(mysql_error());
			while($b_row = mysql_fetch_array($b_result)) {
				$prd_info .= $b_row[prdname]."^".$b_row[prdprice]."^".$b_row[amount]."^^";
			}

			$sql = "update wiz_tax set supp_price='$supp_price', tax_price='$tax_price', prd_info='$prd_info' where orderid = '$row[orderid]'";
			mysql_query($sql,$connect) or error(mysql_error());

		}

		$i++;
	}

	echo "<script>alert('상태를 변경하였습니다.\\n\\n취소완료된 건은 상태가 변경되지 않습니다.');opener.document.location.reload();self.close();</script>";

	// 세금계산서 목록 > 승인
}
else if(!strcmp($mode, "tax_status")) {



	$shop_name 		= $shop_info->com_name;
	$shop_owner 	= $shop_info->com_owner;
	$shop_num			= $shop_info->com_num;
	$shop_address	= $shop_info->com_address;
	$shop_kind 		= $shop_info->com_kind;
	$shop_class		= $shop_info->com_class;
	$shop_tel			= $shop_info->com_tel;
	$shop_email		= $shop_info->shop_email;

	if($tax_type == 'C'){




		$tax_sql = "select * from wiz_tax where orderid = '$orderid' and tax_date != ''";
		$tax_result = mysql_query($tax_sql) or error(mysql_error());
		$tax_info = mysql_fetch_object($tax_result);

		$order_sql = "select orderid,total_price,status,send_name,send_hphone from wiz_order where orderid = '$orderid'";
		$order_result = mysql_query($order_sql) or error(mysql_error());
		$order_info = mysql_fetch_object($order_result);

		$oper_sql = "SELECT pay_test,pay_id,pay_key,pay_agent FROM wiz_operinfo";
		$oper_result = mysql_query($oper_sql) or error(mysql_error());
		$oper_info = mysql_fetch_object($oper_result);

		if($order_info->status == 'OC' || $order_info->status == 'RD' || $order_info->status == 'RC' || $order_info->status == 'CD' || $order_info->status == 'CC' || $order_info->status == 'OR'){
			alert('결제완료및 배송처리된 주문건에서만 발급가능합니다.');
			exit;
		}


		// 상품이름
		$prd_name = "";
		$prd_info = explode("^^", $tax_info->prd_info);
		$no = 0;
		for($ii = 0; $ii < count($prd_info); $ii++) {

			if(!empty($prd_info[$ii])) {
				$tmp_prd = explode("^", $prd_info[$ii]);
				if($ii < 1) $prd_name = cut_str($tmp_prd[0], 25);
				$no++;
			}
		}
		if($no > 1) {
			$prd_name .= " 외 ".($no-1)."건";
		}



		if($oper_info->pay_agent=="DACOM"){
			include_once "$_SERVER[DOCUMENT_ROOT]/adm/product/dacom/lgdacom/XPayClient.php";
			if(!strcmp($oper_info->pay_test, "Y")) {
				//테스트
				$oper_info->pay_id = "".$oper_info->pay_id;
				$platform	= "test";             //LG데이콤 결제서비스 선택(test:테스트, service:서비스)
				$mid = $oper_info->pay_id;
				$pay_key = $oper_info->pay_key;
			}
			else{
				//실거래
				$platform	= "service";
				$mid = $oper_info->pay_id;
				$pay_key = $oper_info->pay_key;
			}


/*
* [현금영수증 발급 요청 페이지]
*
* 파라미터 전달시 POST를 사용하세요
			*/
			$CST_PLATFORM               = $platform;       		//LG유플러스 결제 서비스 선택(test:테스트, service:서비스)
			$CST_MID                    = $mid;            		//상점아이디(LG유플러스으로 부터 발급받으신 상점아이디를 입력하세요)


			//테스트 아이디는 't'를 반드시 제외하고 입력하세요.
			$LGD_MID                    = (("test" == $CST_PLATFORM)?"t":"").$CST_MID;  //상점아이디(자동생성)
			//$LGD_TID                	= $HTTP_POST_VARS["LGD_TID"];			 		//LG유플러스으로 부터 내려받은 거래번호(LGD_TID)
			$LGD_MERTKEY =$pay_key;
			//$LGD_METHOD   		    	= "AUTH";                //메소드('AUTH':승인, 'CANCEL' 취소)
			if($tax_pub=='Y'){
				$LGD_METHOD = 'AUTH';
			}
			else{
				$LGD_METHOD = 'CANCEL';
			}
			$LGD_OID                	= $order_info->orderid;					//주문번호(상점정의 유니크한 주문번호를 입력하세요)
			$LGD_PAYTYPE                = "SC0100";				//결제수단 코드 (SC0030:계좌이체, SC0040:가상계좌, SC0100:무통장입금 단독)
			$LGD_AMOUNT     		    = $order_info->total_price;           	//금액("," 를 제외한 금액을 입력하세요)
			$LGD_CASHCARDNUM        	= $tax_info->cash_info;           //발급번호(현금영수증카드번호,휴대폰번호 등등)
			$LGD_CUSTOM_MERTNAME 		= $site_info[com_name];    	//상점명
			$LGD_CUSTOM_BUSINESSNUM 	= $site_info[com_num];    //사업자등록번호
			$LGD_CUSTOM_MERTPHONE 		= $site_info[com_tel];   	//상점 전화번호

			if($tax_info->cash_type == 'C'){
				//지출증빙
				$LGD_CASHRECEIPTUSE = '2';
				$tr_code="1";
			}
			else if($tax_info->cash_type == 'P'){
				//개인소득공제
				$LGD_CASHRECEIPTUSE = '1';
				$tr_code="0";
			}

			//$LGD_CASHRECEIPTUSE     	= 1;		//현금영수증발급용도('1':소득공제, '2':지출증빙)
			$LGD_PRODUCTINFO        	= $prd_name;			//상품명
			//$LGD_TID        			= "anywi2015020411362483347";					//LG유플러스 거래번호

/* ※ 중요
* 환경설정 파일의 경우 반드시 외부에서 접근이 가능한 경로에 두시면 안됩니다.
* 해당 환경파일이 외부에 노출이 되는 경우 해킹의 위험이 존재하므로 반드시 외부에서 접근이 불가능한 경로에 두시기 바랍니다.
* 예) [Window 계열] C:\inetpub\wwwroot\lgdacom ==> 절대불가(웹 디렉토리)
			*/
			$configPath 				= $_SERVER['DOCUMENT_ROOT']."/adm/product/dacom/lgdacom"; 						 		//LG유플러스에서 제공한 환경파일("/conf/lgdacom.conf") 위치 지정.

			$xpay = &new XPayClient($configPath, $CST_PLATFORM,$LGD_MID,$LGD_MERTKEY);
			//$xpay->SetConf($mid, $pay_key);
			//$xpay->SetConf("t".$mid, $pay_key);
			$xpay->Init_TX($LGD_MID);
			$xpay->Set("LGD_TXNAME", "CashReceipt");
			$xpay->Set("LGD_METHOD", $LGD_METHOD);
			$xpay->Set("LGD_PAYTYPE", $LGD_PAYTYPE);

			if ($LGD_METHOD == "AUTH"){
				// 현금영수증 발급 요청
				$xpay->Set("LGD_OID", $LGD_OID);
				$xpay->Set("LGD_AMOUNT", $LGD_AMOUNT);
				$xpay->Set("LGD_CASHCARDNUM", $LGD_CASHCARDNUM);
				$xpay->Set("LGD_CUSTOM_MERTNAME", $LGD_CUSTOM_MERTNAME);
				$xpay->Set("LGD_CUSTOM_BUSINESSNUM", $LGD_CUSTOM_BUSINESSNUM);
				$xpay->Set("LGD_CUSTOM_MERTPHONE", $LGD_CUSTOM_MERTPHONE);
				$xpay->Set("LGD_CASHRECEIPTUSE", $LGD_CASHRECEIPTUSE);

				if ($LGD_PAYTYPE == "SC0030"){
					//기결제된 계좌이체건 현금영수증 발급요청시 필수
					$xpay->Set("LGD_TID", $LGD_TID);
				}
				else if ($LGD_PAYTYPE == "SC0040"){
					//기결제된 가상계좌건 현금영수증 발급요청시 필수
					$xpay->Set("LGD_TID", $LGD_TID);
					$xpay->Set("LGD_SEQNO", "001");
				}
				else {
					//무통장입금 단독건 발급요청
					$xpay->Set("LGD_PRODUCTINFO", $LGD_PRODUCTINFO);
				}
			}
			else {
				// 현금영수증 취소 요청
				//$xpay->Set("LGD_TID", $LGD_TID);
				$xpay->Set("LGD_OID", $LGD_OID);

				if ($LGD_PAYTYPE == "SC0040"){
					//가상계좌건 현금영수증 발급취소시 필수
					$xpay->Set("LGD_SEQNO", "001");
				}
			}

/*
* 1. 현금영수증 발급/취소 요청 결과처리
*
* 결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
			*/
			if ($xpay->TX()) {
				//1)현금영수증 발급/취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)

				if($xpay->Response_Code() == '0000'){
					if($LGD_METHOD == 'AUTH'){
						$tax_pub = 'Y';

					}
					else if($LGD_METHOD == 'CANCEL'){
						$tax_pub = 'N';
						$wdate_sql = ",wdate=''";
					}
				}
				else{
					$tax_pub = 'N';
				}

/*
echo "현금영수증 발급/취소 요청처리가 완료되었습니다.  <br>";
echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";

echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<br>";
echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<p>";

$keys = $xpay->Response_Names();
foreach($keys as $name) {
echo $name . " = " . $xpay->Response($name, 0) . "<br>";
}
				*/
			}
			else {
				$tax_pub = 'N';

				//2)API 요청 실패 화면처리
/*
echo "현금영수증 발급/취소 요청처리가 실패되었습니다.  <br>";
echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
				*/
			}

		}
		else if($oper_info->pay_agent=="KCP"){

		}

		if(!strcmp($tax_pub, "Y") && strcmp($tmp_tax_pub, "Y")) $tax_pub_sql = ", wdate = now(), shop_name='$shop_name', shop_owner='$shop_owner', shop_num='$shop_num', shop_address='$shop_address', shop_kind='$shop_kind', shop_class='$shop_class', shop_tel='$shop_tel', shop_email='$shop_email' ";

		$sql = "update wiz_tax set tax_pub = '$tax_pub' $tax_pub_sql $wdate_sql where orderid = '$orderid'";
		mysql_query($sql,$connect) or error(mysql_error());



		if($oper_info->pay_agent=="DACOM"){

			alert($xpay->Response_Msg(),"../product/tax_list.php?page=$page&$param");
		}
		else if($oper_info->pay_agent=="KCP"){


			alert("적용되었습니다","../product/tax_list.php?page=$page&$param");
		}
		else{


			complete("적용되었습니다.","../product/tax_list.php?page=$page&$param");
		}
	}
	else{
		if(!strcmp($tax_pub, "Y") && strcmp($tmp_tax_pub, "Y")) $tax_pub_sql = ", wdate = now(), shop_name='$shop_name', shop_owner='$shop_owner', shop_num='$shop_num', shop_address='$shop_address', shop_kind='$shop_kind', shop_class='$shop_class', shop_tel='$shop_tel', shop_email='$shop_email' ";

		$sql = "update wiz_tax set tax_pub = '$tax_pub' $tax_pub_sql where orderid = '$orderid'";
		mysql_query($sql,$connect) or error(mysql_error());

		complete("적용되었습니다.","../product/tax_list.php?page=$page&$param");
	}

	// 세금계산서 삭제
}
else if(!strcmp($mode, "tax_delete")) {

	$orderid_list = explode("|", $selvalue);
	for($ii = 0; $ii < count($orderid_list); $ii++) {
		$orderid = $orderid_list[$ii];
		$sql = "delete from wiz_tax where orderid = '$orderid'";
		mysql_query($sql,$connect) or error(mysql_error());

		$sql = "update wiz_order set tax_type = 'N' where orderid = '$orderid'";
		mysql_query($sql,$connect) or error(mysql_error());
	}

	complete("삭제되었습니다.","tax_list.php?page=$page&$param");

	// 세금계산서 목록 > 상태일괄변경
}
else if(!strcmp($mode, "batchStatusTax")) {

	include_once "$_SERVER[DOCUMENT_ROOT]/adm/inc/site_info.php";

	$shop_name 		= $site_info[com_name];
	$shop_owner 	= $site_info[com_owner];
	$shop_num			= $site_info[com_num];
	$shop_address	= $site_info[com_address];
	$shop_kind 		= $site_info[com_kind];
	$shop_class		= $site_info[com_class];
	$shop_tel			= $site_info[com_tel];
	$shop_email		= $site_info[shop_email];

	if(!strcmp($tax_pub, "Y") && strcmp($tmp_tax_pub, "Y")) $tax_pub_sql = ", wdate = now(), shop_name='$shop_name', shop_owner='$shop_owner', shop_num='$shop_num', shop_address='$shop_address', shop_kind='$shop_kind', shop_class='$shop_class', shop_tel='$shop_tel', shop_email='$shop_email' ";

	$orderid_list = explode("|", $selvalue);
	for($ii = 0; $ii < count($orderid_list); $ii++) {

		$orderid = $orderid_list[$ii];

		$sql = "update wiz_tax set tax_pub = '$tax_pub' $tax_pub_sql where orderid = '$orderid'";
		mysql_query($sql,$connect) or error(mysql_error());

	}

	echo "<script>alert('변경되었습니다.');opener.document.location.reload();self.close();</script>";

}
?>
