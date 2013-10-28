(function($) {
/**
 *	Namespace: the namespace the plugin is located under
 *	pluginName: the name of the plugin
 */
	$.extend(true, $.mjs.nestedSortable.prototype, {
		/*
		 * retrieve the id of the element
		 * this is some context within the existing plugin
		 */
		toJson: function(options){
			var o = $.extend({}, this.options, options),
				ret = [];

			$(this.element).children(o.items).each(function () {
				var level = _recursiveItems(this);
				ret.push(level);
			});
			
			function _recursiveItems(item) {
				var $item = $(item),
					$inputs = $item.find('> .menu-item-settings-wrapper input'),
					currentItem = {};

				$inputs.each(function(){
					var $li = $(this);
					currentItem[$li.attr('name')] = $li.val();
				});

				if ($item.children(o.listType).children(o.items).length > 0) {
					currentItem.children = [];
					$(item).children(o.listType).children(o.items).each(function() {
						var level = _recursiveItems(this);
						currentItem.children.push(level);
					});
				}
				return currentItem;
			}
			return ret.length ? JSON.stringify(ret) : null;
		}
	});

	$.SitemapParser = (function() {
		var $raw,
			$processed,
			$process,
			$form,
			$fieldset,
			$postTypes,
			lines = [],
			howDeep = 0,
			meta = [],
			output = 'html',
			isParsing = false,
			isDirty = false,
			indicatorTimer = false,
			locked = false,
			isHierarchical = true,
			regex = {
				lowercase: /[a-z]/g,
				upper: /[A-Z]/g,
				romanLower: /[ivxl]/g,
				romanUpper: /[IVXL]/g,
				romanPrevious: /[hkuwHKUW]/g,
				numeric: /[0-9]/g
			};
		
		function _showError(err) {
			var error = $('<div class="skeleton-error error"><span class="dismiss">&times;</span><p>' + err + '</p></div>').insertBefore('#skeleton-builder-frame');
			window.setTimeout(function() {
				$(error[0]).fadeOut(250, function() {
					$(this).remove();
				});
			}, 10000);
		}
		
		function _getParent(obj) {
			return meta[_getSelf(meta[obj.pos]).parent] || {};
		}

		function _getPrevious(obj) {
			return meta[(obj || {}).pos-1] || {};
		}

		function _getNext(obj) {
			return meta[obj.pos+1] || {};
		}

		function _getSelf(obj) {
			return meta[(obj || {}).pos] || {};
		}
		
		function _charMath(curr, prev) {
			if(/^(.)\1+$/.test(curr)) {
				return curr.charCodeAt(0) === prev.charCodeAt(0) + 1;
			} else {
				totalIt = function(val, increment) {
					var total = i = 0;
					while (i < val.length) {
						total = total + (String(val).charCodeAt(i) + (increment && i+1===val.length ? 1 : 0))
						i++;
					}
					return total;
				}
				return totalIt(curr) === totalIt(prev,true);
			}
		}
		
		function _isNext(curr, prev) {
			if(curr.type !== prev.type) {
				return false;
			}
						
			if(curr.type === 'numeric') {
				return parseInt(curr.value) === parseInt(prev.value) + 1;
			} else if(!curr.type.indexOf("roman-") && !prev.type.indexOf("roman-")) {
				return _deromanize(curr.value) === _deromanize(prev.value) + 1;
			} else {
				return _charMath(curr.value, prev.value);
			}
		}

		function _getDash(d) {
			return new Array(d+1).join('-');
		}

		function _deromanize (str) {
			var	validator = /^M*(?:D?C{0,3}|C[MD])(?:L?X{0,3}|X[CL])(?:V?I{0,3}|I[XV])$/,
				token = /[MDLV]|C[MD]?|X[CL]?|I[XV]?/g,
				key = {M:1000,CM:900,D:500,CD:400,C:100,XC:90,L:50,XL:40,X:10,IX:9,V:5,IV:4,I:1},
				num = 0, m;
			
			str = str.toUpperCase();
			
			if (!(str && validator.test(str))) {
				return false;
			}
			
			while (m = token.exec(str)) {
				num += key[m[0]];
			}
			
			return num;
		}

		function _createSlug(text) {
			return text
				.trim()
				.toLowerCase()
				.replace(/[\s]+/g, '-')
				.replace(/[^a-z0-9-]/g, '')
				.replace(/-{2,}/,'-');
		}

		function _testCharType(c, pos) {
			var character = c.charAt(0),
				prev = _getPrevious({pos: pos}) || {},
				prevChar = (prev.value || '').charAt(0);
			
			if(character.match(regex.numeric)) {
				return 'numeric';
			}
			
			if(character.match(regex.upper)) {
				if(character.match(regex.romanUpper)) {
					if(prev.type !== 'uppercase') {
						return 'roman-uppercase';
					} else {
						switch(character) {
							case 'I':
								if(prevChar !== 'H') {
									return 'roman-uppercase';
								}
							break;
							case 'V':
								if(prevChar !== 'U') {
									return 'roman-uppercase';
								}
							break;
							case 'X':
								if(prevChar !== 'W') {
									return 'roman-uppercase';
								}
							break;
							case 'L':
								if(prevChar !== 'K') {
									return 'roman-uppercase';
								}
							break;
						}
					}
				}
				return 'uppercase';
			}

			if(character.match(regex.lower)) {
				if(character.match(regex.romanLower)) {
					if(prev.type !== 'lowercase') {
						return 'roman-lowercase';
					} else {
						switch(character) {
							case 'i':
								if(prevChar !== 'h') {
									return 'roman-lowercase';
								}
							break;
							case 'v':
								if(prevChar !== 'u') {
									return 'roman-lowercase';
								}
							break;
							case 'x':
								if(prevChar !== 'w') {
									return 'roman-lowercase';
								}
							break;
							case 'l':
								if(prevChar !== 'k') {
									return 'roman-lowercase';
								}
							break;
						}
					}
				} else {
					return 'lowercase';
				}
			}

			if(character.match(regex.romanLower)) {
				if(prev.type === 'roman-lowercase') {
					return 'roman-lowercase';
				} else if(c.length > 2) {
					return 'roman-lowercase';
				} else if(pos && !prev.value.charAt(0).match(regex.romanPrevious)) {
					return 'roman-lowercase';
				} else {
					return 'test-roman';
				}
			}

			if(character.match(regex.romanUpper)) {
				if(prev.type === 'roman-uppercase') {
					return 'roman-uppercase';
				} else if(c.length > 2) {
					return 'roman-uppercase';
				} else if(pos && !prev.value.charAt(0).match(regex.romanPrevious)) {
					return 'roman-uppercase';
				} else {
					return 'test-roman';
				}
			}

			return 'unknown';
		}

		function _testRoman(obj) {
			var my = _getSelf(obj),
				next = _getNext(obj);

			if(next.type === 'roman-uppercase') {
				my.type = 'roman-uppercase';
				next.previousType = 'roman-uppercase';
			} else if(next.type === 'roman-lowercase') {
				my.type = 'roman-lowercase';
				next.previousType = 'roman-lowercase';
			}
		}

		function _processLine(line) {
			var x = meta.length,
				parts = line.match(/^(\S+)\s(.*)/),
				cleanIndex = parts && parts.length > 1 ? parts[1].replace(/[.]/g, "") : "";

			if(!parts) {
				return false;
			}

			return {
				pos: x,
				previousType: x ? _getPrevious({pos:x}).type : false,
				depth: null,
				type: _testCharType(cleanIndex, x),
				parent: null,
				slug: _createSlug(parts[2]),
				text: $.trim(parts[2]),
				value: cleanIndex,
				raw: {
					index: parts[0],
					body: parts[1]
				}
			};
		}
		
		
		function _isHierarchical() {
			return $postTypes.find(":selected").data('hierarchical');
		}
		
		function _initSortable() {
			$('ul#sortable').nestedSortable({
				forcePlaceholderSize: true,
				handle: '.menu-item-handle',
				items: 'li',
				opacity: 0.6,
				placeholder: 'ui-state-highlight',
				tabSize: 25,
				isTree: true,
				startCollapsed: true,
				toleranceElement: '> dl',
				listType: 'ul',
				stop: function() {
					_lock();
				}
			});
		}
		
		function _flatten() {
			var $items = $('ul#sortable').find('li'),
				$menu = $('<ul id="sortable"></ul>');

			$processed.empty();

			$.each($items, function(i, li) {
				$menu.append(li);
			});
			
			
			$processed.append($menu);
			
			$('ul#sortable > li').addClass('mjs-nestedSortable-no-nesting');

			_initSortable();
		}
		
		function _parse() {
			isParsing = true;
			var lines = $raw.val().split("\n");
			$.each(lines, function(key, line){
				if(line.length) {
					var lineData = _processLine(line);
					if(lineData) {
						meta[lineData.pos] = lineData;
					}
				}
			});

			var advRomanParsing = $.grep(meta, function(n){
				return n.type === "test-roman";
			});
			
			$.each(advRomanParsing, function(key, obj){
				_testRoman(obj);
			});

			_analyze();
		}

		function _setAncestory(current, up) {
			var my = _getSelf(current),
				previous = _getPrevious(current);

			if(my.pos === 0) {
				my.depth = my.parent = 0;
				return my.depth;
			} else if(my.type === previous.type && _isNext(my, previous)) {
				my.parent = previous.parent;
				my.depth = previous.depth;
				return my.depth;
			} else if(my.type === up.type && _isNext(my, up)) {
				my.parent = up.parent;
				my.depth = up.depth;
				return my.depth;
			} else if(up.pos === up.parent && _isNext(my, up)) {
				my.parent = up.pos;
				my.depth = up.depth;
				return my.depth;
			} else {
				if(up.depth === 0) {
					howDeep++;
					my.parent = previous.pos;
					my.depth = howDeep;
					return my.depth;
				} else {
					_setAncestory(my, _getParent(up));
				}
			}
		}

		function _analyze() {
			$.each(meta, function(key, obj){
				_setAncestory(obj, _getPrevious(obj));
			});
						
			_build();
		}

		function _innerLI(obj) {
			var output = '<dl class="menu-item-bar">';
					output += '<dt class="menu-item-handle">';
						output += '<span class="item-title">' + obj.text + '</span>';
						output += '<span class="item-controls">';
							output += '<span class="item-order">';
								output += '<i class="up"><abbr title="Move up">↑</abbr></i>';
								output += '<i class="down"><abbr title="Move down">↓</abbr></i>';
							output += '</span>';
						output += '</span>';
						output += '<span class="addelement"><i class="add"><abbr title="Add Element">+</abbr></i></span>';
					output += '</dt>';
				output += '</dl>';
				output += '<div class="menu-item-settings-wrapper"><div class="menu-item-settings clearfix">';
					output += '<p class="description description-thin"><label>Navigation Label</label> <input type="text" name="label" value="' + obj.text + '" /></p>';
					output += '<p class="description description-thin"><label>Permalink</label> <input type="text" name="permalink" value="' + obj.slug + '" /></p>';
					output += '<p class="description description-thin"><label class="optional">Title Attribute</label> <input type="text" name="title" value="" /></p>';
					output += '<p class="description description-thin"><label class="optional">CSS Classes (optional)</label> <input type="text" name="cls" value="" /></p>';
					output += '<div class="menu-item-actions description-wide submitbox"><span class="delete">Remove</span> | <span class="cancel item-controls">Cancel</span></div>';
				output += '</div></div>';
			return output;
		}

		function _toHTML() {
			var menu = $('<ul id="sortable"></ul>'),
				prevDepth = 0,
				lastItem = null,
				subnav = {},
				items = {};

			$.each(meta, function(key, obj) {
				var li = $('<li class="menuitem'+obj.pos+'"></li>').html(
					_innerLI(obj)
				);
				items["menuitem"+obj.pos] = li;

				if(obj.depth===prevDepth) {
					if(obj.depth){
						lastItem.after(li);
					} else {
						menu.append(li);
					}
				}  else if(obj.depth > prevDepth) {
					if(!subnav["submenu"+obj.parent]) {
						subnav["submenu"+obj.parent] = $('<ul class="submenu'+obj.parent+'"></ul>');
						items["menuitem"+obj.parent].append(subnav["submenu"+obj.parent]);
					}
					subnav["submenu"+obj.parent].append(li);
				} else if(obj.depth < prevDepth) {
					if(obj.depth){
						subnav["submenu"+obj.parent].append(li);
					} else {
						menu.append(li);
					}
				}
				lastItem = li;
				prevDepth = obj.depth;
			});

			return menu;
		}

		function _toDashes() {
			var output = [];
			$.each(meta, function(key, obj){
				output[key] = _getDash(obj.depth) + obj.slug + ':' + obj.text;
			});
			return output.join("\n");
		}

		function _build() {
			if(output === 'html') {
				$processed.empty().append(_toHTML());
				_initSortable();
			} else {
				$processed.val(_toDashes());
			}
			isParsing = false;
		}

		function _setDropZone() {
			document.getElementById('skeleton').addEventListener("dragover",function(e) {
				e = e || event;
				e.preventDefault();
				$fieldset.addClass('dragover');
			}, false);
			document.getElementById('skeleton').addEventListener("dragleave",function(e) {
				e = e || event;
				e.preventDefault();
				$fieldset.removeClass('dragover');
			}, false);
			window.addEventListener("dragover",function(e) {
				e = e || event;
				e.preventDefault();
			});
			window.addEventListener("drop",function(e) {
				e = e || event;
				e.preventDefault();
				$fieldset.removeClass('dragover');
				var files = e.target.files || e.dataTransfer.files,
					targetId = e.target.id || e.explicitOriginalTarget.id,
					text = e.dataTransfer.getData("text/plain");
								
				if(targetId==='skeleton') {
					if(text) {
						$raw.val(text);
						_prepareParse();
						$raw.autosize();
					} else {
						if((files[0] || {}).type==="text/plain") {
							var reader = new FileReader();
							reader.onload = function(e) {
								$raw.val(e.target.result);
								_prepareParse();
								$raw.autosize();
							};
							reader.readAsText(files[0]);
						} else {
							_showError('<strong>' + (files[0] || {}).name + ' - ' + (files[0] || {}).type + ' is not a supported file format.</strong> At this time only plain text files are supported.');
						}
					}
				}
			}, false);
		}

		function _prepareParse() {
			if(meta.length) {
				lines = [],
				howDeep = 0,
				meta = [];
			}
			_parse();
		}

		function _reParse() {
			setTimeout(function(){
				if(isDirty){
					isDirty = false;
					if(!isParsing) {
						_prepareParse();
					}
				}
			}, 250);
		}
		
		function _lock() {
			if(!locked) {
				locked = true;
				$form.addClass('locked');
				$postTypes.prop('disabled', true);
				if(!$form.find('.clearSkeleton').length) {
					$postTypes.after('<button type="button" class="unlock-button button-secondary"><span class="unlock-button-text">Unlock</span></button>');
				}
			}
		}
		
		function _unlock() {
			if(locked) {
				locked = false;
				_prepareParse();
				$postTypes.prop('disabled', false);
				$postTypes.next('.unlock-button').remove();
				$form.removeClass('locked');
				if(!_isHierarchical()) {
					_flatten();
				}
			}
		}
		
		function _initEvents() {
			$raw.on('keyup', function(e) {
				if(e.altKey || e.ctrlKey || e.metaKey) {
					return;
				}
				if(e.which === 13 || e.which === 8) {
					$raw.autosize();
				}
				if(String.fromCharCode(e.keyCode).match(/\w/) || e.keyCode === 8 || e.keyCode === 46) {
					isDirty = true;
					_reParse();
				}
			}).on('paste', function(){
				$raw.autosize();
				isDirty = true;
				_reParse();
			});
			
			$form.on('click', '.dismiss', function() {
				$(this).parent('div').fadeOut(250, function() {
					$(this).remove();
				});
			}).on('click', '.clearSkeleton', function() {
				$(this).remove();
				$raw.val('');
				_unlock();
				_prepareParse();
			}).on('click', '.unlock-button', function() {
				_unlock();
			});
			
			$processed.on("keyup", "input[name=label]", function(e) {
				if(String.fromCharCode(e.keyCode).match(/\w/) || e.keyCode === 8 || e.keyCode === 46) {
					_lock();
					var li = $(this).closest('li.expanded');
					li.find('.menu-item-bar .item-title').html($(this).val());
					li.find('input[name=permalink]:not(.edit)').val(_createSlug($(this).val()));
				}
			}).on("keyup", "input[name=permalink]", function(e) {
				if(String.fromCharCode(e.keyCode).match(/\w/) || e.keyCode === 8 || e.keyCode === 46) {
					_lock();
					$(this).addClass('edit');
				}
			}).on("click", ".addelement", function() {
				_lock();
				meta[meta.length] = {};
				$(this).closest('li').after(
					$('<li class="menuitem'+meta.length+' expanded"></li>').html(
						_innerLI({
							text:'New Menu Item',
							slug:'new-menu-item'
						})
					)
				);
			}).on('click', ".item-controls", function() {
				$(this).closest('li').toggleClass('expanded');
			}).on('click', ".delete", function() {
				_lock();
				$(this).closest('li').remove();
			});
			
			$process.on("click", _onSubmit);
			
			$postTypes.on('change', function(){
				if(meta.length) {
					_lock();
				}
				if(!_isHierarchical()) {
					_flatten();
				} else {
					$('ul#sortable > li').removeClass('mjs-nestedSortable-no-nesting');
				}
			});
			
			if (window.File && window.FileReader && window.FileList && window.Blob) {
				_setDropZone();
			}
		}
		
		function _onSubmit(e) {
			e.preventDefault();
			var json = $('ul#sortable').nestedSortable('toJson', {startDepthCount: 0}),
				ajaxurl = ajaxurl || 'admin-ajax.php',
				toggleSubmission = function(t) {
					var $el = $processed.closest('.menu-edit');
					if(t){
						$el.addClass('submitted');
					} else {
						$el.removeClass('submitted');
					}
				};
			
			if(json) {
				window.clearTimeout(indicatorTimer);
				toggleSubmission(false);
				
				$processed.closest('.menu-edit').removeClass('submitted');
				
				$process.spin({
					lines: 10,
					length: 3,
					width: 1,
					radius: 2,
					top: 'auto',
					left: '-18px',
					color: '#000'
				});
								
				$.post(ajaxurl, {
					'action': 'skeleton_builder',
					'skeleton_builder_action': $('#skeleton_builder_action').val(),
					'skeleton': json,
					'skeleton_post_type': $('#skeleton_post_types').val(),
					'skeleton_menu': $('#menu-name').val()
				}, function(response) {
					if(response.success === true) {
						$process.spin(false);
						if(!$form.find('.clearSkeleton').length) {
							$($form.find('> .manage-menus > .button-secondary')[0]).after('<button class="alignright clearSkeleton button-secondary" type="button">Clear</button>');
						}
						toggleSubmission(true);
						indicatorTimer = window.setTimeout(function() {
							toggleSubmission(false);
						}, 5000);
					} else {
						$process.spin(false);
						$.each(response.data.errors, function(i, err){
							_showError(err);
						});
					}
				});
			} else {
				_showError('Please build a menu before submitting.');
			}
		}
		
		function _initVars() {
			var args = arguments[0];
			$raw = args[0] || $('#skeleton');
			$form = $raw.closest('form');
			$fieldset = $raw.parent('fieldset');
			$processed = args[1] || $('#skeleton-drag-area');
			$process = args[2] || $('.build-skeleton-button');
			$postTypes = args[3] || $('#skeleton_post_types');
		}

		function init() {
			_initVars(arguments);
			_initEvents();
		}

		return {
			init: init
		};
	})();

	$.SitemapParser.init();

})(jQuery);