<? include_once "../../common.php"; ?>
<? include_once "../../inc/admin_check.php"; ?>
<? include_once "../../inc/site_info.php"; ?>
<? include_once "../../inc/oper_info.php"; ?>
<? include_once "../../inc/prd_info.php"; ?>
<? include "../head.php"; ?>
<?

array_push($_COMPONENTS, "/component/admin/shop/product/option/select-option.php");
// array_push($_COMPONENTS, "/adm/manage/components/shop/product/option/supply-option.php");
array_push($_COMPONENTS, "/component/admin/shop/product/option/supply-option2.php");
array_push($_COMPONENTS, "/component/admin/shop/delivery/product-delivery.php");

// 페이지 파라메터 (검색조건이 변하지 않도록)
//--------------------------------------------------------------------------------------------------
$param = "dep_code=$dep_code&dep2_code=$dep2_code&dep3_code=$dep3_code";
$param .= "&special=$special&display=$display&searchopt=$searchopt&searchkey=$searchkey&page=$page&shortpage=$shortpage";
//--------------------------------------------------------------------------------------------------

if($shortpage == "Y") $listpage_url = "prd_shortage.php";
else $listpage_url = "prd_list.php";

$imgpath = WIZHOME_PATH."/data/prdimg";

if(empty($mode)) $mode = "insert";

if($mode == "insert"){

	$catcode01 = $dep_code;
   $catcode02 = $dep_code.$dep2_code;
   $catcode03 = $dep_code.$dep2_code.$dep3_code;
   $prd_row->stock = "100";


// 상품정보를 가져온다.
}else if($mode == "update"){

   $sql = "select wp.*, wc.idx, wc.catcode from wiz_product wp, wiz_cprelation wc where wp.prdcode = '$prdcode' and wp.prdcode = wc.prdcode";
   $result = mysql_query($sql) or error(mysql_error());
   $prd_row = mysql_fetch_object($result);

   $relidx = $prd_row->idx;

   $catcode01 = substr($prd_row->catcode,0,3);
   $catcode02 = substr($prd_row->catcode,0,6);
   $catcode03 = substr($prd_row->catcode,0,9);

}

?>
<script language="JavaScript" type="text/javascript">
<!--
  var loding = false;
  var prd_class = new Array();
<?
   $no = 0;
   $sql = "select catcode, catname, depthno from wiz_category order by priorno01, priorno02, priorno03 asc";
   $result = mysql_query($sql) or error(mysql_error());
   $total = mysql_num_rows($result);
   while($row = mysql_fetch_object($result)){

      $code01 = substr($row->catcode,0,3);
      $code02 = substr($row->catcode,0,6);
      $code03 = substr($row->catcode,0,9);

      if($row->depthno == 1){ $catcode = $code01; $parent = 0; }
      if($row->depthno == 2){ $catcode = $code02; $parent = $code01; }
      if($row->depthno == 3){ $catcode = $code03; $parent = $code02; }
?>

  prd_class[<?=$no?>] = new Array();
  prd_class[<?=$no?>][0] = "<?=$catcode?>";
  prd_class[<?=$no?>][1] = "<?=$row->catname?>";
  prd_class[<?=$no?>][2] = "<?=$parent?>";
  prd_class[<?=$no?>][3] = "<?=$row->depthno?>";

<?
   	$no++;
   }
?>
var tno = <?=$total?>;

function setClass01(){

  var arrayClass = eval("document.frm.class01");
  var arrayClass1 = eval("document.frm.class02");
  var arrayClass2 = eval("document.frm.class03");

  arrayClass.options[0] = new Option(":: 대분류 ::","");
  arrayClass1.options[0] = new Option(":: 중분류 ::","");
  arrayClass2.options[0] = new Option(":: 소분류 ::","");

  for(no=0,sno=1 ; no < tno ; no++){
	  if(prd_class[no][3]=='1'){
		 arrayClass.options[sno] = new Option(prd_class[no][1],prd_class[no][0]);
		 sno++;
	  }
  }
}

function changeClass01(){

  var arrayClass = eval("document.frm.class01");
  var arrayClass1 = eval("document.frm.class02");
  var arrayClass2 = eval("document.frm.class03");

  var selidx = arrayClass.selectedIndex;
  var selvalue = arrayClass.options[selidx].value;

  arrayClass1.options.length=0;
  arrayClass2.options.length=0;
  arrayClass1.options[0] = new Option(":: 중분류 ::","");
  arrayClass2.options[0] = new Option(":: 소분류 ::","");

  for(no=0,sno=1 ; no < tno ; no++){
	  if(prd_class[no][3]=='2' && prd_class[no][2]==selvalue){
		 arrayClass1.options[sno] = new Option(prd_class[no][1],prd_class[no][0]);
		 sno++;
	  }
  }

}

