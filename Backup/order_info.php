<? include_once "../../common.php"; ?>
<? include_once "../../inc/admin_check.php"; ?>
<? include_once "../../inc/site_info.php"; ?>
<? include_once "../../inc/oper_info.php"; ?>
<? include "../head.php"; ?>
<?
// 페이지 파라메터 (검색조건이 변하지 않도록)
//--------------------------------------------------------------------------------------------------
$param = "s_status=$s_status&prev_year=$prev_year&prev_month=$prev_month&prev_day=$prev_day&next_year=$next_year&next_month=$next_month&next_day=$next_day";
$param .= "&searchopt=$searchopt&searchkey=$searchkey";
//--------------------------------------------------------------------------------------------------

// 주문정보 가져오기
$sql = "select * from wiz_order where orderid = '$orderid'";
$result = mysql_query($sql) or error(mysql_error());
$order_info = mysql_fetch_object($result);

// 회원할인
if($order_info->discount_price > 0){
	$discount_msg = " - 회원할인( <b><font color=#ED1C24>".number_format($order_info->discount_price)."원</font></b> )";
}
// 쿠폰사용
if($order_info->coupon_use > 0){
	$coupon_msg = " - 쿠폰 사용(<b><font color=#ED1C24>".number_format($order_info->coupon_use)."원</font></b>)";
}

// 주문 상품정보 가져오기
$sql = "SELECT wb.prdcode, wp.prdname, wp.prdimg_R as prdimg
				FROM wiz_basket as wb
				LEFT JOIN wiz_product as wp
					on wb.prdcode = wp.prdcode
				WHERE wb.orderid='{$orderid}'
				GROUP BY wb.prdcode";
$result = mysql_query($sql,$connect) or error(mysql_error());
$prd_num = mysql_num_rows($result);

$baskets = array();
$no = 0;
while($brow = mysql_fetch_array($result)){
	$prd_price += $brow[prdprice] * $brow[amount];

	// 상품 이미지
	$prdimg_path = "/adm/data/prdimg/".$brow['prdimg'];
	$prdimg = (!@file($_SERVER['DOCUMENT_ROOT'].$prdimg_path)) ? "/adm/images/noimg_S.gif" : $prdimg_path;

	// 상품 링크
	$prdurl = "/child/sub/shop/product.php?ptype=view&prdcode=".$brow['prdcode'];

	// 상품정보
	$basket = array(
		"prdcode" => $brow[prdcode],
		"prdname" => $brow[prdname],
		"prdimg" => $prdimg,
		"url" => $prdurl
	);

	// 옵션 정보
	$sql = "SELECT wb.* FROM wiz_basket as wb WHERE wb.orderid='{$orderid}' and wb.prdcode = '{$brow["prdcode"]}' ";
	$oresult = mysql_query($sql) or error(mysql_error());
	$options = array();
	while($orow = mysql_fetch_array($oresult)){
		$option = array(
			"idx" => $orow[idx],
			"name" => $orow[option],
			"price" => $orow[prdprice],
			"amount" => $orow[amount],

			"status" => $orow[status],
			"reason" => $orow[reason],
			"memo" => $orow[memo],
			"bank" => $orow[bank],
			"account" => $orow[account],
			"acc_name" => $orow[acc_name]
		);
		array_push($options, $option);
	}

	$basket[options] = $options;

	array_push($baskets, $basket);
}

$baskets = json_encode($baskets);
?>
<link href="../style.css" rel="stylesheet" type="text/css">
<script src="http://dmaps.daum.net/map_js_init/postcode.v2.js"></script>
<script language="javascript">
<!--
// 고객 메일발송
function sendEmail(name,email){
	var url = "../member/mail_popup.php?seluser=" + name + ":" + email;
	window.open(url,"sendEmail","height=600, width=800, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, top=100, left=100");
}

// 고객 sms발송
function sendSms(name,hphone){
	var url = "../member/sms_popup.php?seluser=" + hphone;
	window.open(url,"sendSms","height=450, width=360, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, top=100, left=100");
}

