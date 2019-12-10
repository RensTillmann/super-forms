/* eslint-disable no-self-assign */
/* eslint-disable no-mixed-spaces-and-tabs */
/* eslint-disable no-undef */
// jshint ignore: start
/*
    jQuery Masked Input Plugin
    Copyright (c) 2007 - 2015 Josh Bush (digitalbush.com)
    Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
    Version: 1.4.1
*/
(function(factory) {
    "use strict";
    if (
        typeof exports === "object" &&
        exports &&
        typeof module === "object" &&
        module &&
        module.exports === exports
    ) {
        // Browserify. Attach to jQuery module.
        factory(require("jquery"));
    } else if (typeof define === "function" && define.amd) {
        // AMD. Register as an anonymous module.
        define(["jquery"], factory);
    } else {
        // Browser globals
        factory(jQuery);
    }
})(function($) {
    "use strict";
    var caretTimeoutId, ua = navigator.userAgent,
        iPhone = /iphone/i.test(ua),
        chrome = /chrome/i.test(ua),
        android = /android/i.test(ua);
    $.mask = {
        definitions: {
            "9": "[0-9]",
            a: "[A-Za-z]",
            "*": "[A-Za-z0-9]"
        },
        autoclear: !0,
        dataName: "rawMaskFn",
        placeholder: "_"
    };
    $.fn.extend({
        caret: function(begin, end) {
            var range;
            if (0 !== this.length && !this.is(":hidden")) return "number" == typeof begin ? (end = "number" == typeof end ? end : begin,
                this.each(function() {
                    if (this.setSelectionRange) {
                        this.setSelectionRange(begin, end);
                    } else {
                        if (this.createTextRange) {
                            range = this.createTextRange();
                            range.collapse(!0);
                            range.moveEnd("character", end);
                            range.moveStart("character", begin);
                            range.select();
                        }
                    }
                })) : (this[0].setSelectionRange ? (begin = this[0].selectionStart, end = this[0].selectionEnd) : document.selection && document.selection.createRange && (range = document.selection.createRange(),
                begin = 0 - range.duplicate().moveStart("character", -1e5), end = begin + range.text.length), {
                begin: begin,
                end: end
            });
        },
        unmask: function() {
            return this.trigger("unmask");
        },
        mask: function(mask, settings) {
            mask = String(mask);
            var input, defs, tests, partialPosition, firstNonMaskPos, lastRequiredNonMaskPos, len, oldVal;
            if (!mask && this.length > 0) {
                input = $(this[0]);
                var fn = input.data($.mask.dataName);
                return fn ? fn() : void 0;
            }
            return settings = $.extend({
                    autoclear: $.mask.autoclear,
                    placeholder: $.mask.placeholder,
                    completed: null
                }, settings),
                defs = $.mask.definitions,
                tests = [],
                partialPosition = len = mask.length,
                firstNonMaskPos = null,

                $.each(mask.split(""), function(i, c) {
                    if ("?" == c) {
                        len--;
                        partialPosition = i;
                    } else {
                        if (defs[c]) {
                            tests.push(new RegExp(defs[c]));
                            if (null === firstNonMaskPos) {
                                firstNonMaskPos = tests.length - 1;
                            }
                            if (partialPosition > i) {
                                lastRequiredNonMaskPos = tests.length - 1;
                            }
                        } else {
                            tests.push(null);
                        }
                    }
                }),
                this.trigger("unmask").each(function() {
                    function tryFireCompleted() {
                        if (settings.completed) {
                            for (var i = firstNonMaskPos; lastRequiredNonMaskPos >= i; i++)
                                if (tests[i] && buffer[i] === getPlaceholder(i)) return;
                            settings.completed.call(input);
                        }
                    }

                    function getPlaceholder(i) {
                        return settings.placeholder.charAt(i < settings.placeholder.length ? i : 0);
                    }

                    function seekNext(pos) {
                        for (; ++pos < len && !tests[pos];);
                        return pos;
                    }

                    function seekPrev(pos) {
                        for (; --pos >= 0 && !tests[pos];);
                        return pos;
                    }

                    function shiftL(begin, end) {
                        var i, j;
                        if (0 <= begin) {
                            for (i = begin, j = seekNext(end); len > i; i++)
                                if (tests[i]) {
                                    if (!(len > j && tests[i].test(buffer[j]))) break;
                                    buffer[i] = buffer[j];
                                    buffer[j] = getPlaceholder(j);
                                    j = seekNext(j);
                                }
                            writeBuffer();
                            input.caret(Math.max(firstNonMaskPos, begin));
                        }
                    }

                    var shiftR = function (pos) {
                        var i, c, j, t;
                        for (i = pos, c = getPlaceholder(pos); len > i; i++)
                            if (tests[i]) {
                                if (j = seekNext(i), t = buffer[i], buffer[i] = c, !(len > j && tests[j].test(t))) break;
                                c = t;
                            }
                    };

                    var androidInputEvent = function() {
                        var curVal = input.val(),
                            pos = input.caret();
                        if (oldVal && oldVal.length && oldVal.length > curVal.length) {
                            for (checkVal(!0); pos.begin > 0 && !tests[pos.begin - 1];) pos.begin--;
                            if (0 === pos.begin)
                                for (; pos.begin < firstNonMaskPos && !tests[pos.begin];) pos.begin++;
                            input.caret(pos.begin, pos.begin);
                        } else {
                            for (checkVal(!0); pos.begin < len && !tests[pos.begin];) pos.begin++;
                            input.caret(pos.begin, pos.begin);
                        }
                        tryFireCompleted();
                    };

                    var blurEvent = function() {
                        checkVal();
                        if (input.val() != focusText) {
                            input.change();
                        }
                    };

                    var KeydownEvent = function(e) {
                        if (!input.prop("readonly")) {
                            var pos, begin, end, k = e.which || e.keyCode;
                            oldVal = input.val();
                            if(8 === k || 46 === k || iPhone && 127 === k){ 
                        		pos = input.caret();
                            	begin = pos.begin;
                            	end = pos.end;
                            	if(end - begin === 0){
                            		if(46 !== k){
                            			begin = seekPrev(begin);
                            		}else{
                            			end = seekNext(begin - 1);
                            		}
                            		if(46 === k){
                            			end = seekNext(end);
                            		}else{
                            			end = end;
                            		}
                            	}
                                clearBuffer(begin, end); 
                                shiftL(begin, end - 1);
                            	e.preventDefault();
                            }else{ 
                            	if(13 === k){
                            		blurEvent.call(this, e);
                            	}else{
                            		if(27 === k){
										input.val(focusText);
                                		input.caret(0, checkVal());
                                		e.preventDefault();
                            		}
                            	}
                            }
                        }
                    };

                    var keypressEvent = function(e) {
                        if (!input.prop("readonly")) {
                            var p, c, next, k = e.which || e.keyCode,
                                pos = input.caret();
                            if (!(e.ctrlKey || e.altKey || e.metaKey || 32 > k) && k && 13 !== k) {
                                if (pos.end - pos.begin !== 0 && (clearBuffer(pos.begin, pos.end), shiftL(pos.begin, pos.end - 1)),
                                    p = seekNext(pos.begin - 1), len > p && (c = String.fromCharCode(k), tests[p].test(c))) {
                                    // Transform to uppercase?
                                    if($(this).parents('.super-shortcode:eq(0)').hasClass('super-uppercase')){
                                        c = c.toUpperCase();
                                    }
                                    if (shiftR(p), buffer[p] = c, writeBuffer(), next = seekNext(p), android) {
                                        var proxy = function() {
                                            $.proxy($.fn.caret, input, next)();
                                        };
                                        setTimeout(proxy, 0);
                                    } else {
                                    	input.caret(next);
                                    }
                                    if(pos.begin <= lastRequiredNonMaskPos) tryFireCompleted();
                                }
                                e.preventDefault();
                            }
                        }
                    };

                    var clearBuffer = function(start, end) {
                        var i;
                        for (i = start; end > i && len > i; i++) {
                        	if(tests[i]){
                        		buffer[i] = getPlaceholder(i);
                        	}
                        }
                    };

                    var writeBuffer = function() {
                        input.val(buffer.join(""));
                    };

                    var checkVal = function(allow) {
                        var i, c, pos, test = input.val(),
                            lastMatch = -1;
                        for (i = 0, pos = 0; len > i; i++)
                            if (tests[i]) {
                                for (buffer[i] = getPlaceholder(i); pos++ < test.length;)
                                    if ( c = test.charAt(pos - 1), tests[i].test(c) ) {
                                        buffer[i] = c;
                                    	lastMatch = i;
                                        break;
                                    }
                                if (pos > test.length) {
                                    clearBuffer(i + 1, len);
                                    break;
                                }
                            } else {
                            	if(buffer[i] === test.charAt(pos)) {
                            		pos++;
                            		if(partialPosition > i) {
                            			lastMatch = i;
                            		}
                            	}
                            }
                        return allow ? writeBuffer() : partialPosition > lastMatch + 1 ? settings.autoclear || buffer.join("") === defaultBuffer ? (input.val() && input.val(""),
                                clearBuffer(0, len)) : writeBuffer() : (writeBuffer(), input.val(input.val().substring(0, lastMatch + 1))),
                            partialPosition ? i : firstNonMaskPos;
                    };
                    var input = $(this);
                    var buffer = $.map(mask.split(""), function(c, i) {
                        return "?" != c ? defs[c] ? getPlaceholder(i) : c : void 0;
                    });
                    var defaultBuffer = buffer.join("");
                    var focusText = input.val();
                    input.data($.mask.dataName, function() {
						return $.map(buffer, function(c, i) {
					        return tests[i] && c != getPlaceholder(i) ? c : null;
					    }).join("");
					});
                    input.one("unmask", function() {
                    	input.off(".mask").removeData($.mask.dataName);
                    });
                    input.on("focus.mask", function() {
                        if (!input.prop("readonly")) {
                            clearTimeout(caretTimeoutId);
                            var pos = checkVal();
                            caretTimeoutId = setTimeout(function() {
                                if(input.get(0) === document.activeElement){
                                	writeBuffer();
                                	if(pos == mask.replace("?", "").length){
                                		input.caret(0, pos);
                                	}else{
                                		input.caret(pos);
                                	}
                                }
                            }, 10);
                        }
                    });
                    input.on("blur.mask", blurEvent).on("keydown.mask", KeydownEvent).on("keypress.mask", keypressEvent).on("input.mask paste.mask", function() {
                        if(input.prop("readonly")){
                        	setTimeout(function() {
                            	var pos = checkVal(!0);
                            	input.caret(pos);
                            	tryFireCompleted();
                        	}, 0);
                        }
                    });
                    if(chrome && android){
                    	input.off("input.mask").on("input.mask", androidInputEvent);
                    }
                    checkVal();
                });
        }
    });
});