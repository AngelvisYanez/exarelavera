/**
 <b>Settings box</b>. It's good for demo only. You don't need this.
*/
(function($ , undefined) {
 
 $('.ace-settings-btn').on(ace.click_event, function(e){
	e.preventDefault();
	$(this).toggleClass('open');
	$('#ace-settings-box').toggleClass('open');        
 });
 $('#ace-settings-compact').on('click', function(){
	if(this.checked) {
		$('#sidebar').addClass('compact');
		var hover = $('#ace-settings-hover');
		if( hover.length > 0 ) {
			hover.removeAttr('checked').trigger('click');
		}
	}
	else {
		$('#sidebar').removeClass('compact');
		$('#sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset');
	}
	ace.cookie.set('ace_compact',this.checked);
	if(ace.vars['old_ie']) ace.helper.redraw($('#sidebar')[0], true);
 });/*.removeAttr('checked')*/

 $('#ace-settings-highlight').on('click', function(){
	if(this.checked) $('#sidebar .nav-list > li').addClass('highlight');
	else $('#sidebar .nav-list > li').removeClass('highlight');
	
	if(ace.vars['old_ie']) ace.helper.redraw($('#sidebar')[0]);
 });/*.removeAttr('checked')*/

 $('#ace-settings-hover').on('click', function(){
	if($('#sidebar').hasClass('h-sidebar')) return;
	if(this.checked) {
		$('#sidebar li').addClass('hover')
		.filter('.open').removeClass('open').find('> .submenu').css('display', 'none');
		//and remove .open items
	}
	else {
		$('#sidebar li.hover').removeClass('hover');

		var compact = $('#ace-settings-compact');
		if( compact.length > 0 && compact.get(0).checked ) {
			compact.trigger('click');
		}
	}
	ace.cookie.set('ace_hover',this.checked);
	$('.sidebar[data-sidebar-hover=true]').ace_sidebar_hover('reset');
	$('.sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset');
	
	if(ace.vars['old_ie']) ace.helper.redraw($('#sidebar')[0]);
 });/*.removeAttr('checked')*/

try{
    var compact=ace.cookie.get('ace_compact'),hover=ace.cookie.get('ace_hover');    
    if(typeof compact==='undefined'||compact==='true')
        $('#ace-settings-compact').attr('checked','checked');//.trigger('click');
    else $('#ace-settings-compact').removeAttr('checked');    
    if(typeof hover==='undefined'||(hover==='true'||compact==='true'))
        $('#ace-settings-hover').attr('checked','checked');//.trigger('click');
    else $('#ace-settings-hover').removeAttr('checked');
} catch(e) {};

})(jQuery);