/**
 * JQuery flyout plugin.
 * In order to support IE9, this plugin uses jquery's animation instead of CSS3 transitions.
 * Idea and styles reference to Bootstrap popover.
 *
 * @author nobelhuang
 * @license MIT
 */
;(function($){
	/**
	 * The extended flyout plugin entry.
	 * @param {Object|null|string}	- when it is Object or omitted, this function initializes flyouts for elements. When it is string, this function treats it as a command. See default options for option details. Possible commands are: "show", "hide", "toggle" and "destroy".
	 */
	$.fn.flyout = function(options) {

		var opts = null;
		var cmd = null;

		/* normalize input */
		if (typeof(options) === 'object' || !options) {
			/* options for initializing */
			opts = $.extend({}, $.fn.flyout.defaults, options);
		}
		else if (typeof(options) === 'string') {
			/* commands for operations */
			cmd = options;
		}

		/* process for each elements */
		this.each(function() {

			var $this = $(this);

			if (opts) {
				/* initialize the flyout */
				$this.data(CONST.dataKey, {
					opts: opts,
					flyoutId: null
				});

				/* reset the initialization in case this is a re-entered initialization */
				$this.unbind('click', __clickHandler);
				$this.unbind('blur', __blurHandler);

				if (opts.trigger === 'click') {
					/* register handler for showing when click */
					$this.bind('click', __clickHandler);
				}
				if (opts.dismissible) {
					/* register handler for additional dismissing functionality */
					$this.bind('blur', __blurHandler);
				}
			}
			else if (cmd) {
				/* execute commands */
				var data = $this.data(CONST.dataKey);
				if (!data) {
					/* not associtated with a flyout */
					return;
				}

				switch (cmd) {
					case "show":
						__show.call(this, data);
						break;
					case "hide":
						__hide.call(this, data);
						break;
					case "toggle":
						if (data.flyoutId) {
							/* flyout is already in DOM, so hide it */
							__hide.call(this, data);
						}
						else {
							__show.call(this, data);
						}
						break;
					case "destroy":
						__destroy.call(this, data);
						break;
				}
			}
		});

		return this;
	};

	/**
	 * Constants used across this plugin
	 */
	var CONST = {
		dataKey: '__flyoutdata',
		template: '<div class="swlFlyout">' +
				'<div class="arrow"></div>' +
				'<div class="swlFlyout_title"></div>' +
				'<div class="swlFlyout_content"></div>' +
				'</div>',
		idPrefix: 'flyout',
		directionClasses: {
			top:'top', right:'right', bottom:'bottom', left:'left', bottom_left:'bottom_left', bottom_right:'bottom_right',top_left:'top_left', top_right:'top_right',
			right_top:'right_top',left_top:'left_top',right_bottom:'right_bottom',left_bottom:'left_bottom'
		},
		animation: {
			duration: 100,
			easing: 'linear'
		}
	};

	/**
	 * General utility functions
	 */
	function __clickHandler(event) {
		var $this = $(this);

		event.preventDefault();

		$this.flyout('toggle');
	}

	function __blurHandler(event) {
		var $this = $(this);
		$this.flyout('hide');
	}

	/**
	 * Show out the flyout besides the trigger.
	 * It will fire shown.flyout event once the flyout is shown.
	 * @this HTMLElement	- the trigger element
	 * @param {Object|null}	- the associated flyout data in the trigger
	 * @fires shown.flyout
	 */
	function __show(data) {
		var $this = $(this);
		data = data || $this.data(CONST.dataKey);

		var $flyout = null;

		if (data.flyoutId) {
			/* flyout exists, so reuse it */
			$flyout = $('#' + data.flyoutId);
		}
		else {
			/* compose a new flyout element, id will be saved to trigger data */
			var id = CONST.idPrefix + (Math.floor(Math.random() * 1000000));

			$flyout = $(CONST.template);
			
			if (!data.opts.title) {
				/* not title is provided, so hide title area */
				$flyout.find('.swlFlyout_title').hide();
			}

			/* set title and contents */
			var title = (typeof(data.opts.title) === 'function' ? ((data.opts.title).call($this)) : data.opts.title);
			$flyout.attr({ id: id }).find('.swlFlyout_title').text(title);

			var contents = (typeof(data.opts.content) === 'function' ? ((data.opts.content).call($this)) : data.opts.content);
			if (data.opts.html === true) {
				$flyout.find('.swlFlyout_content').html(data.opts.content);
			}
			else {
				$flyout.find('.swlFlyout_content').text(data.opts.content);
			}

			/* save the flyout id for later reference */
			data.flyoutId = id;

			$flyout.appendTo($this.offsetParent());			
		}

		/* set the placement */
		$.each(CONST.directionClasses,function(k,v){$flyout.removeClass(v);});
		//$flyout.removeClass(CONST.directionClasses.top + ' ' + CONST.directionClasses.right + ' ' +
		//					CONST.directionClasses.bottom + ' ' + CONST.directionClasses.left);
		$flyout.addClass(CONST.directionClasses[data.opts.placement]);

		/* set the width if specified */
		if (data.opts.width) {
			$flyout.css({
				width: data.opts.width,
				maxWidth: data.opts.width
			});
		}

		/* calculate and set the position */
		__positioning($this, $flyout, data.opts.placement);

		/* show it out */
		function __onShown() {
			$this.trigger("shown.flyout");
		}
		if (data.opts.animation === true) {
			$flyout.fadeIn(CONST.animation.duration, CONST.animation.easing, __onShown);
		}
		else {
			$flyout.show();
			__onShown.call($flyout[0]);
		}
		if(!data.opts.dismissed&&data.opts.trigger===null&&data.opts.autoDismis){
			if(typeof data.opts.to!=='undefined') clearTimeout(data.opts.to);
			data.opts.to=setTimeout(function(){ __hide(data); },data.opts.timeDismis);
		}
	}

	/**
	 * Hide the flyout.
	 * @this HTMLElement	- the trigger element
	 * @param {Object|null}	- the associated flyout data in the trigger
	 * @fires hidden.flyout
	 */
	function __hide(data) {
		var $this = $(this);
		data = data || $this.data(CONST.dataKey);

		var $flyout = null;

		if (!data.flyoutId) {
			/* nothing to hide */
			return;
		}

		$flyout = $('#' + data.flyoutId);

		function __onHidden() {
			/* also remove this flyout from the flow */
			$flyout.remove();
			/* set id to null to prevent reusing */
			data.flyoutId = null;

			$this.trigger("hidden.flyout");
		}
		if (data.opts.animation === true) {
			$flyout.fadeOut(CONST.animation.duration, CONST.animation.easing, __onHidden);
		}
		else {
			$flyout.hide();
			__onHidden.call($flyout[0]);
		}
	}

	/**
	 * Position the flyout to proper location in the offset container.
	 * This positioning is based on both trigger and flyout are in the same offset
	 * container.
	 */
	function __positioning($trigger, $flyout, placement) {
		var triggerPos = {
			top: $trigger[0].offsetTop,
			left: $trigger[0].offsetLeft
		};
		var triggerSize = {
			width: $trigger.outerWidth(),
			height: $trigger.outerHeight()
		};
		var flyoutSize = {
			width: $flyout.outerWidth(true),
			height: $flyout.outerHeight(true)
		};

		var pos = {};
		switch (placement) {
			case 'top':
				pos = {
					top: triggerPos.top - flyoutSize.height,
					left: Math.round(triggerPos.left + (triggerSize.width - flyoutSize.width) / 2)
				};
				break;
			case 'right':
				pos = {
					top: Math.round(triggerPos.top + (triggerSize.height - flyoutSize.height) / 2),
					left: triggerPos.left + triggerSize.width
				};
				break;
			case 'bottom':
				pos = {
					top: triggerPos.top + triggerSize.height,
					left: Math.round(triggerPos.left + (triggerSize.width - flyoutSize.width) / 2)
				};
				break;
			case 'left':
				pos = {
					top: Math.round(triggerPos.top + (triggerSize.height - flyoutSize.height) / 2),
					left: triggerPos.left - flyoutSize.width
				};
				break;
			case 'left_bottom':
				pos = {
					top: triggerPos.top + triggerSize.height+11,
					left: triggerPos.left + 50 - flyoutSize.width
				};
				break;	
			case 'right_bottom':
				pos = {
					top: triggerPos.top + triggerSize.height+11,
					left: triggerPos.left - 50 + triggerSize.width
				};
				break;	
			case 'left_top':
				pos = {
					top: triggerPos.top - flyoutSize.height-11,
					left: triggerPos.left + 50 - flyoutSize.width
				};
				break;
			case 'right_top':
				pos = {
					top: triggerPos.top - flyoutSize.height-11,
					left: triggerPos.left - 50 + triggerSize.width
				};
				break;	
			case 'top_left':
				pos = {
					top: triggerPos.top - flyoutSize.height-11,
					left: triggerPos.left
				};
				break;	
			case 'top_right':
				pos = {
					top: triggerPos.top - flyoutSize.height-11,
					left: triggerPos.left - flyoutSize.width +triggerSize.width
				};				
				break;
			case 'bottom_left':
				pos = {
					top: triggerPos.top + triggerSize.height+11,
					left: triggerPos.left
				};
				break;	
			case 'bottom_right':
				pos = {
					top: triggerPos.top + triggerSize.height+11,
					left: triggerPos.left - flyoutSize.width +triggerSize.width
				};
				break;	
		}

		$flyout.css(pos);
	}

	/**
	 *
	 */
	function __destroy(data) {
		var $this = $(this);
		data = data || $this.data(CONST.dataKey);

		if (data.flyoutId) {
			/* first remove the flyout from DOM */
			$('#' + data.flyoutId).remove();
			data.flyoutId = null;
		}

		/* unregister any callbacks */
		$this.unbind('click', __clickHandler);
		$this.unbind('blur', __blurHandler);

		/* clean the associated data */
		$this.data(CONST.dataKey, null);
	}

	/**
	 * Flyout default config that could be overridden
	 */
	$.fn.flyout.defaults = {
		animation: true,

		/**
		 * The fixed width style of flyout area. If it is not specified, the width shrinks automatically
		 * according to content width, but will not exceed the max-width defined in stylesheet.
		 * @type {string}
		 */
		width: undefined,

		/**
		 * Title of this flyout, can be text or string returned by function.
		 * If the title is empty, the title area will not appear intentionally.
		 * @type {string|function|null}
		 * @this jqueryObject	- the jquery object of target to which the flyout attaching
		 */
		title: "",

		/**
		 * Contents of this flyout, can be html string or string returned by function.
		 * @type {string|function}
		 * @this jqueryObject	- the jquery object of target to which the flyout attaching
		 */
		content: "",

		/**
		 * Indicates if content is HTML string or simple text. When it is true, contents will be
		 * inserted by using $.html(), otherwise $.text()
		 * @type {boolean}
		 */
		html: false,

		/**
		 * Indicates where to place the flyout, possible values are: "top", "bottom", "left", "right"
		 * @type {string}
		 */
		placement: 'top',

		/**
		 * Indicates if the flyout is intended to be dismissed when the trigger is out of focus
		 * @type {boolean}
		 */
		dismissible: false,

		/**
		 * The way triggers the showing of flyout, possible values are: "click" and "manual"
		 * @type {string}
		 */
		trigger: 'click',
		
		/**
		 * Dismis automatico
		 * @type {boolean}
		 */
		autoDismis: false,
		
		/**
		 * Tiempo para el Dismis automatico
		 * @type {number}
		 */
		timeDismis: 5000
	};
})(jQuery);
$.fn.createFlyout=function(msg,opts,fa_icon,icon_color){ opts=(typeof opts==='object'?opts:{}); if(typeof msg==='object'){opts=msg;msg=undefined;} 
	var o=$.extend({content: '<span class="icon_fly fa-stack fa-sm"><i class="fa fa-square fa-stack-2x '+(opts['icon_color']||(icon_color||'orange'))+'"></i><i class="fa fa-'+(opts['icon']||(fa_icon||'info'))+' fa-stack-1x fa-inverse"></i></span><span class="msg_fly">'+(msg||'El campo no es v&aacute;lido!')+'</span>',html: true,placement: 'top_right',trigger:null,autoDismis:true},opts); this.flyout(o).flyout('hide'); return this;
};

