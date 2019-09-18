<?
include_once "$_SERVER[DOCUMENT_ROOT]/adm/common.php";
include "$_SERVER[DOCUMENT_ROOT]/adm/inc/site_info.php";
include "$_SERVER[DOCUMENT_ROOT]/adm/inc/prd_info.php";
include "$_SERVER[DOCUMENT_ROOT]/adm/inc/oper_info.php";

if($_GET[catcode] != "") $catcode = $_GET[catcode];

array_push($_COMPONENTS, "/component/client/shop/product/option/select-option.php");
array_push($_COMPONENTS, "/component/client/shop/product/option/supply-option.php");
array_push($_COMPONENTS, "/component/client/shop/product/review/review-list.php");
array_push($_COMPONENTS, "/component/client/shop/product/product-view-chart.php");

// 상품정보 가져오기 (이동하지 말것)
$sql = "select *, new as newc from wiz_product wp, wiz_cprelation wc where wp.prdcode='$prdcode' and wc.prdcode = wp.prdcode";
$result = mysql_query($sql) or error(mysql_error());
$total = mysql_num_rows($result);
$prd_row = mysql_fetch_array($result);
if($prdcode == "" || $total <= 0) error("존재하지 않는 상품입니다.");
if($catcode == "") $catcode = $prd_row[catcode];

// 상품 조회수 업데이트
$sql = "update wiz_product set viewcnt = viewcnt + 1 where prdcode = '$prdcode'";
mysql_query($sql) or error(mysql_error());

include "$_SERVER[DOCUMENT_ROOT]/adm/inc/cat_info.php"; 		// 카테고리정보

$shortexp = nl2br($prd_row[shortexp]);
$content = $prd_row[content];
$content_m = $prd_row[content_m];
$prdname = $prd_row[prdname];

// 오늘본 상품목록에 추가
$view_exist = false;
$view_idx = 0;
for($ii=0;$ii<100;$ii++){
	if($_SESSION["view_list"][$ii][prdcode]) $view_idx++;
}
for($ii = 0; $ii < $view_idx; $ii++){
	if($_SESSION["view_list"][$ii][prdcode] == $prdcode){ $view_exist = true; break; }
}
if(!$view_exist){
	$_SESSION["view_list"][$view_idx][prdcode] = $prdcode;
	$_SESSION["view_list"][$view_idx][prdimg] = $prd_row[prdimg_R];
	$_SESSION["view_list"][$view_idx][prdurl] = $_SERVER[PHP_SELF];
}

// 상품 이미지
if(!@file($_SERVER[DOCUMENT_ROOT]."/adm/data/prdimg/".$prd_row[prdimg_M1])) $prdimg = "/adm/images/noimg_M.gif";
else $prdimg = "/adm/data/prdimg/".$prd_row[prdimg_M1];


if(!empty($prd_row[strprice])) $sellprice = $prd_row[strprice];
else $sellprice = number_format($prd_row[sellprice] * ($prd_row[margin] + 100) / 100)."원";

$sql = "select * from wiz_product_option where prdcode='{$prdcode}'";
$result = mysql_query($sql) or error(mysql_error());
$options = array();
while($row = mysql_fetch_array($result)){
	array_push($options, array(
		"option_idx" => $row['idx'],
		"name" => $row['name'],
		"price" => $row['price'],
		"stock" => $row['stock'],
		"enabled" => (bool)$row['enabled']
	));
}

$product = array(
	"prdcode" => $prd_row['prdcode'],
	"prdname" => $prd_row['prdname'],
	"sellprice" => $prd_row['sellprice'],
	"stock" => $prd_row['stock'],
	"shortage" => $prd_row['shortage'],
	"prdname" => $prd_row['prdname'],
	"select_subjects" => $prd_row['select_subjects'],
	"supply_subjects" => $prd_row['supply_subjects'],
	"options" => $options
);

$product = json_encode($product);

$skin = array(
	"calm" => 25,
	"brightening" => 25,
	"moisturizing" => 25,
	"elasticity" => 25
);
if($wiz_session['id']){
	$sql = "select * from uxi_skin where user_id='{$wiz_session['id']}' order by wdate desc";
	$result = mysql_query($sql);
	if($row = mysql_fetch_array($result)){
		$skin = json_decode($row['data'], true);
		$exist = true;
	}
}

$me = array(
	"id" => $wiz_session['id'],
	"name" => $wiz_session['name'],
	"skin" => $skin
);

