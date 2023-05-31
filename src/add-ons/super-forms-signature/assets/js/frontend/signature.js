/* globals jQuery, SUPER */

/*!
 * Signature Pad v4.1.5 | https://github.com/szimek/signature_pad
 * (c) 2023 Szymon Nowak | Released under the MIT license
 */
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).SuperSignaturePad=e()}(this,(function(){"use strict";class t{constructor(t,e,i,s){if(isNaN(t)||isNaN(e))throw new Error(`Point is invalid: (${t}, ${e})`);this.x=+t,this.y=+e,this.pressure=i||0,this.time=s||Date.now()}distanceTo(t){return Math.sqrt(Math.pow(this.x-t.x,2)+Math.pow(this.y-t.y,2))}equals(t){return this.x===t.x&&this.y===t.y&&this.pressure===t.pressure&&this.time===t.time}velocityFrom(t){return this.time!==t.time?this.distanceTo(t)/(this.time-t.time):0}}class e{constructor(t,e,i,s,n,o){this.startPoint=t,this.control2=e,this.control1=i,this.endPoint=s,this.startWidth=n,this.endWidth=o}static fromPoints(t,i){const s=this.calculateControlPoints(t[0],t[1],t[2]).c2,n=this.calculateControlPoints(t[1],t[2],t[3]).c1;return new e(t[1],s,n,t[2],i.start,i.end)}static calculateControlPoints(e,i,s){const n=e.x-i.x,o=e.y-i.y,h=i.x-s.x,r=i.y-s.y,a=(e.x+i.x)/2,d=(e.y+i.y)/2,c=(i.x+s.x)/2,l=(i.y+s.y)/2,u=Math.sqrt(n*n+o*o),v=Math.sqrt(h*h+r*r),_=v/(u+v),m=c+(a-c)*_,p=l+(d-l)*_,g=i.x-m,w=i.y-p;return{c1:new t(a+g,d+w),c2:new t(c+g,l+w)}}length(){let t,e,i=0;for(let s=0;s<=10;s+=1){const n=s/10,o=this.point(n,this.startPoint.x,this.control1.x,this.control2.x,this.endPoint.x),h=this.point(n,this.startPoint.y,this.control1.y,this.control2.y,this.endPoint.y);if(s>0){const s=o-t,n=h-e;i+=Math.sqrt(s*s+n*n)}t=o,e=h}return i}point(t,e,i,s,n){return e*(1-t)*(1-t)*(1-t)+3*i*(1-t)*(1-t)*t+3*s*(1-t)*t*t+n*t*t*t}}class i{constructor(){try{this._et=new EventTarget}catch(t){this._et=document}}addEventListener(t,e,i){this._et.addEventListener(t,e,i)}dispatchEvent(t){return this._et.dispatchEvent(t)}removeEventListener(t,e,i){this._et.removeEventListener(t,e,i)}}class s extends i{constructor(t,e={}){super(),this.canvas=t,this._drawningStroke=!1,this._isEmpty=!0,this._lastPoints=[],this._data=[],this._lastVelocity=0,this._lastWidth=0,this._handleMouseDown=t=>{1===t.buttons&&(this._drawningStroke=!0,this._strokeBegin(t))},this._handleMouseMove=t=>{this._drawningStroke&&this._strokeMoveUpdate(t)},this._handleMouseUp=t=>{1===t.buttons&&this._drawningStroke&&(this._drawningStroke=!1,this._strokeEnd(t))},this._handleTouchStart=t=>{if(t.cancelable&&t.preventDefault(),1===t.targetTouches.length){const e=t.changedTouches[0];this._strokeBegin(e)}},this._handleTouchMove=t=>{t.cancelable&&t.preventDefault();const e=t.targetTouches[0];this._strokeMoveUpdate(e)},this._handleTouchEnd=t=>{if(t.target===this.canvas){t.cancelable&&t.preventDefault();const e=t.changedTouches[0];this._strokeEnd(e)}},this._handlePointerStart=t=>{this._drawningStroke=!0,t.preventDefault(),this._strokeBegin(t)},this._handlePointerMove=t=>{this._drawningStroke&&(t.preventDefault(),this._strokeMoveUpdate(t))},this._handlePointerEnd=t=>{this._drawningStroke&&(t.preventDefault(),this._drawningStroke=!1,this._strokeEnd(t))},this.velocityFilterWeight=e.velocityFilterWeight||.7,this.minWidth=e.minWidth||.5,this.maxWidth=e.maxWidth||2.5,this.throttle="throttle"in e?e.throttle:16,this.minDistance="minDistance"in e?e.minDistance:5,this.dotSize=e.dotSize||0,this.penColor=e.penColor||"black",this.backgroundColor=e.backgroundColor||"rgba(0,0,0,0)",this._strokeMoveUpdate=this.throttle?function(t,e=250){let i,s,n,o=0,h=null;const r=()=>{o=Date.now(),h=null,i=t.apply(s,n),h||(s=null,n=[])};return function(...a){const d=Date.now(),c=e-(d-o);return s=this,n=a,c<=0||c>e?(h&&(clearTimeout(h),h=null),o=d,i=t.apply(s,n),h||(s=null,n=[])):h||(h=window.setTimeout(r,c)),i}}(s.prototype._strokeUpdate,this.throttle):s.prototype._strokeUpdate,this._ctx=t.getContext("2d"),this.clear(),this.on()}clear(){const{_ctx:t,canvas:e}=this;t.fillStyle=this.backgroundColor,t.clearRect(0,0,e.width,e.height),t.fillRect(0,0,e.width,e.height),this._data=[],this._reset(this._getPointGroupOptions()),this._isEmpty=!0}fromDataURL(t,e={}){return new Promise(((i,s)=>{const n=new Image,o=e.ratio||window.devicePixelRatio||1,h=e.width||this.canvas.width/o,r=e.height||this.canvas.height/o,a=e.xOffset||0,d=e.yOffset||0;this._reset(this._getPointGroupOptions()),n.onload=()=>{this._ctx.drawImage(n,a,d,h,r),i()},n.onerror=t=>{s(t)},n.crossOrigin="anonymous",n.src=t,this._isEmpty=!1}))}toDataURL(t="image/png",e){return"image/svg+xml"===t?("object"!=typeof e&&(e=void 0),`data:image/svg+xml;base64,${btoa(this.toSVG(e))}`):("number"!=typeof e&&(e=void 0),this.canvas.toDataURL(t,e))}on(){this.canvas.style.touchAction="none",this.canvas.style.msTouchAction="none",this.canvas.style.userSelect="none";const t=/Macintosh/.test(navigator.userAgent)&&"ontouchstart"in document;window.PointerEvent&&!t?this._handlePointerEvents():(this._handleMouseEvents(),"ontouchstart"in window&&this._handleTouchEvents())}off(){this.canvas.style.touchAction="auto",this.canvas.style.msTouchAction="auto",this.canvas.style.userSelect="auto",this.canvas.removeEventListener("pointerdown",this._handlePointerStart),this.canvas.removeEventListener("pointermove",this._handlePointerMove),this.canvas.ownerDocument.removeEventListener("pointerup",this._handlePointerEnd),this.canvas.removeEventListener("mousedown",this._handleMouseDown),this.canvas.removeEventListener("mousemove",this._handleMouseMove),this.canvas.ownerDocument.removeEventListener("mouseup",this._handleMouseUp),this.canvas.removeEventListener("touchstart",this._handleTouchStart),this.canvas.removeEventListener("touchmove",this._handleTouchMove),this.canvas.removeEventListener("touchend",this._handleTouchEnd)}isEmpty(){return this._isEmpty}fromData(t,{clear:e=!0}={}){e&&this.clear(),this._fromData(t,this._drawCurve.bind(this),this._drawDot.bind(this)),this._data=this._data.concat(t)}toData(){return this._data}_getPointGroupOptions(t){return{penColor:t&&"penColor"in t?t.penColor:this.penColor,dotSize:t&&"dotSize"in t?t.dotSize:this.dotSize,minWidth:t&&"minWidth"in t?t.minWidth:this.minWidth,maxWidth:t&&"maxWidth"in t?t.maxWidth:this.maxWidth,velocityFilterWeight:t&&"velocityFilterWeight"in t?t.velocityFilterWeight:this.velocityFilterWeight}}_strokeBegin(t){this.dispatchEvent(new CustomEvent("beginStroke",{detail:t}));const e=this._getPointGroupOptions(),i=Object.assign(Object.assign({},e),{points:[]});this._data.push(i),this._reset(e),this._strokeUpdate(t)}_strokeUpdate(t){if(0===this._data.length)return void this._strokeBegin(t);this.dispatchEvent(new CustomEvent("beforeUpdateStroke",{detail:t}));const e=t.clientX,i=t.clientY,s=void 0!==t.pressure?t.pressure:void 0!==t.force?t.force:0,n=this._createPoint(e,i,s),o=this._data[this._data.length-1],h=o.points,r=h.length>0&&h[h.length-1],a=!!r&&n.distanceTo(r)<=this.minDistance,d=this._getPointGroupOptions(o);if(!r||!r||!a){const t=this._addPoint(n,d);r?t&&this._drawCurve(t,d):this._drawDot(n,d),h.push({time:n.time,x:n.x,y:n.y,pressure:n.pressure})}this.dispatchEvent(new CustomEvent("afterUpdateStroke",{detail:t}))}_strokeEnd(t){this._strokeUpdate(t),this.dispatchEvent(new CustomEvent("endStroke",{detail:t}))}_handlePointerEvents(){this._drawningStroke=!1,this.canvas.addEventListener("pointerdown",this._handlePointerStart),this.canvas.addEventListener("pointermove",this._handlePointerMove),this.canvas.ownerDocument.addEventListener("pointerup",this._handlePointerEnd)}_handleMouseEvents(){this._drawningStroke=!1,this.canvas.addEventListener("mousedown",this._handleMouseDown),this.canvas.addEventListener("mousemove",this._handleMouseMove),this.canvas.ownerDocument.addEventListener("mouseup",this._handleMouseUp)}_handleTouchEvents(){this.canvas.addEventListener("touchstart",this._handleTouchStart),this.canvas.addEventListener("touchmove",this._handleTouchMove),this.canvas.addEventListener("touchend",this._handleTouchEnd)}_reset(t){this._lastPoints=[],this._lastVelocity=0,this._lastWidth=(t.minWidth+t.maxWidth)/2,this._ctx.fillStyle=t.penColor}_createPoint(e,i,s){const n=this.canvas.getBoundingClientRect();return new t(e-n.left,i-n.top,s,(new Date).getTime())}_addPoint(t,i){const{_lastPoints:s}=this;if(s.push(t),s.length>2){3===s.length&&s.unshift(s[0]);const t=this._calculateCurveWidths(s[1],s[2],i),n=e.fromPoints(s,t);return s.shift(),n}return null}_calculateCurveWidths(t,e,i){const s=i.velocityFilterWeight*e.velocityFrom(t)+(1-i.velocityFilterWeight)*this._lastVelocity,n=this._strokeWidth(s,i),o={end:n,start:this._lastWidth};return this._lastVelocity=s,this._lastWidth=n,o}_strokeWidth(t,e){return Math.max(e.maxWidth/(t+1),e.minWidth)}_drawCurveSegment(t,e,i){const s=this._ctx;s.moveTo(t,e),s.arc(t,e,i,0,2*Math.PI,!1),this._isEmpty=!1}_drawCurve(t,e){const i=this._ctx,s=t.endWidth-t.startWidth,n=2*Math.ceil(t.length());i.beginPath(),i.fillStyle=e.penColor;for(let i=0;i<n;i+=1){const o=i/n,h=o*o,r=h*o,a=1-o,d=a*a,c=d*a;let l=c*t.startPoint.x;l+=3*d*o*t.control1.x,l+=3*a*h*t.control2.x,l+=r*t.endPoint.x;let u=c*t.startPoint.y;u+=3*d*o*t.control1.y,u+=3*a*h*t.control2.y,u+=r*t.endPoint.y;const v=Math.min(t.startWidth+r*s,e.maxWidth);this._drawCurveSegment(l,u,v)}i.closePath(),i.fill()}_drawDot(t,e){const i=this._ctx,s=e.dotSize>0?e.dotSize:(e.minWidth+e.maxWidth)/2;i.beginPath(),this._drawCurveSegment(t.x,t.y,s),i.closePath(),i.fillStyle=e.penColor,i.fill()}_fromData(e,i,s){for(const n of e){const{points:e}=n,o=this._getPointGroupOptions(n);if(e.length>1)for(let s=0;s<e.length;s+=1){const n=e[s],h=new t(n.x,n.y,n.pressure,n.time);0===s&&this._reset(o);const r=this._addPoint(h,o);r&&i(r,o)}else this._reset(o),s(e[0],o)}}toSVG({includeBackgroundColor:t=!1}={}){const e=this._data,i=Math.max(window.devicePixelRatio||1,1),s=this.canvas.width/i,n=this.canvas.height/i,o=document.createElementNS("http://www.w3.org/2000/svg","svg");if(o.setAttribute("xmlns","http://www.w3.org/2000/svg"),o.setAttribute("xmlns:xlink","http://www.w3.org/1999/xlink"),o.setAttribute("viewBox",`0 0 ${s} ${n}`),o.setAttribute("width",s.toString()),o.setAttribute("height",n.toString()),t&&this.backgroundColor){const t=document.createElement("rect");t.setAttribute("width","100%"),t.setAttribute("height","100%"),t.setAttribute("fill",this.backgroundColor),o.appendChild(t)}return this._fromData(e,((t,{penColor:e})=>{const i=document.createElement("path");if(!(isNaN(t.control1.x)||isNaN(t.control1.y)||isNaN(t.control2.x)||isNaN(t.control2.y))){const s=`M ${t.startPoint.x.toFixed(3)},${t.startPoint.y.toFixed(3)} C ${t.control1.x.toFixed(3)},${t.control1.y.toFixed(3)} ${t.control2.x.toFixed(3)},${t.control2.y.toFixed(3)} ${t.endPoint.x.toFixed(3)},${t.endPoint.y.toFixed(3)}`;i.setAttribute("d",s),i.setAttribute("stroke-width",(2.25*t.endWidth).toFixed(3)),i.setAttribute("stroke",e),i.setAttribute("fill","none"),i.setAttribute("stroke-linecap","round"),o.appendChild(i)}}),((t,{penColor:e,dotSize:i,minWidth:s,maxWidth:n})=>{const h=document.createElement("circle"),r=i>0?i:(s+n)/2;h.setAttribute("r",r.toString()),h.setAttribute("cx",t.x.toString()),h.setAttribute("cy",t.y.toString()),h.setAttribute("fill",e),o.appendChild(h)})),o.outerHTML}}return s}));

