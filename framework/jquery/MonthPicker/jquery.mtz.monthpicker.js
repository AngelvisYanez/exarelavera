/*
 * jQuery UI Monthpicker
 *
 * @licensed MIT <see below>
 * @licensed GPL <see below>
 *
 * @author Luciano Costa
 * http://lucianocosta.info/jquery.mtz.monthpicker/
 *
 * Depends:
 *  jquery.ui.core.js
 */

/**
 * MIT License
 * Copyright (c) 2011, Luciano Costa
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
/**
 * GPL LIcense
 * Copyright (c) 2011, Luciano Costa
 * 
 * This program is free software: you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License as published by the 
 * Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 * 
 * You should have received a copy of the GNU General Public License along 
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

;(function ($) {

    var methods = {
        init : function (options) { 
            return this.each(function () {
                var 
                    $this = $(this),
                    data = $this.data('monthpicker'),
                    year = (options && options.year) ? options.year : (new Date()).getFullYear(),
                    settings = $.extend({
                        pattern: 'mm/yyyy',
                        selectedMonth: null,
                        selectedMonthName: '',
                        selectedYear: year,
                        startYear: year - 10,
                        finalYear: year + 10,
                        monthNames: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],                
                        id: "monthpicker_" + (Math.random() * Math.random()).toString().replace('.', ''),
                        openOnFocus: true,
                        disabledMonths: []
                    }, options);

                settings.dateSeparator = settings.pattern.replace(/(mmm|mm|m|yyyy|yy|y)/ig,'');

                // If the plugin hasn't been initialized yet for this element
                if (!data) {

                    $(this).data('monthpicker', {
                        'target': $this,
                        'settings': settings
                    });

                    if (settings.openOnFocus === true) {
                        $this.on('focus', function () {
                            $this.monthpicker('show');
                        });
                    }

                    $this.monthpicker('parseInputValue', settings);

                    $this.monthpicker('mountWidget', settings);

                    $this.on('monthpicker-click-month', function (e, month, year) {
                        $this.monthpicker('setValue', settings);
                        $this.monthpicker('hide');
                    });

                    // hide widget when user clicks elsewhere on page
                    $this.addClass("mtz-monthpicker-widgetcontainer");
                    $(document).unbind("mousedown.mtzmonthpicker").on("mousedown.mtzmonthpicker", function (e) {
                        if (!e.target.className || e.target.className.toString().indexOf('mtz-monthpicker') < 0) {
                            $(this).monthpicker('hideAll'); 
                        }
                    });
                }
            });
        },

        show: function (aux) {            
            $(this).monthpicker('hideAll'); 
            var item=(typeof aux!=='undefined'?$(aux):this),widget = $('#' + this.data('monthpicker').settings.id);
            widget.css("top", item.offset().top  + item.outerHeight());
            if ($(window).width() > (widget.width() + item.offset().left) ){
                widget.css("left", item.offset().left);
            } else {
                widget.css("left", item.offset().left - widget.width());
            }
            widget.show();
            widget.find('select').focus();
            this.trigger('monthpicker-show');
        },

        hide: function () {
            var widget = $('#' + this.data('monthpicker').settings.id);
            if (widget.is(':visible')) {
                widget.hide();
                this.trigger('monthpicker-hide');
            }
        },

        hideAll: function () {
            $(".mtz-monthpicker-widgetcontainer").each(function () {
                if (typeof($(this).data("monthpicker"))!="undefined") { 
                    $(this).monthpicker('hide'); 
                }
            });
        },

        setValue: function (settings) {
			//console.log(settings);
            var 
                month = settings.selectedMonth,
                year = settings.selectedYear;

            if(settings.pattern.indexOf('mmm') >= 0) {
                month = settings.selectedMonthName;
            } else if(settings.pattern.indexOf('mm') >= 0 && settings.selectedMonth < 10) {
                month = '0' + settings.selectedMonth;
            }

            if(settings.pattern.indexOf('yyyy') < 0) {
                year = year.toString().substr(2,2);
            } 

            if (settings.pattern.indexOf('y') > settings.pattern.indexOf(settings.dateSeparator)) {
                this.val(month + settings.dateSeparator + year);
            } else {
                this.val(year + settings.dateSeparator + month);
            }
            //console.log(year + settings.dateSeparator + month);
            this.change();
        },

        disableMonths: function (months) {
            if(typeof this.data('monthpicker')==='undefined') return;
            var settings = this.data('monthpicker').settings,
                container = $('#' + settings.id);

            settings.disabledMonths = months;

            container.find('.mtz-monthpicker-month').each(function () {
                var m = parseInt($(this).data('month'));
                if ($.inArray(m, months) >= 0) {
                    $(this).addClass('ui-state-disabled');
                } else {
                    $(this).removeClass('ui-state-disabled');
                }
            });
        },
		enableMonths: function (months) {
                    if(typeof this.data('monthpicker')==='undefined') return;
            var 
                settings = this.data('monthpicker').settings,
                container = $('#' + settings.id);

            settings.disabledMonths = months;

            container.find('.mtz-monthpicker-month').each(function () {
                var m = parseInt($(this).data('month'));
                if ($.inArray(m, months) >= 0) {
                    $(this).removeClass('ui-state-disabled');
                } else {
					$(this).addClass('ui-state-disabled');                    
                }
            });
        },
		
		setMonthActive: function (month) {
                    month=(typeof month==='undefined'||month===null||isNaN(month)||month<0||month>12)?0:month;
            var 
                settings = this.data('monthpicker').settings,
                container = $('#' + settings.id);
				settings['selectedMonth']=month;

            container.find('.mtz-monthpicker-month').each(function () {
				$(this).removeClass('ui-state-active');
                var m = parseInt($(this).data('month'));
                if (m===month) {
                    $(this).addClass('ui-state-active');
                    settings.selectedMonthName = $(this).text();
                }
            });
            
			this.monthpicker('setValue', settings); this.trigger('monthpicker-click-month'); /*console.log('ber',month);*/ return this;
        },

        mountWidget: function (settings) {
            var
                monthpicker = this,
                container = $('<div id="'+ settings.id +'" class="ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" />'),
                header = $('<div class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all mtz-monthpicker" />'),
                combo = $('<select class="mtz-monthpicker mtz-monthpicker-year" />'),
                table = $('<table class="mtz-monthpicker" />'),
                tbody = $('<tbody class="mtz-monthpicker" />'),
                tr = $('<tr class="mtz-monthpicker" />'),
                td = '',
                selectedYear = settings.selectedYear,
                option = null,
                attrSelectedYear = $(this).data('selected-year'),
                attrStartYear = $(this).data('start-year'),
                attrFinalYear = $(this).data('final-year');

            if (attrSelectedYear) {
                settings.selectedYear = attrSelectedYear;
            }

            if (attrStartYear) {
                settings.startYear = attrStartYear;
            }

            if (attrFinalYear) {
                settings.finalYear = attrFinalYear;
            }

            container.css({
                position:'absolute',
                zIndex:999999,
                whiteSpace:'nowrap',
                //width:'250px',
                overflow:'hidden',
                textAlign:'center',
                display:'none',
                top: monthpicker.offset().top + monthpicker.outerHeight(),
                left: monthpicker.offset().left
            });

            combo.on('change', function () { 
                var months = $(this).parent().parent().find('td[data-month]');
                months.removeClass('ui-state-active');
                if ($(this).val() == settings.selectedYear) {
                    months.filter('td[data-month='+ settings.selectedMonth +']').addClass('ui-state-active');
                }
                monthpicker.trigger('monthpicker-change-year', $(this).val());
            });

            // mount years combo
            for (var i = settings.startYear; i <= settings.finalYear; i++) {
                var option = $('<option class="mtz-monthpicker" />').attr('value', i).append(i);
                if (settings.selectedYear == i) {
                    option.attr('selected', 'selected');
                }
                combo.append(option);
            }
            header.append(combo).appendTo(container);

            // mount months table
            for (var i=1; i<=12; i++) {
                td = $('<td class="ui-state-default ui-corner-all mtz-monthpicker mtz-monthpicker-month" />').attr('data-month',i);
                if (settings.selectedMonth == i) {
                   td.addClass('ui-state-active');
                }
                td.append(settings.monthNames[i-1]);
                tr.append(td).appendTo(tbody);
                if (i % 3 === 0) {
                    tr = $('<tr class="mtz-monthpicker" />'); 
                }
            }

            tbody.find('.mtz-monthpicker-month').on('click', function () {
                var m = parseInt($(this).data('month'));
                if ($.inArray(m, settings.disabledMonths) < 0 ) {
                    settings.selectedYear = $(this).closest('.ui-datepicker').find('.mtz-monthpicker-year').first().val();
                    settings.selectedMonth = $(this).data('month');
                    settings.selectedMonthName = $(this).text();
                    monthpicker.trigger('monthpicker-click-month', $(this).data('month'));
                    $(this).closest('table').find('.ui-state-active').removeClass('ui-state-active');
                    $(this).addClass('ui-state-active');
                }
            });

            table.append(tbody).appendTo(container);

            container.appendTo('body');
        },

        destroy: function () {
            return this.each(function () {
                $(this).removeClass('mtz-monthpicker-widgetcontainer').unbind('focus').removeData('monthpicker');
            });
        },

        getDate: function () {
            var settings = this.data('monthpicker').settings;
            if (settings.selectedMonth && settings.selectedYear) {
                return new Date(settings.selectedYear, settings.selectedMonth -1);
            } else {
                return null;
            }
        },
		getMonth: function () {
            var settings = this.data('monthpicker').settings;
            if (settings.selectedMonth) {
                return settings.selectedMonth;
            } else {
                return null;
            }
        },
		getYear: function () {
            var settings = this.data('monthpicker').settings;
            if (settings.selectedYear) {
                return settings.selectedYear;
            } else {
                return null;
            }
        },
        parseInputValue: function (settings) {
            if (this.val()) {
                if (settings.dateSeparator) {
                    var val = this.val().toString().split(settings.dateSeparator);
                    if (settings.pattern.indexOf('m') === 0) {
                        settings.selectedMonth = val[0];
                        settings.selectedYear = val[1];
                    } else {
                        settings.selectedMonth = val[1];
                        settings.selectedYear = val[0];                                
                    }
                }
            }
        },
        getMonth: function () { return this.data('monthpicker').settings['selectedMonth']; },
        getYear: function () { return this.data('monthpicker').settings['selectedYear']; },
        getMonthLong: function () { var monthLongs=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],mes=(this.data('monthpicker').settings['selectedMonth'])*1-1; if(mes<0||mes>12) return ''; return monthLongs[mes]; }

    };

    $.fn.monthpicker = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call( arguments, 1 ));
        } else if (typeof method === 'object' || ! method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.mtz.monthpicker');
        }    
    };	
    //$.fn.setMonthLong=function (m){ m=m||0; m=m*1-1; var meses=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; if(m<0||m>11){ if(this.is(':input')) this.val(''); else this.html(''); return null; } if(this.is(':input')) this.val(meses[m]); else this.html(meses[m]);  return meses[m]; };
    $.fn.getMonthSel=function (){ ($('#Month').val().split('-'))[1]*1; };
	$.fn.createPeriodPicker= function(startYear,finalYear,prepend,action){ return this.createMonthPicker({selectedYear:finalYear,startYear:startYear,finalYear:finalYear},action); };
	$.fn.createMonthPicker= function(opts,button,action){ var t=this; opts=opts||{}; if(typeof button==='function') action=button; else if($(button).length){ $(button).on('click',function(){ t.monthpicker('show',button); }); opts['openOnFocus']=false; }
		act=function (e, month) { var pl=$($(e['target']).data('monthplacer')); if(pl.length===1){ var lg=$(e['target']).monthpicker('getMonthLong'); if(pl.is(':input')) pl.val(lg); else pl.html(lg); } if(typeof action==='function') action(e,month); };
		var d = new Date(),prepend='<span>'+(typeof opts['prepend']==='string'?opts['prepend']:'A&ntilde;o')+'&nbsp;</span>';			
		opts['finalYear']=opts['finalYear']||(opts['startYear']||d.getFullYear());opts['startYear']=opts['startYear']||opts['finalYear'];opts['selectedYear']=opts['selectedYear']||opts['finalYear'];		
		this.monthpicker('destroy').monthpicker($.extend({pattern: 'yyyy-mm',openOnFocus: true},opts)).monthpicker('setMonthActive', d.getMonth()+1).bind('monthpicker-click-month',act);
		var header=$('#'+this.data('monthpicker').settings.id).find('.ui-datepicker-header'); if(opts['showYear']===false) header.find('select').hide();  if(prepend) header.prepend(prepend); return this;	
	};
	$.createMonthRange= function(fromDate,toDate,year,opts){
		$(fromDate).monthpicker('destroy');
		$(toDate).monthpicker('destroy');
		var options = $.extend({pattern: 'yyyy-mm',selectedYear: year,startYear: year,finalYear: year,openOnFocus: true, showYear:false},opts||{});
		$(fromDate).createMonthPicker($.extend(options,{prepend:'Seleccione Mes Inicio'}), function (e, month) {
                    if(typeof $(toDate).data('monthpicker')==='undefined') return;
			$(toDate).monthpicker('disableMonths', []);                         
			var settings=$(toDate).data('monthpicker').settings;
			for(var i=(month-1);i>=0;i--)
				settings['disabledMonths'].push(i);
			if((settings['selectedMonth']*1)<month)                        
				$(toDate).monthpicker('setMonthActive', month);
			$(toDate).monthpicker('disableMonths', settings['disabledMonths']);
		});
		$(toDate).createMonthPicker($.extend(options,{prepend:'Seleccione Mes Fin'}), function (e, month) {
                    if(typeof $(fromDate).data('monthpicker')==='undefined') return;
			$(fromDate).monthpicker('disableMonths', []); 
			var settings=$(fromDate).data('monthpicker').settings;
			for(var i=(month+1);i<=12;i++)
				settings['disabledMonths'].push(i);
			if((settings['selectedMonth']*1)>month)                       
				$(fromDate).monthpicker('setMonthActive', month);

			$(fromDate).monthpicker('disableMonths', settings['disabledMonths']);
		});
                $(fromDate).monthpicker('setMonthActive', 1);
                $(toDate).monthpicker('setMonthActive', 12); 
	}; 	
})(jQuery);