// 우편번호 찾기
function searchZip() {
	kind = 'send_';
	new daum.Postcode({
		oncomplete: function(data) {
			// 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분.
			// 우편번호와 주소 정보를 해당 필드에 넣고, 커서를 상세주소 필드로 이동한다.
			eval('document.frm.'+kind+'post').value = data.zonecode;
			if (data.userSelectedType === 'R') { // 사용자가 도로명 주소를 선택했을 경우

     eval('document.frm.'+kind+'address').value = data.roadAddress;

                } else { // 사용자가 지번 주소를 선택했을 경우(J)

     eval('document.frm.'+kind+'address').value = data.jibunAddress;

                }
			//eval('document.frm.'+kind+'address').value = data.address;

			//전체 주소에서 연결 번지 및 ()로 묶여 있는 부가정보를 제거하고자 할 경우,
			//아래와 같은 정규식을 사용해도 된다. 정규식은 개발자의 목적에 맞게 수정해서 사용 가능하다.
			//var addr = data.address.replace(/(\s|^)\(.+\)$|\S+~\S+/g, '');
			//document.getElementById('addr').value = addr;

			if(eval('document.frm.'+kind+'address2') != null)
				eval('document.frm.'+kind+'address2').focus();
		}
	}).open();
}

function searchZip2() {
	kind = 'rece_';
	new daum.Postcode({
		oncomplete: function(data) {
			// 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분.
			// 우편번호와 주소 정보를 해당 필드에 넣고, 커서를 상세주소 필드로 이동한다.
			eval('document.frm.'+kind+'post').value = data.zonecode;
			if (data.userSelectedType === 'R') { // 사용자가 도로명 주소를 선택했을 경우

     eval('document.frm.'+kind+'address').value = data.roadAddress;

                } else { // 사용자가 지번 주소를 선택했을 경우(J)

     eval('document.frm.'+kind+'address').value = data.jibunAddress;

                }
			//eval('document.frm.'+kind+'address').value = data.address;

			//전체 주소에서 연결 번지 및 ()로 묶여 있는 부가정보를 제거하고자 할 경우,
			//아래와 같은 정규식을 사용해도 된다. 정규식은 개발자의 목적에 맞게 수정해서 사용 가능하다.
			//var addr = data.address.replace(/(\s|^)\(.+\)$|\S+~\S+/g, '');
			//document.getElementById('addr').value = addr;

			if(eval('document.frm.'+kind+'address2') != null)
				eval('document.frm.'+kind+'address2').focus();
		}
	}).open();
}

function basketCancel( idx, prdname ) {

<? if(!strcmp($order_info->status, "OR") || !strcmp($order_info->status, "OY") || !strcmp($order_info->status, "DR")) { ?>

	if(cancel.style.display == "" && document.cFrm.idx.value == idx) cancel.style.display = "none";
	else cancel.style.display = "";

	document.cFrm.idx.value = idx;
	document.getElementById("cPrd").innerHTML = prdname;

<? } else { ?>

	alert("배송처리/주문취소된 주문의 상품은 취소할 수 없습니다.");

<? } ?>

}

function resetCancel() {
	document.cFrm.idx.value = "";
	document.getElementById("cPrd").innerHTML = "";
	cancel.style.display = "none";
}

function cancelCheck( frm ) {

	if(frm.idx.value == "") {
		alert("취소상품이 선택되지 않았습니다.");
		return false;
	}

	if(frm.reason.value == "") {
		alert("취소사유를 선택해주세요.");
		frm.reason.focus();
		return false;
	}

	if(frm.bank != undefined) {

		if(frm.repay[0].checked != true && frm.repay[1].checked != true) {
			alert("환불방법을 선택하세요.")
			return false;
		}
		if(frm.repay[1].checked == true) {
			if(frm.bank.value == "") {
				alert("은행을 선택하세요.");
				frm.bank.focus();
				return false;
			}

			if(frm.account.value == "") {
				alert("입금계좌를 입력하세요.");
				frm.account.focus();
				return false;
			}

			if(frm.acc_name.value == "") {
				alert("예금주를 입력하세요.");
				frm.acc_name.focus();
				return false;
			}
		}

	}

}