function changeClass02(){

  var arrayClass1 = eval("document.frm.class02");
  var arrayClass2 = eval("document.frm.class03");

  var selidx = arrayClass1.selectedIndex;
  var selvalue = arrayClass1.options[selidx].value;

  arrayClass2.options.length=0;
  arrayClass2.options[0] = new Option(":: 소분류 ::","");

  for(no=0,sno=1 ; no < tno ; no++){
	  if(prd_class[no][3]=='3' && prd_class[no][2]==selvalue){
		 arrayClass2.options[sno] = new Option(prd_class[no][1],prd_class[no][0]);
		 sno++;
	  }
  }

}

function changeClass03(){
}

// 상품카테고리 설정
function setCategory(){

  var arrayClass01 = eval("document.frm.class01");
  var arrayClass02 = eval("document.frm.class02");
  var arrayClass03 = eval("document.frm.class03");

  for(no=1; no < arrayClass01.length; no++){
    if(arrayClass01.options[no].value == '<?=$catcode01?>'){
      arrayClass01.options[no].selected = true;
      changeClass01();
    }
  }

  for(no=1; no < arrayClass02.length; no++){
    if(arrayClass02.options[no].value == '<?=$catcode02?>'){
      arrayClass02.options[no].selected = true;
      changeClass02();
    }
  }

  for(no=1; no < arrayClass03.length; no++){
    if(arrayClass03.options[no].value == '<?=$catcode03?>')
      arrayClass03.options[no].selected = true;
  }

}

function inputCheck(frm){

   if(loding == false){
   	alert("상품정보를 가져오고 있습니다. 잠시후 재시도 하세요");
   	return false;
   }
	if(frm.prdname.value == ""){
		alert("상품명을 입력하세요");
		frm.prdname.focus();
		return false;
	}
	if(frm.sellprice.value == ""){
		alert("판매가를 입력하세요");
		frm.sellprice.focus();
		return false;
	}

	if(window.select_option){
		frm.select_subjects.value = select_option.subjects.join(",");
		frm.select_options.value  = JSON.stringify(select_option.options);
	}

	if(window.supply_option){
		frm.supply_subjects.value = supply_option.subjects.join(",");
		frm.supply_options.value  = JSON.stringify(supply_option.options);
	}

	content.outputBodyHTML();
	content_m.outputBodyHTML();

/*
	var optvalue = "";
	var length = frm.optcode_tmp.length;
	for(ii = 0; ii < length; ii++){ optvalue += frm.optcode_tmp.options[ii].value+"^^"; }
	frm.optcode.value = optvalue;
*/
}

//해당 이미지를 삭제한다.
function deleteImage(prdcode, prdimg, imgpath){
	if(imgpath == ""){
		alert("삭제할 이미지가 없습니다.");
		return;
	}else{
	if(confirm("이미지를 삭제하시겠습니까?"))
		document.location = "prd_save.php?mode=delete_image&prdcode="+prdcode+"&prdimg="+prdimg+"&imgpath="+imgpath;
	}
	return;
}


function prdlayCheck(){
	<?
	if(@file($imgpath."/".$prd_row->prdimg_S2)) echo "document.frm.prdlay_check2.checked = true; prdlay2.style.display='';";
	if(@file($imgpath."/".$prd_row->prdimg_S3)) echo "document.frm.prdlay_check3.checked = true; prdlay3.style.display='';";
	if(@file($imgpath."/".$prd_row->prdimg_S4)) echo "document.frm.prdlay_check4.checked = true; prdlay4.style.display='';";
	if(@file($imgpath."/".$prd_row->prdimg_S5)) echo "document.frm.prdlay_check5.checked = true; prdlay5.style.display='';";
	?>
}

function lodingComplete(){
	loding = true;
}

function prdCategory(){
  var url = "prd_catlist.php?prdcode=<?=$prdcode?>";
  window.open(url, "prdCategory", "height=330, width=600, menubar=no, scrollbars=yes, resizable=yes, toolbar=no, status=no, left=150, top=100");
}

function prdIcon(){
	var url = "prd_icon.php";
	window.open(url, "prdIcon", "height=250, width=450, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, left=150, top=100");
}

function setImgsize(){
	var url = "prd_imgsize.php";
   window.open(url, "setImgsize", "height=250, width=300, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, left=150, top=100");
}


// 상품별쿠폰 발급회원
function popMycoupon(prdcode){
	var url = "shop_mycoupon.php?prdcode=" + prdcode;
	window.open(url,"MyCouponList","height=400, width=600, menubar=no, scrollbars=yes, resizable=no, toolbar=no, status=no, top=100, left=100");
}



