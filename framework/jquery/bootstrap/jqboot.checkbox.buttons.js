/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
$(function () {
    $('.button-checkbox').each(function(){
        // Settings
        var $widget = $(this),
            $button = $widget.find('button'),
            $checkbox = $widget.find('input:checkbox').addClass('hidden'),
            color = $button.data('color'),
            settings = {
                on:{ icon:'glyphicon glyphicon-check'},
                off:{ icon: 'glyphicon glyphicon-unchecked'}
            };
        // Event Handlers
        $button.on('click',function(){
            $checkbox.prop('checked', !$checkbox.is(':checked'));
            $checkbox.triggerHandler('change');
            updateDisplay();
        });
        $checkbox.on('change',function(){ updateDisplay(); });
        // Actions
        function updateDisplay() {
            var isChecked = $checkbox.is(':checked');            
            $button.data('state', (isChecked) ? "on" : "off");// Set the button's state            
            $button.find('.state-icon').removeClass().addClass('state-icon ' + settings[$button.data('state')].icon);// Set the button's icon
            // Update the button's color
            if (isChecked) $button.removeClass('btn-default').addClass('btn-' + color + ' active');
            else $button.removeClass('btn-' + color + ' active').addClass('btn-default');
        }
        // Initialization
        function init(){
            updateDisplay();
            // Inject the icon if applicable
            if ($button.find('.state-icon').length===0){
                $button.html('<span><i class="state-icon '+settings[$button.data('state')].icon+'"></i> '+$button.html()+'</span>');
            }
        }
        init();
    });
});