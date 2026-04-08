(function($){
    $.fn.chosenDesc=function(options){
        return this.each(function(){ options=(typeof options!=='undefined'?options:{});
            var $select=$(this),descMap={},template=(typeof options['template']!=='undefined'?options['template']:function(text, templateData){return text;});
            $select.on('chosen:updated', function(){
                descMap={};
                $select.find('option').each(function(i){ $(this).attr('data-numero',i); descMap[i]=$(this).data(); descMap[i]['opttxtsaved']=$(this).text(); });
            });
            $select.trigger('chosen:updated');
            /*$select.find('option').filter(function(){ return $(this).attr('value');}).each(function(i){ $(this).attr('data-numero',i); descMap[i]=$(this).data();descMap[i]['opttxtsaved']=$(this).text(); });*/
            $select.chosen(options);
            var $chosen = $select.next().addClass('chosenDesc-container');
            $select.on('chosen:searchready', function(){
                setTimeout(function(){
                    $chosen.find('.chosen-results li').each(function(i){ var $li=$(this),index=$li.attr('data-option-array-index'); $li.html(template.call($select,$li.html(),descMap[index])); });
                }, 0);
            });
            $select.on('chosen:showing_dropdown chosen:activate', function(){
                setTimeout(function(){
                    var aux=[]; $.each(descMap,function(i,v){ if(v['opttxtsaved']!=='') aux.push(v); });
                    $chosen.find('.chosen-results li').each(function(i){ var $li=$(this); $li.html(template.call($select,aux[i]['opttxtsaved'],aux[i])); });
                }, 0);
            });
        });
    };
})(jQuery);