$me = json_encode($me);
?>


	<!--제품 상세보기 시작-->
		<!-- 상품 간략 설명 -->
		<div id="product_view_wrap">

			<!-- 상품 이미지 -->
			<div class="view-top">
				<div class="container">
					<div id="product_view_image">

						<div id="View_Product_Img">
							<img src="<?=$prdimg?>" name="prdimg">
						</div>

						<div id="zoom_btn_wrap" style="padding:10px 0px;">
							<!-- 확대보기 -->

							<a href="<?=$prev_prdcode?>"><img src="/adm/images/but_view_prev.gif" border=0></a>
							<img src="/adm/images/but_view_zoom.gif" border=0 onClick="prdZoom();" style="cursor:pointer">
							<a href="<?=$next_prdcode?>"><img src="/adm/images/but_view_next.gif" border=0></a>

							<!-- //확대보기 -->
						</div>

						<div id="thumbnail_wrap">
							<ul class="view-thumb-ul">
							<? $imgpath = $_SERVER[DOCUMENT_ROOT]."/adm/data/prdimg"; ?>
							<?php
							for($ii = 1; $ii <= 5; $ii++) {
								if(@file($imgpath."/".$prd_row["prdimg_S".$ii])){
									?>
									<li>
										<div class="view-thumb-box">
											<img src="/adm/data/prdimg/<?=$prd_row["prdimg_S".$ii]?>" onMouseOver="document.prdimg.src='/adm/data/prdimg/<?=$prd_row["prdimg_M".$ii]?>'"></td>
										</div>
									</li><!-- //상품 썸네일 -->

									<?php
								}
							}
							?>
							</ul>
						</div><!-- //상품 이미지 -->

					</div>

					<!-- 제품정보 -->
					<div id="product_view_info">

						<form name="prdForm" id="prdForm" action="/adm/product/prd_save.php" method="post" enctype="application/json" onsubmit="return checkSubmit(this);">
							<input type="hidden" name="mode"    value="insert">
							<input type="hidden" name="direct"  value="buy">
							<input type="hidden" name="prdcode" value="<?=$prdcode?>">
							<input type="hidden" name="options">

							<table width="100%" border=0 cellpadding=0 cellspacing=0>
								<tr>
									<td class="p_name">
										<h1 class="item_title"><?=$prdname?></h1>
										<span class="prd-span"><?=$prd_row[prdcom]?></span>
									</td>
								</tr>

								<tr>
									<td class="item-info-td">
										<table class="item-info-style">
											<? if($prd_row[conprice] > $prd_row[sellprice]){ ?>
											<tr>
												<th class="p_tit">판매가</th>
												<td class="p_info"><span style="text-decoration:line-through;"><?=number_format($conprice)?>원</span><img style="margin:0 10px;" src="/child/img/icon/conprice.png" alt=""><?=$sellprice?></td>
											</tr>
											<? } ?>
											<tr>
												<th class="p_tit">판매가</th>
												<td class="p_info"><span class="price_b weight-medium"><?=$sellprice?></span></td>
											</tr>
											<tr>
												<th class="p_tit">용량</th>
												<td class="p_info"><span class="weight-light"><?=$prd_row[origin]?>ml</span></td>
											</tr>
										</table><!-- //상품 가격 -->
									</td><!-- //상품 가격 -->
								</tr>

								<tr>
									<td>
										<div>
											<select-option
													v-if="product.select_subjects"
													v-bind:prdcode="product.prdcode"
													v-on:change="changeOption">
											</select-option>
											<supply-option
													v-if="product.supply_subjects"
													v-bind:prdcode="product.prdcode"
													v-on:change="changeOption">
											</supply-option>
										</div>

										<div class="product-option-table">
											<div class="po-row">
												<div class="po-th">
													<div class="p_tit">라벨문구</div>
												</div>
												<div class="po-td">
													<input v-model="label" type="text" name="label" placeholder="라벨 문구를 입력해주세요. (15자 이내)" maxlength="15" />
												</div>
											</div>
										</div>

										<div>
												<ul id="option_list" class="option_list">
														<li v-for="(item, index) in options" class="option_item">
																<div class="option_header">
																		<div class="option_values">
																				{{ item.name }}
																		</div>
																		<button v-on:click="options.splice(index, 1)" type="button" class="option_delete" v-if="item.type != 'product'">X</button>
																</div>
																<div class="option_footer">
																		<div class="option_control" style="display: inline-block;">
																				<button v-on:click="decAmount(item)" type="button" class="option_decrease">-</button>
																				<input v-model="item.amount" v-on:change="changeAmount(item)" type="text" class="option_amount" onkeydown="return onkeydown_number(event);"/>
																				<button v-on:click="incAmount(item)" type="button" class="option_increase">+</button>
																		</div>
																		<label class="option_price">{{ parseInt(item.price * item.amount).format() }}원</label>
																</div>
														</li>
												</ul>
										</div>

									</td>
								</tr>

								<tr>
									<td id="item_scrape_style" class="View_info_scrape">
										<table border="0" cellpadding="0" cellspacing="0" width="100%">
											<tr>
												<td class="p_tit">스크랩하기</td>
												<td style="padding-left:20px;">
													<img src="/adm/images/i_tw.png" border=0 style="cursor:pointer" onclick="snsTwitter('<?=$prd_info->prdname?>','http://<?=$HTTP_HOST?><?=$REQUEST_URI?>');">
													<img src="/adm/images/i_fb.png" border=0 style="cursor:pointer" onclick="snsFacebook('<?=$prd_info->prdname?>','http://<?=$HTTP_HOST?><?=$REQUEST_URI?>');">
													<img src="/adm/images/i_blog.png" border=0 style="cursor:pointer" onclick="snsnaver('<?=$prd_info->prdname?>','http://<?=$HTTP_HOST?><?=$REQUEST_URI?>');">
												</td>
											</tr>
										</table></td>
									</tr>
									<tr>
										<td align="right">
											<div class="view-btn-wrap">
												<? if(empty($strprice)) { ?>
												<div class="view-btn">
													<button v-on:click="basket" type="button" class="btn btn_border">장바구니</button>
												</div>
												<div class="view-btn">
													<button v-on:click="buy" type="button" class="btn btn_border btn_point">바로구매</button>
												</div>
												<!-- <div class="view-btn">
													<button v-on:click="wish" class="btn btn_border">관심상품</button>
												</div> -->


											<? } ?>
										</div>


									</div>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
		</div>



		<div class="view-bottom">
			<div class="container">
				<div v-if="exist" class="view-chart-area">
					<div class="view-chart-top">
						<span class="view-chat-title">
							<!-- <span v-if="me.name" class="title-medium b">고객님</span> -->
							<span class="title-regular6 weight-light"><strong class="title-medium b">고객님</strong>의 <br>피부측정결과</span>
						</span>
					</div><div class="view-chart-bottom">
						<div class="m-scroll-area scroll-m">
							<div class="scroll">
								<div class="inner">
									<product-view-chart v-bind:me="me" v-bind:options="options"></product-view-chart>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div v-if="!exist" class="view-chart-area" style="position: relative;">
					<div class="view-chart-top">
						<span class="view-chat-title">
							<!-- <span v-if="me.name" class="title-medium b">고객님</span> -->
							<span class="title-regular6 weight-light"><strong class="title-medium b">고객님</strong>의 <br>피부측정결과</span>
						</span>
					</div><div class="view-chart-bottom">
						<div class="m-scroll-area scroll-m">
							<div class="scroll">
								<div class="inner">
									<product-view-chart></product-view-chart>
								</div>
							</div>
						</div>
					</div>
					<div style="position: absolute; left: 0; right: 0; top: 0; bottom: 0; background-color: rgba(255,255,255,0.7); ">
						<div style="position: absolute;top: 0; bottom: 0; left: 0; right: 0; width: 100%; height: 13px; margin: auto;text-align:center;z-index: 2;">
							<span>앱에서 측정하신 고객만 확인가능합니다</span>
						</div>
					</div>
				</div>

				<!-- 상세 정보-->
				<div class="View_Detail_Wrap">
					<a name="info"></a>
					<ul>
						<li class="current"><a href="#info"><p>상품정보</p></a></li>
						<li><a href="#review"><p>상품 후기</p></a></li>
						<li><a href="#rel"><p>배송 및 교환</p></a></li>
					</ul>
				</div>
				<div class="view-box">
					<div class="v-pc" data-animate="fade"><?=$content?></div>
					<div class="v-m" data-animate="fade"><?=$content_m?></div>
				</div>

				<!-- 기대평/리뷰 -->
				<div class="View_Detail_Wrap">
					<a name="review"></a>
					<ul>
						<li><a href="#info"><p>상품정보</p></a></li>
						<li class="current"><a href="#review"><p>상품 후기</p></a></li>
						<li><a href="#rel"><p>배송 및 교환</p></a></li>
					</ul>
				</div>
				<div class="view-box">
					<h3 class="view-title-bor">리뷰</h3>
					<!-- 리뷰 --><? /* include "$_SERVER[DOCUMENT_ROOT]/adm/product/prd_review.php"; */ ?>
					<review-list
						prdcode="<?=$prdcode?>"
						viewtype="list"
						searchbar="false"
						limit="5">
					</review-list>
				</div>

				<div class="View_Detail_Wrap">
					<a name="rel"></a>
					<ul>
						<li><a href="#info"><p>상품정보</p></a></li>
						<li><a href="#review"><p>상품 후기</p></a></li>
						<li class="current"><a href="#rel"><p>배송 및 교환</p></a></li>
					</ul>
				</div>
				<div class="view-box">
					<h3 class="view-title-bor">반품/교환 안내</h3>
					<? $code="prdinfo"; include "$_SERVER[DOCUMENT_ROOT]/adm/module/page.php"; // 페이지 ?>
				</div>
			</div>
		</div>


	</div>

	<script>
	Vue.data.product = <?=$product?>;
	Vue.data.options = [];
	Vue.data.label = "";
	Vue.data.me = <?=$me?>;
	Vue.data.exist = <?=(($exist)?"true":"false")?>;

	Vue.created.push(function(app){
		if(!app.product.select_subjects){
			app.options.push({
				option_idx: "",
				type: "product",
				name: "본품",
				price: app.product.sellprice,
				stock: app.product.stock,
				skin: {
					calm: 0,
					brightening: 0,
					moitureizing: 0,
					elasticity: 0
				},
				amount: 1
			});
		}
	});

	Vue.computed.getBaskets = function(){
		var baskets = [];
		if(this.check()){
			baskets.push({
				prdcode: this.product.prdcode,
				options: this.options
			});
		}
		return baskets;
	};

	Vue.computed.enable = function(){
		var enable = "N";
		if(this.product){
			if(this.product.select_subjects){
				for(var i in this.product.options){
					if(!this.product.options[i].enabled) continue;
					if(0 < this.product.options[i].stock) enable = "Y";
				}
			}
			else{
				switch(this.product.shortage){
					case "Y": enable = "N"; break;
					case "N": enable = "Y"; break;
					case "S": enable = (0 < this.product.stock) ? "Y" : "N"; break;
				}
			}
		}
		return enable;
	};

	Vue.methods.wish = function(){
		$.ajax({
			url: "/adm/product/inc/wishlist_ajax.php",
			method: "post",
			data: {
				prdcode: this.product.prdcode,
				amount: 1
			},
			dataType: "json",
			success: function(res){
				if(res.error) alert(res.error);
				else{
					if(confirm("관심상품에 추가되었습니다.\n해당 페이지로 이동하시겠습니까?")){
						window.location.href = "/child/sub/member/wishlist.php";
					}
				}
			}
		});
	};
	Vue.methods.basket = function(){
		if(!this.check(true)) return;
		$.ajax({
			url: "/adm/product/inc/basket_ajax.php",
			method: "post",
			data: {
				prdcode: this.product.prdcode,
				options: this.options,
				label: this.label
			},
			dataType: "json",
			success: function(res){
				if(res.error) alert(res.error);
				else{
					if(confirm("장바구니에 추가되었습니다.\n해당 페이지로 이동하시겠습니까?")){
						window.location.href = "/child/sub/member/cart.php";
					}
				}
			}
		});
	};

	Vue.methods.buy = function(){
		if(this.options.length <= 1){
			alert("이펙터 중 최소 1가지 이상 선택해주세요.");
			return;
		}

		document.prdForm.options.value = JSON.stringify(this.options);
		document.prdForm.submit();
	};

	Vue.methods.check = function(notice){
		if(this.product.select_subjects){
			var confirm = false;
			for(var i in this.options){
				if(this.options[i].type == "select") confirm = true;
			}
			if(!confirm){
				if(notice) alert("선택 옵션이 선택되지 않았습니다");
				return false;
			}
		}
		else if(!this.options.length){
			if(notice) alert("옵션이 선택되지 않았습니다");
			return false;
		}
		return true;
	};

	Vue.methods.incAmount = function(item){
		if(parseInt(item.amount) < parseInt(item.stock) || this.product.shortage == 'N'){
			item.amount++;
		}
	};

	Vue.methods.decAmount = function(item){
		if(1 < parseInt(item.amount)){
			item.amount--;
		}
	};

	Vue.methods.changeAmount = function(item){
		if(parseInt(item.stock) < parseInt(item.amount)){
			item.amount = item.stock;
		}
		if(parseInt(item.amount) < 1){
			item.amount = 1;
		}
	};

	Vue.methods.changeOption = function(item){
		var exist = false;

		for(var i in this.options){
			if(this.options[i].option_idx == item.option_idx){
				exist = true;
				break;
			}
		}
		if(!exist) this.options.push(item);
		else{
			alert("이미 선택되어 있습니다");
		}
	}
	</script>
	<!-- 실제 컨텐츠 끝 -->