var clickvalue='';
function viewCancel( idx ) {

	ccontent =eval("ccontent_"+idx+".style");

	if(clickvalue != ccontent) {
		if(clickvalue!='') {
			clickvalue.display='none';
		}

		ccontent.display='block';
		ccontent.display='';
		clickvalue=ccontent;
	} else {
		ccontent.display='none';
		clickvalue='';
	}

}

function orderPrint() {
	var url = "order_print.php?selorder=<?=$orderid?>";
	window.open(url,"OderPrint","height=650, width=750, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, top=100, left=100");
}

// 세금계산서발행
function qclick(idnum) {

	tax00.style.display='none';
  tax01.style.display='none';
  tax02.style.display='none';

  if(idnum != ""){
	  tax=eval("tax"+idnum+".style");
	  tax.display='block';
		tax00.style.display='block';
	}
}

// 세금계산서 출력
function printTax(orderid) {

	var url = "/adm/product/print_tax_sup.php?orderid=" + orderid;
	window.open(url, "taxPub", "height=750, width=670, menubar=no, scrollbars=no, resizable=no, toolbar=no, status=no, left=50, top=50");

}
-->
</script>

<div id="location">HOME > 상품관리</div>
<div id="S_contents">
<h3>주문정보<span>주문상세 정보입니다.</span></h3>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="bbs_basic_list">
	<thead>
    <tr>
      <td width="10%">상품코드</td>
      <td width="5%"></td>
      <td>상품정보</td>
    </tr>
  </thead>
	<tbody>
   	<tr v-for="basket in baskets">
      <td align="center">{{ basket.prdcode }}</td>
      <td><a v-bind:href="basket.url" target='_blank'><img v-bind:src='basket.prdimg' width='50' height='50' border='0'></a></td>
      <td align="left" style="text-align: left;">
				<div style="display: flex; width: 100%; height: auto;">
          <div style="padding-left: 24px; width: 100%;">
            <div style="font-weight: 600;"><a v-bind:href="basket.url" target="prdview">{{ basket.prdname}}</a></div>
            <div class="option_list mdc-elevation-z1">
							<div v-for="option in basket.options">
                <table style="width:100%;">
                  <tr>
                    <td width="60%" style="position: relative; text-align: left;"><span v-bind:title="option.name">{{ option.name }}</span></td>
                    <td width="20%">{{ option.amount }}개</td>
                    <td width="20%">{{ (option.price * option.amount).format() }}원</td>
										<td align="center">
											<div v-if="option.status == 'CA'">
												취소신청<br>
												<button type="button" class="h18 t3 color small round black_s" v-on:click="javascript:viewCancel(option.idx);">취소내역보기</button>
											</div>
											<div v-else-if="option.status == 'CI'">
												처리중<br>
												<button type="button" class="h18 t3 color small round black_s" onClick="viewCancel('$row->idx')">취소내역보기</button>
											</div>
											<div v-else-if="option.status == 'CC'">
												취소완료<br>
												<button type="button" class="h18 t3 color small round black_s" onClick="viewCancel('$row->idx')">취소내역보기</button>
											</div>
											<div v-else>
												<button type="button" class="h18 t3 color small round black_s" v-on:click="javascript:basketCancel(option.idx, basket.prdname)">취소</button>
											</div>
										</td>
                  </tr>
									<tr v-bind:id="'ccontent_'+option.idx" style="display:none;">
							      <td colspan="8" width="100%">
							        <table width="100%" border="0" width="100%" cellspacing="0" cellpadding="0" class="inner_table left">
							          <tr>
							            <th width="15%">취소사유</th>
							            <td width="85%" colspan="3">{{ option.reason }}</td>
							          </tr>
							          <tr>
							            <th>메모</th>
							            <td colspan="3">{{ option.memo }}</td>
							          </tr>
							          <tr if="option.repay == 'C'">
							            <th>환불방법</th>
							            <td colspan="3">계좌이체</td>
							          </tr>
							          <tr if="option.bank">
							            <th width="15%">은행명</th>
							            <td width="35%">{{ option.bank }}</td>
							            <th width="15%">계좌번호</th>
							            <td width="35%">{{ option.account }} {{ option.acc_name}}</td>
							          </tr>
							        </table>
							      </td>
							    </tr>
                </table>
							</div>
            </div>
          </div>
        </div>
			</td>
    </tr>
	</tbody>
</table>

