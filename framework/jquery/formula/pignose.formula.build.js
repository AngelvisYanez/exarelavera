/************************************************************************************************************
 *
 * @ Version 2.0.7
 * @ Formula Generator
 * @ Update 11. 11. 2016
 * @ Author PIGNOSE
 * @ Licensed under MIT.
 *
 ***********************************************************************************************************/

(function ($) {
    var _PLUGIN_VERSION_ = '2.0.7';
    String.prototype.toFormulaDecimal = function () {
        var split = this.split('.');
        return split[0].replace(/[^\d.]*/gi, '').replace(/\B(?=(\d{3})+(?!\d))/g, ",") + (typeof split[1] !== 'undefined' ? '.' + split[1].replace(/[^\d.]*/gi, '') : '');
    };

    String.prototype.toFormulaString = function (shift) {
        var keyCode = parseInt(this);
        if (keyCode == 106) {
            return 'x';
        } else if (((keyCode === 187 || keyCode === 61) && shift === true) || keyCode === 107) {
            return '+';
        } else if (keyCode === 189 || keyCode === 173 || keyCode === 109) {
            return '-';
        } else if (keyCode === 190 || keyCode === 110) {
            return '.';
        } else if (keyCode === 191 || keyCode === 111) {
            return '/';
        } else {
            return String.fromCharCode(keyCode);
        }
    };


    $.fn.formula = function (opt) {
        var _opt = {
            id: 'formula',
            cursorAnimTime: 160,
            cursorDelayTime: 500,
            strings: {
                formula: 'Formula',
                validationError: 'Validation Error',
                validationPassed: 'Passed'
            },
            import: {
                item: null,
            },
            export: {
                filter: function (data) {
                    var filterData = data;
                    if (typeof data !== 'undefined') {
                        filterData = data.map(function (e, i) {
                            return (typeof e !== 'undefined' && typeof e.value !== 'undefined') ? e.value : e;
                        });
                    }
                    return filterData;
                },
                item: function (e) {
                    return e.data('value') !== 'undefined' && e.data('value') !== null ? e.data('value') : e.text();
                }
            }
        };

        var _args = arguments;
        $.extend(true,_opt, opt);

        return this.each(function () {
            var $this = $(this);
            $this.data('formula', this);

            this.init = function () {
                var _this = this;
                var drag = false, move = false, offset = null;
                this.container = $(this).addClass(this.opt.id + '-container');
                this.container.wrap('<div class="' + this.opt.id + '-wrapper"></div>');

                this.alert = $('<div class="' + this.opt.id + '-alert">' + _opt.strings.formula + '</div>');
                this.alert.insertBefore(_this.container);

                this.text = $('<textarea id="' + this.opt.id + '-text" name="' + this.opt.id + '-text" class="' + this.opt.id + '-text"></textarea>');
                this.text.insertAfter(this.container).focus();
                this.text.bind('blur', function () {
                    if (_this.cursor !== null) {
                        _this.cursor.remove();
                        _this.destroyDrag();
                    }
                });

                this.text.unbind('dblclick.' + this.opt.id + 'Handler').bind('dblclick.' + this.opt.id + 'Handler', function (event) {
                    _this.selectAll();
                });

                this.text.unbind('mousedown.' + this.opt.id + 'Handler').bind('mousedown.' + this.opt.id + 'Handler', function (event) {
                    drag = true;

                    offset = {
                        x: event.offsetX,
                        y: event.offsetY
                    };
                });

                this.text.unbind('mouseup.' + this.opt.id + 'Handler').bind('mouseup.' + this.opt.id + 'Handler', function (event) {
                    drag = false;
                    if (move === true) {
                        move = false;
                    } else {
                        _this.click({
                            x: event.offsetX,
                            y: event.offsetY
                        });
                    }
                });

                var startIndex;
                this.text.unbind('mousemove.' + this.opt.id + 'Handler').bind('mousemove.' + this.opt.id + 'Handler', function (event) {
                    if (drag === false) {
                        return true;
                    }

                    if (Math.abs(offset.x - event.offsetX) <= 5 && Math.abs(offset.y - event.offsetY) <= 5) {
                        return true;
                    }

                    if (_this.container.hasClass('formula-active')) {
                        _this.click({
                            x: event.offsetX,
                            y: event.offsetY
                        });
                    }

                    move = true;
                    if (_this.container.find('.' + _this.opt.id + '-drag').length > 0) {
                        var endIndex = 0;
                        _this.destroyDrag();
                        _this.click({
                            x: event.offsetX,
                            y: event.offsetY
                        });
                        endIndex = _this.cursor.index();

                        $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                        var start = 0, end = 0;
                        if (startIndex > endIndex) {
                            end = startIndex;
                            start = endIndex;
                            $drag.insertBefore(_this.cursor);
                        } else {
                            start = startIndex;
                            end = endIndex;
                            $drag.insertAfter(_this.cursor);
                        }

                        if (start == end) {
                            return true;
                        }

                        _this.container.children(':not(".' + _this.opt.id + '-cursor")').filter(':gt("' + start + '")').filter(':lt("' + (end - start) + '")')
                        .add(_this.container.children(':not(".' + _this.opt.id + '-cursor")').eq(start)).each(function () {
                            var $this = $(this);
                            $this.appendTo($drag);
                        });

                        if (startIndex > endIndex) {
                            $drag.insertAfter(_this.cursor);
                        } else {
                            $drag.insertBefore(_this.cursor);
                        }
                    } else {
                        _this.destroyDrag();
                        _this.click({
                            x: event.offsetX,
                            y: event.offsetY
                        });
                        startIndex = _this.cursor.index();

                        $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                        $drag.insertAfter(_this.cursor);
                    }
                });

                _this.text.unbind('keydown.' + this.opt.id + 'Handler').bind('keydown.' + this.opt.id + 'Handler', function (event) {
                    event.preventDefault();
                    var $drag, $prev, $next, $item, $dragItem, text, parentPadding;

                    if (_this.cursor !== null && _this.cursor.length > 0) {
                        var keyCode = event.which;
                        if (keyCode == 116 || (keyCode == 82 && event.ctrlKey)) {
                            location.reload();
                        } else if (keyCode == 65 && event.ctrlKey) {
                            _this.selectAll();
                        } else if (keyCode >= 96 && keyCode <= 105) {
                            keyCode -= 48;
                        } else if (keyCode === 8) {
                            $drag = _this.container.find('.' + _this.opt.id + '-drag');
                            if ($drag.length > 0) {
                                _this.cursor.insertBefore($drag);
                                $drag.remove();
                            } else if (_this.cursor.length > 0 && _this.cursor.prev().length > 0) {
                                $prev = _this.cursor.prev();
                                if ($prev.hasClass(_this.opt.id + '-unit') && $prev.text().length > 1) {
                                    text = $prev.text();
                                    _this.setDecimal($prev, text.substring(0, text.length - 1).toFormulaDecimal());
                                } else {
                                    $prev.remove();
                                }
                            }
                            _this.syntaxCheck();
                            return false;
                        } else if (keyCode == 46) {
                            $drag = _this.container.find('.' + _this.opt.id + '-drag');
                            if ($drag.length > 0) {
                                _this.cursor.insertAfter($drag);
                                $drag.remove();
                            } else {
                                if (_this.cursor.length > 0 && _this.cursor.next().length > 0) {
                                    $next = _this.cursor.next();
                                    if ($next.hasClass(_this.opt.id + '-unit') && $next.text().length > 1) {
                                        text = $next.text();
                                        _this.setDecimal($next, text.substring(1, text.length).toFormulaDecimal());
                                    } else {
                                        $next.remove();
                                    }
                                }
                            }
                            _this.syntaxCheck();
                            return false;
                        } else if (keyCode >= 37 && keyCode <= 40) {
                            if (keyCode == 37) {
                                if (_this.cursor.length > 0 && _this.cursor.prev().length > 0) {
                                    if (event.shiftKey) {
                                        $drag = _this.container.find('.' + _this.opt.id + '-drag');
                                        if ($drag.length < 1) {
                                            $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                            $drag.insertAfter(_this.cursor);
                                        } else {
                                            if ($drag.data('active') === false) {
                                                _this.destroyDrag();
                                                $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                                $drag.insertAfter(_this.cursor);
                                            }
                                        }
                                        $drag.data('active', true);

                                        $prev = _this.cursor.prev();
                                        if ($prev.hasClass(_this.opt.id + '-drag')) {
                                            $dragItem = $drag.children('*');
                                            if ($dragItem.length < 1) {
                                                $drag.remove();
                                            } else {
                                                $dragItem.last().insertAfter($drag);
                                                _this.cursor.insertAfter($drag);
                                            }
                                        } else {
                                            _this.cursor.prev().prependTo($drag);
                                        }
                                    } else {
                                        _this.destroyDrag();
                                        _this.cursor.insertBefore(_this.cursor.prev());
                                    }
                                } else {
                                    _this.destroyDrag();
                                }
                            } else if (keyCode == 38) {
                                if (_this.cursor.prev().length > 0 || _this.cursor.next().length > 0) {
                                    parentPadding = {
                                        x: parseFloat(_this.container.css('padding-left').replace(/[^\d.]/gi, '')),
                                        y: parseFloat(_this.container.css('padding-top').replace(/[^\d.]/gi, ''))
                                    };

                                    $item = _this.cursor.prev();
                                    if ($item.length < 0) {
                                        $item = _this.cursor.next();
                                    }
                                    _this.click({
                                        x: _this.cursor.position().left + $item.outerWidth(),
                                        y: _this.cursor.position().top - $item.outerHeight() / 2
                                    });
                                } else {

                                }
                            } else if (keyCode === 39) {
                                if (_this.cursor.length > 0 && _this.cursor.next().length > 0) {
                                    if (event.shiftKey) {
                                        $drag = _this.container.find('.' + _this.opt.id + '-drag');
                                        if ($drag.length < 1) {
                                            $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                            $drag.insertBefore(_this.cursor);
                                        } else {
                                            if ($drag.data('active') === false) {
                                                _this.destroyDrag();
                                                $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                                $drag.insertBefore(_this.cursor);
                                            }
                                        }
                                        $drag.data('active', true);

                                        $next = _this.cursor.next();
                                        if ($next.hasClass(_this.opt.id + '-drag')) {
                                            $dragItem = $drag.children('*');
                                            if ($dragItem.length < 1) {
                                                $drag.remove();
                                            } else {
                                                $dragItem.first().insertBefore($drag);
                                                _this.cursor.insertBefore($drag);
                                            }
                                        } else {
                                            _this.cursor.next().appendTo($drag);
                                        }
                                    } else {
                                        _this.destroyDrag();
                                        _this.cursor.insertAfter(_this.cursor.next());
                                    }
                                } else {
                                    _this.destroyDrag();
                                }
                            } else if (keyCode == 40) {
                                if (_this.cursor.prev().length > 0 || _this.cursor.next().length > 0) {
                                    parentPadding = {
                                        x: parseFloat(_this.container.css('padding-left').replace(/[^\d.]/gi, '')),
                                        y: parseFloat(_this.container.css('padding-top').replace(/[^\d.]/gi, ''))
                                    };

                                    $item = _this.cursor.prev();
                                    if ($item.length < 0) {
                                        $item = _this.cursor.next();
                                    }
                                    _this.click({
                                        x: _this.cursor.position().left + $item.outerWidth(),
                                        y: _this.cursor.position().top + $item.outerHeight() * 1.5
                                    });
                                } else {

                                }
                            }
                            return false;
                        } else if (keyCode == 35 || keyCode == 36) {
                            if (keyCode == 35) {
                                if (_this.cursor.length > 0 && _this.container.children(':last').length > 0) {
                                    if (event.shiftKey) {
                                        $drag = _this.container.find('.' + _this.opt.id + '-drag');
                                        if ($drag.length < 1) {
                                            $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                            $drag.insertBefore(_this.cursor);
                                        } else {
                                            if ($drag.data('active') === false) {
                                                _this.destroyDrag();
                                                $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                                $drag.insertBefore(_this.cursor);
                                            }
                                        }
                                        $drag.data('active', true);
                                        _this.cursor.nextAll().appendTo($drag);
                                    } else {
                                        _this.destroyDrag();
                                        _this.cursor.insertAfter(_this.container.children(':last'));
                                    }
                                }
                            } else if (keyCode == 36) {
                                if (_this.cursor.length > 0 && _this.container.children(':first').length > 0) {
                                    if (event.shiftKey) {
                                        $drag = _this.container.find('.' + _this.opt.id + '-drag');
                                        if ($drag.length < 1) {
                                            $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                            $drag.insertAfter(_this.cursor);
                                        } else {
                                            if ($drag.data('active') === false) {
                                                _this.destroyDrag();
                                                $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                                                $drag.insertAfter(_this.cursor);
                                            }
                                        }
                                        $drag.data('active', true);
                                        _this.cursor.prevAll().each(function () {
                                            var $this = $(this);
                                            $this.prependTo($drag);
                                        });
                                    } else {
                                        _this.destroyDrag();
                                        _this.cursor.insertBefore(_this.container.children(':first'));
                                    }
                                }
                            } else {
                                return false;
                            }
                        }else if (((keyCode >= 48 && keyCode <= 57)||keyCode===110) === false &&
                                  (keyCode !== 88 && keyCode !== 106 && keyCode !== 107 && keyCode !== 109 && keyCode !== 111 && keyCode !== 189 && keyCode !== 190 /*&& keyCode !== 191*/) === true) {
                            return false;
                        }// Agregado por erik para que funcione el NumPad
						/*else if ((keyCode >= 48 && keyCode <= 57) === false &&
                                  (keyCode !== 88 && keyCode !== 187 && keyCode !== 189 && keyCode !== 190 && keyCode !== 191) === true) {
                            return false;
                        }*/

                        _this.keydown(keyCode.toString().toFormulaString(event.shiftKey), event.shiftKey);
                        _this.syntaxCheck();
                    }
                });
            };

            this.syntaxCheck = function (callback) {
                var _this = this;
                var formula = _this.getFormula().data;

                if (typeof formula !== 'undefined') {
                    var result = new FormulaParser(formula);
                    if (result.status === true) {
                        _this.alert.text(_this.opt.strings.validationPassed).addClass(_this.opt.id + '-alert-good').removeClass(_this.opt.id + '-alert-error');
                        if (typeof callback === 'function') {
                            callback(true);
                        }
                    }
                    else {
                        _this.alert.text(_this.opt.strings.validationError).removeClass(_this.opt.id + '-alert-good').addClass(_this.opt.id + '-alert-error');
                        if (typeof callback === 'function') {
                            callback(false);
                        }
                    }
                }
            };

            this.destroyDrag = function () {
                var _this = this;
                var $drag = _this.container.find('.' + _this.opt.id + '-drag');
                $drag.children('*').each(function () {
                    var $this = $(this);
                    $this.insertBefore($drag);
                });
                $drag.remove();
            };

            this.selectAll = function () {
                var _this = this;
                _this.destroyDrag();
                $drag = $('<div class="' + _this.opt.id + '-drag"></div>');
                $drag.prependTo(_this.container);
                _this.container.children(':not(".' + _this.opt.id + '-cursor")').each(function () {
                    var $this = $(this);
                    $this.appendTo($drag);
                });
            };

            this.click = function (pos) {
                var _this = this;
                var $cursor = $('<div class="' + this.opt.id + '-cursor"></div>');
                var check = null, idx = null;

                pos = pos || { x: 0, y: 0 };

                this.container.find('.' + this.opt.id + '-cursor').remove();
                $cursor.appendTo(this.container);
                this.cursor = $cursor;

                var parentPos = {
                    x: _this.container.offset().left,
                    y: _this.container.offset().top
                };

                var parentPadding = {
                    x: parseFloat(_this.container.css('padding-left').replace(/[^\d.]/gi, '')),
                    y: parseFloat(_this.container.css('padding-top').replace(/[^\d.]/gi, ''))
                };

                var checkArea = [];

                _this.container.children('*:not(".' + _this.opt.id + '-cursor")').each(function () {
                    var $this = $(this);
                    checkArea.push({
                        x: $this.offset().left - parentPos.x + parentPadding.x,
                        y: $this.offset().top - parentPos.y,
                        e: $this
                    });
                });


                var $pointer = null;
                var maxY = 0, maxDiff = 10000;
                for (idx in checkArea) {
                    check = checkArea[idx];
                    if (check.y <= pos.y) {
                        if (check.y >= maxY * 0.5 && check.x <= pos.x) {
                            if (check.y >= maxY) {
                                maxY = check.y;
                            }
                            if (pos.x - check.x <= maxDiff) {
                                maxDiff = pos.x - check.x;
                                $pointer = check.e;
                            }
                        }
                    }
                }

                if ($pointer === null) {
                    maxY = 0;
                    maxDiff = 10000;
                    for (idx in checkArea) {
                        check = checkArea[idx];
                        if (check.y >= maxY * 0.5 && check.x <= pos.x) {
                            if (check.y >= maxY) {
                                maxY = check.y;
                            }
                            if (pos.x - check.x < maxDiff) {
                                maxDiff = pos.x - check.x;
                                $pointer = check.e;
                            }
                        }
                    }
                }

                if (checkArea.length > 0 && $pointer !== null && maxY + checkArea[0].e.outerHeight() >= pos.y) {
                    _this.cursor.insertAfter($pointer);
                } else {
                    if (checkArea.length > 0 && pos.x > checkArea[0].x) {
                        _this.cursor.appendTo(_this.container);
                    } else {
                        _this.cursor.prependTo(_this.container);
                    }
                }

                var loop = function () {
                    setTimeout(function () {
                        if ($cursor.hasClass('inactive')) {
                            $cursor.removeClass('inactive');
                            $cursor.stop().animate({ opacity: 1 }, _this.opt.cursorAnimTime);
                        } else {
                            $cursor.addClass('inactive');
                            $cursor.stop().animate({ opacity: 0 }, _this.opt.cursorAnimTime);
                        }

                        if ($cursor.length > 0) {
                            loop();
                        }
                    }, _this.opt.cursorDelayTime);
                };
                loop();

                _this.destroyDrag();
            };

            this.keydown = function (key, shift) {
                var _this = this;
				//console.log(key);
                var convert = {
                    0: '.',
                    1: '!',
                    2: '@',
                    3: '#',
                    4: '$',
                    5: '%',
                    6: '&',
                    7: '/',
                    8: '(',
                    9: ')'
                };

                if (shift && (key >= 0 && key <= 9)) {
                    key = convert[key];
                }
				//console.log(key);
                key = $.trim(key);

                this.insertChar.call(this, key);
            };

            this.insertChar = function (key) {
				
                var _this = this;
                if ((key >= 0 && key <= 9) || $.inArray(key.toLowerCase(), _this.permitedKey) != -1) {
                    if ((key >= 0 && key <= 9) || key == '.') {
                        var $unit = $('<div class="' + _this.opt.id + '-item ' + _this.opt.id + '-unit">' + key + '</div>');
                        var $item = null;
                        var decimal = '', merge = false;

                        $drag = _this.container.find('.' + _this.opt.id + '-drag');

                        if ($drag.length > 0) {
                            _this.cursor.insertBefore($drag);
                            $drag.remove();
                        }

                        if (this.cursor !== null && this.cursor.length > 0) {
                            this.cursor.before($unit);
                        } else {
                            this.container.append($unit);
                        }

                        var $prev = $unit.prev();
                        var $next = $unit.next();

                        if ($prev.length > 0 && $prev.hasClass(_this.opt.id + '-cursor')) {
                            $prev = $prev.prev();
                        }

                        if ($next.length > 0 && $next.hasClass(_this.opt.id + '-cursor')) {
                            $next = $next.next();
                        }

                        if ($prev.length > 0 && $prev.hasClass(_this.opt.id + '-unit')) {
                            merge = true;
                            $item = $prev;
                            $item.append($unit[0].innerHTML);
                        } else if ($next.length > 0 && $next.hasClass(_this.opt.id + '-unit')) {
                            merge = true;
                            $item = $next;
                            $item.prepend($unit[0].innerHTML);
                        }

                        if (merge === true) {
                            decimal = $item.text().toFormulaDecimal();
                            _this.setDecimal($item, decimal);
                            $unit.remove();
                        }
                    } else if (key !== '') {
						if(key==='*') key='x';
                        var $operator = $('<div class="' + _this.opt.id + '-item ' + _this.opt.id + '-operator">' + key.toLowerCase() + '</div>');
                        if (this.cursor !== null && this.cursor.length > 0) {
                            this.cursor.before($operator);
                        } else {
                            this.container.append($operator);
                        }
                        if (key == '(' || key == ')') {
                            $operator.addClass(_this.opt.id + '-bracket');
                        }
                    }
                }
            };

            this.empty = function () {
                this.container.find(':not(".' + this.opt.id + '-cursor")').remove();
                return this.container;
            };

            this.setDecimal = function (e, decimal) {
                var _this = this;
                if (decimal !== '') {
                    e.empty();
                    var split = decimal.split('.');
                    var $prefix = $('<span class="' + _this.opt.id + '-prefix ' + _this.opt.id + '-decimal-highlight">' + split[0] + '</span>');
                    $prefix.appendTo(e);
                    if (typeof split[1] !== 'undefined') {
                        var $surfix = $('<span class="' + _this.opt.id + '-surfix ' + _this.opt.id + '-decimal-highlight">.' + split[1] + '</span>');
                        $surfix.appendTo(e);
                    }
                }
            };

            this.setFormula = function (data) {
                var _this = this;
                this.empty();
				this.cursor=null; //agregado por erik
                try {
                    var obj = null;
                    if (typeof data !== 'object') {
                        obj = JSON.parse(data);
                    } else {
                        obj = data;
                    }
					//console.log(obj);
                    var decodedData = new FormulaParser(JSON.parse(JSON.stringify(obj)));
					//console.log(decodedData);
                    if (decodedData.status === true) {
                        this.insertFormula.call(this, decodedData.data);
                    }
                } catch (e) {
                    console.trace(e.stack);
                }
            };

            this.getFormula = function () {
                var _this = this;
                var data = [];
                //var originalData = null; //agregado por erik
				var filterData = null;
                 
                if (typeof _this.opt.export.filter === 'function') {
					//console.log('ver');
                    _this.container.find('.formula-item').each(function () {
                        var $this = $(this);
                        var item = {};
                        item.value = ($this.data('value') ? $this.data('value') : $this.text());

                        if ($this.hasClass(_this.opt.id + '-unit')) {
                            item.type = 'unit';
                            item.value = item.value.toFormulaDecimal();
                        } else if ($this.hasClass(_this.opt.id + '-custom')) {
                            item.type = 'item';
                            if (typeof _this.opt.export !== 'undefined' && typeof _this.opt.export.item === 'function') {
                                try { // agregado por erik
                                    var itemAux=_this.opt.export.item.call(_this, $this);
									if(typeof itemAux==='object') $.extend(item,itemAux); else item.value = itemAux;
                                } catch (e) {
                                    item.value = '0';
                                }
                            } else {
                                item.value = '0';
                            }
                        } else if ($this.hasClass(_this.opt.id + '-operator')) {
                            item = item.value === 'x' ? '*' : item.value;
                        }
                        data.push(item);
                    });		
					//console.log(JSON.parse(JSON.stringify(data)));
					//console.log(data);
					filterData = new FormulaParser(JSON.parse(JSON.stringify(data)));
					
					//originalData=$.extend(true,{},(Array.isArray(data)&&data.length===1?data[0]:data)); //agregado por erik
                    data = _this.opt.export.filter(data);	
					//console.log(data);
                    

                    return {
                        data: data,
                        filterData: filterData//,
						//originalData:originalData
                    };
                } else {
                    _this.container.find('.formula-item').each(function () {
                        var $this = $(this);
                        var value = ($this.data('value') ? $this.data('value') : $this.text());
                        if ($this.hasClass(_this.opt.id + '-unit')) {
                            value = value.toFormulaDecimal();
                        } else if ($this.hasClass(_this.opt.id + '-operator') && value === 'x') {
                            value = '*';
                        } else if ($this.hasClass(_this.opt.id + '-custom')) {
                            if (typeof _this.opt.export !== 'undefined' && typeof _this.opt.export.item === 'function') {
                                try {
                                    value = _this.opt.export.call(_this, $this);
                                } catch (e) {
                                    value = '0';
                                }
                            } else {
                                value = '0';
                            }
                        }
                        data.push(value);
                    });

                    return {
                        data: data.join(' '),
                        filterData: filterData//,
						//originalData:originalData
                    };
                }
            };

            this.insert = function (e) {
                var _this = this;

                
                if (typeof e === 'string') {
                    e = $(e);
                }
                e.addClass(_this.opt.id + '-item');
				if (_this.cursor === null || _this.cursor.length < 1) {
                    //_this.click();
					$(_this).append(e); // cambiado por erik
                }else
					e.insertBefore(_this.cursor);
                _this.text.focus();
                _this.syntaxCheck();
            };

            this.insertFormula = function (data) {
                var _this = this;
                var idx = 0;

                if (typeof data === 'string') {
                    var data_split = data.split('');
                    for (idx in data_split) {
                        this.insertChar.call(this, data_split[idx]);
                    }
                } else {
                    for (idx in data) {
                        var item = data[idx];
                        if (typeof item !== 'object') {
                            var data_splited = item.toString().split('');
                            for (var key in data_splited) {
                                this.insertChar.call(this, data_splited[key]);
                            }
                        } else {
                            if (typeof _this.opt.import.item === 'function') {
                                var $e = _this.opt.import.item.call(_this, item);
                                if (typeof $e !== 'undefined' && $e !== null) {
                                    _this.insert($e);
                                }
                            }
                        }
                    }
                }
                _this.syntaxCheck();
            };

            if (_args.length < 1 || typeof _args[0] === 'object') {
                this.alert = null;
                this.text = null;
                this.container = null;
                this.cursor = null;
                this.opt = _opt;
                this.permitedKey = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 'x', '*', '/', '.', '+', '-', '%', '^', '(', ')'];
                $.extend(opt, _opt);
                this.init.call(this);
            } else {
                this[opt].apply(this, Array.prototype.slice.call(_args, 1));
            }
        });
    };

    $.fn.formula.getVersion = function () {
        return _PLUGIN_VERSION_;
    };
}(jQuery));

