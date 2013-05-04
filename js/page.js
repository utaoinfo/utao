$(function(){
	// 搜索框
	showKeyWord();
	/*setInterval(function(){
		showKeyWord();
	},5*1000);*/
	
	$('#kwInput').blur(function(){
		var kwValue = $(this).val();
		if(!kwValue && !placeholderSupport){
			$('#kwPlaceholder').show();
		}
	}).focus(function(){
		if(  $(this).val() && !placeholderSupport){
			$('#kwPlaceholder').hide();
		}	
	}).keyup(function(){
		if( !placeholderSupport){
			if($(this).val()){
				$('#kwPlaceholder').hide();
			}else{
				$('#kwPlaceholder').show();
			}
		}
	});
	//按了enter键搜索
	$("#kwInput").keydown(function(event){
		event=event||window.event;
		if(event.keyCode==13){
			goSearch();	
		}
	});
	
	// 点击搜索
	$('#searchBtn').click(function(){
		goSearch();
	});

	// 热词
	/*showHotWords();
	setInterval(function(){
		showHotWords();
	},50*1000);*/
});
var searchUrl = "/glist.html?";
var testInput = document.createElement("input");
var placeholderSupport = 1;
if(!('placeholder' in testInput))
	placeholderSupport = 0;

// 搜索词显示
function showKeyWord(){
	var keyword = "搜索 商品/品牌"
	if( typeof(keyword)!='undefined' && keyword ){
		placeholder = decodeURIComponent(keyword);
	}
	if(!placeholder){
		if(typeof(KEYWORDS)=='undefined' || $('#kwInput:focus').size()>0)
			return false;
		var placeholder = '',kwIndex=0;
		for(var i=0;i<50;i++){
			kwIndex = parseInt(Math.random()*10);
			if(KEYWORDS[kwIndex]){
				placeholder = KEYWORDS[kwIndex];
				break;
			}
		}
	}
	if(placeholderSupport){
		$('#kwInput').attr('placeholder',placeholder);
	}else{
		$('#kwPlaceholder').html(placeholder).show();
	}
}

// 热词显示
function showHotWords(){
	if(typeof(KEYWORDS)=='undefined')
		return false;
	var hwIndexArr = {},hotLinks='热搜：',separator = '',validWordNum = 0;
	for(var i=0;validWordNum<4;i++){
		var hwIndex = parseInt(Math.random()*100);
		
		if(!hwIndexArr.hwIndex && KEYWORDS[hwIndex]){
			hwIndexArr[i] = hwIndex;
			hotWord = KEYWORDS[hwIndex];
			hotLinks += separator+'<a href="'+searchUrl+'kwInput='+encodeURIComponent(hotWord)+'" target="_blank" title="'+hotWord+'" >'+hotWord+'</a>';
			separator = '&nbsp;&nbsp;';
			validWordNum +=1;
		}
		if(validWordNum>100)
			break;	
	}
	$('#hotWords').html(hotLinks);
}

function goSearch(){
	var kwValue = $('#kwInput').val();
	if(!kwValue){
		return false;
	}
	// 首页新开窗口
	if(document.location.host=='www.utao.info' && document.location.search =='' ){
		window.open(searchUrl+'kw='+encodeURIComponent(kwValue));
		return false;
	}else{
		document.location.href = searchUrl+'kw='+encodeURIComponent(kwValue);
		return false;
	}
}