<script>
Vue.data.baskets = <?=$baskets?>;
</script>

	<form name="cFrm" action="order_save.php" method="post" onSubmit="return cancelCheck(this)">
	<input type="hidden" name="orderid" value="<?=$orderid?>">
	<input type="hidden" name="orderstatus" value="<?=$order_info->status?>">
	<input type="hidden" name="mode" value="cancel">
	<input type="hidden" name="idx" value="">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" id="cancel" style="display:none">
        <tr>
        	<td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0"class="table_basic top5">
              <tr>
                <th width="15%">취소상품</th>
                <td width="85%" id="cPrd" colspan="5"></td>
              </tr>
              <tr>
                <th>취소사유</th>
                <td colspan="5">
                	<select name="reason">
                		<option value="">:: 취소사유를 선택하세요 ::</option>
                		<option value="고객변심">고객변심</option>
                		<option value="품절">품절</option>
                		<option value="배송지연">배송지연</option>
                		<option value="이중주문">이중주문</option>
                		<option value="시스템오류">시스템오류</option>
                		<option value="누락배송">누락배송</option>
                		<option value="택배분실">택배분실</option>
                		<option value="상품불량">상품불량</option>
                		<option value="기타">기타</option>
                	</select>
                </td>
              </tr>
              <tr>
                <th>메모</th>
                <td colspan="5">
                	<textarea name="memo" class="input" style="width:98%;height:100px"></textarea>
                </td>
              </tr>
				<?
					if(strcmp($order_info->status, "OR") && strcmp($order_info->pay_metho, "PC")) {
				?>
              <tr>
                <th>환불방법</th>
                <td colspan="5">
                	<input type="radio" name="repay" value="C"> 계좌이체
                </td>
              </tr>
              <tr>
                <th>환불계좌</th>
                <td>
                	<select name="bank">
                		<option value="">:: 선택하세요 :: </option>
                		<option value="경남은행">경남은행 </option>
                		<option value="광주은행">광주은행 </option>
                		<option value="국민은행">국민은행 </option>
                		<option value="기업은행">기업은행 </option>
                		<option value="농협">농협 </option>
                		<option value="대구은행">대구은행 </option>
                		<option value="도이치뱅크">도이치뱅크 </option>
                		<option value="부산은행">부산은행 </option>
                		<option value="산업은행">산업은행 </option>
                		<option value="상호저축은행">상호저축은행 </option>
                		<option value="새마을금고">새마을금고 </option>
                		<option value="수협중앙회">수협중앙회 </option>
                		<option value="신용협동조합">신용협동조합 </option>
                		<option value="신한은행">신한은행 </option>
                		<option value="외환은행">외환은행 </option>
                		<option value="우리은행">우리은행 </option>
                		<option value="우체국">우체국 </option>
                		<option value="전북은행">전북은행 </option>
                		<option value="제주은행">제주은행 </option>
                		<option value="하나은행">하나은행 </option>
                		<option value="한국시티은행">한국시티은행 </option>
                		<option value="HSBC">HSBC </option>
                		<option value="SC제일은행">SC제일은행 </option>
                	</select>
                </td>
                <th>계좌번호</th>
                <td>
                	<input type="text" name="account" class="input">
                </td>
                <th>예금주</th>
                <td>
                	<input type="text" name="acc_name" class="input">
                </td>
              </tr>
				<?
					}
				?>
            </table>
        	</td>
        </tr>
        <tr>
        	<td align="center" height="35">
				<button type="submit" class="h18 t3 color small round red_s" >확인</button>
				<button type="button" class="h18 t3 color small round black_s" onClick="resetCancel()">취소</button>
        	</td>
        </tr>
      </table>
	  </form>

      <table width="100%" border="0" cellspacing="0" cellpadding="0" height="38" class="">
        <tr><td height="10"></td></tr>
        <tr>
          <td align="right">
          상품합계( <b><font color="#ED1C24"><?=number_format($order_info->prd_price)?>원</font></b> )
          <?=$discount_msg?>
           + 배송비( <b><font color="#ED1C24"><?=number_format($order_info->deliver_price)?>원</font></b>)
           <?=$coupon_msg?>

          =
          <b><font color="#000000">총 결제금액 :</font> <font color="#ED1C24"><?=number_format($order_info->total_price)?>원</font></b>
          </td>
        </tr>
        <tr><td height="10"></td></tr>
      </table>

      <br>
      <form name="frm" action="order_save.php" method="post">
      <input type="hidden" name="tmp">
      <input type="hidden" name="mode" value="update">
      <input type="hidden" name="page" value="<?=$page?>">
      <input type="hidden" name="orderid" value="<?=$orderid?>">
      <input type="hidden" name="total_price" value="<?=$order_info->total_price?>">
      <input type="hidden" name="prd_info" value="<?=$prd_info?>">
      <input type="hidden" name="s_status" value="<?=$s_status?>">
      <input type="hidden" name="prev_year" value="<?=$prev_year?>">
      <input type="hidden" name="prev_month" value="<?=$prev_month?>">
      <input type="hidden" name="prev_day" value="<?=$prev_day?>">
      <input type="hidden" name="next_year" value="<?=$next_year?>">
      <input type="hidden" name="next_month" value="<?=$next_month?>">
      <input type="hidden" name="next_day" value="<?=$next_day?>">
      <input type="hidden" name="searchopt" value="<?=$searchopt?>">
      <input type="hidden" name="searchkey" value="<?=$searchkey?>">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="15%">주문번호</th>
                <td width="35%"><?=$orderid?></td>
                <th width="15%">결제방법</th>
                <td width="35%"><?=pay_method($order_info->pay_method)?></td>
              </tr>
              <tr>
                <th>주문일자</th>
                <td><?=$order_info->order_date?></td>
                <th>에스크로여부</th>
                <td><?=$order_info->escrow_check?></td>
              </tr>
              <tr>
                <th>결제계좌</th>
                <td><?=$order_info->account?></td>
                <th>입금인</th>
                <td><?=$order_info->account_name?></td>
              </tr>
              <tr>
                <th>운송장번호</th>
                <td><input name="deliver_num" type="text" value="<?=$order_info->deliver_num?>" class="input"></td>
                <th>발송일자</th>
                <td>
				<script>
					function dateclick(){
					frm.deliver_date.value=<?=date('Y').date('m').date('d').date('H').date('i')?>;
					}
				</script>
                	<input name="deliver_date" type="text" value="<?=$order_info->deliver_date?>" class="input" onclick="dateclick()">
		    			<b>발송일자 입력형식(년월일시분)</b><br>
		    			예) <?=date('Y')?>년 <?=date('m')?>월 <?=date('d')?>일 <?=date('H')?>시 <?=date('i')?>분 =
		    			<?=date('Y').date('m').date('d').date('H').date('i')?>
                </td>
              </tr>
              <tr>
                <th>처리상태</th>
                <td>
                	<? if(!strcmp($order_info->status, "OC") || !strcmp($order_info->status, "RC")) {	//주문취소,취소완료인 경우 상태변경 불가능 ?>
                	<b><font color="#ED1C24"><?=order_status($order_info->status);?></font></b>
                	<? } else { ?>
		                <select name="chg_status">
		                <option value="">----------</option>
							<?
							if($order_info->status == "" || $order_info->status == "OR"){
							?>
		                <option value="OR" <? if($order_info->status == "OR") echo "selected"; ?>>주문접수</option>
		                <option value="OY" <? if($order_info->status == "OY") echo "selected"; ?>>결제완료</option>
		                <option value="OC" <? if($order_info->status == "OC") echo "selected"; ?>>주문취소</option>
							<?
							}else{
							?>
		                <option value="OY" <? if($order_info->status == "OY") echo "selected"; ?>>결제완료</option>
		                <option value="DR" <? if($order_info->status == "DR") echo "selected"; ?>>배송준비중</option>
		                <option value="DI" <? if($order_info->status == "DI") echo "selected"; ?>>배송처리</option>
		                <option value="DC" <? if($order_info->status == "DC") echo "selected"; ?>>배송완료</option>
		                <option value="OC" <? if($order_info->status == "OC") echo "selected"; ?>>주문취소</option>
		                <option value="">----------</option>
		                <option value="RD" <? if($order_info->status == "RD") echo "selected"; ?>>취소요청</option>
		                <option value="RC" <? if($order_info->status == "RC") echo "selected"; ?>>취소완료</option>
		                <option value="CD" <? if($order_info->status == "CD") echo "selected"; ?>>교환요청</option>
		                <option value="CC" <? if($order_info->status == "CC") echo "selected"; ?>>교환완료</option>
		                <? } ?>
		                </select>
		              <? } ?>
                </td>
                <td></td>
                <td></td>
              </tr>
              <tr>
                <th>처리시간</th>
                <td colspan="3">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="inner_table">
                    <tr>
                      <th width="25%" align="center" height="25">주문접수</th>
                      <th width="25%" align="center">결제완료</th>
                      <th width="25%" align="center">배송완료</th>
                      <th width="25%" align="center">주문취소</th>
                    </tr>
                    <tr>
                      <td align="center" height="25"><? if($order_info->order_date == "0000-00-00 00:00:00") echo "-"; else echo $order_info->order_date; ?></td>
                      <td align="center"> <? if($order_info->pay_date == "0000-00-00 00:00:00") echo "-"; else echo $order_info->pay_date; ?> </td>
                      <td align="center"> <? if($order_info->send_date == "0000-00-00 00:00:00") echo "-"; else echo $order_info->send_date; ?> </td>
                      <td align="center"> <? if($order_info->cancel_date == "0000-00-00 00:00:00") echo "-"; else echo $order_info->cancel_date; ?> </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table></td>
        </tr>
      </table>

      <h4 class="top15">주문자정보</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
        <tr>
          <th width="15%">주문자명</th>
          <td width="35%"><input name="send_name" type="text" value="<?=$order_info->send_name?>" class="input"> <a href="../member/member_input.php?mode=update&id=<?=$order_info->send_id?>" target="_blank"><?=$order_info->send_id?></a></td>
          <th width="15%">이메일</th>
          <td width="35%">
		    <input name="send_email" type="text" value="<?=$order_info->send_email?>" class="input">
			<button type="button" class="h18 t3 color small round black_s" onclick="sendEmail('<?=$order_info->send_name?>','<?=$order_info->send_email?>')">발송</button>
	        </td>
        </tr>
        <tr>
          <th>전화번호</th>
          <td><input name="send_tphone" type="text" value="<?=$order_info->send_tphone?>" class="input"></td>
          <th>휴대폰</th>
          <td>
				<input name="send_hphone" type="text" value="<?=$order_info->send_hphone?>" class="input">
				<button type="button" class="h18 t3 color small round black_s" onclick="sendSms('<?=$order_info->send_name?>','<?=$order_info->send_hphone?>')">발송</button>
			</td>
        </tr>
        <tr>
          <th>우편번호</th>
          <td colspan="3">

            <input name="send_post" type="text" value="<?=$order_info->send_post?>" size="5" class="input">
	  		  <button type="button" class="h22 t3 small gray_s" onClick="searchZip();">우편번호검색</button>
          </td>
        </tr>
        <tr>
          <th>주소</th>
          <td colspan="3"><input name="send_address" type="text" value="<?=$order_info->send_address?>" size="60" class="input"></td>
        </tr>
      </table>

		<h4 class="top15">수취인정보</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
        <tr>
          <th>수취인명</th>
          <td colspan="3"><input name="rece_name" type="text" value="<?=$order_info->rece_name?>" class="input"></td>
        </tr>
        <tr>
          <th width="15%">전화번호</th>
          <td width="35%"><input name="rece_tphone" type="text" value="<?=$order_info->rece_tphone?>" class="input"></td>
          <th width="15%">휴대폰</th>
          <td width="35%"><input name="rece_hphone" type="text" value="<?=$order_info->rece_hphone?>" class="input"></td>
        </tr>
        <tr>
          <th>우편번호</th>
          <td colspan="3">

            <input name="rece_post" type="text" value="<?=$order_info->rece_post?>" size="5" class="input">
	  			<button type="button" class="h22 t3 small gray_s" onClick="searchZip2();">우편번호검색</button>
          </td>
        </tr>
        <tr>
          <th>주소</th>
          <td colspan="3"><input name="rece_address" type="text" value="<?=$order_info->rece_address?>" size="60" class="input"></td>
        </tr>
        <tr>
          <th>요청사항</th>
          <td colspan="3"><textarea name="demand" rows="6" cols="60" class="textarea" style="width:98%"><?=$order_info->demand?></textarea></td>
        </tr>
        <tr>
          <th>주문취소 사유</th>
          <td colspan="3"><textarea name="cancelmsg" rows="6" cols="60" class="textarea" style="width:98%"><?=$order_info->cancelmsg?></textarea></td>
        </tr>
        <tr>
          <th>관리자메모</th>
          <td colspan="3"><textarea name="descript" rows="6" cols="60" class="textarea" style="width:98%"><?=$order_info->descript?></textarea></td>
        </tr>
      </table>


		<?
		if(!strcmp($oper_info[tax_use], "Y")) {
			$sql = "select * from wiz_tax where orderid = '$orderid'";
			$result = mysql_query($sql) or error(mysql_error());
			$tax_info = mysql_fetch_array($result);
		?>
		<h4 class="top15">증빙서류 정보</h4>

      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
        <tr>
          <th width="15%">발급여부</th>
          <td width="85%">

            <div>
                <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                          <label><input type="radio" name="tax_type" value="N" onClick="qclick('');" <? if(!strcmp($order_info->tax_type, "N") || empty($order_iinfo->tax_type)) echo "checked" ?> /> 발행안함</label>
                          <label><input type="radio" name="tax_type" value="T" onClick="qclick('01');" <? if(!strcmp($order_info->tax_type, "T")) echo "checked" ?> /> 세금계산서 신청</label>
                          <label><input type="radio" name="tax_type" value="C" onClick="qclick('02');" <? if(!strcmp($order_info->tax_type, "C")) echo "checked" ?> /> 현금영수증 신청</label>
                          <!--font color="red" onClick="printTax('<?=$orderid?>')" style="cursor:pointer">[출력]</font-->
                        </td>
                    </tr>
                </table>
            </div>



            <div id="tax00" class="top5" style="display:<? if(strcmp($order_info->tax_type, "N")) echo "block"; else echo "none"; ?>;">
                <table border="0" cellspacing="0" cellpadding="0" class="inner_table left">
                    <tr>
                        <th width="100">발급여부</th>
                        <td width="500">
                            <input type="hidden" name="tmp_tax_pub" value="<?=$tax_info[tax_pub]?>">
                            <input type="radio" name="tax_pub" value="Y" <? if(!strcmp($tax_info[tax_pub], "Y")) echo "checked" ?>> 발급완료
                            <input type="radio" name="tax_pub" value="N" <? if(!strcmp($tax_info[tax_pub], "N") || empty($tax_info[tax_pub])) echo "checked" ?>> 발급대기
                        </td>
                    </tr>
                </table>
            </div>


            <div id="tax01" class="top5" style="display:<? if(!strcmp($order_info->tax_type, "T")) echo "block"; else echo "none"; ?>">
                <table border="0" cellspacing="0" cellpadding="0" class="inner_table left">
                    <tr>
                        <th width="100">사업자 번호</th>
                        <td colspan="3" width="500"><input type="text" name="com_num" value="<?=$tax_info[com_num]?>" class="input" size="20"></td>
                    </tr>
                    <tr>
                        <th>상 호</th>
                        <td><input type="text" name="com_name" value="<?=$tax_info[com_name]?>" class="input"></td>
                        <th width="100">대표자</th>
                        <td><input type="text" name="com_owner" value="<?=$tax_info[com_owner]?>" class="input"></td>
                    </tr>
                    <tr>
                        <th>사업장 소재지</th><td colspan="3"><input type="text" name="com_address" value="<?=$tax_info[com_address]?>" class="input" size="50"></td>
                    </tr>
                    <tr>
                        <th>업 태</th><td><input type="text" name="com_kind" value="<?=$tax_info[com_kind]?>" class="input"></td>
                        <th>종 목</th><td><input type="text" name="com_class" value="<?=$tax_info[com_class]?>" class="input"></td>
                    </tr>
                    <tr>
                        <th>전화번호</th><td><input type="text" name="com_tel" value="<?=$tax_info[com_tel]?>" class="input"></td>
                        <th>이메일</th><td><input type="text" name="com_email" value="<?=$tax_info[com_email]?>" class="input"></td>
                    </tr>
                </table>
            </div>


            <div id="tax02" class="top5" style="display:<? if(!strcmp($order_info->tax_type, "C")) echo "block"; else echo "none"; ?>">
                <table border="0" cellspacing="0" cellpadding="0" class="inner_table left">
                    <tr>
                        <th width="100">발급사유</th>
                        <td width="500">
                              <input type="radio" name="cash_type" value="C" <? if(!strcmp($tax_info[cash_type], "C")) echo "checked" ?>> 사업자 지출증빙용
                              <input type="radio" name="cash_type" value="P" <? if(!strcmp($tax_info[cash_type], "P")) echo "checked" ?>> 개인소득 공제용
                        </td>
                    </tr>
                    <tr>
                        <th>신청정보</th>
                        <td>
                              <input type="radio" name="cash_type2" value="CARDNUM" <? if(!strcmp($tax_info[cash_type2], "CARDNUM")) echo "checked" ?>> 현금영수증 카드번호
                              <input type="radio" name="cash_type2" value="COMNUM" <? if(!strcmp($tax_info[cash_type2], "COMNUM")) echo "checked" ?>> 사업자 등록번호
                              <input type="radio" name="cash_type2" value="HPHONE" <? if(!strcmp($tax_info[cash_type2], "HPHONE")) echo "checked" ?>> 휴대전화번호
                              <input type="radio" name="cash_type2" value="RESNO" <? if(!strcmp($tax_info[cash_type2], "RESNO")) echo "checked" ?>> 주민등록번호<br>
                              <input type="text" name="cash_info" value="<?=$tax_info[cash_info]?>" class="input" size="30">
                        </td>
                    </tr>
                    <tr>
                        <th>신청자명</th><td colspan="3"><input type="text" name="cash_name" value="<?=$tax_info[cash_name]?>" class="input" size="30"></td>
                    </tr>
                </table>
            </div>

          </td>
        </tr>
      </table>
		<? } ?>

		<? if(!strcmp($oper_info[pay_agent], "KCP") && strcmp($order_info->paymethod, "PC")) { ?>
		<h4 class="top15">현금영수증 정보</h4> KCP 상점정보에 등록된 현금영수증 정보를 입력하세요.
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="inner_table left">
        <tr>
          <th width="15%">발급여부</th>
          <td width="35%">
          	<input type="text" name="id_info" value="<?=$order_info->id_info?>" class="input">
          </td>
          <th width="15%">국세청 승인여부</th>
          <td width="35%">
          	<input type="radio" name="bill_yn" value="Y" <? if(!strcmp($order_info->bill_yn, "Y")) { ?> checked <? } ?>> 승인
          	<input type="radio" name="bill_yn" value="N" <? if(!strcmp($order_info->bill_yn, "N")) { ?> checked <? } ?>> 미승인
          </td>

        </tr>
		<tr>
		 <th width="15%">승인번호</th>
          <td width="85%" colspan="3">
          	<input type="text" name="authno" value="<?=$order_info->authno?>" class="input">
          </td>
		</tr>

      </table>
		<? } ?>

      <br>
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="33%"></td>
          <td width="33%" align="center">
				<button type="button" class="b h28 t5 color blue_big" onclick="this.form.submit()">확인</button>
				<button type="button" class="b h28 t5 color gray_big" onClick="document.location='order_list.php?<?=$param?>'">목록</button>
          </td>
          <td width="33%" align="right">
				<button type="button" class="b h28 t5 color blue_big" onClick="orderPrint()">인쇄</button>
		  </td>
        </tr>
      </table>
	  </form>

<? include "../foot.php"; ?>
<style>
.option_list { margin: 8px 0; padding: 0 16px; background: #fafafa; }
.option_list th { position: relative; }
.option_list td { border: 0 !important; }
.option_list span{ position: absolute; width: 100%; overflow: hidden; white-space:nowrap; text-overflow: ellipsis; top: 0; }
.option_list table { padding: 8px 0; border-top: 1px solid #e5e5e5; }
.option_list table:first-child { border: 0; }
</style>
