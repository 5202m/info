/**
* 平滑移动
*/
function toscroll(obj, cutpx) {
	var pos = $(obj).offset().top;
	if(cutpx!=0){
		pos = pos-cutpx;
	}
	$("html,body").animate({ scrollTop: pos }, 1000);
	return false;
}
