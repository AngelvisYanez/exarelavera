(function ($) {
    $.getClassFont=function(iconClassName){ return (!!iconClassName)?(iconClassName.substring(0, 3)==='fa-'||iconClassName.substring(0, 3)==='fa '?'fa-ico':(iconClassName.substring(0, 5)==='glyph'?'glyph-ico':'font-extra')):'';  };
    $.getIconContent=function(iconClassName,li){ 
        var icon={classes:iconClassName, font:$.getClassFont(iconClassName), icon:$.getCSSValue(iconClassName, 'content', ':before')};
        if(icon.icon!==null&&typeof li!=='undefined') $(li).attr('data-icon', icon.icon).addClass(icon.font);
        return icon;
    };
    $.getCSSValue = function (classname, property, pseudo) {
        if(classname!==null && typeof classname !=='undefined' && classname.trim()!==''){
            classname='.'+classname.trim();
            //console.log(classname);
            var pseudo = pseudo || null, classReaplce = classname.replace('.', ''), element = document.createElement('i');
            element.className = classReaplce;

            document.body.appendChild(element);		
            var value = getComputedStyle(
                document.querySelector(classname.replace(' ', '.')), pseudo
            ).getPropertyValue(property);
            //console.log(document.querySelector(classname));
            document.body.removeChild(element);
            return value.replace(/\"/g,'');
        } else return null;
    };
    $.fn.chosenIcon = function (options) {
        return this.each(function(){
            var $select = $(this), $chosen, iconMap = {};

            // 1. Retrieve icon class from data attribute and build object for each list item
            $select.find('option').filter(function(){ return $(this).text(); }).each(function (i) {
                $(this).attr('data-numero',i);
                var iconSrc = $(this).attr('data-icon');
                iconMap[i] = $.trim(iconSrc);
            });
            // 2. Execute chosen plugin
            $select.chosen(options);
            // 2.1 add Class for specific styling
            $chosen = $select.next().addClass('chosenIcon-container');
            // 3. add data in lis with icon name
            $select.on('chosen:searchready', function () {
                setTimeout(function () {
                    $chosen.find('.chosen-results li').each(function (i) {
                        //console.log($(this).attr('data-option-array-index')+' '+iconMap[$(this).attr('data-option-array-index')-1]);
                        $.getIconContent(iconMap[$(this).attr('data-option-array-index')-1],this);
                    });
                }, 0);
            });
            $select.on('chosen:showing_dropdown chosen:activate', function () {
                setTimeout(function () {
                    $chosen.find('.chosen-results li').each(function (i) { 
                        $.getIconContent(iconMap[i],this); 
                    });
                }, 0);
            });
            // 4. Change image on chosen selected element when form changes
            $select.on('chosen:updated',function(){ $select.trigger('change'); });
            $select.change(function(){
                var icon=$.getIconContent(($select.find('option:selected').attr('data-icon'))? $select.find('option:selected').attr('data-icon') : null);
                if (!!icon.classes && icon.icon!==null){                    
                    $chosen.find('.chosen-single span').attr('data-icon', icon.icon).removeAttr('class').addClass(icon.font);
                }else $chosen.find('.chosen-single span').removeAttr('data-icon').removeAttr('class');
            });
            //console.log($select);
            $select.trigger('change');
        });
    };
})(jQuery);
$.fn.createChosenIcon=function (clase,options){ // Requiere jquery.chosen
    clase=clase||'input-sm';options=$.extend({width:"100%"},(options||{}));
    $(this).chosenIcon(options);
	this.each(function(i,obj){ var tab=$(obj).attr('tabindex')||''; /*if(tab!=='') $(obj).removeAttr('tabindex');*/  var id=$(obj).attr('id')||''; if(id!==''){$("#"+id+"_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-control '+clase);$("#"+id+"_chosen").find(".chosen-search").find('input').addClass('text');  if(tab!==''){ $("#"+id+"_chosen").attr('tabindex',tab).on('focus',function(){  $(obj).trigger('chosen:open.chosen'); });  } } }); return this;
};