(function($) { // Hide scope, no $ conflict
	"use strict";
	// Init Signature
	SUPER.init_signature = function(){
		var i, nodes = document.querySelectorAll('.super-signature:not(.super-initialized)');
		for(i=0; i<nodes.length; i++){
			nodes[i].classList.add('super-initialized');
			var canvasWrapper = nodes[i].querySelector('.super-signature-canvas');
			var field = nodes[i].querySelector('.super-shortcode-field');
			var canvas = canvasWrapper.querySelector('canvas');
			// Set canvas to proper width
			var width = canvasWrapper.getBoundingClientRect().width;
			var height = canvasWrapper.getBoundingClientRect().height;
			canvas.width = width;
			canvas.height = height;
			if(typeof SUPER.signatures === 'undefined') SUPER.signatures = {};
			var formUid = nodes[i].closest('.super-form').dataset.sfuid;
			var fieldName = field.name;
			if(typeof SUPER.signatures[formUid] === 'undefined') SUPER.signatures[formUid] = {};
			if(typeof SUPER.signatures[formUid][fieldName] === 'undefined') SUPER.signatures[formUid][fieldName] = {};
			SUPER.signatures[formUid][fieldName] = new SuperSignaturePad(canvas, {
				penColor: field.dataset.color, // (string) Color used to draw the lines. Can be any color format accepted by context.fillStyle. Defaults to "black".
				dotSize: Number(field.dataset.thickness), // (float or function) Radius of a single dot. Also the width of the start of a mark.
				minWidth: Number(field.dataset.thickness)/5, // (float) Minimum width of a line. Defaults to 0.5.
				maxWidth: Number(field.dataset.thickness)*2, // (float) Maximum width of a line. Defaults to 2.5.
				throttle: 16, // (integer) Draw the next point at most once per every x milliseconds. Set it to 0 to turn off throttling. Defaults to 16.
				minDistance: 1, // (integer) Add the next point only if the previous one is farther than x pixels. Defaults to 5.
				velocityFilterWeight: 0.7 // (float) Weight used to modify new velocity based on the previous velocity. Defaults to 0.7.
				//backgroundColor: 'rgb(255,255,255)' // (string) Color used to clear the background. Can be any color format accepted by context.fillStyle. Defaults to "rgba(0,0,0,0)" (transparent black). Use a non-transparent color e.g. "rgb(255,255,255)" (opaque white) if you'd like to save signatures as JPEG images.
			});
			var signaturePad = SUPER.signatures[formUid][fieldName];
			var disallowEdit = signaturePad.canvas.closest('.super-signature').querySelector('.super-shortcode-field').dataset.disallowedit;
			if(disallowEdit==='true'){
				signaturePad.off();
				// Remove clear button
				if(canvasWrapper.parentNode.querySelector('.super-signature-clear')){
					canvasWrapper.parentNode.querySelector('.super-signature-clear').remove();
				}
			}
			SUPER.getSignatureDimensions(field, signaturePad, function(field, signaturePad, dimensions) {
				// Set canvas to proper width
				var canvasWrapper = signaturePad.canvas.parentNode;
				var width = canvasWrapper.getBoundingClientRect().width;
				var height = canvasWrapper.getBoundingClientRect().height;
				signaturePad.canvas.width = width;
				signaturePad.canvas.height = height;
				signaturePad.fromDataURL(field.value, { ratio: 1, width: dimensions.width, height: dimensions.height, xOffset: 0, yOffset: 0 });
			}, function(error) {
				console.error('Error:', error);
			});
		}
		if(SUPER.signatures){
			Object.keys(SUPER.signatures).forEach(function(formUid) {
				Object.keys(SUPER.signatures[formUid]).forEach(function(fieldName) {
					var signaturePad = SUPER.signatures[formUid][fieldName];
					var wrapper = signaturePad.canvas.closest('.super-field-wrapper');
					signaturePad.addEventListener('afterUpdateStroke', () => {
						var field = wrapper.querySelector('.super-shortcode-field');
						if(signaturePad.isEmpty()==false){
							if(!signaturePad.canvas.closest('.super-signature').classList.contains('super-filled')){
								signaturePad.canvas.closest('.super-signature').classList.add('super-filled');
							}
							var dataUrl = signaturePad.toDataURL();
							field.value = dataUrl;
						}else{
							signaturePad.canvas.closest('.super-signature').classList.remove('super-filled');
						}
						SUPER.after_field_change_blur_hook({el: field});
					}, { once: false });

					var clear = wrapper.querySelector('.super-signature-clear');
					if(clear){
						clear.addEventListener('click', function(){
							signaturePad.clear();
							var p = this.closest('.super-signature');
							p.classList.remove('super-filled');
							p.querySelector('.super-shortcode-field').value = '';
						});
					}

				});
			});
		}
	};
	SUPER.getSignatureDimensions = function(field, signaturePad, successCallback, errorCallback){
		var img = new Image();
		img.onload = function() {
			successCallback(field, signaturePad, { width: img.width, height: img.height });
		};
		img.onerror = function() {
			successCallback(field, signaturePad, { width: 0, height: 0 });
		};
		img.src = field.value;
	};

    // @since 1.2.2 - remove initialized class from signature element after the column has been cloned
    SUPER.init_remove_initialized_class = function($form, $unique_field_names, $clone){
        if($clone.querySelector('.super-signature.super-initialized')){
			$clone.querySelector('.super-signature.super-initialized').classList.remove('super-initialized');
		}
    };

    // @since 1.2.2 - clear signatures after form is cleared
    SUPER.init_clear_signatures = function(form){
		if(typeof SUPER.signatures === 'undefined') SUPER.signatures = {};
		var i, field, formUid, fieldName, disallowEdit, signaturePad, nodes = form.querySelectorAll('.super-signature.super-initialized');
		for(i=0; i<nodes.length; i++){
			field = nodes[i].querySelector('.super-shortcode-field');
			disallowEdit = field.dataset.disallowedit;
			formUid = nodes[i].closest('.super-form').dataset.sfuid;
			fieldName = field.name;
			if(typeof SUPER.signatures[formUid] === 'undefined') SUPER.signatures[formUid] = {};
			if(typeof SUPER.signatures[formUid][fieldName] === 'undefined') SUPER.signatures[formUid][fieldName] = {};
			signaturePad = SUPER.signatures[formUid][fieldName];
			if(disallowEdit!=='true'){
				signaturePad.clear();
				field.value = '';
			}else{
				// Make sure it has filled class if not empty
				if(!signaturePad.isEmpty()) nodes[i].classList.add('super-filled');
			}
		}
    };

    // @since 1.2.2 - initialize dynamically added signature elements
    SUPER.init_signature_after_duplicating_column = function(form, uniqueFieldNames, clone){
		var i,nodes;
		if( typeof clone !== 'undefined' ) {
			nodes = clone.querySelectorAll('.super-signature .super-signature-canvas > canvas');
			for( i=0; i < nodes.length; i++){
				nodes[i].remove();
			}
			SUPER.init_signature();
		}
    };

	jQuery(document).ready(function ($) {

		var $doc = $(document);
		SUPER.init_signature();
		// tmp $doc.on('click', '.super-signature-clear', function() {
		// tmp 	var $parent = $(this).parents('.super-signature:eq(0)');
		// tmp 	var $canvas = $parent.find('.super-signature-canvas');
		// tmp 	$canvas.signature('clear');
		// tmp 	$parent.removeClass('super-filled');
		// tmp 	$parent.find('.super-shortcode-field').val('');
		// tmp 	$parent.find('.super-signature-lines').val('');
		// tmp });

		$doc.ajaxComplete(function() {
			SUPER.init_signature();
		});

	});

})(jQuery);	