/************************************************************************************************************
 *
 * @ Version 2.0.6
 * @ FormulaParser
 * @ Date 11. 11. 2016
 * @ Author PIGNOSE
 * @ Licensed under MIT.
 *
 ***********************************************************************************************************/

var FormulaParser = (function () {
    var _PLUGIN_VERSION_ = '2.0.6';

    function FormulaParser(formula) {
        var idx;
        this.formula = formula;

        /***********************************************
         *
         * @ Note OperandToken Declaration
         *
         **********************************************/

        this.OperandToken = {};
        this.OperandToken.Addition = ['+'];
        this.OperandToken.Subtraction = ['-'];
        this.OperandToken.Multiplication = ['x', '*'];
        this.OperandToken.Division = ['/'];
        this.OperandToken.Mod = ['%'];
        this.OperandToken.Pow = ['^'];
        this.OperandToken.Bracket = ['(', ')', '[', ']', '{', '}'];

        /***********************************************
         *
         * @ Note Resitration the priority.
         *
         **********************************************/

        this.OperandPriority = [];
        this.OperandPriority[0] = [].concat(this.OperandToken.Mod, this.OperandToken.Pow);
        this.OperandPriority[1] = [].concat(this.OperandToken.Multiplication, this.OperandToken.Division);
        this.OperandPriority[2] = [].concat(this.OperandToken.Addition, this.OperandToken.Subtraction);

        /***********************************************
         *
         * @ Note Resitration operators.
         *
         **********************************************/

        this.Operators = [];
        for (idx in this.OperandToken) {
            var item = this.OperandToken[idx];
            this.Operators = this.Operators.concat(item);
        }

        /***********************************************
         *
         * @ Note Resitration units.
         *
         **********************************************/

        this.Units = [].concat(this.Operators, this.OperandToken.Bracket);

        /***********************************************
         *
         * @ Note Resitration parsers.
         *
         **********************************************/

        this.Parsers = [
            'Initializer',
        	'LayerParser',
        	'SyntaxParser',
            'FilterParser',
            'StringParser'
        ];

        this.ParserMap = {};

        for (idx in this.Parsers) {
            var parser = this.Parsers[idx];
            this.ParserMap[parser] = parser;
        }

        this.Message = {};
        this.Message[0x01] = 'Formula must has characters than {0} times';
        this.Message[0x02] = '\'{0}\' operator is not supported.';
        this.Message[0x03] = 'Left side operand is not valid.';
        this.Message[0x04] = 'Right side operand is not valid.';
        this.Message[0x05] = 'Bracket must be opened.';
        this.Message[0x06] = 'Bracket must be closed.';
        this.Message[0x20] = 'Operator\'s key must be in data.';
        this.Message[0x21] = 'Left operand\'s key must be in data.';
        this.Message[0x22] = 'Right operand\'s key must be in data.';
        this.Message[0xA0] = 'Formula expression is null or undefined.';

        /***********************************************
         *
         * @ Start to parsing.
         *
         **********************************************/

        return this.init();
    }

    /**
     * This method retuns current version. (This isn't prototype function.)
     * @namespace FormulaParser
     * @method getVersion
     * @return {Number}
     */
    FormulaParser.getVersion = function () {
        return _PLUGIN_VERSION_;
    };

    /**
     * When item is in the array, This will returns true.
     * @namespace FormulaParser
     * @method inArray
     * @param {Dynamic} i - item
     * @param {Array} a - array
     * @return {bool}
     */
    FormulaParser.prototype.inArray = function (i, a) {
        for (var idx in a) if (a[idx] === i) return idx;
        return -1;
    };

    /**
     * When item is operand type(number, object), This will returns true.
     * @namespace FormulaParser
     * @method isOperand
     * @param {Dynamic} i - item
     * @return {bool}
     */
    FormulaParser.prototype.isOperand = function (i) {
        return typeof i === 'object' || this.isNumeric(i);
    };

    /**
     * Get operator string to priority number.
     * @namespace FormulaParser
     * @method getOperatorPriority
     * @param {String} operator
     * @return {Number}
     */
    FormulaParser.prototype.getOperatorPriority = function (operator) {
        if (this.inArray(operator, this.Operators) === -1) {
            return -1;
        } else {
            var priority = -1;
            for (var idx = 0; idx < this.OperandPriority.length; idx++) {
                if (this.inArray(operator, this.OperandPriority[idx]) !== -1) {
                    priority = idx;
                    break;
                }
            }
            return priority;
        }
    };

    /**
     * When item is number type, This will returns true. The method is part of isOperand.
     * @namespace FormulaParser
     * @method isNumeric
     * @param {Number} n - number
     * @return {bool}
     */
    FormulaParser.prototype.isNumeric = function (n) {
        return (/\d+(\.\d*)?|\.\d+/).test(n);
    };

    /**
     * This method can make string type formula to array.
     * @namespace FormulaParser
     * @method stringToArray
     * @param {String} s - formula string
     * @return {array}
     */
    FormulaParser.prototype.stringToArray = function (s) {
        var data = [];
        var dataSplited = s.split('');
        var dataSplitedLen = dataSplited.length;
        for (var idx = 0; idx < dataSplitedLen; idx++) {
            var item = dataSplited[idx];
            if (this.inArray(item, this.Units) !== -1 || this.isOperand(item) === true) {
                if (idx > 0 && this.isOperand(item) === true && this.isOperand(data[data.length - 1]) === true) {
                    data[data.length - 1] += item.toString();
                } else {
                    data.push(item);
                }
            }
        }
        return data;
    };

    /**
     * Standard logger for formula parser, But this method does not display in console.
     * @namespace FormulaParser
     * @method log
     * @param {Number} code - return code
     * @param {Dynamic} data - return data
     * @param {Array} mapping - return message mapping data
     * @return {array}
     */
    FormulaParser.prototype.log = function (code, data, mapping) {
        var message = this.Message[code], idx, item;

        for (idx in mapping) {
            item = mapping[idx];
            message = message.replace(new RegExp('\\\{' + idx + '\\\}', 'g'), item);
        }

        var obj = {
            status: code === 0x00,
            code: code,
            msg: message
        };

        if (typeof data !== 'undefined') {
            for (idx in data) {
                item = data[idx];
                if (typeof item !== 'function') {
                    obj[idx] = item;
                }
            }
        }

        return obj;
    };

    /**
     * Layer parser is examination all formula syntax minutely and parsing by search method.
     * @namespace FormulaParser
     * @method layerParser
     * @related search method
     * @param {Array} data - formula array data
     * @param {Number} pos - formula stack cursor
     * @param {Number} depth - formula search depth (start from 0)
     * @return {Object}
     */
    FormulaParser.prototype.layerParser = function (data, pos, depth) {
        var innerDepth = 0;
        var startPos = [], endPos = [];
        var currentParser = this.ParserMap.LayerParser;
        var totalLength = data.length;

        depth = depth || 0;

        if (data.length === 1 && typeof data[0] !== 'object') {
            return {
                status: true,
                data: data[0],
                length: 1
            };
        }

        for (var idx = 0; idx < data.length; idx++) {
            var item = data[idx];
            if (item === '(') {
                innerDepth++;
                startPos[innerDepth] = idx + 1;
            } else if (item === ')') {
                if (innerDepth < 1) {
                    return this.log(0x05, {
                        stack: currentParser,
                        col: startPos.length > 0 ? startPos[startPos.length - 1] : 0
                    });
                }

                if (innerDepth === 1) {
                    var paramData = [];
                    endPos[innerDepth] = idx - 1;

                    for (var j = startPos[innerDepth]; j <= endPos[innerDepth]; j++) {
                        paramData.push(data[j]);
                    }

                    var result = this.search(paramData, pos + startPos[innerDepth] + 1, depth + 1);

                    if (result.status === false) {
                        return result;
                    } else {
                        var length = result.length;
                        //if (typeof result.data === 'object' && typeof result.data[0] !== 'object' && result.data.length === 1) {
						if ( typeof result.data[0] === 'object' && result.data.length === 1) {
                            result.data = result.data[0];
                        }
                        data.splice(startPos[innerDepth] - 1, length + 2, result.data);
                        idx -= length + 1;
                    }
                }
                innerDepth--;
            }
        }

        if (innerDepth > 0) {
            return this.log(0x06, {
                stack: currentParser,
                col: data.length || -1
            });
        }

        return {
            status: true,
            depth: depth,
            length: totalLength || -1
        };
    };

    /**
     * Syntax layer makes formula object from formula expression.
     * @namespace FormulaParser
     * @method syntaxParser
     * @related search method
     * @param {Array} data - formula array data
     * @param {Number} pos - formula stack cursor
     * @param {Number} depth - formula search depth (start from 0)
     * @param {Number} length - compressed formula expression length
     * @param {Array} operators - permitted formula unit array
     * @return {Object}
     */
    FormulaParser.prototype.syntaxParser = function (data, pos, depth, length, operators) {
        this.currentParser = this.ParserMap.SyntaxParser;

        data = data || [];
        pos = pos || 0;
        depth = depth || 0;

        var cursor = pos;

        if (typeof data[0] !== 'undefined' && typeof data[0][0] === 'object' && typeof data[0].operator === 'undefined') {
            data[0] = data[0][0];
        }

        if (data.length < 3) {
            if (data.length <= 1 && typeof data[0] === 'object' && typeof data[0].operator !== 'undefined') {
                return data[0];
            } else {
                return this.log(0x01, {
                    stack: this.currentParser,
                    col: pos + (typeof data[0] === 'object' ? data[0].length : 0) + 1
                }, [3]);
            }
        }

        if (typeof data.length !== 'undefined') {
            if (data.length > 1) {
                for (var idx = 0; idx < data.length; idx++) {
                    cursor = idx + pos;
                    var item = data[idx];
                    if (this.inArray(item, this.Operators) === -1 && this.isOperand(item) === false) {
                        return this.log(0x02, {
                            stack: this.currentParser,
                            col: cursor
                        }, [item]);
                    }

                    if (this.inArray(item, operators) !== -1) {
                        if (this.isOperand(data[idx - 1]) === false) {
                            return this.log(0x03, {
                                stack: this.currentParser,
                                col: cursor - 1
                            });
                        }

                        if (this.isOperand(data[idx + 1]) === false) {
                            return this.log(0x04, {
                                stack: this.currentParser,
                                col: cursor + 1
                            });
                        }

                        data.splice(idx - 1, 3, {
                            operator: item,
                            operand1: data[idx - 1],
                            operand2: data[idx + 1],
                            length: length
                        });

                        if (typeof data[idx - 1][0] === 'object') {
                            data[idx - 1] = data[idx - 1][0];
                        }

                        idx--;
                    }
                }
            }
        }

        return {
            status: true,
            data: data
        };
    };

    /**
     * Filter parser remains the formula object's only useful data for user
     * @namespace FormulaParser
     * @method filterParser
     * @related search method
     * @param {Object} data - formula object
     * @return {Object}
     */
    FormulaParser.prototype.filterParser = function (data) {
		//console.log(data);
        if (typeof data[0] === 'object') {			
            data = data[0];
        }
		

        if (typeof data.operand1 === 'object') {
            this.filterParser(data.operand1);
        }

        if (typeof data.operand2 === 'object') {
            this.filterParser(data.operand2);
        }

        if (typeof data.length !== 'undefined') {
            delete data.length;
        }

        return data;
    };

    /**
     * String parser is using for convert formula object to readable formula array.
     * @namespace FormulaParser
     * @method stringParser
     * @related collapse method
     * @param {Object} data - formula object
     * @param {Number} depth - formula parse depth
     * @param {Number} pos - formula stack cursor
     * @return {Array}
     */
    FormulaParser.prototype.stringParser = function (data, depth, pos) {
        this.currentParser = this.ParserMap.StringParser;

        var _this = this;
        var formula = [];

        depth = depth || 0;
        pos = pos || 0;

        if (typeof data.value === 'undefined') {
            if (typeof data.operator === 'undefined') {
                return this.log(0x20, {
                    stack: this.currentParser,
                    col: pos,
                    depth: depth
                });
            } else if (typeof data.operand1 === 'undefined') {
                return this.log(0x21, {
                    stack: this.currentParser,
                    col: pos,
                    depth: depth
                });
            } else if (typeof data.operand2 === 'undefined') {
                return this.log(0x22, {
                    stack: this.currentParser,
                    col: pos,
                    depth: depth
                });
            }
        } else {
			//console.log('verr',data);
			if(data.type==='item'){
				return {
					status: true,
					data: data
				};
			}else
            return {
                status: true,
                data: ((data.value.type === 'unit') ? data.value.unit : data.value)
            };
        }

        var params = ['operand1', 'operator', 'operand2'];
        for (var idx = 0; idx < params.length; idx++) {
            var param = params[idx];
            if (typeof data[param] === 'object') {
                var result = _this.stringParser(data[param], depth + 1, pos + idx);
                if (result.status === false) {
                    return result;
                } else {
                    formula = formula.concat(result.data);
                    if (typeof data.operator !== 'undefined' && typeof result.operator !== 'undefined') {
                        if (this.getOperatorPriority(data.operator) < this.getOperatorPriority(result.operator) && this.getOperatorPriority(data.operator) !== -1) {
                            formula.splice([formula.length - 3], 0, '(');
                            formula.splice([formula.length], 0, ')');
                        }
                    }
                }
            } else {
                formula.push(data[param]);
            }
        }

        return {
            status: true,
            data: formula,
            operator: depth > 0 ? data.operator : undefined
        };
    };

    /**
     * Search method routes each of commands to right steps.
     * @namespace FormulaParser
     * @method search
     * @related layerParser, syntaxParser, filterParser methods.
     * @param {Array} data - formula array data
     * @param {Number} pos - formula stack cursor
     * @param {Number} depth - formula search depth (start from 0)
     * @return {Object}
     */
    FormulaParser.prototype.search = function (data, pos, depth) {
        var _super = this;
        pos = pos || 0;
        depth = depth || 0;

        if (typeof data === 'string' && depth < 1) {
            data = this.stringToArray(data);
        }

        var result = null;
        var len = this.OperandPriority.length + 1;
        var parserLength = 0;
		//console.log(data);
        var parserComplete = function () {
            if (depth === 0) {
                data = _super.filterParser(data);				
            }

            return {
                status: true,
                data: data,
                length: depth === 0 ? undefined : parserLength,
                depth: depth === 0 ? undefined : depth
            };
        };
		//console.log('ver',data);
        for (var i = 0; i < len; i++) {
            if (result !== null && typeof result.data !== 'undefined' && result.data.length === 1) {
                return parserComplete.call();
            }

            if (i === 0) {
                result = this.layerParser(data, pos, depth);
                parserLength = result.length;
            } else {
                result = this.syntaxParser(data, pos, depth, parserLength, this.OperandPriority[i - 1]);
            }

            if (result.status === false) {
                return result;
            } else if (i + 1 === len) {
                return parserComplete.call();
            }
        }
    };

    /**
     * Collapse method can convert formula object to readable and user-friendly formula array.
     * @namespace FormulaParser
     * @method collapse
     * @related stringParser method.
     * @param {Object} data - formula object data
     * @param {Number} depth - formula search depth (start from 0)
     * @return {Object}
     */
    FormulaParser.prototype.collapse = function (data, depth) {
        var _this = this, formula = null;
        depth = depth || 0;
		//console.log(data);
        formula = this.stringParser(data, depth);
		//console.log('vergas');
        return {
            status: true,
            data: formula.data
        };
    };

    /**
     * Init method is fired when you declare FormulaParser object by new keyword.
     * @namespace FormulaParser
     * @method init
     * @related FormulaParser object.
     * @return {Dynamic}
     */
    FormulaParser.prototype.init = function () {
        if (typeof this.formula === 'undefined' || this.formula === null) {
            return this.log(0xA0, {
                stack: this.Parsers.Initializer,
                col: 0
            });
        } else if (typeof this.formula === 'string' || (typeof this.formula === 'object' && typeof this.formula.operator === 'undefined')) {
			//console.log(this.formula.operator);
            return this.search(this.formula);
        } else if (typeof this.formula === 'object' && typeof this.formula.operator !== 'undefined') {
			//console.log(this.formula.operator);
            return this.collapse(this.formula);
        } else {
            console.error('Unkown type formula', this.formula);
        }
    };

    return FormulaParser;
})();