function prdFocus(){
frm.prdname.focus();
}


//-->
</script>

<style>
.button { padding: 8px 12px; }
.button.blue  { color: #fff; background: #4999de; border: 1px solid #3384c9; }
.button.black { color: #fff; background: #333; border: 1px solid #222; }
</style>

 <div id="location">HOME > 상품관리</div>
<div id="S_contents">
<h3>상품등록<span>상품 상세정보를 설정합니다.</span></h3>

		<h4>기본정보</h4>
      <form name="frm" action="prd_save.php?<?=$param?>" method="post" onSubmit="return inputCheck(this)" enctype="multipart/form-data">
      <input type="hidden" name="tmp">
      <input type="hidden" name="mode" value="<?=$mode?>">
      <input type="hidden" name="prdcode" value="<?=$prdcode?>">
      <input type="hidden" name="relidx" value="<?=$relidx?>">
			<input type="hidden" name="select_subjects">
			<input type="hidden" name="supply_subjects">
			<input type="hidden" name="select_options">
			<input type="hidden" name="supply_options">

      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th>상품분류</th>
                <td colspan="3">
                <select name="class01" onChange="changeClass01();">
                </select>
                <select name="class02" onChange="changeClass02();">
                </select>
                <select name="class03" onChange="changeClass03();">
                </select>&nbsp;
                <? if($mode == "update"){ ?>
					<button type="button" class="h18 t3 color small round black_s" onclick="prdCategory()">분류추가</button>
                <? } ?>
                </td>
              </tr>
              <tr>
                <th>상품그룹</th>
                <td colspan="3">
                  <input type="checkbox" name="new" value="Y" <? if($prd_row->new == "Y") echo "checked"; ?>> <img src="/adm/images/icon_new.gif" border="0"> &nbsp;
                  <input type="checkbox" name="best" value="Y" <? if($prd_row->best == "Y") echo "checked"; ?>> <img src="/adm/images/icon_best.gif" border="0"> &nbsp;
                  <input type="checkbox" name="popular" value="Y" <? if($prd_row->popular == "Y") echo "checked"; ?>> <img src="/adm/images/icon_hit.gif" border="0"> &nbsp;
                  <input type="checkbox" name="recom" value="Y" <? if($prd_row->recom == "Y") echo "checked"; ?>> <img src="/adm/images/icon_rec.gif" border="0"> &nbsp;
                  <input type="checkbox" name="sale" value="Y" <? if($prd_row->sale == "Y") echo "checked"; ?>> <img src="/adm/images/icon_sale.gif" border="0"> &nbsp;
                </td>
              </tr>
              <tr>
                <th>상품아이콘</th>
                <td colspan="3">
                	<table cellspacing=0 cellpadding=0><tr><td>
                	<table cellspacing=0 cellpadding=0>
                	<?
                	$prdicon= explode("/",$prd_row->prdicon);
                  for($ii=0; $ii<count($prdicon); $ii++){
                     $prdicon_list[$prdicon[$ii]] = true;
                  }

									$no = 0;

									// 업로드 디렉토리 생성
									if(!is_dir('../../data/prdicon')) mkdir('../../data/prdicon', 0707);

									if($handle = opendir('../../data/prdicon')){
										while(false !== ($file_name = readdir($handle))){
											if($file_name != "." && $file_name != ".."){
												if($no%7 == 0) echo "<tr>";
									?>
                  <td><input type="checkbox" name="prdicon[]" value="<?=$file_name?>" <? if($prdicon_list["$file_name"]==true) echo "checked";?>></td>
                  <td>&nbsp;<img src="/adm/data/prdicon/<?=$file_name?>" border="0"></td>
                  <?
												$no++;
											}
										}
										closedir($handle);
									}
									?>
                  </table></td>
                  <td>&nbsp;
					<button type="button" class="h22 t4 small icon gray" onClick="prdIcon()"><span class="icon_plus"></span>아이콘관리</button>
				  </td>
                  </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <th>상품명 <font color="red">*</font></th>
                <td colspan="3">
                <input type="text" name="prdname" value="<?=$prd_row->prdname?>" size="60" class="input">
                </td>
              </tr>
			  <tr>
				  <th width="15%">상품명 (영문)</th>
				  <td colspan="3">
					  <input name="prdcom" type="text" value="<?=$prd_row->prdcom?>" size="60" class="input">
				  </td>
				</tr>
				<tr>
				 <th width="15%">용량 (ml)</th>
				 <td colspan="3">
					 <input name="origin" type="text" value="<?=$prd_row->origin?>" size="60" class="input">
				 </td>
			   </tr>
              <?/*<tr>
                <th width="15%">제조사</th>
                <td width="35%">
                	<input name="prdcom" type="text" value="<?=$prd_row->prdcom?>" class="input">
                	<select onChange="this.form.prdcom.value = this.value">
                	<option value="">::선택::</option>
                	<?
                	$sql = "select distinct prdcom from wiz_product where prdcom != '' order by prdcom asc";
                	$result = mysql_query($sql);
                	while($row = mysql_fetch_object($result)){
                	?>
                	<option value="<?=$row->prdcom?>"><?=$row->prdcom?></option>
                	<?
                	}
                	?>
                	<select>
                </td>
                <th width="15%">원산지</th>
                <td width="35%">
                	<input name="origin" type="text" value="<?=$prd_row->origin?>" class="input">
                	<select onChange="this.form.origin.value = this.value">
                	<option value="">::선택::</option>
                	<?
                	$sql = "select distinct origin from wiz_product where origin != '' order by origin asc";
                	$result = mysql_query($sql);
                	while($row = mysql_fetch_object($result)){
                	?>
                	<option value="<?=$row->origin?>"><?=$row->origin?></option>
                	<?
                	}
                	?>
                	<select>
                </td>
              </tr>*/?>
              <tr>
                <?/*<th>브랜드</th>
                <td>
                	<select name="brand" style="width:130px">
                	<option value="">::선택::</option>
                	<?
                	$sql = "select idx, brdname from wiz_brand where brduse != 'N' order by priorno asc";
                	$result = mysql_query($sql);
                	while($row = mysql_fetch_object($result)){
                	?>
                	<option value="<?=$row->idx?>" <? if(!strcmp($row->idx, $prd_row->brand)) echo "selected" ?>><?=$row->brdname?></option>
                	<?
                	}
                	?>
                	<select>
                </td>*/?>
                <th>상품진열</th>
                <td>
                <input type="radio" name="showset" value="Y" <? if($prd_row->showset == "Y" || empty($prd_row->showset)) echo "checked"; ?>> 진열함&nbsp;
                <input type="radio" name="showset" value="N" <? if($prd_row->showset == "N") echo "checked"; ?>> 진열안함
                </td>
              </tr>
							<tr>
								<!-- mjs -->
								<!-- <th>공제분류</th> -->
								<!-- <td colspan="3"> -->
									<!-- <input type="radio" name="deductset" value="normal" <? if($prd_row->deductset == "Y" || empty($prd_row->deductset)) echo "checked"; ?>> 일반상품&nbsp; -->
	                <!-- <input type="radio" name="deductset" value="culture" <? if($prd_row->deductset == "N") echo "checked"; ?>> 도서&문화상품 -->
								<!-- </td> -->
							</tr>

              <input type="hidden" name="prior" value="<? if(empty($prd_row->prior)) echo date(ymdHis); else echo $prd_row->prior; ?>" maxlength="12" class="input">
              <!--tr>
                <td>우선순위</td>
                <td>
                <input type="text" name="prior" value="<? if(empty($prd_row->prior)) echo date(ymdHis); else echo $prd_row->prior; ?>" maxlength="12" class="input">
                </td>
                <td></td>
                <td>

                </td-->
                <!--td>선호도</td>
                <td>
                <select name="prefer">
                <option value="1" <? if($prd_row->prefer == "1") echo "selected"; ?>>별1
                <option value="2" <? if($prd_row->prefer == "2") echo "selected"; ?>>별2
                <option value="3" <? if($prd_row->prefer == "3" || $prd_row->prefer == "") echo "selected"; ?>>별3
                <option value="4" <? if($prd_row->prefer == "4") echo "selected"; ?>>별4
                <option value="5" <? if($prd_row->prefer == "5") echo "selected"; ?>>별5
                </select>
                </td//-->
              <!--/tr-->
            </table>
          </td>
        </tr>
      </table>
      <!--table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr><td height="2"></td></tr>
        <tr>
          <td width="17%"></td>
          <td>(숫자가 클수록 진열시 앞에 나옵니다. 최대 12자리) </td>
        </tr>
      </table-->

      <br>
			<h4>상품정보</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="2" class="table_basic">
			  <tr>
			    <th width="15%">상품정보</th>
			    <td>
          	<input type="radio" name="info_use" onClick="if(this.checked==true) addinfo.style.display='none';" value="N" <? if($prd_row->info_use == "" || $prd_row->info_use == "N") echo "checked"; ?>> 사용안함
          	<input type="radio" name="info_use" onClick="if(this.checked==true) addinfo.style.display='';" value="Y" <? if($prd_row->info_use == "Y") echo "checked"; ?>> 사용함
          </td>
			  </tr>
			</table>
      <div id="addinfo" style=display:<? if($prd_row->info_use == "" || $prd_row->info_use == "N") echo "none"; else echo "show"; ?>>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="15%">상품정보</td>
                <td width="85%">

                	<table border="1" cellspacing="5" cellpadding="0">
                		<tr>
                			<td></td>
                			<td>상품가격</td>
                			<td>1,000원 (예시)</td>
                		</tr>
                		<tr>
                			<td>1.</td>
                			<td><input name="info_name1" type="text" value="<?=$prd_row->info_name1?>" size="15" class="input"></td>
                			<td><input name="info_value1" type="text" value="<?=$prd_row->info_value1?>" size="20" class="input"></td>
                			<td width="60"></td>
                			<td>4.</td>
                			<td><input name="info_name4" type="text" value="<?=$prd_row->info_name4?>" size="15" class="input"></td>
                			<td><input name="info_value4" type="text" value="<?=$prd_row->info_value4?>" size="20" class="input"></td>
                		</tr>
                		<tr>
                			<td>2.</td>
                			<td><input name="info_name2" type="text" value="<?=$prd_row->info_name2?>" size="15" class="input"></td>
                			<td><input name="info_value2" type="text" value="<?=$prd_row->info_value2?>" size="20" class="input"></td>
                			<td></td>
                			<td>5.</td>
                			<td><input name="info_name5" type="text" value="<?=$prd_row->info_name5?>" size="15" class="input"></td>
                			<td><input name="info_value5" type="text" value="<?=$prd_row->info_value5?>" size="20" class="input"></td>
                		</tr>
                		<tr>
                			<td>3.</td>
                			<td><input name="info_name3" type="text" value="<?=$prd_row->info_name3?>" size="15" class="input"></td>
                			<td><input name="info_value3" type="text" value="<?=$prd_row->info_value3?>" size="20" class="input"></td>
                			<td></td>
                			<?/*<td>6.</td>
                			<td><input name="info_name6" type="text" value="<?=$prd_row->info_name6?>" size="15" class="input"></td>
                			<td><input name="info_value6" type="text" value="<?=$prd_row->info_value6?>" size="20" class="input"></td>*/?>
							<td></td>
							<td></td>
							<td></td>
                		</tr>
                	</table>

                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <br>
      </div>

		<h4 class="top20">가격및재고</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="15%">판매가 <font color="red">*</font></th>
                <td width="35%"><input name="sellprice" type="text" value="<?=$prd_row->sellprice?>" class="input"></td>
                <th width="15%">정가</th>
                <td width="35%"><input name="conprice" type="text" value="<?=$prd_row->conprice?>" class="input"><br>* <s>5,000</s>로 표기됨, 0 입력시 표기안됨 </td>
              </tr>
              <tr>
                <th>재고량</th>
                <td colspan="3">
                	<input type="radio" name="shortage" value="Y" <? if($prd_row->shortage == "Y") echo "checked"; ?>>&nbsp;품절 &nbsp;
                	<input type="radio" name="shortage" value="N" <? if($prd_row->shortage == "N" || empty($prd_row->shortage)) echo "checked"; ?>>무제한
                	<input type="radio" name="shortage" value="S" <? if($prd_row->shortage == "S") echo "checked"; ?>>수량
                	<input name="stock" type="text" size="5" value="<?=$prd_row->stock?>" class="input">개<br>
                	수량을 지정하면 재고가 없을시 판매중지
                </td>
              </tr>
              <tr>
              	<th>판매가대체문구</th>
                <td colspan="3">
                	<input name="strprice" type="text" value="<?=$prd_row->strprice?>" class="input">
                	판매가대체문구를 입력하면 가격대신 입력한 문구가 보이며 구매가 불가능합니다.
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>


      <h4 class="top20">배송비</h4>
			<div>
				<product-delivery v-bind:prdcode="prdcode"></product-delivery>
			</div>

		<h4 class="top20">상품옵션</h4>

		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
			<tr>
				<th width="15%">선택옵션</th>
				<td>
					<select-option v-bind:prdcode="prdcode"></select-option>
				</td>
			</tr>
			<tr>
				<th width="15%">옵션 추가하기</th>
				<td>
					<supply-option v-bind:prdcode="prdcode"></supply-option>
				</td>
			</tr>
    </table>

		<table width="100%" border="0" cellspacing="0" cellpadding="2" class="top20">
			<tr>
			<td width="15%"><h4>상품사진</h4></td>
			<td>
			<input type="checkbox" name="prdlay_check2" onClick="if(this.checked==true) prdlay2.style.display=''; else prdlay2.style.display='none';"><font color="red">이미지추가2</font>
			<input type="checkbox" name="prdlay_check3" onClick="if(this.checked==true) prdlay3.style.display=''; else prdlay3.style.display='none';"><font color="red">이미지추가3</font>
			<input type="checkbox" name="prdlay_check4" onClick="if(this.checked==true) prdlay4.style.display=''; else prdlay4.style.display='none';"><font color="red">이미지추가4</font>
			<input type="checkbox" name="prdlay_check5" onClick="if(this.checked==true) prdlay5.style.display=''; else prdlay5.style.display='none';"><font color="red">이미지추가5</font> &nbsp; &nbsp;
			<button style="border:0" type="button" class="h18 t3 color small round black_s" onClick="setImgsize();">이미지사이즈설정</button>
			</td>
			</tr>
		</table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="75%">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="20%">원본 이미지</th>
                <td width="80%" colspan="3">
									<!-- <input type="file" name="realimg" class="input"> -->
									<div class="input-file">
										<input type="text" readonly="readonly" class="file-name" />
										<label class="file-label" for="file-realimg">파일 선택</label>
										<input id="file-realimg" class="file-upload" name="realimg" type="file" class="input" />
									</div>
									[GIF, JPG, PNG]<br>원본이미지를 등록하면 나머지 이미지가 자동생성 됩니다.
								</td>
              </tr>
              <tr>
                <th>
                  상품목록 이미지 <font color="red">*</font><br>
                  &nbsp;&nbsp;⇒ 크기 : <?=$oper_info[prdimg_R]?> x <?=$oper_info[prdimg_R]?></th>
                <td colspan="3">
                <!-- <input type="file" name="prdimg_R" class="input"> -->
								<div class="input-file">
									<input type="text" readonly="readonly" class="file-name" />
									<label class="file-label" for="file-prdimg_R">파일 선택</label>
									<input id="file-prdimg_R" class="file-upload" name="prdimg_R" type="file" class="input" />
								</div>

                <? if( @file($imgpath."/".$prd_row->prdimg_R) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_R?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_R?>" target="_blank" onMouseOver="document.prdimg1.src='../../data/prdimg/<?=$prd_row->prdimg_R?>';"><?=$prd_row->prdimg_R?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  축소이미지 이미지1<br>
                  &nbsp;&nbsp;⇒ 크기 : <?=$oper_info[prdimg_S]?> x <?=$oper_info[prdimg_S]?></th>
                <td colspan="3">
                <!-- <input type="file" name="prdimg_S1" class="input"> -->
								<div class="input-file">
									<input type="text" readonly="readonly" class="file-name" />
									<label class="file-label" for="file-prdimg_S1">파일 선택</label>
									<input id="file-prdimg_S1" class="file-upload" name="prdimg_S1" type="file" class="input" />
								</div>

                <? if( @file($imgpath."/".$prd_row->prdimg_S1) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_S1?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_S1?>" target="_blank" onMouseOver="document.prdimg1.src='../../data/prdimg/<?=$prd_row->prdimg_S1?>';"><?=$prd_row->prdimg_S1?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  제품상세 이미지1 <font color="red">*</font><br>
                  &nbsp;&nbsp;⇒ 크기 : <?=$oper_info[prdimg_M]?> x <?=$oper_info[prdimg_M]?></th>
                <td colspan="3">
                <!-- <input type="file" name="prdimg_M1" class="input"> -->
								<div class="input-file">
									<input type="text" readonly="readonly" class="file-name" />
									<label class="file-label" for="file-prdimg_M1">파일 선택</label>
									<input id="file-prdimg_M1" class="file-upload" name="prdimg_M1" type="file" class="input" />
								</div>

                <? if( @file($imgpath."/".$prd_row->prdimg_M1) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_M1?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_M1?>" target="_blank" onMouseOver="document.prdimg1.src='../../data/prdimg/<?=$prd_row->prdimg_M1?>';"><?=$prd_row->prdimg_M1?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  확대 이미지1 <font color="red">*</font><br>
                  &nbsp;&nbsp;⇒ 크기 : <?=$oper_info[prdimg_L]?> x <?=$oper_info[prdimg_L]?></th>
                <td colspan="3">
                <!-- <input type="file" name="prdimg_L1" class="input"> -->
								<div class="input-file">
									<input type="text" readonly="readonly" class="file-name" />
									<label class="file-label" for="file-prdimg_L1">파일 선택</label>
									<input id="file-prdimg_L1" class="file-upload" name="prdimg_L1" type="file" class="input" />
								</div>

                <? if( @file($imgpath."/".$prd_row->prdimg_L1) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_L1?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_L1?>" target="_blank" onMouseOver="document.prdimg1.src='../../data/prdimg/<?=$prd_row->prdimg_L1?>';"><?=$prd_row->prdimg_L1?></a>)
                <? } ?>

                </td>
              </tr>
            </table>
          </td>
          <td width="25%" height="100">
            <table width="100%" height="100%" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <td align="center" bgcolor="#ffffff">
                <?
                if(@file($imgpath."/".$prd_row->prdimg_R))
                	echo "<img src='../../data/prdimg/$prd_row->prdimg_R' name='prdimg1' width='100'>";
                else
                	echo "No<br>Image";
					 			?>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <div id="prdlay2" style="display:none">
      <table width="100%" height="10" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td></td>
        </tr>
      </table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="75%">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="20%">원본 이미지</th>
                <td width="80%" colspan="3"><input type="file" name="realimg2" class="input"></td>
              </tr>
              <tr>
                <th>
                  축소 이미지2</th>
                <td colspan="3">
                <input type="file" name="prdimg_S2" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_S2) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_S2?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_S2?>" target="_blank" onMouseOver="document.prdimg2.src='../../data/prdimg/<?=$prd_row->prdimg_S2?>';"><?=$prd_row->prdimg_S2?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  상세 이미지2</th>
                <td colspan="3">
                <input type="file" name="prdimg_M2" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_M2) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_M2?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_M2?>" target="_blank" onMouseOver="document.prdimg2.src='../../data/prdimg/<?=$prd_row->prdimg_M2?>';"><?=$prd_row->prdimg_M2?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  확대 이미지2</th>
                <td colspan="3">
                <input type="file" name="prdimg_L2" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_L2) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_L2?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_L2?>" target="_blank" onMouseOver="document.prdimg2.src='../../data/prdimg/<?=$prd_row->prdimg_L2?>';"><?=$prd_row->prdimg_L2?></a>)
                <? } ?>

                </td>
              </tr>
            </table>
          </td>
          <td width="25%" height="100">
            <table width="100%" height="100%" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <td align="center" bgcolor="#ffffff">
                <?
                if(@file($imgpath."/".$prd_row->prdimg_M2))
                	echo "<img src='../../data/prdimg/$prd_row->prdimg_M2' name='prdimg2' width='100'>";
                else
                	echo "No<br>Image";
					 ?>
					 </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      </div>
      <div id="prdlay3" style=display:none>
      <table width="100%" height="10" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td></td>
        </tr>
      </table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="75%">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="20%">원본 이미지</th>
                <td width="80%" colspan="3"><input type="file" name="realimg3" class="input"></td>
              </tr>
              <tr>
                <th>
                  축소 이미지3</th>
                <td colspan="3">
                <input type="file" name="prdimg_S3" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_S3) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_S3?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_S3?>" target="_blank" onMouseOver="document.prdimg3.src='../../data/prdimg/<?=$prd_row->prdimg_S3?>';"><?=$prd_row->prdimg_S3?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  상세 이미지3</th>
                <td colspan="3">
                <input type="file" name="prdimg_M3" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_M3) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_M3?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_M3?>" target="_blank" onMouseOver="document.prdimg3.src='../../data/prdimg/<?=$prd_row->prdimg_M3?>';"><?=$prd_row->prdimg_M3?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  확대 이미지3</th>
                <td colspan="3">
                <input type="file" name="prdimg_L3" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_L3) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_L3?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_L3?>" target="_blank" onMouseOver="document.prdimg3.src='../../data/prdimg/<?=$prd_row->prdimg_L3?>';"><?=$prd_row->prdimg_L3?></a>)
                <? } ?>

                </td>
              </tr>
            </table>
          </td>
          <td width="25%" height="100">
            <table width="100%" height="100%" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <td align="center" bgcolor="#ffffff">
                <?
                if(@file($imgpath."/".$prd_row->prdimg_M3))
                	echo "<img src='../../data/prdimg/$prd_row->prdimg_M3' name='prdimg3' width='100'>";
                else
                	echo "No<br>Image";
					 ?>
					 </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      </div>
      <div id="prdlay4" style=display:none>
      <table width="100%" height="10" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td></td>
        </tr>
      </table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="75%">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="20%">원본 이미지</th>
                <td width="80%" colspan="3"><input type="file" name="realimg4" class="input"></td>
              </tr>
              <tr>
                <th>
                  축소 이미지4</th>
                <td colspan="3">
                <input type="file" name="prdimg_S4" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_S4) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_S4?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_S4?>" target="_blank" onMouseOver="document.prdimg4.src='../../data/prdimg/<?=$prd_row->prdimg_S4?>';"><?=$prd_row->prdimg_S4?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  상세 이미지4</th>
                <td colspan="3">
                <input type="file" name="prdimg_M4" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_M4) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_M4?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_M4?>" target="_blank" onMouseOver="document.prdimg4.src='../../data/prdimg/<?=$prd_row->prdimg_M4?>';"><?=$prd_row->prdimg_M4?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  확대 이미지4</th>
                <td colspan="3">
                <input type="file" name="prdimg_L4" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_L4) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_L4?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_L4?>" target="_blank" onMouseOver="document.prdimg4.src='../../data/prdimg/<?=$prd_row->prdimg_L4?>';"><?=$prd_row->prdimg_L4?></a>)
                <? } ?>

                </td>
              </tr>
            </table>
          </td>
          <td width="25%" height="100">
            <table width="100%" height="100%" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <td align="center" bgcolor="#ffffff">
                <?
                if(@file($imgpath."/".$prd_row->prdimg_M4))
                	echo "<img src='../../data/prdimg/$prd_row->prdimg_M4' name='prdimg4' width='100'>";
                else
                	echo "No<br>Image";
					 ?>
					 </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      </div>
      <div id="prdlay5" style=display:none>
      <table width="100%" height="10" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td></td>
        </tr>
      </table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="75%">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="20%">원본 이미지</th>
                <td width="80%" colspan="3"><input type="file" name="realimg5" class="input"></td>
              </tr>
              <tr>
                <th>
                  축소 이미지5</th>
                <td colspan="3">
                <input type="file" name="prdimg_S5" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_S5) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_S5?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_S5?>" target="_blank" onMouseOver="document.prdimg5.src='../../data/prdimg/<?=$prd_row->prdimg_S5?>';"><?=$prd_row->prdimg_S5?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  상세 이미지5</th>
                <td colspan="3">
                <input type="file" name="prdimg_M5" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_M5) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_M5?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_M5?>" target="_blank" onMouseOver="document.prdimg5.src='../../data/prdimg/<?=$prd_row->prdimg_M5?>';"><?=$prd_row->prdimg_M5?></a>)
                <? } ?>

                </td>
              </tr>
              <tr>
                <th>
                  확대 이미지5</th>
                <td colspan="3">
                <input type="file" name="prdimg_L5" class="input">

                <? if( @file($imgpath."/".$prd_row->prdimg_L5) ){ ?>
                <input type="checkbox" name="delimg[]" value="<?=$prd_row->prdimg_L5?>">삭제 (<a href="/adm/data/prdimg/<?=$prd_row->prdimg_L5?>" target="_blank" onMouseOver="document.prdimg5.src='../../data/prdimg/<?=$prd_row->prdimg_L5?>';"><?=$prd_row->prdimg_L5?></a>)
                <? } ?>

                </td>
              </tr>
            </table>
          </td>
          <td width="25%" height="100">
            <table width="100%" height="100%" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <td align="center" bgcolor="#ffffff">
                <?
                if(@file($imgpath."/".$prd_row->prdimg_M5))
                	echo "<img src='../../data/prdimg/$prd_row->prdimg_M5' name='prdimg5' width='100'>";
                else
                	echo "No<br>Image";
					 ?>
					 </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      </div>

    <?/* if(!strcmp($oper_info[prdrel_use], "Y")) { ?>
		<h4 class="top20">관련상품</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="100%">
                <iframe width="100%" height="95" frameborder="0" src="prd_relation.php?mode=<?=$mode?>&prdcode=<?=$prdcode?>"></iframe>
                </th>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    	<? } */?>

		<h4 class="top20">상품설명</h4>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_basic">
              <tr>
                <th width="15%">관리자주석</th>
                <td width="85%">
                <textarea name="stortexp" rows="3" cols="90" class="textarea"><?=$prd_row->stortexp?></textarea>
                </td>
              </tr>
              <tr>
                <th colspan="3" align="center">PC 상세설명</th>
              </tr>
              <tr>
                <td colspan="3">
                <?
                $edit_content = $prd_row->content;
                include "../../webedit/WIZEditor.html";
                ?>
                </td>
              </tr>
							<tr>
								<th colspan="3" align="center">모바일 상세설명</th>
							</tr>
							<tr>
								<td colspan="3">
								<?
								$edit_type = "mobile";
								$edit_content = $prd_row->content_m;
								include "../../webedit/WIZEditor.html";
								?>
								</td>
							</tr>
            </table>
          </td>
        </tr>
      </table>
      <table width="100%" height="10" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td></td>
        </tr>
      </table>


      <br>
      <table align="center" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td>
			<button type="submit" class="b h28 t5 color blue_big">확인</button>
			<button type="button" class="b h28 t5 color gray_big" onclick="document.location='<?=$listpage_url?>?<?=$param?>';">목록</button>
          </td>
        </tr>
	  </table>
	  </form>

<script>
window.addEventListener('load', function(){
	setClass01();setCategory();prdlayCheck();lodingComplete();prdFocus();
}, false);
</script>

<script>
Vue.data.prdcode = "<?=$prdcode?>";
</script>

<? include "../foot.php"; ?>
