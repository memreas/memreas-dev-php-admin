/* Prototype JavaScript framework, version 1.6.0.3
 * (c) 2005-2008 Sam Stephenson
 *
 * Prototype is freely distributable under the terms of an MIT-style license.
 * For details, see the Prototype web site: http://www.prototypejs.org/
 *
 *--------------------------------------------------------------------------*/

var Prototype = {
 Version: '1.6.0.3',

 Browser: {
 IE: !!(window.attachEvent &&
 navigator.userAgent.indexOf('Opera') === -1),
 Opera: navigator.userAgent.indexOf('Opera') > -1,
 WebKit: navigator.userAgent.indexOf('AppleWebKit/') > -1,
 Gecko: navigator.userAgent.indexOf('Gecko') > -1 &&
 navigator.userAgent.indexOf('KHTML') === -1,
 MobileSafari: !!navigator.userAgent.match(/Apple.*Mobile.*Safari/)
 },

 BrowserFeatures: {
 XPath: !!document.evaluate,
 SelectorsAPI: !!document.querySelector,
 ElementExtensions: !!window.HTMLElement,
 SpecificElementExtensions:
 document.createElement('div')['__proto__'] &&
 document.createElement('div')['__proto__'] !==
 document.createElement('form')['__proto__']
 },

 ScriptFragment: '<script[^>]*>([\\S\\s]*?)<\/script>',
 JSONFilter: /^\/\*-secure-([\s\S]*)\*\/\s*$/,

 emptyFunction: function() { },
 K: function(x) { return x }
};

if (Prototype.Browser.MobileSafari)
 Prototype.BrowserFeatures.SpecificElementExtensions = false;


/* Based on Alex Arnell's inheritance implementation. */
var Class = {
 create: function() {
 var parent = null, properties = $A(arguments);
 if (Object.isFunction(properties[0]))
 parent = properties.shift();

 function klass() {
 this.initialize.apply(this, arguments);
 }

 Object.extend(klass, Class.Methods);
 klass.superclass = parent;
 klass.subclasses = [];

 if (parent) {
 var subclass = function() { };
 subclass.prototype = parent.prototype;
 klass.prototype = new subclass;
 parent.subclasses.push(klass);
 }

 for (var i = 0; i < properties.length; i++)
 klass.addMethods(properties[i]);

 if (!klass.prototype.initialize)
 klass.prototype.initialize = Prototype.emptyFunction;

 klass.prototype.constructor = klass;

 return klass;
 }
};

Class.Methods = {
 addMethods: function(source) {
 var ancestor = this.superclass && this.superclass.prototype;
 var properties = Object.keys(source);

 if (!Object.keys({ toString: true }).length)
 properties.push("toString", "valueOf");

 for (var i = 0, length = properties.length; i < length; i++) {
 var property = properties[i], value = source[property];
 if (ancestor && Object.isFunction(value) &&
 value.argumentNames().first() == "$super") {
 var method = value;
 value = (function(m) {
 return function() { return ancestor[m].apply(this, arguments) };
 })(property).wrap(method);

 value.valueOf = method.valueOf.bind(method);
 value.toString = method.toString.bind(method);
 }
 this.prototype[property] = value;
 }

 return this;
 }
};

var Abstract = { };

Object.extend = function(destination, source) {
 for (var property in source)
 destination[property] = source[property];
 return destination;
};

Object.extend(Object, {
 inspect: function(object) {
 try {
 if (Object.isUndefined(object)) return 'undefined';
 if (object === null) return 'null';
 return object.inspect ? object.inspect() : String(object);
 } catch (e) {
 if (e instanceof RangeError) return '...';
 throw e;
 }
 },

 toJSON: function(object) {
 var type = typeof object;
 switch (type) {
 case 'undefined':
 case 'function':
 case 'unknown': return;
 case 'boolean': return object.toString();
 }

 if (object === null) return 'null';
 if (object.toJSON) return object.toJSON();
 if (Object.isElement(object)) return;

 var results = [];
 for (var property in object) {
 var value = Object.toJSON(object[property]);
 if (!Object.isUndefined(value))
 results.push(property.toJSON() + ': ' + value);
 }

 return '{' + results.join(', ') + '}';
 },

 toQueryString: function(object) {
 return $H(object).toQueryString();
 },

 toHTML: function(object) {
 return object && object.toHTML ? object.toHTML() : String.interpret(object);
 },

 keys: function(object) {
 var keys = [];
 for (var property in object)
 keys.push(property);
 return keys;
 },

 values: function(object) {
 var values = [];
 for (var property in object)
 values.push(object[property]);
 return values;
 },

 clone: function(object) {
 return Object.extend({ }, object);
 },

 isElement: function(object) {
 return !!(object && object.nodeType == 1);
 },

 isArray: function(object) {
 return object != null && typeof object == "object" &&
 'splice' in object && 'join' in object;
 },

 isHash: function(object) {
 return object instanceof Hash;
 },

 isFunction: function(object) {
 return typeof object == "function";
 },

 isString: function(object) {
 return typeof object == "string";
 },

 isNumber: function(object) {
 return typeof object == "number";
 },

 isUndefined: function(object) {
 return typeof object == "undefined";
 }
});

Object.extend(Function.prototype, {
 argumentNames: function() {
 var names = this.toString().match(/^[\s\(]*function[^(]*\(([^\)]*)\)/)[1]
 .replace(/\s+/g, '').split(',');
 return names.length == 1 && !names[0] ? [] : names;
 },

 bind: function() {
 if (arguments.length < 2 && Object.isUndefined(arguments[0])) return this;
 var __method = this, args = $A(arguments), object = args.shift();
 return function() {
 return __method.apply(object, args.concat($A(arguments)));
 }
 },

 bindAsEventListener: function() {
 var __method = this, args = $A(arguments), object = args.shift();
 return function(event) {
 return __method.apply(object, [event || window.event].concat(args));
 }
 },

 curry: function() {
 if (!arguments.length) return this;
 var __method = this, args = $A(arguments);
 return function() {
 return __method.apply(this, args.concat($A(arguments)));
 }
 },

 delay: function() {
 var __method = this, args = $A(arguments), timeout = args.shift() * 1000;
 return window.setTimeout(function() {
 return __method.apply(__method, args);
 }, timeout);
 },

 defer: function() {
 var args = [0.01].concat($A(arguments));
 return this.delay.apply(this, args);
 },

 wrap: function(wrapper) {
 var __method = this;
 return function() {
 return wrapper.apply(this, [__method.bind(this)].concat($A(arguments)));
 }
 },

 methodize: function() {
 if (this._methodized) return this._methodized;
 var __method = this;
 return this._methodized = function() {
 return __method.apply(null, [this].concat($A(arguments)));
 };
 }
});

Date.prototype.toJSON = function() {
 return '"' + this.getUTCFullYear() + '-' +
 (this.getUTCMonth() + 1).toPaddedString(2) + '-' +
 this.getUTCDate().toPaddedString(2) + 'T' +
 this.getUTCHours().toPaddedString(2) + ':' +
 this.getUTCMinutes().toPaddedString(2) + ':' +
 this.getUTCSeconds().toPaddedString(2) + 'Z"';
};

var Try = {
 these: function() {
 var returnValue;

 for (var i = 0, length = arguments.length; i < length; i++) {
 var lambda = arguments[i];
 try {
 returnValue = lambda();
 break;
 } catch (e) { }
 }

 return returnValue;
 }
};

RegExp.prototype.match = RegExp.prototype.test;

RegExp.escape = function(str) {
 return String(str).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
};

/*--------------------------------------------------------------------------*/

var PeriodicalExecuter = Class.create({
 initialize: function(callback, frequency) {
 this.callback = callback;
 this.frequency = frequency;
 this.currentlyExecuting = false;

 this.registerCallback();
 },

 registerCallback: function() {
 this.timer = setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
 },

 execute: function() {
 this.callback(this);
 },

 stop: function() {
 if (!this.timer) return;
 clearInterval(this.timer);
 this.timer = null;
 },

 onTimerEvent: function() {
 if (!this.currentlyExecuting) {
 try {
 this.currentlyExecuting = true;
 this.execute();
 } finally {
 this.currentlyExecuting = false;
 }
 }
 }
});
Object.extend(String, {
 interpret: function(value) {
 return value == null ? '' : String(value);
 },
 specialChar: {
 '\b': '\\b',
 '\t': '\\t',
 '\n': '\\n',
 '\f': '\\f',
 '\r': '\\r',
 '\\': '\\\\'
 }
});

Object.extend(String.prototype, {
 gsub: function(pattern, replacement) {
 var result = '', source = this, match;
 replacement = arguments.callee.prepareReplacement(replacement);

 while (source.length > 0) {
 if (match = source.match(pattern)) {
 result += source.slice(0, match.index);
 result += String.interpret(replacement(match));
 source = source.slice(match.index + match[0].length);
 } else {
 result += source, source = '';
 }
 }
 return result;
 },

 sub: function(pattern, replacement, count) {
 replacement = this.gsub.prepareReplacement(replacement);
 count = Object.isUndefined(count) ? 1 : count;

 return this.gsub(pattern, function(match) {
 if (--count < 0) return match[0];
 return replacement(match);
 });
 },

 scan: function(pattern, iterator) {
 this.gsub(pattern, iterator);
 return String(this);
 },

 truncate: function(length, truncation) {
 length = length || 30;
 truncation = Object.isUndefined(truncation) ? '...' : truncation;
 return this.length > length ?
 this.slice(0, length - truncation.length) + truncation : String(this);
 },

 strip: function() {
 return this.replace(/^\s+/, '').replace(/\s+$/, '');
 },

 stripTags: function() {
 return this.replace(/<\/?[^>]+>/gi, '');
 },

 stripScripts: function() {
 return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
 },

 extractScripts: function() {
 var matchAll = new RegExp(Prototype.ScriptFragment, 'img');
 var matchOne = new RegExp(Prototype.ScriptFragment, 'im');
 return (this.match(matchAll) || []).map(function(scriptTag) {
 return (scriptTag.match(matchOne) || ['', ''])[1];
 });
 },

 evalScripts: function() {
 return this.extractScripts().map(function(script) { return eval(script) });
 },

 escapeHTML: function() {
 var self = arguments.callee;
 self.text.data = this;
 return self.div.innerHTML;
 },

 unescapeHTML: function() {
 var div = new Element('div');
 div.innerHTML = this.stripTags();
 return div.childNodes[0] ? (div.childNodes.length > 1 ?
 $A(div.childNodes).inject('', function(memo, node) { return memo+node.nodeValue }) :
 div.childNodes[0].nodeValue) : '';
 },

 toQueryParams: function(separator) {
 var match = this.strip().match(/([^?#]*)(#.*)?$/);
 if (!match) return { };

 return match[1].split(separator || '&').inject({ }, function(hash, pair) {
 if ((pair = pair.split('='))[0]) {
 var key = decodeURIComponent(pair.shift());
 var value = pair.length > 1 ? pair.join('=') : pair[0];
 if (value != undefined) value = decodeURIComponent(value);

 if (key in hash) {
 if (!Object.isArray(hash[key])) hash[key] = [hash[key]];
 hash[key].push(value);
 }
 else hash[key] = value;
 }
 return hash;
 });
 },

 toArray: function() {
 return this.split('');
 },

 succ: function() {
 return this.slice(0, this.length - 1) +
 String.fromCharCode(this.charCodeAt(this.length - 1) + 1);
 },

 times: function(count) {
 return count < 1 ? '' : new Array(count + 1).join(this);
 },

 camelize: function() {
 var parts = this.split('-'), len = parts.length;
 if (len == 1) return parts[0];

 var camelized = this.charAt(0) == '-'
 ? parts[0].charAt(0).toUpperCase() + parts[0].substring(1)
 : parts[0];

 for (var i = 1; i < len; i++)
 camelized += parts[i].charAt(0).toUpperCase() + parts[i].substring(1);

 return camelized;
 },

 capitalize: function() {
 return this.charAt(0).toUpperCase() + this.substring(1).toLowerCase();
 },

 underscore: function() {
 return this.gsub(/::/, '/').gsub(/([A-Z]+)([A-Z][a-z])/,'#{1}_#{2}').gsub(/([a-z\d])([A-Z])/,'#{1}_#{2}').gsub(/-/,'_').toLowerCase();
 },

 dasherize: function() {
 return this.gsub(/_/,'-');
 },

 inspect: function(useDoubleQuotes) {
 var escapedString = this.gsub(/[\x00-\x1f\\]/, function(match) {
 var character = String.specialChar[match[0]];
 return character ? character : '\\u00' + match[0].charCodeAt().toPaddedString(2, 16);
 });
 if (useDoubleQuotes) return '"' + escapedString.replace(/"/g, '\\"') + '"';
 return "'" + escapedString.replace(/'/g, '\\\'') + "'";
 },

 toJSON: function() {
 return this.inspect(true);
 },

 unfilterJSON: function(filter) {
 return this.sub(filter || Prototype.JSONFilter, '#{1}');
 },

 isJSON: function() {
 var str = this;
 if (str.blank()) return false;
 str = this.replace(/\\./g, '@').replace(/"[^"\\\n\r]*"/g, '');
 return (/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/).test(str);
 },

 evalJSON: function(sanitize) {
 var json = this.unfilterJSON();
 try {
 if (!sanitize || json.isJSON()) return eval('(' + json + ')');
 } catch (e) { }
 throw new SyntaxError('Badly formed JSON string: ' + this.inspect());
 },

 include: function(pattern) {
 return this.indexOf(pattern) > -1;
 },

 startsWith: function(pattern) {
 return this.indexOf(pattern) === 0;
 },

 endsWith: function(pattern) {
 var d = this.length - pattern.length;
 return d >= 0 && this.lastIndexOf(pattern) === d;
 },

 empty: function() {
 return this == '';
 },

 blank: function() {
 return /^\s*$/.test(this);
 },

 interpolate: function(object, pattern) {
 return new Template(this, pattern).evaluate(object);
 }
});

if (Prototype.Browser.WebKit || Prototype.Browser.IE) Object.extend(String.prototype, {
 escapeHTML: function() {
 return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
 },
 unescapeHTML: function() {
 return this.stripTags().replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
 }
});

String.prototype.gsub.prepareReplacement = function(replacement) {
 if (Object.isFunction(replacement)) return replacement;
 var template = new Template(replacement);
 return function(match) { return template.evaluate(match) };
};

String.prototype.parseQuery = String.prototype.toQueryParams;

Object.extend(String.prototype.escapeHTML, {
 div: document.createElement('div'),
 text: document.createTextNode('')
});

String.prototype.escapeHTML.div.appendChild(String.prototype.escapeHTML.text);

var Template = Class.create({
 initialize: function(template, pattern) {
 this.template = template.toString();
 this.pattern = pattern || Template.Pattern;
 },

 evaluate: function(object) {
 if (Object.isFunction(object.toTemplateReplacements))
 object = object.toTemplateReplacements();

 return this.template.gsub(this.pattern, function(match) {
 if (object == null) return '';

 var before = match[1] || '';
 if (before == '\\') return match[2];

 var ctx = object, expr = match[3];
 var pattern = /^([^.[]+|\[((?:.*?[^\\])?)\])(\.|\[|$)/;
 match = pattern.exec(expr);
 if (match == null) return before;

 while (match != null) {
 var comp = match[1].startsWith('[') ? match[2].gsub('\\\\]', ']') : match[1];
 ctx = ctx[comp];
 if (null == ctx || '' == match[3]) break;
 expr = expr.substring('[' == match[3] ? match[1].length : match[0].length);
 match = pattern.exec(expr);
 }

 return before + String.interpret(ctx);
 });
 }
});
Template.Pattern = /(^|.|\r|\n)(#\{(.*?)\})/;

var $break = { };

var Enumerable = {
 each: function(iterator, context) {
 var index = 0;
 try {
 this._each(function(value) {
 iterator.call(context, value, index++);
 });
 } catch (e) {
 if (e != $break) throw e;
 }
 return this;
 },

 eachSlice: function(number, iterator, context) {
 var index = -number, slices = [], array = this.toArray();
 if (number < 1) return array;
 while ((index += number) < array.length)
 slices.push(array.slice(index, index+number));
 return slices.collect(iterator, context);
 },

 all: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var result = true;
 this.each(function(value, index) {
 result = result && !!iterator.call(context, value, index);
 if (!result) throw $break;
 });
 return result;
 },

 any: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var result = false;
 this.each(function(value, index) {
 if (result = !!iterator.call(context, value, index))
 throw $break;
 });
 return result;
 },

 collect: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var results = [];
 this.each(function(value, index) {
 results.push(iterator.call(context, value, index));
 });
 return results;
 },

 detect: function(iterator, context) {
 var result;
 this.each(function(value, index) {
 if (iterator.call(context, value, index)) {
 result = value;
 throw $break;
 }
 });
 return result;
 },

 findAll: function(iterator, context) {
 var results = [];
 this.each(function(value, index) {
 if (iterator.call(context, value, index))
 results.push(value);
 });
 return results;
 },

 grep: function(filter, iterator, context) {
 iterator = iterator || Prototype.K;
 var results = [];

 if (Object.isString(filter))
 filter = new RegExp(filter);

 this.each(function(value, index) {
 if (filter.match(value))
 results.push(iterator.call(context, value, index));
 });
 return results;
 },

 include: function(object) {
 if (Object.isFunction(this.indexOf))
 if (this.indexOf(object) != -1) return true;

 var found = false;
 this.each(function(value) {
 if (value == object) {
 found = true;
 throw $break;
 }
 });
 return found;
 },

 inGroupsOf: function(number, fillWith) {
 fillWith = Object.isUndefined(fillWith) ? null : fillWith;
 return this.eachSlice(number, function(slice) {
 while(slice.length < number) slice.push(fillWith);
 return slice;
 });
 },

 inject: function(memo, iterator, context) {
 this.each(function(value, index) {
 memo = iterator.call(context, memo, value, index);
 });
 return memo;
 },

 invoke: function(method) {
 var args = $A(arguments).slice(1);
 return this.map(function(value) {
 return value[method].apply(value, args);
 });
 },

 max: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var result;
 this.each(function(value, index) {
 value = iterator.call(context, value, index);
 if (result == null || value >= result)
 result = value;
 });
 return result;
 },

 min: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var result;
 this.each(function(value, index) {
 value = iterator.call(context, value, index);
 if (result == null || value < result)
 result = value;
 });
 return result;
 },

 partition: function(iterator, context) {
 iterator = iterator || Prototype.K;
 var trues = [], falses = [];
 this.each(function(value, index) {
 (iterator.call(context, value, index) ?
 trues : falses).push(value);
 });
 return [trues, falses];
 },

 pluck: function(property) {
 var results = [];
 this.each(function(value) {
 results.push(value[property]);
 });
 return results;
 },

 reject: function(iterator, context) {
 var results = [];
 this.each(function(value, index) {
 if (!iterator.call(context, value, index))
 results.push(value);
 });
 return results;
 },

 sortBy: function(iterator, context) {
 return this.map(function(value, index) {
 return {
 value: value,
 criteria: iterator.call(context, value, index)
 };
 }).sort(function(left, right) {
 var a = left.criteria, b = right.criteria;
 return a < b ? -1 : a > b ? 1 : 0;
 }).pluck('value');
 },

 toArray: function() {
 return this.map();
 },

 zip: function() {
 var iterator = Prototype.K, args = $A(arguments);
 if (Object.isFunction(args.last()))
 iterator = args.pop();

 var collections = [this].concat(args).map($A);
 return this.map(function(value, index) {
 return iterator(collections.pluck(index));
 });
 },

 size: function() {
 return this.toArray().length;
 },

 inspect: function() {
 return '#<Enumerable:' + this.toArray().inspect() + '>';
 }
};

Object.extend(Enumerable, {
 map: Enumerable.collect,
 find: Enumerable.detect,
 select: Enumerable.findAll,
 filter: Enumerable.findAll,
 member: Enumerable.include,
 entries: Enumerable.toArray,
 every: Enumerable.all,
 some: Enumerable.any
});
function $A(iterable) {
 if (!iterable) return [];
 if (iterable.toArray) return iterable.toArray();
 var length = iterable.length || 0, results = new Array(length);
 while (length--) results[length] = iterable[length];
 return results;
}

if (Prototype.Browser.WebKit) {
 $A = function(iterable) {
 if (!iterable) return [];
 // In Safari, only use the `toArray` method if it's not a NodeList.
 // A NodeList is a function, has an function `item` property, and a numeric
 // `length` property. Adapted from Google Doctype.
 if (!(typeof iterable === 'function' && typeof iterable.length ===
 'number' && typeof iterable.item === 'function') && iterable.toArray)
 return iterable.toArray();
 var length = iterable.length || 0, results = new Array(length);
 while (length--) results[length] = iterable[length];
 return results;
 };
}

Array.from = $A;

Object.extend(Array.prototype, Enumerable);

if (!Array.prototype._reverse) Array.prototype._reverse = Array.prototype.reverse;

Object.extend(Array.prototype, {
 _each: function(iterator) {
 for (var i = 0, length = this.length; i < length; i++)
 iterator(this[i]);
 },

 clear: function() {
 this.length = 0;
 return this;
 },

 first: function() {
 return this[0];
 },

 last: function() {
 return this[this.length - 1];
 },

 compact: function() {
 return this.select(function(value) {
 return value != null;
 });
 },

 flatten: function() {
 return this.inject([], function(array, value) {
 return array.concat(Object.isArray(value) ?
 value.flatten() : [value]);
 });
 },

 without: function() {
 var values = $A(arguments);
 return this.select(function(value) {
 return !values.include(value);
 });
 },

 reverse: function(inline) {
 return (inline !== false ? this : this.toArray())._reverse();
 },

 reduce: function() {
 return this.length > 1 ? this : this[0];
 },

 uniq: function(sorted) {
 return this.inject([], function(array, value, index) {
 if (0 == index || (sorted ? array.last() != value : !array.include(value)))
 array.push(value);
 return array;
 });
 },

 intersect: function(array) {
 return this.uniq().findAll(function(item) {
 return array.detect(function(value) { return item === value });
 });
 },

 clone: function() {
 return [].concat(this);
 },

 size: function() {
 return this.length;
 },

 inspect: function() {
 return '[' + this.map(Object.inspect).join(', ') + ']';
 },

 toJSON: function() {
 var results = [];
 this.each(function(object) {
 var value = Object.toJSON(object);
 if (!Object.isUndefined(value)) results.push(value);
 });
 return '[' + results.join(', ') + ']';
 }
});

// use native browser JS 1.6 implementation if available
if (Object.isFunction(Array.prototype.forEach))
 Array.prototype._each = Array.prototype.forEach;

if (!Array.prototype.indexOf) Array.prototype.indexOf = function(item, i) {
 i || (i = 0);
 var length = this.length;
 if (i < 0) i = length + i;
 for (; i < length; i++)
 if (this[i] === item) return i;
 return -1;
};

if (!Array.prototype.lastIndexOf) Array.prototype.lastIndexOf = function(item, i) {
 i = isNaN(i) ? this.length : (i < 0 ? this.length + i : i) + 1;
 var n = this.slice(0, i).reverse().indexOf(item);
 return (n < 0) ? n : i - n - 1;
};

Array.prototype.toArray = Array.prototype.clone;

function $w(string) {
 if (!Object.isString(string)) return [];
 string = string.strip();
 return string ? string.split(/\s+/) : [];
}

if (Prototype.Browser.Opera){
 Array.prototype.concat = function() {
 var array = [];
 for (var i = 0, length = this.length; i < length; i++) array.push(this[i]);
 for (var i = 0, length = arguments.length; i < length; i++) {
 if (Object.isArray(arguments[i])) {
 for (var j = 0, arrayLength = arguments[i].length; j < arrayLength; j++)
 array.push(arguments[i][j]);
 } else {
 array.push(arguments[i]);
 }
 }
 return array;
 };
}
Object.extend(Number.prototype, {
 toColorPart: function() {
 return this.toPaddedString(2, 16);
 },

 succ: function() {
 return this + 1;
 },

 times: function(iterator, context) {
 $R(0, this, true).each(iterator, context);
 return this;
 },

 toPaddedString: function(length, radix) {
 var string = this.toString(radix || 10);
 return '0'.times(length - string.length) + string;
 },

 toJSON: function() {
 return isFinite(this) ? this.toString() : 'null';
 }
});

$w('abs round ceil floor').each(function(method){
 Number.prototype[method] = Math[method].methodize();
});
function $H(object) {
 return new Hash(object);
};

var Hash = Class.create(Enumerable, (function() {

 function toQueryPair(key, value) {
 if (Object.isUndefined(value)) return key;
 return key + '=' + encodeURIComponent(String.interpret(value));
 }

 return {
 initialize: function(object) {
 this._object = Object.isHash(object) ? object.toObject() : Object.clone(object);
 },

 _each: function(iterator) {
 for (var key in this._object) {
 var value = this._object[key], pair = [key, value];
 pair.key = key;
 pair.value = value;
 iterator(pair);
 }
 },

 set: function(key, value) {
 return this._object[key] = value;
 },

 get: function(key) {
 // simulating poorly supported hasOwnProperty
 if (this._object[key] !== Object.prototype[key])
 return this._object[key];
 },

 unset: function(key) {
 var value = this._object[key];
 delete this._object[key];
 return value;
 },

 toObject: function() {
 return Object.clone(this._object);
 },

 keys: function() {
 return this.pluck('key');
 },

 values: function() {
 return this.pluck('value');
 },

 index: function(value) {
 var match = this.detect(function(pair) {
 return pair.value === value;
 });
 return match && match.key;
 },

 merge: function(object) {
 return this.clone().update(object);
 },

 update: function(object) {
 return new Hash(object).inject(this, function(result, pair) {
 result.set(pair.key, pair.value);
 return result;
 });
 },

 toQueryString: function() {
 return this.inject([], function(results, pair) {
 var key = encodeURIComponent(pair.key), values = pair.value;

 if (values && typeof values == 'object') {
 if (Object.isArray(values))
 return results.concat(values.map(toQueryPair.curry(key)));
 } else results.push(toQueryPair(key, values));
 return results;
 }).join('&');
 },

 inspect: function() {
 return '#<Hash:{' + this.map(function(pair) {
 return pair.map(Object.inspect).join(': ');
 }).join(', ') + '}>';
 },

 toJSON: function() {
 return Object.toJSON(this.toObject());
 },

 clone: function() {
 return new Hash(this);
 }
 }
})());

Hash.prototype.toTemplateReplacements = Hash.prototype.toObject;
Hash.from = $H;
var ObjectRange = Class.create(Enumerable, {
 initialize: function(start, end, exclusive) {
 this.start = start;
 this.end = end;
 this.exclusive = exclusive;
 },

 _each: function(iterator) {
 var value = this.start;
 while (this.include(value)) {
 iterator(value);
 value = value.succ();
 }
 },

 include: function(value) {
 if (value < this.start)
 return false;
 if (this.exclusive)
 return value < this.end;
 return value <= this.end;
 }
});

var $R = function(start, end, exclusive) {
 return new ObjectRange(start, end, exclusive);
};

var Ajax = {
 getTransport: function() {
 return Try.these(
 function() {return new XMLHttpRequest()},
 function() {return new ActiveXObject('Msxml2.XMLHTTP')},
 function() {return new ActiveXObject('Microsoft.XMLHTTP')}
 ) || false;
 },

 activeRequestCount: 0
};

Ajax.Responders = {
 responders: [],

 _each: function(iterator) {
 this.responders._each(iterator);
 },

 register: function(responder) {
 if (!this.include(responder))
 this.responders.push(responder);
 },

 unregister: function(responder) {
 this.responders = this.responders.without(responder);
 },

 dispatch: function(callback, request, transport, json) {
 this.each(function(responder) {
 if (Object.isFunction(responder[callback])) {
 try {
 responder[callback].apply(responder, [request, transport, json]);
 } catch (e) { }
 }
 });
 }
};

Object.extend(Ajax.Responders, Enumerable);

Ajax.Responders.register({
 onCreate: function() { Ajax.activeRequestCount++ },
 onComplete: function() { Ajax.activeRequestCount-- }
});

Ajax.Base = Class.create({
 initialize: function(options) {
 this.options = {
 method: 'post',
 asynchronous: true,
 contentType: 'application/x-www-form-urlencoded',
 encoding: 'UTF-8',
 parameters: '',
 evalJSON: true,
 evalJS: true
 };
 Object.extend(this.options, options || { });

 this.options.method = this.options.method.toLowerCase();

 if (Object.isString(this.options.parameters))
 this.options.parameters = this.options.parameters.toQueryParams();
 else if (Object.isHash(this.options.parameters))
 this.options.parameters = this.options.parameters.toObject();
 }
});

Ajax.Request = Class.create(Ajax.Base, {
 _complete: false,

 initialize: function($super, url, options) {
 $super(options);
 this.transport = Ajax.getTransport();
 this.request(url);
 },

 request: function(url) {
 this.url = url;
 this.method = this.options.method;
 var params = Object.clone(this.options.parameters);

 if (!['get', 'post'].include(this.method)) {
 // simulate other verbs over post
 params['_method'] = this.method;
 this.method = 'post';
 }

 this.parameters = params;

 if (params = Object.toQueryString(params)) {
 // when GET, append parameters to URL
 if (this.method == 'get')
 this.url += (this.url.include('?') ? '&' : '?') + params;
 else if (/Konqueror|Safari|KHTML/.test(navigator.userAgent))
 params += '&_=';
 }

 try {
 var response = new Ajax.Response(this);
 if (this.options.onCreate) this.options.onCreate(response);
 Ajax.Responders.dispatch('onCreate', this, response);

 this.transport.open(this.method.toUpperCase(), this.url,
 this.options.asynchronous);

 if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

 this.transport.onreadystatechange = this.onStateChange.bind(this);
 this.setRequestHeaders();

 this.body = this.method == 'post' ? (this.options.postBody || params) : null;
 this.transport.send(this.body);

 /* Force Firefox to handle ready state 4 for synchronous requests */
 if (!this.options.asynchronous && this.transport.overrideMimeType)
 this.onStateChange();

 }
 catch (e) {
 this.dispatchException(e);
 }
 },

 onStateChange: function() {
 var readyState = this.transport.readyState;
 if (readyState > 1 && !((readyState == 4) && this._complete))
 this.respondToReadyState(this.transport.readyState);
 },

 setRequestHeaders: function() {
 var headers = {
 'X-Requested-With': 'XMLHttpRequest',
 'X-Prototype-Version': Prototype.Version,
 'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
 };

 if (this.method == 'post') {
 headers['Content-type'] = this.options.contentType +
 (this.options.encoding ? '; charset=' + this.options.encoding : '');

 /* Force "Connection: close" for older Mozilla browsers to work
 * around a bug where XMLHttpRequest sends an incorrect
 * Content-length header. See Mozilla Bugzilla #246651.
 */
 if (this.transport.overrideMimeType &&
 (navigator.userAgent.match(/Gecko\/(\d{4})/) || [0,2005])[1] < 2005)
 headers['Connection'] = 'close';
 }

 // user-defined headers
 if (typeof this.options.requestHeaders == 'object') {
 var extras = this.options.requestHeaders;

 if (Object.isFunction(extras.push))
 for (var i = 0, length = extras.length; i < length; i += 2)
 headers[extras[i]] = extras[i+1];
 else
 $H(extras).each(function(pair) { headers[pair.key] = pair.value });
 }

 for (var name in headers)
 this.transport.setRequestHeader(name, headers[name]);
 },

 success: function() {
 var status = this.getStatus();
 return !status || (status >= 200 && status < 300);
 },

 getStatus: function() {
 try {
 return this.transport.status || 0;
 } catch (e) { return 0 }
 },

 respondToReadyState: function(readyState) {
 var state = Ajax.Request.Events[readyState], response = new Ajax.Response(this);

 if (state == 'Complete') {
 try {
 this._complete = true;
 (this.options['on' + response.status]
 || this.options['on' + (this.success() ? 'Success' : 'Failure')]
 || Prototype.emptyFunction)(response, response.headerJSON);
 } catch (e) {
 this.dispatchException(e);
 }

 var contentType = response.getHeader('Content-type');
 if (this.options.evalJS == 'force'
 || (this.options.evalJS && this.isSameOrigin() && contentType
 && contentType.match(/^\s*(text|application)\/(x-)?(java|ecma)script(;.*)?\s*$/i)))
 this.evalResponse();
 }

 try {
 (this.options['on' + state] || Prototype.emptyFunction)(response, response.headerJSON);
 Ajax.Responders.dispatch('on' + state, this, response, response.headerJSON);
 } catch (e) {
 this.dispatchException(e);
 }

 if (state == 'Complete') {
 // avoid memory leak in MSIE: clean up
 this.transport.onreadystatechange = Prototype.emptyFunction;
 }
 },

 isSameOrigin: function() {
 var m = this.url.match(/^\s*https?:\/\/[^\/]*/);
 return !m || (m[0] == '#{protocol}//#{domain}#{port}'.interpolate({
 protocol: location.protocol,
 domain: document.domain,
 port: location.port ? ':' + location.port : ''
 }));
 },

 getHeader: function(name) {
 try {
 return this.transport.getResponseHeader(name) || null;
 } catch (e) { return null }
 },

 evalResponse: function() {
 try {
 return eval((this.transport.responseText || '').unfilterJSON());
 } catch (e) {
 this.dispatchException(e);
 }
 },

 dispatchException: function(exception) {
 (this.options.onException || Prototype.emptyFunction)(this, exception);
 Ajax.Responders.dispatch('onException', this, exception);
 }
});

Ajax.Request.Events =
 ['Uninitialized', 'Loading', 'Loaded', 'Interactive', 'Complete'];

Ajax.Response = Class.create({
 initialize: function(request){
 this.request = request;
 var transport = this.transport = request.transport,
 readyState = this.readyState = transport.readyState;

 if((readyState > 2 && !Prototype.Browser.IE) || readyState == 4) {
 this.status = this.getStatus();
 this.statusText = this.getStatusText();
 this.responseText = String.interpret(transport.responseText);
 this.headerJSON = this._getHeaderJSON();
 }

 if(readyState == 4) {
 var xml = transport.responseXML;
 this.responseXML = Object.isUndefined(xml) ? null : xml;
 this.responseJSON = this._getResponseJSON();
 }
 },

 status: 0,
 statusText: '',

 getStatus: Ajax.Request.prototype.getStatus,

 getStatusText: function() {
 try {
 return this.transport.statusText || '';
 } catch (e) { return '' }
 },

 getHeader: Ajax.Request.prototype.getHeader,

 getAllHeaders: function() {
 try {
 return this.getAllResponseHeaders();
 } catch (e) { return null }
 },

 getResponseHeader: function(name) {
 return this.transport.getResponseHeader(name);
 },

 getAllResponseHeaders: function() {
 return this.transport.getAllResponseHeaders();
 },

 _getHeaderJSON: function() {
 var json = this.getHeader('X-JSON');
 if (!json) return null;
 json = decodeURIComponent(escape(json));
 try {
 return json.evalJSON(this.request.options.sanitizeJSON ||
 !this.request.isSameOrigin());
 } catch (e) {
 this.request.dispatchException(e);
 }
 },

 _getResponseJSON: function() {
 var options = this.request.options;
 if (!options.evalJSON || (options.evalJSON != 'force' &&
 !(this.getHeader('Content-type') || '').include('application/json')) ||
 this.responseText.blank())
 return null;
 try {
 return this.responseText.evalJSON(options.sanitizeJSON ||
 !this.request.isSameOrigin());
 } catch (e) {
 this.request.dispatchException(e);
 }
 }
});

Ajax.Updater = Class.create(Ajax.Request, {
 initialize: function($super, container, url, options) {
 this.container = {
 success: (container.success || container),
 failure: (container.failure || (container.success ? null : container))
 };

 options = Object.clone(options);
 var onComplete = options.onComplete;
 options.onComplete = (function(response, json) {
 this.updateContent(response.responseText);
 if (Object.isFunction(onComplete)) onComplete(response, json);
 }).bind(this);

 $super(url, options);
 },

 updateContent: function(responseText) {
 var receiver = this.container[this.success() ? 'success' : 'failure'],
 options = this.options;

 if (!options.evalScripts) responseText = responseText.stripScripts();

 if (receiver = $(receiver)) {
 if (options.insertion) {
 if (Object.isString(options.insertion)) {
 var insertion = { }; insertion[options.insertion] = responseText;
 receiver.insert(insertion);
 }
 else options.insertion(receiver, responseText);
 }
 else receiver.update(responseText);
 }
 }
});

Ajax.PeriodicalUpdater = Class.create(Ajax.Base, {
 initialize: function($super, container, url, options) {
 $super(options);
 this.onComplete = this.options.onComplete;

 this.frequency = (this.options.frequency || 2);
 this.decay = (this.options.decay || 1);

 this.updater = { };
 this.container = container;
 this.url = url;

 this.start();
 },

 start: function() {
 this.options.onComplete = this.updateComplete.bind(this);
 this.onTimerEvent();
 },

 stop: function() {
 this.updater.options.onComplete = undefined;
 clearTimeout(this.timer);
 (this.onComplete || Prototype.emptyFunction).apply(this, arguments);
 },

 updateComplete: function(response) {
 if (this.options.decay) {
 this.decay = (response.responseText == this.lastText ?
 this.decay * this.options.decay : 1);

 this.lastText = response.responseText;
 }
 this.timer = this.onTimerEvent.bind(this).delay(this.decay * this.frequency);
 },

 onTimerEvent: function() {
 this.updater = new Ajax.Updater(this.container, this.url, this.options);
 }
});
function $(element) {
 if (arguments.length > 1) {
 for (var i = 0, elements = [], length = arguments.length; i < length; i++)
 elements.push($(arguments[i]));
 return elements;
 }
 if (Object.isString(element))
 element = document.getElementById(element);
 return Element.extend(element);
}

if (Prototype.BrowserFeatures.XPath) {
 document._getElementsByXPath = function(expression, parentElement) {
 var results = [];
 var query = document.evaluate(expression, $(parentElement) || document,
 null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
 for (var i = 0, length = query.snapshotLength; i < length; i++)
 results.push(Element.extend(query.snapshotItem(i)));
 return results;
 };
}

/*--------------------------------------------------------------------------*/

if (!window.Node) var Node = { };

if (!Node.ELEMENT_NODE) {
 // DOM level 2 ECMAScript Language Binding
 Object.extend(Node, {
 ELEMENT_NODE: 1,
 ATTRIBUTE_NODE: 2,
 TEXT_NODE: 3,
 CDATA_SECTION_NODE: 4,
 ENTITY_REFERENCE_NODE: 5,
 ENTITY_NODE: 6,
 PROCESSING_INSTRUCTION_NODE: 7,
 COMMENT_NODE: 8,
 DOCUMENT_NODE: 9,
 DOCUMENT_TYPE_NODE: 10,
 DOCUMENT_FRAGMENT_NODE: 11,
 NOTATION_NODE: 12
 });
}

(function() {
 var element = this.Element;
 this.Element = function(tagName, attributes) {
 attributes = attributes || { };
 tagName = tagName.toLowerCase();
 var cache = Element.cache;
 if (Prototype.Browser.IE && attributes.name) {
 tagName = '<' + tagName + ' name="' + attributes.name + '">';
 delete attributes.name;
 return Element.writeAttribute(document.createElement(tagName), attributes);
 }
 if (!cache[tagName]) cache[tagName] = Element.extend(document.createElement(tagName));
 return Element.writeAttribute(cache[tagName].cloneNode(false), attributes);
 };
 Object.extend(this.Element, element || { });
 if (element) this.Element.prototype = element.prototype;
}).call(window);

Element.cache = { };

Element.Methods = {
 visible: function(element) {
 return $(element).style.display != 'none';
 },

 toggle: function(element) {
 element = $(element);
 Element[Element.visible(element) ? 'hide' : 'show'](element);
 return element;
 },

 hide: function(element) {
 element = $(element);
 element.style.display = 'none';
 return element;
 },

 show: function(element) {
 element = $(element);
 element.style.display = '';
 return element;
 },

 remove: function(element) {
 element = $(element);
 element.parentNode.removeChild(element);
 return element;
 },

 update: function(element, content) {
 element = $(element);
 if (content && content.toElement) content = content.toElement();
 if (Object.isElement(content)) return element.update().insert(content);
 content = Object.toHTML(content);
 element.innerHTML = content.stripScripts();
 content.evalScripts.bind(content).defer();
 return element;
 },

 replace: function(element, content) {
 element = $(element);
 if (content && content.toElement) content = content.toElement();
 else if (!Object.isElement(content)) {
 content = Object.toHTML(content);
 var range = element.ownerDocument.createRange();
 range.selectNode(element);
 content.evalScripts.bind(content).defer();
 content = range.createContextualFragment(content.stripScripts());
 }
 element.parentNode.replaceChild(content, element);
 return element;
 },

 insert: function(element, insertions) {
 element = $(element);

 if (Object.isString(insertions) || Object.isNumber(insertions) ||
 Object.isElement(insertions) || (insertions && (insertions.toElement || insertions.toHTML)))
 insertions = {bottom:insertions};

 var content, insert, tagName, childNodes;

 for (var position in insertions) {
 content = insertions[position];
 position = position.toLowerCase();
 insert = Element._insertionTranslations[position];

 if (content && content.toElement) content = content.toElement();
 if (Object.isElement(content)) {
 insert(element, content);
 continue;
 }

 content = Object.toHTML(content);

 tagName = ((position == 'before' || position == 'after')
 ? element.parentNode : element).tagName.toUpperCase();

 childNodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts());

 if (position == 'top' || position == 'after') childNodes.reverse();
 childNodes.each(insert.curry(element));

 content.evalScripts.bind(content).defer();
 }

 return element;
 },

 wrap: function(element, wrapper, attributes) {
 element = $(element);
 if (Object.isElement(wrapper))
 $(wrapper).writeAttribute(attributes || { });
 else if (Object.isString(wrapper)) wrapper = new Element(wrapper, attributes);
 else wrapper = new Element('div', wrapper);
 if (element.parentNode)
 element.parentNode.replaceChild(wrapper, element);
 wrapper.appendChild(element);
 return wrapper;
 },

 inspect: function(element) {
 element = $(element);
 var result = '<' + element.tagName.toLowerCase();
 $H({'id': 'id', 'className': 'class'}).each(function(pair) {
 var property = pair.first(), attribute = pair.last();
 var value = (element[property] || '').toString();
 if (value) result += ' ' + attribute + '=' + value.inspect(true);
 });
 return result + '>';
 },

 recursivelyCollect: function(element, property) {
 element = $(element);
 var elements = [];
 while (element = element[property])
 if (element.nodeType == 1)
 elements.push(Element.extend(element));
 return elements;
 },

 ancestors: function(element) {
 return $(element).recursivelyCollect('parentNode');
 },

 descendants: function(element) {
 return $(element).select("*");
 },

 firstDescendant: function(element) {
 element = $(element).firstChild;
 while (element && element.nodeType != 1) element = element.nextSibling;
 return $(element);
 },

 immediateDescendants: function(element) {
 if (!(element = $(element).firstChild)) return [];
 while (element && element.nodeType != 1) element = element.nextSibling;
 if (element) return [element].concat($(element).nextSiblings());
 return [];
 },

 previousSiblings: function(element) {
 return $(element).recursivelyCollect('previousSibling');
 },

 nextSiblings: function(element) {
 return $(element).recursivelyCollect('nextSibling');
 },

 siblings: function(element) {
 element = $(element);
 return element.previousSiblings().reverse().concat(element.nextSiblings());
 },

 match: function(element, selector) {
 if (Object.isString(selector))
 selector = new Selector(selector);
 return selector.match($(element));
 },

 up: function(element, expression, index) {
 element = $(element);
 if (arguments.length == 1) return $(element.parentNode);
 var ancestors = element.ancestors();
 return Object.isNumber(expression) ? ancestors[expression] :
 Selector.findElement(ancestors, expression, index);
 },

 down: function(element, expression, index) {
 element = $(element);
 if (arguments.length == 1) return element.firstDescendant();
 return Object.isNumber(expression) ? element.descendants()[expression] :
 Element.select(element, expression)[index || 0];
 },

 previous: function(element, expression, index) {
 element = $(element);
 if (arguments.length == 1) return $(Selector.handlers.previousElementSibling(element));
 var previousSiblings = element.previousSiblings();
 return Object.isNumber(expression) ? previousSiblings[expression] :
 Selector.findElement(previousSiblings, expression, index);
 },

 next: function(element, expression, index) {
 element = $(element);
 if (arguments.length == 1) return $(Selector.handlers.nextElementSibling(element));
 var nextSiblings = element.nextSiblings();
 return Object.isNumber(expression) ? nextSiblings[expression] :
 Selector.findElement(nextSiblings, expression, index);
 },

 select: function() {
 var args = $A(arguments), element = $(args.shift());
 return Selector.findChildElements(element, args);
 },

 adjacent: function() {
 var args = $A(arguments), element = $(args.shift());
 return Selector.findChildElements(element.parentNode, args).without(element);
 },

 identify: function(element) {
 element = $(element);
 var id = element.readAttribute('id'), self = arguments.callee;
 if (id) return id;
 do { id = 'anonymous_element_' + self.counter++ } while ($(id));
 element.writeAttribute('id', id);
 return id;
 },

 readAttribute: function(element, name) {
 element = $(element);
 if (Prototype.Browser.IE) {
 var t = Element._attributeTranslations.read;
 if (t.values[name]) return t.values[name](element, name);
 if (t.names[name]) name = t.names[name];
 if (name.include(':')) {
 return (!element.attributes || !element.attributes[name]) ? null :
 element.attributes[name].value;
 }
 }
 return element.getAttribute(name);
 },

 writeAttribute: function(element, name, value) {
 element = $(element);
 var attributes = { }, t = Element._attributeTranslations.write;

 if (typeof name == 'object') attributes = name;
 else attributes[name] = Object.isUndefined(value) ? true : value;

 for (var attr in attributes) {
 name = t.names[attr] || attr;
 value = attributes[attr];
 if (t.values[attr]) name = t.values[attr](element, value);
 if (value === false || value === null)
 element.removeAttribute(name);
 else if (value === true)
 element.setAttribute(name, name);
 else element.setAttribute(name, value);
 }
 return element;
 },

 getHeight: function(element) {
 return $(element).getDimensions().height;
 },

 getWidth: function(element) {
 return $(element).getDimensions().width;
 },

 classNames: function(element) {
 return new Element.ClassNames(element);
 },

 hasClassName: function(element, className) {
 if (!(element = $(element))) return;
 var elementClassName = element.className;
 return (elementClassName.length > 0 && (elementClassName == className ||
 new RegExp("(^|\\s)" + className + "(\\s|$)").test(elementClassName)));
 },

 addClassName: function(element, className) {
 if (!(element = $(element))) return;
 if (!element.hasClassName(className))
 element.className += (element.className ? ' ' : '') + className;
 return element;
 },

 removeClassName: function(element, className) {
 if (!(element = $(element))) return;
 element.className = element.className.replace(
 new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
 return element;
 },

 toggleClassName: function(element, className) {
 if (!(element = $(element))) return;
 return element[element.hasClassName(className) ?
 'removeClassName' : 'addClassName'](className);
 },

 // removes whitespace-only text node children
 cleanWhitespace: function(element) {
 element = $(element);
 var node = element.firstChild;
 while (node) {
 var nextNode = node.nextSibling;
 if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
 element.removeChild(node);
 node = nextNode;
 }
 return element;
 },

 empty: function(element) {
 return $(element).innerHTML.blank();
 },

 descendantOf: function(element, ancestor) {
 element = $(element), ancestor = $(ancestor);

 if (element.compareDocumentPosition)
 return (element.compareDocumentPosition(ancestor) & 8) === 8;

 if (ancestor.contains)
 return ancestor.contains(element) && ancestor !== element;

 while (element = element.parentNode)
 if (element == ancestor) return true;

 return false;
 },

 scrollTo: function(element) {
 element = $(element);
 var pos = element.cumulativeOffset();
 window.scrollTo(pos[0], pos[1]);
 return element;
 },

 getStyle: function(element, style) {
 element = $(element);
 style = style == 'float' ? 'cssFloat' : style.camelize();
 var value = element.style[style];
 if (!value || value == 'auto') {
 var css = document.defaultView.getComputedStyle(element, null);
 value = css ? css[style] : null;
 }
 if (style == 'opacity') return value ? parseFloat(value) : 1.0;
 return value == 'auto' ? null : value;
 },

 getOpacity: function(element) {
 return $(element).getStyle('opacity');
 },

 setStyle: function(element, styles) {
 element = $(element);
 var elementStyle = element.style, match;
 if (Object.isString(styles)) {
 element.style.cssText += ';' + styles;
 return styles.include('opacity') ?
 element.setOpacity(styles.match(/opacity:\s*(\d?\.?\d*)/)[1]) : element;
 }
 for (var property in styles)
 if (property == 'opacity') element.setOpacity(styles[property]);
 else
 elementStyle[(property == 'float' || property == 'cssFloat') ?
 (Object.isUndefined(elementStyle.styleFloat) ? 'cssFloat' : 'styleFloat') :
 property] = styles[property];

 return element;
 },

 setOpacity: function(element, value) {
 element = $(element);
 element.style.opacity = (value == 1 || value === '') ? '' :
 (value < 0.00001) ? 0 : value;
 return element;
 },

 getDimensions: function(element) {
 element = $(element);
 var display = element.getStyle('display');
 if (display != 'none' && display != null) // Safari bug
 return {width: element.offsetWidth, height: element.offsetHeight};

 // All *Width and *Height properties give 0 on elements with display none,
 // so enable the element temporarily
 var els = element.style;
 var originalVisibility = els.visibility;
 var originalPosition = els.position;
 var originalDisplay = els.display;
 els.visibility = 'hidden';
 els.position = 'absolute';
 els.display = 'block';
 var originalWidth = element.clientWidth;
 var originalHeight = element.clientHeight;
 els.display = originalDisplay;
 els.position = originalPosition;
 els.visibility = originalVisibility;
 return {width: originalWidth, height: originalHeight};
 },

 makePositioned: function(element) {
 element = $(element);
 var pos = Element.getStyle(element, 'position');
 if (pos == 'static' || !pos) {
 element._madePositioned = true;
 element.style.position = 'relative';
 // Opera returns the offset relative to the positioning context, when an
 // element is position relative but top and left have not been defined
 if (Prototype.Browser.Opera) {
 element.style.top = 0;
 element.style.left = 0;
 }
 }
 return element;
 },

 undoPositioned: function(element) {
 element = $(element);
 if (element._madePositioned) {
 element._madePositioned = undefined;
 element.style.position =
 element.style.top =
 element.style.left =
 element.style.bottom =
 element.style.right = '';
 }
 return element;
 },

 makeClipping: function(element) {
 element = $(element);
 if (element._overflow) return element;
 element._overflow = Element.getStyle(element, 'overflow') || 'auto';
 if (element._overflow !== 'hidden')
 element.style.overflow = 'hidden';
 return element;
 },

 undoClipping: function(element) {
 element = $(element);
 if (!element._overflow) return element;
 element.style.overflow = element._overflow == 'auto' ? '' : element._overflow;
 element._overflow = null;
 return element;
 },

 cumulativeOffset: function(element) {
 var valueT = 0, valueL = 0;
 do {
 valueT += element.offsetTop || 0;
 valueL += element.offsetLeft || 0;
 element = element.offsetParent;
 } while (element);
 return Element._returnOffset(valueL, valueT);
 },

 positionedOffset: function(element) {
 var valueT = 0, valueL = 0;
 do {
 valueT += element.offsetTop || 0;
 valueL += element.offsetLeft || 0;
 element = element.offsetParent;
 if (element) {
 if (element.tagName.toUpperCase() == 'BODY') break;
 var p = Element.getStyle(element, 'position');
 if (p !== 'static') break;
 }
 } while (element);
 return Element._returnOffset(valueL, valueT);
 },

 absolutize: function(element) {
 element = $(element);
 if (element.getStyle('position') == 'absolute') return element;
 // Position.prepare(); // To be done manually by Scripty when it needs it.

 var offsets = element.positionedOffset();
 var top = offsets[1];
 var left = offsets[0];
 var width = element.clientWidth;
 var height = element.clientHeight;

 element._originalLeft = left - parseFloat(element.style.left || 0);
 element._originalTop = top - parseFloat(element.style.top || 0);
 element._originalWidth = element.style.width;
 element._originalHeight = element.style.height;

 element.style.position = 'absolute';
 element.style.top = top + 'px';
 element.style.left = left + 'px';
 element.style.width = width + 'px';
 element.style.height = height + 'px';
 return element;
 },

 relativize: function(element) {
 element = $(element);
 if (element.getStyle('position') == 'relative') return element;
 // Position.prepare(); // To be done manually by Scripty when it needs it.

 element.style.position = 'relative';
 var top = parseFloat(element.style.top || 0) - (element._originalTop || 0);
 var left = parseFloat(element.style.left || 0) - (element._originalLeft || 0);

 element.style.top = top + 'px';
 element.style.left = left + 'px';
 element.style.height = element._originalHeight;
 element.style.width = element._originalWidth;
 return element;
 },

 cumulativeScrollOffset: function(element) {
 var valueT = 0, valueL = 0;
 do {
 valueT += element.scrollTop || 0;
 valueL += element.scrollLeft || 0;
 element = element.parentNode;
 } while (element);
 return Element._returnOffset(valueL, valueT);
 },

 getOffsetParent: function(element) {
 if (element.offsetParent) return $(element.offsetParent);
 if (element == document.body) return $(element);

 while ((element = element.parentNode) && element != document.body)
 if (Element.getStyle(element, 'position') != 'static')
 return $(element);

 return $(document.body);
 },

 viewportOffset: function(forElement) {
 var valueT = 0, valueL = 0;

 var element = forElement;
 do {
 valueT += element.offsetTop || 0;
 valueL += element.offsetLeft || 0;

 // Safari fix
 if (element.offsetParent == document.body &&
 Element.getStyle(element, 'position') == 'absolute') break;

 } while (element = element.offsetParent);

 element = forElement;
 do {
 if (!Prototype.Browser.Opera || (element.tagName && (element.tagName.toUpperCase() == 'BODY'))) {
 valueT -= element.scrollTop || 0;
 valueL -= element.scrollLeft || 0;
 }
 } while (element = element.parentNode);

 return Element._returnOffset(valueL, valueT);
 },

 clonePosition: function(element, source) {
 var options = Object.extend({
 setLeft: true,
 setTop: true,
 setWidth: true,
 setHeight: true,
 offsetTop: 0,
 offsetLeft: 0
 }, arguments[2] || { });

 // find page position of source
 source = $(source);
 var p = source.viewportOffset();

 // find coordinate system to use
 element = $(element);
 var delta = [0, 0];
 var parent = null;
 // delta [0,0] will do fine with position: fixed elements,
 // position:absolute needs offsetParent deltas
 if (Element.getStyle(element, 'position') == 'absolute') {
 parent = element.getOffsetParent();
 delta = parent.viewportOffset();
 }

 // correct by body offsets (fixes Safari)
 if (parent == document.body) {
 delta[0] -= document.body.offsetLeft;
 delta[1] -= document.body.offsetTop;
 }

 // set position
 if (options.setLeft) element.style.left = (p[0] - delta[0] + options.offsetLeft) + 'px';
 if (options.setTop) element.style.top = (p[1] - delta[1] + options.offsetTop) + 'px';
 if (options.setWidth) element.style.width = source.offsetWidth + 'px';
 if (options.setHeight) element.style.height = source.offsetHeight + 'px';
 return element;
 }
};

Element.Methods.identify.counter = 1;

Object.extend(Element.Methods, {
 getElementsBySelector: Element.Methods.select,
 childElements: Element.Methods.immediateDescendants
});

Element._attributeTranslations = {
 write: {
 names: {
 className: 'class',
 htmlFor: 'for'
 },
 values: { }
 }
};

if (Prototype.Browser.Opera) {
 Element.Methods.getStyle = Element.Methods.getStyle.wrap(
 function(proceed, element, style) {
 switch (style) {
 case 'left': case 'top': case 'right': case 'bottom':
 if (proceed(element, 'position') === 'static') return null;
 case 'height': case 'width':
 // returns '0px' for hidden elements; we want it to return null
 if (!Element.visible(element)) return null;

 // returns the border-box dimensions rather than the content-box
 // dimensions, so we subtract padding and borders from the value
 var dim = parseInt(proceed(element, style), 10);

 if (dim !== element['offset' + style.capitalize()])
 return dim + 'px';

 var properties;
 if (style === 'height') {
 properties = ['border-top-width', 'padding-top',
 'padding-bottom', 'border-bottom-width'];
 }
 else {
 properties = ['border-left-width', 'padding-left',
 'padding-right', 'border-right-width'];
 }
 return properties.inject(dim, function(memo, property) {
 var val = proceed(element, property);
 return val === null ? memo : memo - parseInt(val, 10);
 }) + 'px';
 default: return proceed(element, style);
 }
 }
 );

 Element.Methods.readAttribute = Element.Methods.readAttribute.wrap(
 function(proceed, element, attribute) {
 if (attribute === 'title') return element.title;
 return proceed(element, attribute);
 }
 );
}

else if (Prototype.Browser.IE) {
 // IE doesn't report offsets correctly for static elements, so we change them
 // to "relative" to get the values, then change them back.
 Element.Methods.getOffsetParent = Element.Methods.getOffsetParent.wrap(
 function(proceed, element) {
 element = $(element);
 // IE throws an error if element is not in document
 try { element.offsetParent }
 catch(e) { return $(document.body) }
 var position = element.getStyle('position');
 if (position !== 'static') return proceed(element);
 element.setStyle({ position: 'relative' });
 var value = proceed(element);
 element.setStyle({ position: position });
 return value;
 }
 );

 $w('positionedOffset viewportOffset').each(function(method) {
 Element.Methods[method] = Element.Methods[method].wrap(
 function(proceed, element) {
 element = $(element);
 try { element.offsetParent }
 catch(e) { return Element._returnOffset(0,0) }
 var position = element.getStyle('position');
 if (position !== 'static') return proceed(element);
 // Trigger hasLayout on the offset parent so that IE6 reports
 // accurate offsetTop and offsetLeft values for position: fixed.
 var offsetParent = element.getOffsetParent();
 if (offsetParent && offsetParent.getStyle('position') === 'fixed')
 offsetParent.setStyle({ zoom: 1 });
 element.setStyle({ position: 'relative' });
 var value = proceed(element);
 element.setStyle({ position: position });
 return value;
 }
 );
 });

 Element.Methods.cumulativeOffset = Element.Methods.cumulativeOffset.wrap(
 function(proceed, element) {
 try { element.offsetParent }
 catch(e) { return Element._returnOffset(0,0) }
 return proceed(element);
 }
 );

 Element.Methods.getStyle = function(element, style) {
 element = $(element);
 style = (style == 'float' || style == 'cssFloat') ? 'styleFloat' : style.camelize();
 var value = element.style[style];
 if (!value && element.currentStyle) value = element.currentStyle[style];

 if (style == 'opacity') {
 if (value = (element.getStyle('filter') || '').match(/alpha\(opacity=(.*)\)/))
 if (value[1]) return parseFloat(value[1]) / 100;
 return 1.0;
 }

 if (value == 'auto') {
 if ((style == 'width' || style == 'height') && (element.getStyle('display') != 'none'))
 return element['offset' + style.capitalize()] + 'px';
 return null;
 }
 return value;
 };

 Element.Methods.setOpacity = function(element, value) {
 function stripAlpha(filter){
 return filter.replace(/alpha\([^\)]*\)/gi,'');
 }
 element = $(element);
 var currentStyle = element.currentStyle;
 if ((currentStyle && !currentStyle.hasLayout) ||
 (!currentStyle && element.style.zoom == 'normal'))
 element.style.zoom = 1;

 var filter = element.getStyle('filter'), style = element.style;
 if (value == 1 || value === '') {
 (filter = stripAlpha(filter)) ?
 style.filter = filter : style.removeAttribute('filter');
 return element;
 } else if (value < 0.00001) value = 0;
 style.filter = stripAlpha(filter) +
 'alpha(opacity=' + (value * 100) + ')';
 return element;
 };

 Element._attributeTranslations = {
 read: {
 names: {
 'class': 'className',
 'for': 'htmlFor'
 },
 values: {
 _getAttr: function(element, attribute) {
 return element.getAttribute(attribute, 2);
 },
 _getAttrNode: function(element, attribute) {
 var node = element.getAttributeNode(attribute);
 return node ? node.value : "";
 },
 _getEv: function(element, attribute) {
 attribute = element.getAttribute(attribute);
 return attribute ? attribute.toString().slice(23, -2) : null;
 },
 _flag: function(element, attribute) {
 return $(element).hasAttribute(attribute) ? attribute : null;
 },
 style: function(element) {
 return element.style.cssText.toLowerCase();
 },
 title: function(element) {
 return element.title;
 }
 }
 }
 };

 Element._attributeTranslations.write = {
 names: Object.extend({
 cellpadding: 'cellPadding',
 cellspacing: 'cellSpacing'
 }, Element._attributeTranslations.read.names),
 values: {
 checked: function(element, value) {
 element.checked = !!value;
 },

 style: function(element, value) {
 element.style.cssText = value ? value : '';
 }
 }
 };

 Element._attributeTranslations.has = {};

 $w('colSpan rowSpan vAlign dateTime accessKey tabIndex ' +
 'encType maxLength readOnly longDesc frameBorder').each(function(attr) {
 Element._attributeTranslations.write.names[attr.toLowerCase()] = attr;
 Element._attributeTranslations.has[attr.toLowerCase()] = attr;
 });

 (function(v) {
 Object.extend(v, {
 href: v._getAttr,
 src: v._getAttr,
 type: v._getAttr,
 action: v._getAttrNode,
 disabled: v._flag,
 checked: v._flag,
 readonly: v._flag,
 multiple: v._flag,
 onload: v._getEv,
 onunload: v._getEv,
 onclick: v._getEv,
 ondblclick: v._getEv,
 onmousedown: v._getEv,
 onmouseup: v._getEv,
 onmouseover: v._getEv,
 onmousemove: v._getEv,
 onmouseout: v._getEv,
 onfocus: v._getEv,
 onblur: v._getEv,
 onkeypress: v._getEv,
 onkeydown: v._getEv,
 onkeyup: v._getEv,
 onsubmit: v._getEv,
 onreset: v._getEv,
 onselect: v._getEv,
 onchange: v._getEv
 });
 })(Element._attributeTranslations.read.values);
}

else if (Prototype.Browser.Gecko && /rv:1\.8\.0/.test(navigator.userAgent)) {
 Element.Methods.setOpacity = function(element, value) {
 element = $(element);
 element.style.opacity = (value == 1) ? 0.999999 :
 (value === '') ? '' : (value < 0.00001) ? 0 : value;
 return element;
 };
}

else if (Prototype.Browser.WebKit) {
 Element.Methods.setOpacity = function(element, value) {
 element = $(element);
 element.style.opacity = (value == 1 || value === '') ? '' :
 (value < 0.00001) ? 0 : value;

 if (value == 1)
 if(element.tagName.toUpperCase() == 'IMG' && element.width) {
 element.width++; element.width--;
 } else try {
 var n = document.createTextNode(' ');
 element.appendChild(n);
 element.removeChild(n);
 } catch (e) { }

 return element;
 };

 // Safari returns margins on body which is incorrect if the child is absolutely
 // positioned. For performance reasons, redefine Element#cumulativeOffset for
 // KHTML/WebKit only.
 Element.Methods.cumulativeOffset = function(element) {
 var valueT = 0, valueL = 0;
 do {
 valueT += element.offsetTop || 0;
 valueL += element.offsetLeft || 0;
 if (element.offsetParent == document.body)
 if (Element.getStyle(element, 'position') == 'absolute') break;

 element = element.offsetParent;
 } while (element);

 return Element._returnOffset(valueL, valueT);
 };
}

if (Prototype.Browser.IE || Prototype.Browser.Opera) {
 // IE and Opera are missing .innerHTML support for TABLE-related and SELECT elements
 Element.Methods.update = function(element, content) {
 element = $(element);

 if (content && content.toElement) content = content.toElement();
 if (Object.isElement(content)) return element.update().insert(content);

 content = Object.toHTML(content);
 var tagName = element.tagName.toUpperCase();

 if (tagName in Element._insertionTranslations.tags) {
 $A(element.childNodes).each(function(node) { element.removeChild(node) });
 Element._getContentFromAnonymousElement(tagName, content.stripScripts())
 .each(function(node) { element.appendChild(node) });
 }
 else element.innerHTML = content.stripScripts();

 content.evalScripts.bind(content).defer();
 return element;
 };
}

if ('outerHTML' in document.createElement('div')) {
 Element.Methods.replace = function(element, content) {
 element = $(element);

 if (content && content.toElement) content = content.toElement();
 if (Object.isElement(content)) {
 element.parentNode.replaceChild(content, element);
 return element;
 }

 content = Object.toHTML(content);
 var parent = element.parentNode, tagName = parent.tagName.toUpperCase();

 if (Element._insertionTranslations.tags[tagName]) {
 var nextSibling = element.next();
 var fragments = Element._getContentFromAnonymousElement(tagName, content.stripScripts());
 parent.removeChild(element);
 if (nextSibling)
 fragments.each(function(node) { parent.insertBefore(node, nextSibling) });
 else
 fragments.each(function(node) { parent.appendChild(node) });
 }
 else element.outerHTML = content.stripScripts();

 content.evalScripts.bind(content).defer();
 return element;
 };
}

Element._returnOffset = function(l, t) {
 var result = [l, t];
 result.left = l;
 result.top = t;
 return result;
};

Element._getContentFromAnonymousElement = function(tagName, html) {
 var div = new Element('div'), t = Element._insertionTranslations.tags[tagName];
 if (t) {
 div.innerHTML = t[0] + html + t[1];
 t[2].times(function() { div = div.firstChild });
 } else div.innerHTML = html;
 return $A(div.childNodes);
};

Element._insertionTranslations = {
 before: function(element, node) {
 element.parentNode.insertBefore(node, element);
 },
 top: function(element, node) {
 element.insertBefore(node, element.firstChild);
 },
 bottom: function(element, node) {
 element.appendChild(node);
 },
 after: function(element, node) {
 element.parentNode.insertBefore(node, element.nextSibling);
 },
 tags: {
 TABLE: ['<table>', '</table>', 1],
 TBODY: ['<table><tbody>', '</tbody></table>', 2],
 TR: ['<table><tbody><tr>', '</tr></tbody></table>', 3],
 TD: ['<table><tbody><tr><td>', '</td></tr></tbody></table>', 4],
 SELECT: ['<select>', '</select>', 1]
 }
};

(function() {
 Object.extend(this.tags, {
 THEAD: this.tags.TBODY,
 TFOOT: this.tags.TBODY,
 TH: this.tags.TD
 });
}).call(Element._insertionTranslations);

Element.Methods.Simulated = {
 hasAttribute: function(element, attribute) {
 attribute = Element._attributeTranslations.has[attribute] || attribute;
 var node = $(element).getAttributeNode(attribute);
 return !!(node && node.specified);
 }
};

Element.Methods.ByTag = { };

Object.extend(Element, Element.Methods);

if (!Prototype.BrowserFeatures.ElementExtensions &&
 document.createElement('div')['__proto__']) {
 window.HTMLElement = { };
 window.HTMLElement.prototype = document.createElement('div')['__proto__'];
 Prototype.BrowserFeatures.ElementExtensions = true;
}

Element.extend = (function() {
 if (Prototype.BrowserFeatures.SpecificElementExtensions)
 return Prototype.K;

 var Methods = { }, ByTag = Element.Methods.ByTag;

 var extend = Object.extend(function(element) {
 if (!element || element._extendedByPrototype ||
 element.nodeType != 1 || element == window) return element;

 var methods = Object.clone(Methods),
 tagName = element.tagName.toUpperCase(), property, value;

 // extend methods for specific tags
 if (ByTag[tagName]) Object.extend(methods, ByTag[tagName]);

 for (property in methods) {
 value = methods[property];
 if (Object.isFunction(value) && !(property in element))
 element[property] = value.methodize();
 }

 element._extendedByPrototype = Prototype.emptyFunction;
 return element;

 }, {
 refresh: function() {
 // extend methods for all tags (Safari doesn't need this)
 if (!Prototype.BrowserFeatures.ElementExtensions) {
 Object.extend(Methods, Element.Methods);
 Object.extend(Methods, Element.Methods.Simulated);
 }
 }
 });

 extend.refresh();
 return extend;
})();

Element.hasAttribute = function(element, attribute) {
 if (element.hasAttribute) return element.hasAttribute(attribute);
 return Element.Methods.Simulated.hasAttribute(element, attribute);
};

Element.addMethods = function(methods) {
 var F = Prototype.BrowserFeatures, T = Element.Methods.ByTag;

 if (!methods) {
 Object.extend(Form, Form.Methods);
 Object.extend(Form.Element, Form.Element.Methods);
 Object.extend(Element.Methods.ByTag, {
 "FORM": Object.clone(Form.Methods),
 "INPUT": Object.clone(Form.Element.Methods),
 "SELECT": Object.clone(Form.Element.Methods),
 "TEXTAREA": Object.clone(Form.Element.Methods)
 });
 }

 if (arguments.length == 2) {
 var tagName = methods;
 methods = arguments[1];
 }

 if (!tagName) Object.extend(Element.Methods, methods || { });
 else {
 if (Object.isArray(tagName)) tagName.each(extend);
 else extend(tagName);
 }

 function extend(tagName) {
 tagName = tagName.toUpperCase();
 if (!Element.Methods.ByTag[tagName])
 Element.Methods.ByTag[tagName] = { };
 Object.extend(Element.Methods.ByTag[tagName], methods);
 }

 function copy(methods, destination, onlyIfAbsent) {
 onlyIfAbsent = onlyIfAbsent || false;
 for (var property in methods) {
 var value = methods[property];
 if (!Object.isFunction(value)) continue;
 if (!onlyIfAbsent || !(property in destination))
 destination[property] = value.methodize();
 }
 }

 function findDOMClass(tagName) {
 var klass;
 var trans = {
 "OPTGROUP": "OptGroup", "TEXTAREA": "TextArea", "P": "Paragraph",
 "FIELDSET": "FieldSet", "UL": "UList", "OL": "OList", "DL": "DList",
 "DIR": "Directory", "H1": "Heading", "H2": "Heading", "H3": "Heading",
 "H4": "Heading", "H5": "Heading", "H6": "Heading", "Q": "Quote",
 "INS": "Mod", "DEL": "Mod", "A": "Anchor", "IMG": "Image", "CAPTION":
 "TableCaption", "COL": "TableCol", "COLGROUP": "TableCol", "THEAD":
 "TableSection", "TFOOT": "TableSection", "TBODY": "TableSection", "TR":
 "TableRow", "TH": "TableCell", "TD": "TableCell", "FRAMESET":
 "FrameSet", "IFRAME": "IFrame"
 };
 if (trans[tagName]) klass = 'HTML' + trans[tagName] + 'Element';
 if (window[klass]) return window[klass];
 klass = 'HTML' + tagName + 'Element';
 if (window[klass]) return window[klass];
 klass = 'HTML' + tagName.capitalize() + 'Element';
 if (window[klass]) return window[klass];

 window[klass] = { };
 window[klass].prototype = document.createElement(tagName)['__proto__'];
 return window[klass];
 }

 if (F.ElementExtensions) {
 copy(Element.Methods, HTMLElement.prototype);
 copy(Element.Methods.Simulated, HTMLElement.prototype, true);
 }

 if (F.SpecificElementExtensions) {
 for (var tag in Element.Methods.ByTag) {
 var klass = findDOMClass(tag);
 if (Object.isUndefined(klass)) continue;
 copy(T[tag], klass.prototype);
 }
 }

 Object.extend(Element, Element.Methods);
 delete Element.ByTag;

 if (Element.extend.refresh) Element.extend.refresh();
 Element.cache = { };
};

document.viewport = {
 getDimensions: function() {
 var dimensions = { }, B = Prototype.Browser;
 $w('width height').each(function(d) {
 var D = d.capitalize();
 if (B.WebKit && !document.evaluate) {
 // Safari <3.0 needs self.innerWidth/Height
 dimensions[d] = self['inner' + D];
 } else if (B.Opera && parseFloat(window.opera.version()) < 9.5) {
 // Opera <9.5 needs document.body.clientWidth/Height
 dimensions[d] = document.body['client' + D]
 } else {
 dimensions[d] = document.documentElement['client' + D];
 }
 });
 return dimensions;
 },

 getWidth: function() {
 return this.getDimensions().width;
 },

 getHeight: function() {
 return this.getDimensions().height;
 },

 getScrollOffsets: function() {
 return Element._returnOffset(
 window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
 window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop);
 }
};
/* Portions of the Selector class are derived from Jack Slocum's DomQuery,
 * part of YUI-Ext version 0.40, distributed under the terms of an MIT-style
 * license. Please see http://www.yui-ext.com/ for more information. */

var Selector = Class.create({
 initialize: function(expression) {
 this.expression = expression.strip();

 if (this.shouldUseSelectorsAPI()) {
 this.mode = 'selectorsAPI';
 } else if (this.shouldUseXPath()) {
 this.mode = 'xpath';
 this.compileXPathMatcher();
 } else {
 this.mode = "normal";
 this.compileMatcher();
 }

 },

 shouldUseXPath: function() {
 if (!Prototype.BrowserFeatures.XPath) return false;

 var e = this.expression;

 // Safari 3 chokes on :*-of-type and :empty
 if (Prototype.Browser.WebKit &&
 (e.include("-of-type") || e.include(":empty")))
 return false;

 // XPath can't do namespaced attributes, nor can it read
 // the "checked" property from DOM nodes
 if ((/(\[[\w-]*?:|:checked)/).test(e))
 return false;

 return true;
 },

 shouldUseSelectorsAPI: function() {
 if (!Prototype.BrowserFeatures.SelectorsAPI) return false;

 if (!Selector._div) Selector._div = new Element('div');

 // Make sure the browser treats the selector as valid. Test on an
 // isolated element to minimize cost of this check.
 try {
 Selector._div.querySelector(this.expression);
 } catch(e) {
 return false;
 }

 return true;
 },

 compileMatcher: function() {
 var e = this.expression, ps = Selector.patterns, h = Selector.handlers,
 c = Selector.criteria, le, p, m;

 if (Selector._cache[e]) {
 this.matcher = Selector._cache[e];
 return;
 }

 this.matcher = ["this.matcher = function(root) {",
 "var r = root, h = Selector.handlers, c = false, n;"];

 while (e && le != e && (/\S/).test(e)) {
 le = e;
 for (var i in ps) {
 p = ps[i];
 if (m = e.match(p)) {
 this.matcher.push(Object.isFunction(c[i]) ? c[i](m) :
 new Template(c[i]).evaluate(m));
 e = e.replace(m[0], '');
 break;
 }
 }
 }

 this.matcher.push("return h.unique(n);\n}");
 eval(this.matcher.join('\n'));
 Selector._cache[this.expression] = this.matcher;
 },

 compileXPathMatcher: function() {
 var e = this.expression, ps = Selector.patterns,
 x = Selector.xpath, le, m;

 if (Selector._cache[e]) {
 this.xpath = Selector._cache[e]; return;
 }

 this.matcher = ['.//*'];
 while (e && le != e && (/\S/).test(e)) {
 le = e;
 for (var i in ps) {
 if (m = e.match(ps[i])) {
 this.matcher.push(Object.isFunction(x[i]) ? x[i](m) :
 new Template(x[i]).evaluate(m));
 e = e.replace(m[0], '');
 break;
 }
 }
 }

 this.xpath = this.matcher.join('');
 Selector._cache[this.expression] = this.xpath;
 },

 findElements: function(root) {
 root = root || document;
 var e = this.expression, results;

 switch (this.mode) {
 case 'selectorsAPI':
 // querySelectorAll queries document-wide, then filters to descendants
 // of the context element. That's not what we want.
 // Add an explicit context to the selector if necessary.
 if (root !== document) {
 var oldId = root.id, id = $(root).identify();
 e = "#" + id + " " + e;
 }

 results = $A(root.querySelectorAll(e)).map(Element.extend);
 root.id = oldId;

 return results;
 case 'xpath':
 return document._getElementsByXPath(this.xpath, root);
 default:
 return this.matcher(root);
 }
 },

 match: function(element) {
 this.tokens = [];

 var e = this.expression, ps = Selector.patterns, as = Selector.assertions;
 var le, p, m;

 while (e && le !== e && (/\S/).test(e)) {
 le = e;
 for (var i in ps) {
 p = ps[i];
 if (m = e.match(p)) {
 // use the Selector.assertions methods unless the selector
 // is too complex.
 if (as[i]) {
 this.tokens.push([i, Object.clone(m)]);
 e = e.replace(m[0], '');
 } else {
 // reluctantly do a document-wide search
 // and look for a match in the array
 return this.findElements(document).include(element);
 }
 }
 }
 }

 var match = true, name, matches;
 for (var i = 0, token; token = this.tokens[i]; i++) {
 name = token[0], matches = token[1];
 if (!Selector.assertions[name](element, matches)) {
 match = false; break;
 }
 }

 return match;
 },

 toString: function() {
 return this.expression;
 },

 inspect: function() {
 return "#<Selector:" + this.expression.inspect() + ">";
 }
});

Object.extend(Selector, {
 _cache: { },

 xpath: {
 descendant: "//*",
 child: "/*",
 adjacent: "/following-sibling::*[1]",
 laterSibling: '/following-sibling::*',
 tagName: function(m) {
 if (m[1] == '*') return '';
 return "[local-name()='" + m[1].toLowerCase() +
 "' or local-name()='" + m[1].toUpperCase() + "']";
 },
 className: "[contains(concat(' ', @class, ' '), ' #{1} ')]",
 id: "[@id='#{1}']",
 attrPresence: function(m) {
 m[1] = m[1].toLowerCase();
 return new Template("[@#{1}]").evaluate(m);
 },
 attr: function(m) {
 m[1] = m[1].toLowerCase();
 m[3] = m[5] || m[6];
 return new Template(Selector.xpath.operators[m[2]]).evaluate(m);
 },
 pseudo: function(m) {
 var h = Selector.xpath.pseudos[m[1]];
 if (!h) return '';
 if (Object.isFunction(h)) return h(m);
 return new Template(Selector.xpath.pseudos[m[1]]).evaluate(m);
 },
 operators: {
 '=': "[@#{1}='#{3}']",
 '!=': "[@#{1}!='#{3}']",
 '^=': "[starts-with(@#{1}, '#{3}')]",
 '$=': "[substring(@#{1}, (string-length(@#{1}) - string-length('#{3}') + 1))='#{3}']",
 '*=': "[contains(@#{1}, '#{3}')]",
 '~=': "[contains(concat(' ', @#{1}, ' '), ' #{3} ')]",
 '|=': "[contains(concat('-', @#{1}, '-'), '-#{3}-')]"
 },
 pseudos: {
 'first-child': '[not(preceding-sibling::*)]',
 'last-child': '[not(following-sibling::*)]',
 'only-child': '[not(preceding-sibling::* or following-sibling::*)]',
 'empty': "[count(*) = 0 and (count(text()) = 0)]",
 'checked': "[@checked]",
 'disabled': "[(@disabled) and (@type!='hidden')]",
 'enabled': "[not(@disabled) and (@type!='hidden')]",
 'not': function(m) {
 var e = m[6], p = Selector.patterns,
 x = Selector.xpath, le, v;

 var exclusion = [];
 while (e && le != e && (/\S/).test(e)) {
 le = e;
 for (var i in p) {
 if (m = e.match(p[i])) {
 v = Object.isFunction(x[i]) ? x[i](m) : new Template(x[i]).evaluate(m);
 exclusion.push("(" + v.substring(1, v.length - 1) + ")");
 e = e.replace(m[0], '');
 break;
 }
 }
 }
 return "[not(" + exclusion.join(" and ") + ")]";
 },
 'nth-child': function(m) {
 return Selector.xpath.pseudos.nth("(count(./preceding-sibling::*) + 1) ", m);
 },
 'nth-last-child': function(m) {
 return Selector.xpath.pseudos.nth("(count(./following-sibling::*) + 1) ", m);
 },
 'nth-of-type': function(m) {
 return Selector.xpath.pseudos.nth("position() ", m);
 },
 'nth-last-of-type': function(m) {
 return Selector.xpath.pseudos.nth("(last() + 1 - position()) ", m);
 },
 'first-of-type': function(m) {
 m[6] = "1"; return Selector.xpath.pseudos['nth-of-type'](m);
 },
 'last-of-type': function(m) {
 m[6] = "1"; return Selector.xpath.pseudos['nth-last-of-type'](m);
 },
 'only-of-type': function(m) {
 var p = Selector.xpath.pseudos; return p['first-of-type'](m) + p['last-of-type'](m);
 },
 nth: function(fragment, m) {
 var mm, formula = m[6], predicate;
 if (formula == 'even') formula = '2n+0';
 if (formula == 'odd') formula = '2n+1';
 if (mm = formula.match(/^(\d+)$/)) // digit only
 return '[' + fragment + "= " + mm[1] + ']';
 if (mm = formula.match(/^(-?\d*)?n(([+-])(\d+))?/)) { // an+b
 if (mm[1] == "-") mm[1] = -1;
 var a = mm[1] ? Number(mm[1]) : 1;
 var b = mm[2] ? Number(mm[2]) : 0;
 predicate = "[((#{fragment} - #{b}) mod #{a} = 0) and " +
 "((#{fragment} - #{b}) div #{a} >= 0)]";
 return new Template(predicate).evaluate({
 fragment: fragment, a: a, b: b });
 }
 }
 }
 },

 criteria: {
 tagName: 'n = h.tagName(n, r, "#{1}", c); c = false;',
 className: 'n = h.className(n, r, "#{1}", c); c = false;',
 id: 'n = h.id(n, r, "#{1}", c); c = false;',
 attrPresence: 'n = h.attrPresence(n, r, "#{1}", c); c = false;',
 attr: function(m) {
 m[3] = (m[5] || m[6]);
 return new Template('n = h.attr(n, r, "#{1}", "#{3}", "#{2}", c); c = false;').evaluate(m);
 },
 pseudo: function(m) {
 if (m[6]) m[6] = m[6].replace(/"/g, '\\"');
 return new Template('n = h.pseudo(n, "#{1}", "#{6}", r, c); c = false;').evaluate(m);
 },
 descendant: 'c = "descendant";',
 child: 'c = "child";',
 adjacent: 'c = "adjacent";',
 laterSibling: 'c = "laterSibling";'
 },

 patterns: {
 // combinators must be listed first
 // (and descendant needs to be last combinator)
 laterSibling: /^\s*~\s*/,
 child: /^\s*>\s*/,
 adjacent: /^\s*\+\s*/,
 descendant: /^\s/,

 // selectors follow
 tagName: /^\s*(\*|[\w\-]+)(\b|$)?/,
 id: /^#([\w\-\*]+)(\b|$)/,
 className: /^\.([\w\-\*]+)(\b|$)/,
 pseudo:
/^:((first|last|nth|nth-last|only)(-child|-of-type)|empty|checked|(en|dis)abled|not)(\((.*?)\))?(\b|$|(?=\s|[:+~>]))/,
 attrPresence: /^\[((?:[\w]+:)?[\w]+)\]/,
 attr: /\[((?:[\w-]*:)?[\w-]+)\s*(?:([!^$*~|]?=)\s*((['"])([^\4]*?)\4|([^'"][^\]]*?)))?\]/
 },

 // for Selector.match and Element#match
 assertions: {
 tagName: function(element, matches) {
 return matches[1].toUpperCase() == element.tagName.toUpperCase();
 },

 className: function(element, matches) {
 return Element.hasClassName(element, matches[1]);
 },

 id: function(element, matches) {
 return element.id === matches[1];
 },

 attrPresence: function(element, matches) {
 return Element.hasAttribute(element, matches[1]);
 },

 attr: function(element, matches) {
 var nodeValue = Element.readAttribute(element, matches[1]);
 return nodeValue && Selector.operators[matches[2]](nodeValue, matches[5] || matches[6]);
 }
 },

 handlers: {
 // UTILITY FUNCTIONS
 // joins two collections
 concat: function(a, b) {
 for (var i = 0, node; node = b[i]; i++)
 a.push(node);
 return a;
 },

 // marks an array of nodes for counting
 mark: function(nodes) {
 var _true = Prototype.emptyFunction;
 for (var i = 0, node; node = nodes[i]; i++)
 node._countedByPrototype = _true;
 return nodes;
 },

 unmark: function(nodes) {
 for (var i = 0, node; node = nodes[i]; i++)
 node._countedByPrototype = undefined;
 return nodes;
 },

 // mark each child node with its position (for nth calls)
 // "ofType" flag indicates whether we're indexing for nth-of-type
 // rather than nth-child
 index: function(parentNode, reverse, ofType) {
 parentNode._countedByPrototype = Prototype.emptyFunction;
 if (reverse) {
 for (var nodes = parentNode.childNodes, i = nodes.length - 1, j = 1; i >= 0; i--) {
 var node = nodes[i];
 if (node.nodeType == 1 && (!ofType || node._countedByPrototype)) node.nodeIndex = j++;
 }
 } else {
 for (var i = 0, j = 1, nodes = parentNode.childNodes; node = nodes[i]; i++)
 if (node.nodeType == 1 && (!ofType || node._countedByPrototype)) node.nodeIndex = j++;
 }
 },

 // filters out duplicates and extends all nodes
 unique: function(nodes) {
 if (nodes.length == 0) return nodes;
 var results = [], n;
 for (var i = 0, l = nodes.length; i < l; i++)
 if (!(n = nodes[i])._countedByPrototype) {
 n._countedByPrototype = Prototype.emptyFunction;
 results.push(Element.extend(n));
 }
 return Selector.handlers.unmark(results);
 },

 // COMBINATOR FUNCTIONS
 descendant: function(nodes) {
 var h = Selector.handlers;
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 h.concat(results, node.getElementsByTagName('*'));
 return results;
 },

 child: function(nodes) {
 var h = Selector.handlers;
 for (var i = 0, results = [], node; node = nodes[i]; i++) {
 for (var j = 0, child; child = node.childNodes[j]; j++)
 if (child.nodeType == 1 && child.tagName != '!') results.push(child);
 }
 return results;
 },

 adjacent: function(nodes) {
 for (var i = 0, results = [], node; node = nodes[i]; i++) {
 var next = this.nextElementSibling(node);
 if (next) results.push(next);
 }
 return results;
 },

 laterSibling: function(nodes) {
 var h = Selector.handlers;
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 h.concat(results, Element.nextSiblings(node));
 return results;
 },

 nextElementSibling: function(node) {
 while (node = node.nextSibling)
 if (node.nodeType == 1) return node;
 return null;
 },

 previousElementSibling: function(node) {
 while (node = node.previousSibling)
 if (node.nodeType == 1) return node;
 return null;
 },

 // TOKEN FUNCTIONS
 tagName: function(nodes, root, tagName, combinator) {
 var uTagName = tagName.toUpperCase();
 var results = [], h = Selector.handlers;
 if (nodes) {
 if (combinator) {
 // fastlane for ordinary descendant combinators
 if (combinator == "descendant") {
 for (var i = 0, node; node = nodes[i]; i++)
 h.concat(results, node.getElementsByTagName(tagName));
 return results;
 } else nodes = this[combinator](nodes);
 if (tagName == "*") return nodes;
 }
 for (var i = 0, node; node = nodes[i]; i++)
 if (node.tagName.toUpperCase() === uTagName) results.push(node);
 return results;
 } else return root.getElementsByTagName(tagName);
 },

 id: function(nodes, root, id, combinator) {
 var targetNode = $(id), h = Selector.handlers;
 if (!targetNode) return [];
 if (!nodes && root == document) return [targetNode];
 if (nodes) {
 if (combinator) {
 if (combinator == 'child') {
 for (var i = 0, node; node = nodes[i]; i++)
 if (targetNode.parentNode == node) return [targetNode];
 } else if (combinator == 'descendant') {
 for (var i = 0, node; node = nodes[i]; i++)
 if (Element.descendantOf(targetNode, node)) return [targetNode];
 } else if (combinator == 'adjacent') {
 for (var i = 0, node; node = nodes[i]; i++)
 if (Selector.handlers.previousElementSibling(targetNode) == node)
 return [targetNode];
 } else nodes = h[combinator](nodes);
 }
 for (var i = 0, node; node = nodes[i]; i++)
 if (node == targetNode) return [targetNode];
 return [];
 }
 return (targetNode && Element.descendantOf(targetNode, root)) ? [targetNode] : [];
 },

 className: function(nodes, root, className, combinator) {
 if (nodes && combinator) nodes = this[combinator](nodes);
 return Selector.handlers.byClassName(nodes, root, className);
 },

 byClassName: function(nodes, root, className) {
 if (!nodes) nodes = Selector.handlers.descendant([root]);
 var needle = ' ' + className + ' ';
 for (var i = 0, results = [], node, nodeClassName; node = nodes[i]; i++) {
 nodeClassName = node.className;
 if (nodeClassName.length == 0) continue;
 if (nodeClassName == className || (' ' + nodeClassName + ' ').include(needle))
 results.push(node);
 }
 return results;
 },

 attrPresence: function(nodes, root, attr, combinator) {
 if (!nodes) nodes = root.getElementsByTagName("*");
 if (nodes && combinator) nodes = this[combinator](nodes);
 var results = [];
 for (var i = 0, node; node = nodes[i]; i++)
 if (Element.hasAttribute(node, attr)) results.push(node);
 return results;
 },

 attr: function(nodes, root, attr, value, operator, combinator) {
 if (!nodes) nodes = root.getElementsByTagName("*");
 if (nodes && combinator) nodes = this[combinator](nodes);
 var handler = Selector.operators[operator], results = [];
 for (var i = 0, node; node = nodes[i]; i++) {
 var nodeValue = Element.readAttribute(node, attr);
 if (nodeValue === null) continue;
 if (handler(nodeValue, value)) results.push(node);
 }
 return results;
 },

 pseudo: function(nodes, name, value, root, combinator) {
 if (nodes && combinator) nodes = this[combinator](nodes);
 if (!nodes) nodes = root.getElementsByTagName("*");
 return Selector.pseudos[name](nodes, value, root);
 }
 },

 pseudos: {
 'first-child': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++) {
 if (Selector.handlers.previousElementSibling(node)) continue;
 results.push(node);
 }
 return results;
 },
 'last-child': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++) {
 if (Selector.handlers.nextElementSibling(node)) continue;
 results.push(node);
 }
 return results;
 },
 'only-child': function(nodes, value, root) {
 var h = Selector.handlers;
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 if (!h.previousElementSibling(node) && !h.nextElementSibling(node))
 results.push(node);
 return results;
 },
 'nth-child': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, formula, root);
 },
 'nth-last-child': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, formula, root, true);
 },
 'nth-of-type': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, formula, root, false, true);
 },
 'nth-last-of-type': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, formula, root, true, true);
 },
 'first-of-type': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, "1", root, false, true);
 },
 'last-of-type': function(nodes, formula, root) {
 return Selector.pseudos.nth(nodes, "1", root, true, true);
 },
 'only-of-type': function(nodes, formula, root) {
 var p = Selector.pseudos;
 return p['last-of-type'](p['first-of-type'](nodes, formula, root), formula, root);
 },

 // handles the an+b logic
 getIndices: function(a, b, total) {
 if (a == 0) return b > 0 ? [b] : [];
 return $R(1, total).inject([], function(memo, i) {
 if (0 == (i - b) % a && (i - b) / a >= 0) memo.push(i);
 return memo;
 });
 },

 // handles nth(-last)-child, nth(-last)-of-type, and (first|last)-of-type
 nth: function(nodes, formula, root, reverse, ofType) {
 if (nodes.length == 0) return [];
 if (formula == 'even') formula = '2n+0';
 if (formula == 'odd') formula = '2n+1';
 var h = Selector.handlers, results = [], indexed = [], m;
 h.mark(nodes);
 for (var i = 0, node; node = nodes[i]; i++) {
 if (!node.parentNode._countedByPrototype) {
 h.index(node.parentNode, reverse, ofType);
 indexed.push(node.parentNode);
 }
 }
 if (formula.match(/^\d+$/)) { // just a number
 formula = Number(formula);
 for (var i = 0, node; node = nodes[i]; i++)
 if (node.nodeIndex == formula) results.push(node);
 } else if (m = formula.match(/^(-?\d*)?n(([+-])(\d+))?/)) { // an+b
 if (m[1] == "-") m[1] = -1;
 var a = m[1] ? Number(m[1]) : 1;
 var b = m[2] ? Number(m[2]) : 0;
 var indices = Selector.pseudos.getIndices(a, b, nodes.length);
 for (var i = 0, node, l = indices.length; node = nodes[i]; i++) {
 for (var j = 0; j < l; j++)
 if (node.nodeIndex == indices[j]) results.push(node);
 }
 }
 h.unmark(nodes);
 h.unmark(indexed);
 return results;
 },

 'empty': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++) {
 // IE treats comments as element nodes
 if (node.tagName == '!' || node.firstChild) continue;
 results.push(node);
 }
 return results;
 },

 'not': function(nodes, selector, root) {
 var h = Selector.handlers, selectorType, m;
 var exclusions = new Selector(selector).findElements(root);
 h.mark(exclusions);
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 if (!node._countedByPrototype) results.push(node);
 h.unmark(exclusions);
 return results;
 },

 'enabled': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 if (!node.disabled && (!node.type || node.type !== 'hidden'))
 results.push(node);
 return results;
 },

 'disabled': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 if (node.disabled) results.push(node);
 return results;
 },

 'checked': function(nodes, value, root) {
 for (var i = 0, results = [], node; node = nodes[i]; i++)
 if (node.checked) results.push(node);
 return results;
 }
 },

 operators: {
 '=': function(nv, v) { return nv == v; },
 '!=': function(nv, v) { return nv != v; },
 '^=': function(nv, v) { return nv == v || nv && nv.startsWith(v); },
 '$=': function(nv, v) { return nv == v || nv && nv.endsWith(v); },
 '*=': function(nv, v) { return nv == v || nv && nv.include(v); },
 '$=': function(nv, v) { return nv.endsWith(v); },
 '*=': function(nv, v) { return nv.include(v); },
 '~=': function(nv, v) { return (' ' + nv + ' ').include(' ' + v + ' '); },
 '|=': function(nv, v) { return ('-' + (nv || "").toUpperCase() +
 '-').include('-' + (v || "").toUpperCase() + '-'); }
 },

 split: function(expression) {
 var expressions = [];
 expression.scan(/(([\w#:.~>+()\s-]+|\*|\[.*?\])+)\s*(,|$)/, function(m) {
 expressions.push(m[1].strip());
 });
 return expressions;
 },

 matchElements: function(elements, expression) {
 var matches = $$(expression), h = Selector.handlers;
 h.mark(matches);
 for (var i = 0, results = [], element; element = elements[i]; i++)
 if (element._countedByPrototype) results.push(element);
 h.unmark(matches);
 return results;
 },

 findElement: function(elements, expression, index) {
 if (Object.isNumber(expression)) {
 index = expression; expression = false;
 }
 return Selector.matchElements(elements, expression || '*')[index || 0];
 },

 findChildElements: function(element, expressions) {
 expressions = Selector.split(expressions.join(','));
 var results = [], h = Selector.handlers;
 for (var i = 0, l = expressions.length, selector; i < l; i++) {
 selector = new Selector(expressions[i].strip());
 h.concat(results, selector.findElements(element));
 }
 return (l > 1) ? h.unique(results) : results;
 }
});

if (Prototype.Browser.IE) {
 Object.extend(Selector.handlers, {
 // IE returns comment nodes on getElementsByTagName("*").
 // Filter them out.
 concat: function(a, b) {
 for (var i = 0, node; node = b[i]; i++)
 if (node.tagName !== "!") a.push(node);
 return a;
 },

 // IE improperly serializes _countedByPrototype in (inner|outer)HTML.
 unmark: function(nodes) {
 for (var i = 0, node; node = nodes[i]; i++)
 node.removeAttribute('_countedByPrototype');
 return nodes;
 }
 });
}

function $$() {
 return Selector.findChildElements(document, $A(arguments));
}
var Form = {
 reset: function(form) {
 $(form).reset();
 return form;
 },

 serializeElements: function(elements, options) {
 if (typeof options != 'object') options = { hash: !!options };
 else if (Object.isUndefined(options.hash)) options.hash = true;
 var key, value, submitted = false, submit = options.submit;

 var data = elements.inject({ }, function(result, element) {
 if (!element.disabled && element.name) {
 key = element.name; value = $(element).getValue();
 if (value != null && element.type != 'file' && (element.type != 'submit' || (!submitted &&
 submit !== false && (!submit || key == submit) && (submitted = true)))) {
 if (key in result) {
 // a key is already present; construct an array of values
 if (!Object.isArray(result[key])) result[key] = [result[key]];
 result[key].push(value);
 }
 else result[key] = value;
 }
 }
 return result;
 });

 return options.hash ? data : Object.toQueryString(data);
 }
};

Form.Methods = {
 serialize: function(form, options) {
 return Form.serializeElements(Form.getElements(form), options);
 },

 getElements: function(form) {
 return $A($(form).getElementsByTagName('*')).inject([],
 function(elements, child) {
 if (Form.Element.Serializers[child.tagName.toLowerCase()])
 elements.push(Element.extend(child));
 return elements;
 }
 );
 },

 getInputs: function(form, typeName, name) {
 form = $(form);
 var inputs = form.getElementsByTagName('input');

 if (!typeName && !name) return $A(inputs).map(Element.extend);

 for (var i = 0, matchingInputs = [], length = inputs.length; i < length; i++) {
 var input = inputs[i];
 if ((typeName && input.type != typeName) || (name && input.name != name))
 continue;
 matchingInputs.push(Element.extend(input));
 }

 return matchingInputs;
 },

 disable: function(form) {
 form = $(form);
 Form.getElements(form).invoke('disable');
 return form;
 },

 enable: function(form) {
 form = $(form);
 Form.getElements(form).invoke('enable');
 return form;
 },

 findFirstElement: function(form) {
 var elements = $(form).getElements().findAll(function(element) {
 return 'hidden' != element.type && !element.disabled;
 });
 var firstByIndex = elements.findAll(function(element) {
 return element.hasAttribute('tabIndex') && element.tabIndex >= 0;
 }).sortBy(function(element) { return element.tabIndex }).first();

 return firstByIndex ? firstByIndex : elements.find(function(element) {
 return ['input', 'select', 'textarea'].include(element.tagName.toLowerCase());
 });
 },

 focusFirstElement: function(form) {
 form = $(form);
 form.findFirstElement().activate();
 return form;
 },

 request: function(form, options) {
 form = $(form), options = Object.clone(options || { });

 var params = options.parameters, action = form.readAttribute('action') || '';
 if (action.blank()) action = window.location.href;
 options.parameters = form.serialize(true);

 if (params) {
 if (Object.isString(params)) params = params.toQueryParams();
 Object.extend(options.parameters, params);
 }

 if (form.hasAttribute('method') && !options.method)
 options.method = form.method;

 return new Ajax.Request(action, options);
 }
};

/*--------------------------------------------------------------------------*/

Form.Element = {
 focus: function(element) {
 $(element).focus();
 return element;
 },

 select: function(element) {
 $(element).select();
 return element;
 }
};

Form.Element.Methods = {
 serialize: function(element) {
 element = $(element);
 if (!element.disabled && element.name) {
 var value = element.getValue();
 if (value != undefined) {
 var pair = { };
 pair[element.name] = value;
 return Object.toQueryString(pair);
 }
 }
 return '';
 },

 getValue: function(element) {
 element = $(element);
 var method = element.tagName.toLowerCase();
 return Form.Element.Serializers[method](element);
 },

 setValue: function(element, value) {
 element = $(element);
 var method = element.tagName.toLowerCase();
 Form.Element.Serializers[method](element, value);
 return element;
 },

 clear: function(element) {
 $(element).value = '';
 return element;
 },

 present: function(element) {
 return $(element).value != '';
 },

 activate: function(element) {
 element = $(element);
 try {
 element.focus();
 if (element.select && (element.tagName.toLowerCase() != 'input' ||
 !['button', 'reset', 'submit'].include(element.type)))
 element.select();
 } catch (e) { }
 return element;
 },

 disable: function(element) {
 element = $(element);
 element.disabled = true;
 return element;
 },

 enable: function(element) {
 element = $(element);
 element.disabled = false;
 return element;
 }
};

/*--------------------------------------------------------------------------*/

var Field = Form.Element;
var $F = Form.Element.Methods.getValue;

/*--------------------------------------------------------------------------*/

Form.Element.Serializers = {
 input: function(element, value) {
 switch (element.type.toLowerCase()) {
 case 'checkbox':
 case 'radio':
 return Form.Element.Serializers.inputSelector(element, value);
 default:
 return Form.Element.Serializers.textarea(element, value);
 }
 },

 inputSelector: function(element, value) {
 if (Object.isUndefined(value)) return element.checked ? element.value : null;
 else element.checked = !!value;
 },

 textarea: function(element, value) {
 if (Object.isUndefined(value)) return element.value;
 else element.value = value;
 },

 select: function(element, value) {
 if (Object.isUndefined(value))
 return this[element.type == 'select-one' ?
 'selectOne' : 'selectMany'](element);
 else {
 var opt, currentValue, single = !Object.isArray(value);
 for (var i = 0, length = element.length; i < length; i++) {
 opt = element.options[i];
 currentValue = this.optionValue(opt);
 if (single) {
 if (currentValue == value) {
 opt.selected = true;
 return;
 }
 }
 else opt.selected = value.include(currentValue);
 }
 }
 },

 selectOne: function(element) {
 var index = element.selectedIndex;
 return index >= 0 ? this.optionValue(element.options[index]) : null;
 },

 selectMany: function(element) {
 var values, length = element.length;
 if (!length) return null;

 for (var i = 0, values = []; i < length; i++) {
 var opt = element.options[i];
 if (opt.selected) values.push(this.optionValue(opt));
 }
 return values;
 },

 optionValue: function(opt) {
 // extend element because hasAttribute may not be native
 return Element.extend(opt).hasAttribute('value') ? opt.value : opt.text;
 }
};

/*--------------------------------------------------------------------------*/

Abstract.TimedObserver = Class.create(PeriodicalExecuter, {
 initialize: function($super, element, frequency, callback) {
 $super(callback, frequency);
 this.element = $(element);
 this.lastValue = this.getValue();
 },

 execute: function() {
 var value = this.getValue();
 if (Object.isString(this.lastValue) && Object.isString(value) ?
 this.lastValue != value : String(this.lastValue) != String(value)) {
 this.callback(this.element, value);
 this.lastValue = value;
 }
 }
});

Form.Element.Observer = Class.create(Abstract.TimedObserver, {
 getValue: function() {
 return Form.Element.getValue(this.element);
 }
});

Form.Observer = Class.create(Abstract.TimedObserver, {
 getValue: function() {
 return Form.serialize(this.element);
 }
});

/*--------------------------------------------------------------------------*/

Abstract.EventObserver = Class.create({
 initialize: function(element, callback) {
 this.element = $(element);
 this.callback = callback;

 this.lastValue = this.getValue();
 if (this.element.tagName.toLowerCase() == 'form')
 this.registerFormCallbacks();
 else
 this.registerCallback(this.element);
 },

 onElementEvent: function() {
 var value = this.getValue();
 if (this.lastValue != value) {
 this.callback(this.element, value);
 this.lastValue = value;
 }
 },

 registerFormCallbacks: function() {
 Form.getElements(this.element).each(this.registerCallback, this);
 },

 registerCallback: function(element) {
 if (element.type) {
 switch (element.type.toLowerCase()) {
 case 'checkbox':
 case 'radio':
 Event.observe(element, 'click', this.onElementEvent.bind(this));
 break;
 default:
 Event.observe(element, 'change', this.onElementEvent.bind(this));
 break;
 }
 }
 }
});

Form.Element.EventObserver = Class.create(Abstract.EventObserver, {
 getValue: function() {
 return Form.Element.getValue(this.element);
 }
});

Form.EventObserver = Class.create(Abstract.EventObserver, {
 getValue: function() {
 return Form.serialize(this.element);
 }
});
if (!window.Event) var Event = { };

Object.extend(Event, {
 KEY_BACKSPACE: 8,
 KEY_TAB: 9,
 KEY_RETURN: 13,
 KEY_ESC: 27,
 KEY_LEFT: 37,
 KEY_UP: 38,
 KEY_RIGHT: 39,
 KEY_DOWN: 40,
 KEY_DELETE: 46,
 KEY_HOME: 36,
 KEY_END: 35,
 KEY_PAGEUP: 33,
 KEY_PAGEDOWN: 34,
 KEY_INSERT: 45,

 cache: { },

 relatedTarget: function(event) {
 var element;
 switch(event.type) {
 case 'mouseover': element = event.fromElement; break;
 case 'mouseout': element = event.toElement; break;
 default: return null;
 }
 return Element.extend(element);
 }
});

Event.Methods = (function() {
 var isButton;

 if (Prototype.Browser.IE) {
 var buttonMap = { 0: 1, 1: 4, 2: 2 };
 isButton = function(event, code) {
 return event.button == buttonMap[code];
 };

 } else if (Prototype.Browser.WebKit) {
 isButton = function(event, code) {
 switch (code) {
 case 0: return event.which == 1 && !event.metaKey;
 case 1: return event.which == 1 && event.metaKey;
 default: return false;
 }
 };

 } else {
 isButton = function(event, code) {
 return event.which ? (event.which === code + 1) : (event.button === code);
 };
 }

 return {
 isLeftClick: function(event) { return isButton(event, 0) },
 isMiddleClick: function(event) { return isButton(event, 1) },
 isRightClick: function(event) { return isButton(event, 2) },

 element: function(event) {
 event = Event.extend(event);

 var node = event.target,
 type = event.type,
 currentTarget = event.currentTarget;

 if (currentTarget && currentTarget.tagName) {
 // Firefox screws up the "click" event when moving between radio buttons
 // via arrow keys. It also screws up the "load" and "error" events on images,
 // reporting the document as the target instead of the original image.
 if (type === 'load' || type === 'error' ||
 (type === 'click' && currentTarget.tagName.toLowerCase() === 'input'
 && currentTarget.type === 'radio'))
 node = currentTarget;
 }
 if (node) {
 if (node.nodeType == Node.TEXT_NODE) node = node.parentNode;
 return Element.extend(node);
 } else return false;
 },

 findElement: function(event, expression) {
 var element = Event.element(event);
 if (!expression) return element;
 var elements = [element].concat(element.ancestors());
 return Selector.findElement(elements, expression, 0);
 },

 pointer: function(event) {
 var docElement = document.documentElement,
 body = document.body || { scrollLeft: 0, scrollTop: 0 };
 return {
 x: event.pageX || (event.clientX +
 (docElement.scrollLeft || body.scrollLeft) -
 (docElement.clientLeft || 0)),
 y: event.pageY || (event.clientY +
 (docElement.scrollTop || body.scrollTop) -
 (docElement.clientTop || 0))
 };
 },

 pointerX: function(event) { return Event.pointer(event).x },
 pointerY: function(event) { return Event.pointer(event).y },

 stop: function(event) {
 Event.extend(event);
 event.preventDefault();
 event.stopPropagation();
 event.stopped = true;
 }
 };
})();

Event.extend = (function() {
 var methods = Object.keys(Event.Methods).inject({ }, function(m, name) {
 m[name] = Event.Methods[name].methodize();
 return m;
 });

 if (Prototype.Browser.IE) {
 Object.extend(methods, {
 stopPropagation: function() { this.cancelBubble = true },
 preventDefault: function() { this.returnValue = false },
 inspect: function() { return "[object Event]" }
 });

 return function(event) {
 if (!event) return false;
 if (event._extendedByPrototype) return event;

 event._extendedByPrototype = Prototype.emptyFunction;
 var pointer = Event.pointer(event);
 Object.extend(event, {
 target: event.srcElement,
 relatedTarget: Event.relatedTarget(event),
 pageX: pointer.x,
 pageY: pointer.y
 });
 return Object.extend(event, methods);
 };

 } else {
 Event.prototype = Event.prototype || document.createEvent("HTMLEvents")['__proto__'];
 Object.extend(Event.prototype, methods);
 return Prototype.K;
 }
})();

Object.extend(Event, (function() {
 var cache = Event.cache;

 function getEventID(element) {
 if (element._prototypeEventID) return element._prototypeEventID[0];
 arguments.callee.id = arguments.callee.id || 1;
 return element._prototypeEventID = [++arguments.callee.id];
 }

 function getDOMEventName(eventName) {
 if (eventName && eventName.include(':')) return "dataavailable";
 return eventName;
 }

 function getCacheForID(id) {
 return cache[id] = cache[id] || { };
 }

 function getWrappersForEventName(id, eventName) {
 var c = getCacheForID(id);
 return c[eventName] = c[eventName] || [];
 }

 function createWrapper(element, eventName, handler) {
 var id = getEventID(element);
 var c = getWrappersForEventName(id, eventName);
 if (c.pluck("handler").include(handler)) return false;

 var wrapper = function(event) {
 if (!Event || !Event.extend ||
 (event.eventName && event.eventName != eventName))
 return false;

 Event.extend(event);
 handler.call(element, event);
 };

 wrapper.handler = handler;
 c.push(wrapper);
 return wrapper;
 }

 function findWrapper(id, eventName, handler) {
 var c = getWrappersForEventName(id, eventName);
 return c.find(function(wrapper) { return wrapper.handler == handler });
 }

 function destroyWrapper(id, eventName, handler) {
 var c = getCacheForID(id);
 if (!c[eventName]) return false;
 c[eventName] = c[eventName].without(findWrapper(id, eventName, handler));
 }

 function destroyCache() {
 for (var id in cache)
 for (var eventName in cache[id])
 cache[id][eventName] = null;
 }


 // Internet Explorer needs to remove event handlers on page unload
 // in order to avoid memory leaks.
 if (window.attachEvent) {
 window.attachEvent("onunload", destroyCache);
 }

 // Safari has a dummy event handler on page unload so that it won't
 // use its bfcache. Safari <= 3.1 has an issue with restoring the "document"
 // object when page is returned to via the back button using its bfcache.
 if (Prototype.Browser.WebKit) {
 window.addEventListener('unload', Prototype.emptyFunction, false);
 }

 return {
 observe: function(element, eventName, handler) {
 element = $(element);
 var name = getDOMEventName(eventName);

 var wrapper = createWrapper(element, eventName, handler);
 if (!wrapper) return element;

 if (element.addEventListener) {
 element.addEventListener(name, wrapper, false);
 } else {
 element.attachEvent("on" + name, wrapper);
 }

 return element;
 },

 stopObserving: function(element, eventName, handler) {
 element = $(element);
 var id = getEventID(element), name = getDOMEventName(eventName);

 if (!handler && eventName) {
 getWrappersForEventName(id, eventName).each(function(wrapper) {
 element.stopObserving(eventName, wrapper.handler);
 });
 return element;

 } else if (!eventName) {
 Object.keys(getCacheForID(id)).each(function(eventName) {
 element.stopObserving(eventName);
 });
 return element;
 }

 var wrapper = findWrapper(id, eventName, handler);
 if (!wrapper) return element;

 if (element.removeEventListener) {
 element.removeEventListener(name, wrapper, false);
 } else {
 element.detachEvent("on" + name, wrapper);
 }

 destroyWrapper(id, eventName, handler);

 return element;
 },

 fire: function(element, eventName, memo) {
 element = $(element);
 if (element == document && document.createEvent && !element.dispatchEvent)
 element = document.documentElement;

 var event;
 if (document.createEvent) {
 event = document.createEvent("HTMLEvents");
 event.initEvent("dataavailable", true, true);
 } else {
 event = document.createEventObject();
 event.eventType = "ondataavailable";
 }

 event.eventName = eventName;
 event.memo = memo || { };

 if (document.createEvent) {
 element.dispatchEvent(event);
 } else {
 element.fireEvent(event.eventType, event);
 }

 return Event.extend(event);
 }
 };
})());

Object.extend(Event, Event.Methods);

Element.addMethods({
 fire: Event.fire,
 observe: Event.observe,
 stopObserving: Event.stopObserving
});

Object.extend(document, {
 fire: Element.Methods.fire.methodize(),
 observe: Element.Methods.observe.methodize(),
 stopObserving: Element.Methods.stopObserving.methodize(),
 loaded: false
});

(function() {
 /* Support for the DOMContentLoaded event is based on work by Dan Webb,
 Matthias Miller, Dean Edwards and John Resig. */

 var timer;

 function fireContentLoadedEvent() {
 if (document.loaded) return;
 if (timer) window.clearInterval(timer);
 document.fire("dom:loaded");
 document.loaded = true;
 }

 if (document.addEventListener) {
 if (Prototype.Browser.WebKit) {
 timer = window.setInterval(function() {
 if (/loaded|complete/.test(document.readyState))
 fireContentLoadedEvent();
 }, 0);

 Event.observe(window, "load", fireContentLoadedEvent);

 } else {
 document.addEventListener("DOMContentLoaded",
 fireContentLoadedEvent, false);
 }

 } else {
 document.write("<script id=__onDOMContentLoaded defer src=//:><\/script>");
 $("__onDOMContentLoaded").onreadystatechange = function() {
 if (this.readyState == "complete") {
 this.onreadystatechange = null;
 fireContentLoadedEvent();
 }
 };
 }
})();
/*------------------------------- DEPRECATED -------------------------------*/

Hash.toQueryString = Object.toQueryString;

var Toggle = { display: Element.toggle };

Element.Methods.childOf = Element.Methods.descendantOf;

var Insertion = {
 Before: function(element, content) {
 return Element.insert(element, {before:content});
 },

 Top: function(element, content) {
 return Element.insert(element, {top:content});
 },

 Bottom: function(element, content) {
 return Element.insert(element, {bottom:content});
 },

 After: function(element, content) {
 return Element.insert(element, {after:content});
 }
};

var $continue = new Error('"throw $continue" is deprecated, use "return" instead');

// This should be moved to script.aculo.us; notice the deprecated methods
// further below, that map to the newer Element methods.
var Position = {
 // set to true if needed, warning: firefox performance problems
 // NOT neeeded for page scrolling, only if draggable contained in
 // scrollable elements
 includeScrollOffsets: false,

 // must be called before calling withinIncludingScrolloffset, every time the
 // page is scrolled
 prepare: function() {
 this.deltaX = window.pageXOffset
 || document.documentElement.scrollLeft
 || document.body.scrollLeft
 || 0;
 this.deltaY = window.pageYOffset
 || document.documentElement.scrollTop
 || document.body.scrollTop
 || 0;
 },

 // caches x/y coordinate pair to use with overlap
 within: function(element, x, y) {
 if (this.includeScrollOffsets)
 return this.withinIncludingScrolloffsets(element, x, y);
 this.xcomp = x;
 this.ycomp = y;
 this.offset = Element.cumulativeOffset(element);

 return (y >= this.offset[1] &&
 y < this.offset[1] + element.offsetHeight &&
 x >= this.offset[0] &&
 x < this.offset[0] + element.offsetWidth);
 },

 withinIncludingScrolloffsets: function(element, x, y) {
 var offsetcache = Element.cumulativeScrollOffset(element);

 this.xcomp = x + offsetcache[0] - this.deltaX;
 this.ycomp = y + offsetcache[1] - this.deltaY;
 this.offset = Element.cumulativeOffset(element);

 return (this.ycomp >= this.offset[1] &&
 this.ycomp < this.offset[1] + element.offsetHeight &&
 this.xcomp >= this.offset[0] &&
 this.xcomp < this.offset[0] + element.offsetWidth);
 },

 // within must be called directly before
 overlap: function(mode, element) {
 if (!mode) return 0;
 if (mode == 'vertical')
 return ((this.offset[1] + element.offsetHeight) - this.ycomp) /
 element.offsetHeight;
 if (mode == 'horizontal')
 return ((this.offset[0] + element.offsetWidth) - this.xcomp) /
 element.offsetWidth;
 },

 // Deprecation layer -- use newer Element methods now (1.5.2).

 cumulativeOffset: Element.Methods.cumulativeOffset,

 positionedOffset: Element.Methods.positionedOffset,

 absolutize: function(element) {
 Position.prepare();
 return Element.absolutize(element);
 },

 relativize: function(element) {
 Position.prepare();
 return Element.relativize(element);
 },

 realOffset: Element.Methods.cumulativeScrollOffset,

 offsetParent: Element.Methods.getOffsetParent,

 page: Element.Methods.viewportOffset,

 clone: function(source, target, options) {
 options = options || { };
 return Element.clonePosition(target, source, options);
 }
};

/*--------------------------------------------------------------------------*/

if (!document.getElementsByClassName) document.getElementsByClassName = function(instanceMethods){
 function iter(name) {
 return name.blank() ? null : "[contains(concat(' ', @class, ' '), ' " + name + " ')]";
 }

 instanceMethods.getElementsByClassName = Prototype.BrowserFeatures.XPath ?
 function(element, className) {
 className = className.toString().strip();
 var cond = /\s/.test(className) ? $w(className).map(iter).join('') : iter(className);
 return cond ? document._getElementsByXPath('.//*' + cond, element) : [];
 } : function(element, className) {
 className = className.toString().strip();
 var elements = [], classNames = (/\s/.test(className) ? $w(className) : null);
 if (!classNames && !className) return elements;

 var nodes = $(element).getElementsByTagName('*');
 className = ' ' + className + ' ';

 for (var i = 0, child, cn; child = nodes[i]; i++) {
 if (child.className && (cn = ' ' + child.className + ' ') && (cn.include(className) ||
 (classNames && classNames.all(function(name) {
 return !name.toString().blank() && cn.include(' ' + name + ' ');
 }))))
 elements.push(Element.extend(child));
 }
 return elements;
 };

 return function(className, parentElement) {
 return $(parentElement || document.body).getElementsByClassName(className);
 };
}(Element.Methods);

/*--------------------------------------------------------------------------*/

Element.ClassNames = Class.create();
Element.ClassNames.prototype = {
 initialize: function(element) {
 this.element = $(element);
 },

 _each: function(iterator) {
 this.element.className.split(/\s+/).select(function(name) {
 return name.length > 0;
 })._each(iterator);
 },

 set: function(className) {
 this.element.className = className;
 },

 add: function(classNameToAdd) {
 if (this.include(classNameToAdd)) return;
 this.set($A(this).concat(classNameToAdd).join(' '));
 },

 remove: function(classNameToRemove) {
 if (!this.include(classNameToRemove)) return;
 this.set($A(this).without(classNameToRemove).join(' '));
 },

 toString: function() {
 return $A(this).join(' ');
 }
};

Object.extend(Element.ClassNames.prototype, Enumerable);

/*--------------------------------------------------------------------------*/

Element.addMethods();
// script.aculo.us builder.js v1.7.1_beta3, Fri May 25 17:19:41 +0200 2007

// Copyright (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
//
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/

var Builder = {
 NODEMAP: {
 AREA: 'map',
 CAPTION: 'table',
 COL: 'table',
 COLGROUP: 'table',
 LEGEND: 'fieldset',
 OPTGROUP: 'select',
 OPTION: 'select',
 PARAM: 'object',
 TBODY: 'table',
 TD: 'table',
 TFOOT: 'table',
 TH: 'table',
 THEAD: 'table',
 TR: 'table'
 },
 // note: For Firefox < 1.5, OPTION and OPTGROUP tags are currently broken,
 // due to a Firefox bug
 node: function(elementName) {
 elementName = elementName.toUpperCase();
 
 // try innerHTML approach
 var parentTag = this.NODEMAP[elementName] || 'div';
 var parentElement = document.createElement(parentTag);
 try { // prevent IE "feature": http://dev.rubyonrails.org/ticket/2707
 parentElement.innerHTML = "<" + elementName + "></" + elementName + ">";
 } catch(e) {}
 var element = parentElement.firstChild || null;
 
 // see if browser added wrapping tags
 if(element && (element.tagName.toUpperCase() != elementName))
 element = element.getElementsByTagName(elementName)[0];
 
 // fallback to createElement approach
 if(!element) element = document.createElement(elementName);
 
 // abort if nothing could be created
 if(!element) return;

 // attributes (or text)
 if(arguments[1])
 if(this._isStringOrNumber(arguments[1]) ||
 (arguments[1] instanceof Array) ||
 arguments[1].tagName) {
 this._children(element, arguments[1]);
 } else {
 var attrs = this._attributes(arguments[1]);
 if(attrs.length) {
 try { // prevent IE "feature": http://dev.rubyonrails.org/ticket/2707
 parentElement.innerHTML = "<" +elementName + " " +
 attrs + "></" + elementName + ">";
 } catch(e) {}
 element = parentElement.firstChild || null;
 // workaround firefox 1.0.X bug
 if(!element) {
 element = document.createElement(elementName);
 for(attr in arguments[1]) 
 element[attr == 'class' ? 'className' : attr] = arguments[1][attr];
 }
 if(element.tagName.toUpperCase() != elementName)
 element = parentElement.getElementsByTagName(elementName)[0];
 }
 } 

 // text, or array of children
 if(arguments[2])
 this._children(element, arguments[2]);

 return element;
 },
 _text: function(text) {
 return document.createTextNode(text);
 },

 ATTR_MAP: {
 'className': 'class',
 'htmlFor': 'for'
 },

 _attributes: function(attributes) {
 var attrs = [];
 for(attribute in attributes)
 attrs.push((attribute in this.ATTR_MAP ? this.ATTR_MAP[attribute] : attribute) +
 '="' + attributes[attribute].toString().escapeHTML().gsub(/"/,'&quot;') + '"');
 return attrs.join(" ");
 },
 _children: function(element, children) {
 if(children.tagName) {
 element.appendChild(children);
 return;
 }
 if(typeof children=='object') { // array can hold nodes and text
 children.flatten().each( function(e) {
 if(typeof e=='object')
 element.appendChild(e)
 else
 if(Builder._isStringOrNumber(e))
 element.appendChild(Builder._text(e));
 });
 } else
 if(Builder._isStringOrNumber(children))
 element.appendChild(Builder._text(children));
 },
 _isStringOrNumber: function(param) {
 return(typeof param=='string' || typeof param=='number');
 },
 build: function(html) {
 var element = this.node('div');
 $(element).update(html.strip());
 return element.down();
 },
 dump: function(scope) { 
 if(typeof scope != 'object' && typeof scope != 'function') scope = window; //global scope 
 
 var tags = ("A ABBR ACRONYM ADDRESS APPLET AREA B BASE BASEFONT BDO BIG BLOCKQUOTE BODY " +
 "BR BUTTON CAPTION CENTER CITE CODE COL COLGROUP DD DEL DFN DIR DIV DL DT EM FIELDSET " +
 "FONT FORM FRAME FRAMESET H1 H2 H3 H4 H5 H6 HEAD HR HTML I IFRAME IMG INPUT INS ISINDEX "+
 "KBD LABEL LEGEND LI LINK MAP MENU META NOFRAMES NOSCRIPT OBJECT OL OPTGROUP OPTION P "+
 "PARAM PRE Q S SAMP SCRIPT SELECT SMALL SPAN STRIKE STRONG STYLE SUB SUP TABLE TBODY TD "+
 "TEXTAREA TFOOT TH THEAD TITLE TR TT U UL VAR").split(/\s+/);
 
 tags.each( function(tag){ 
 scope[tag] = function() { 
 return Builder.node.apply(Builder, [tag].concat($A(arguments))); 
 } 
 });
 }
}

// script.aculo.us effects.js v1.7.1_beta3, Fri May 25 17:19:41 +0200 2007

// Copyright (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
// Contributors:
// Justin Palmer (http://encytemedia.com/)
// Mark Pilgrim (http://diveintomark.org/)
// Martin Bialasinki
// 
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/ 

// converts rgb() and #xxx to #xxxxxx format, 
// returns self (or first argument) if not convertable 
String.prototype.parseColor = function() { 
 var color = '#';
 if(this.slice(0,4) == 'rgb(') { 
 var cols = this.slice(4,this.length-1).split(','); 
 var i=0; do { color += parseInt(cols[i]).toColorPart() } while (++i<3); 
 } else { 
 if(this.slice(0,1) == '#') { 
 if(this.length==4) for(var i=1;i<4;i++) color += (this.charAt(i) + this.charAt(i)).toLowerCase(); 
 if(this.length==7) color = this.toLowerCase(); 
 } 
 } 
 return(color.length==7 ? color : (arguments[0] || this)); 
}

/*--------------------------------------------------------------------------*/

Element.collectTextNodes = function(element) { 
 return $A($(element).childNodes).collect( function(node) {
 return (node.nodeType==3 ? node.nodeValue : 
 (node.hasChildNodes() ? Element.collectTextNodes(node) : ''));
 }).flatten().join('');
}

Element.collectTextNodesIgnoreClass = function(element, className) { 
 return $A($(element).childNodes).collect( function(node) {
 return (node.nodeType==3 ? node.nodeValue : 
 ((node.hasChildNodes() && !Element.hasClassName(node,className)) ? 
 Element.collectTextNodesIgnoreClass(node, className) : ''));
 }).flatten().join('');
}

Element.setContentZoom = function(element, percent) {
 element = $(element); 
 element.setStyle({fontSize: (percent/100) + 'em'}); 
 if(Prototype.Browser.WebKit) window.scrollBy(0,0);
 return element;
}

Element.getInlineOpacity = function(element){
 return $(element).style.opacity || '';
}

Element.forceRerendering = function(element) {
 try {
 element = $(element);
 var n = document.createTextNode(' ');
 element.appendChild(n);
 element.removeChild(n);
 } catch(e) { }
};

/*--------------------------------------------------------------------------*/

Array.prototype.call = function() {
 var args = arguments;
 this.each(function(f){ f.apply(this, args) });
}

/*--------------------------------------------------------------------------*/

var Effect = {
 _elementDoesNotExistError: {
 name: 'ElementDoesNotExistError',
 message: 'The specified DOM element does not exist, but is required for this effect to operate'
 },
 tagifyText: function(element) {
 if(typeof Builder == 'undefined')
 throw("Effect.tagifyText requires including script.aculo.us' builder.js library");
 
 var tagifyStyle = 'position:relative';
 if(Prototype.Browser.IE) tagifyStyle += ';zoom:1';
 
 element = $(element);
 $A(element.childNodes).each( function(child) {
 if(child.nodeType==3) {
 child.nodeValue.toArray().each( function(character) {
 element.insertBefore(
 Builder.node('span',{style: tagifyStyle},
 character == ' ' ? String.fromCharCode(160) : character), 
 child);
 });
 Element.remove(child);
 }
 });
 },
 multiple: function(element, effect) {
 var elements;
 if(((typeof element == 'object') || 
 (typeof element == 'function')) && 
 (element.length))
 elements = element;
 else
 elements = $(element).childNodes;
 
 var options = Object.extend({
 speed: 0.1,
 delay: 0.0
 }, arguments[2] || {});
 var masterDelay = options.delay;

 $A(elements).each( function(element, index) {
 new effect(element, Object.extend(options, { delay: index * options.speed + masterDelay }));
 });
 },
 PAIRS: {
 'slide': ['SlideDown','SlideUp'],
 'blind': ['BlindDown','BlindUp'],
 'appear': ['Appear','Fade']
 },
 toggle: function(element, effect) {
 element = $(element);
 effect = (effect || 'appear').toLowerCase();
 var options = Object.extend({
 queue: { position:'end', scope:(element.id || 'global'), limit: 1 }
 }, arguments[2] || {});
 Effect[element.visible() ? 
 Effect.PAIRS[effect][1] : Effect.PAIRS[effect][0]](element, options);
 }
};

var Effect2 = Effect; // deprecated

/* ------------- transitions ------------- */

Effect.Transitions = {
 linear: Prototype.K,
 sinoidal: function(pos) {
 return (-Math.cos(pos*Math.PI)/2) + 0.5;
 },
 reverse: function(pos) {
 return 1-pos;
 },
 flicker: function(pos) {
 var pos = ((-Math.cos(pos*Math.PI)/4) + 0.75) + Math.random()/4;
 return (pos > 1 ? 1 : pos);
 },
 wobble: function(pos) {
 return (-Math.cos(pos*Math.PI*(9*pos))/2) + 0.5;
 },
 pulse: function(pos, pulses) { 
 pulses = pulses || 5; 
 return (
 Math.round((pos % (1/pulses)) * pulses) == 0 ? 
 ((pos * pulses * 2) - Math.floor(pos * pulses * 2)) : 
 1 - ((pos * pulses * 2) - Math.floor(pos * pulses * 2))
 );
 },
 none: function(pos) {
 return 0;
 },
 full: function(pos) {
 return 1;
 }
};

/* ------------- core effects ------------- */

Effect.ScopedQueue = Class.create();
Object.extend(Object.extend(Effect.ScopedQueue.prototype, Enumerable), {
 initialize: function() {
 this.effects = [];
 this.interval = null; 
 },
 _each: function(iterator) {
 this.effects._each(iterator);
 },
 add: function(effect) {
 var timestamp = new Date().getTime();
 
 var position = (typeof effect.options.queue == 'string') ? 
 effect.options.queue : effect.options.queue.position;
 
 switch(position) {
 case 'front':
 // move unstarted effects after this effect 
 this.effects.findAll(function(e){ return e.state=='idle' }).each( function(e) {
 e.startOn += effect.finishOn;
 e.finishOn += effect.finishOn;
 });
 break;
 case 'with-last':
 timestamp = this.effects.pluck('startOn').max() || timestamp;
 break;
 case 'end':
 // start effect after last queued effect has finished
 timestamp = this.effects.pluck('finishOn').max() || timestamp;
 break;
 }
 
 effect.startOn += timestamp;
 effect.finishOn += timestamp;

 if(!effect.options.queue.limit || (this.effects.length < effect.options.queue.limit))
 this.effects.push(effect);
 
 if(!this.interval)
 this.interval = setInterval(this.loop.bind(this), 15);
 },
 remove: function(effect) {
 this.effects = this.effects.reject(function(e) { return e==effect });
 if(this.effects.length == 0) {
 clearInterval(this.interval);
 this.interval = null;
 }
 },
 loop: function() {
 var timePos = new Date().getTime();
 for(var i=0, len=this.effects.length;i<len;i++) 
 this.effects[i] && this.effects[i].loop(timePos);
 }
});

Effect.Queues = {
 instances: $H(),
 get: function(queueName) {
 if(typeof queueName != 'string') return queueName;
 
 if(!this.instances[queueName])
 this.instances[queueName] = new Effect.ScopedQueue();
 
 return this.instances[queueName];
 }
}
Effect.Queue = Effect.Queues.get('global');

Effect.DefaultOptions = {
 transition: Effect.Transitions.sinoidal,
 duration: 1.0, // seconds
 fps: 100, // 100= assume 66fps max.
 sync: false, // true for combining
 from: 0.0,
 to: 1.0,
 delay: 0.0,
 queue: 'parallel'
}

Effect.Base = function() {};
Effect.Base.prototype = {
 position: null,
 start: function(options) {
 function codeForEvent(options,eventName){
 return (
 (options[eventName+'Internal'] ? 'this.options.'+eventName+'Internal(this);' : '') +
 (options[eventName] ? 'this.options.'+eventName+'(this);' : '')
 );
 }
 if(options.transition === false) options.transition = Effect.Transitions.linear;
 this.options = Object.extend(Object.extend({},Effect.DefaultOptions), options || {});
 this.currentFrame = 0;
 this.state = 'idle';
 this.startOn = this.options.delay*1000;
 this.finishOn = this.startOn+(this.options.duration*1000);
 this.fromToDelta = this.options.to-this.options.from;
 this.totalTime = this.finishOn-this.startOn;
 this.totalFrames = this.options.fps*this.options.duration;
 
 eval('this.render = function(pos){ '+
 'if(this.state=="idle"){this.state="running";'+
 codeForEvent(options,'beforeSetup')+
 (this.setup ? 'this.setup();':'')+ 
 codeForEvent(options,'afterSetup')+
 '};if(this.state=="running"){'+
 'pos=this.options.transition(pos)*'+this.fromToDelta+'+'+this.options.from+';'+
 'this.position=pos;'+
 codeForEvent(options,'beforeUpdate')+
 (this.update ? 'this.update(pos);':'')+
 codeForEvent(options,'afterUpdate')+
 '}}');
 
 this.event('beforeStart');
 if(!this.options.sync)
 Effect.Queues.get(typeof this.options.queue == 'string' ? 
 'global' : this.options.queue.scope).add(this);
 },
 loop: function(timePos) {
 if(timePos >= this.startOn) {
 if(timePos >= this.finishOn) {
 this.render(1.0);
 this.cancel();
 this.event('beforeFinish');
 if(this.finish) this.finish(); 
 this.event('afterFinish');
 return; 
 }
 var pos = (timePos - this.startOn) / this.totalTime,
 frame = Math.round(pos * this.totalFrames);
 if(frame > this.currentFrame) {
 this.render(pos);
 this.currentFrame = frame;
 }
 }
 },
 cancel: function() {
 if(!this.options.sync)
 Effect.Queues.get(typeof this.options.queue == 'string' ? 
 'global' : this.options.queue.scope).remove(this);
 this.state = 'finished';
 },
 event: function(eventName) {
 if(this.options[eventName + 'Internal']) this.options[eventName + 'Internal'](this);
 if(this.options[eventName]) this.options[eventName](this);
 },
 inspect: function() {
 var data = $H();
 for(property in this)
 if(typeof this[property] != 'function') data[property] = this[property];
 return '#<Effect:' + data.inspect() + ',options:' + $H(this.options).inspect() + '>';
 }
}

Effect.Parallel = Class.create();
Object.extend(Object.extend(Effect.Parallel.prototype, Effect.Base.prototype), {
 initialize: function(effects) {
 this.effects = effects || [];
 this.start(arguments[1]);
 },
 update: function(position) {
 this.effects.invoke('render', position);
 },
 finish: function(position) {
 this.effects.each( function(effect) {
 effect.render(1.0);
 effect.cancel();
 effect.event('beforeFinish');
 if(effect.finish) effect.finish(position);
 effect.event('afterFinish');
 });
 }
});

Effect.Event = Class.create();
Object.extend(Object.extend(Effect.Event.prototype, Effect.Base.prototype), {
 initialize: function() {
 var options = Object.extend({
 duration: 0
 }, arguments[0] || {});
 this.start(options);
 },
 update: Prototype.emptyFunction
});

Effect.Opacity = Class.create();
Object.extend(Object.extend(Effect.Opacity.prototype, Effect.Base.prototype), {
 initialize: function(element) {
 this.element = $(element);
 if(!this.element) throw(Effect._elementDoesNotExistError);
 // make this work on IE on elements without 'layout'
 if(Prototype.Browser.IE && (!this.element.currentStyle.hasLayout))
 this.element.setStyle({zoom: 1});
 var options = Object.extend({
 from: this.element.getOpacity() || 0.0,
 to: 1.0
 }, arguments[1] || {});
 this.start(options);
 },
 update: function(position) {
 this.element.setOpacity(position);
 }
});

Effect.Move = Class.create();
Object.extend(Object.extend(Effect.Move.prototype, Effect.Base.prototype), {
 initialize: function(element) {
 this.element = $(element);
 if(!this.element) throw(Effect._elementDoesNotExistError);
 var options = Object.extend({
 x: 0,
 y: 0,
 mode: 'relative'
 }, arguments[1] || {});
 this.start(options);
 },
 setup: function() {
 // Bug in Opera: Opera returns the "real" position of a static element or
 // relative element that does not have top/left explicitly set.
 // ==> Always set top and left for position relative elements in your stylesheets 
 // (to 0 if you do not need them) 
 this.element.makePositioned();
 this.originalLeft = parseFloat(this.element.getStyle('left') || '0');
 this.originalTop = parseFloat(this.element.getStyle('top') || '0');
 if(this.options.mode == 'absolute') {
 // absolute movement, so we need to calc deltaX and deltaY
 this.options.x = this.options.x - this.originalLeft;
 this.options.y = this.options.y - this.originalTop;
 }
 },
 update: function(position) {
 this.element.setStyle({
 left: Math.round(this.options.x * position + this.originalLeft) + 'px',
 top: Math.round(this.options.y * position + this.originalTop) + 'px'
 });
 }
});

// for backwards compatibility
Effect.MoveBy = function(element, toTop, toLeft) {
 return new Effect.Move(element, 
 Object.extend({ x: toLeft, y: toTop }, arguments[3] || {}));
};

Effect.Scale = Class.create();
Object.extend(Object.extend(Effect.Scale.prototype, Effect.Base.prototype), {
 initialize: function(element, percent) {
 this.element = $(element);
 if(!this.element) throw(Effect._elementDoesNotExistError);
 var options = Object.extend({
 scaleX: true,
 scaleY: true,
 scaleContent: true,
 scaleFromCenter: false,
 scaleMode: 'box', // 'box' or 'contents' or {} with provided values
 scaleFrom: 100.0,
 scaleTo: percent
 }, arguments[2] || {});
 this.start(options);
 },
 setup: function() {
 this.restoreAfterFinish = this.options.restoreAfterFinish || false;
 this.elementPositioning = this.element.getStyle('position');
 
 this.originalStyle = {};
 ['top','left','width','height','fontSize'].each( function(k) {
 this.originalStyle[k] = this.element.style[k];
 }.bind(this));
 
 this.originalTop = this.element.offsetTop;
 this.originalLeft = this.element.offsetLeft;
 
 var fontSize = this.element.getStyle('font-size') || '100%';
 ['em','px','%','pt'].each( function(fontSizeType) {
 if(fontSize.indexOf(fontSizeType)>0) {
 this.fontSize = parseFloat(fontSize);
 this.fontSizeType = fontSizeType;
 }
 }.bind(this));
 
 this.factor = (this.options.scaleTo - this.options.scaleFrom)/100;
 
 this.dims = null;
 if(this.options.scaleMode=='box')
 this.dims = [this.element.offsetHeight, this.element.offsetWidth];
 if(/^content/.test(this.options.scaleMode))
 this.dims = [this.element.scrollHeight, this.element.scrollWidth];
 if(!this.dims)
 this.dims = [this.options.scaleMode.originalHeight,
 this.options.scaleMode.originalWidth];
 },
 update: function(position) {
 var currentScale = (this.options.scaleFrom/100.0) + (this.factor * position);
 if(this.options.scaleContent && this.fontSize)
 this.element.setStyle({fontSize: this.fontSize * currentScale + this.fontSizeType });
 this.setDimensions(this.dims[0] * currentScale, this.dims[1] * currentScale);
 },
 finish: function(position) {
 if(this.restoreAfterFinish) this.element.setStyle(this.originalStyle);
 },
 setDimensions: function(height, width) {
 var d = {};
 if(this.options.scaleX) d.width = Math.round(width) + 'px';
 if(this.options.scaleY) d.height = Math.round(height) + 'px';
 if(this.options.scaleFromCenter) {
 var topd = (height - this.dims[0])/2;
 var leftd = (width - this.dims[1])/2;
 if(this.elementPositioning == 'absolute') {
 if(this.options.scaleY) d.top = this.originalTop-topd + 'px';
 if(this.options.scaleX) d.left = this.originalLeft-leftd + 'px';
 } else {
 if(this.options.scaleY) d.top = -topd + 'px';
 if(this.options.scaleX) d.left = -leftd + 'px';
 }
 }
 this.element.setStyle(d);
 }
});

Effect.Highlight = Class.create();
Object.extend(Object.extend(Effect.Highlight.prototype, Effect.Base.prototype), {
 initialize: function(element) {
 this.element = $(element);
 if(!this.element) throw(Effect._elementDoesNotExistError);
 var options = Object.extend({ startcolor: '#ffff99' }, arguments[1] || {});
 this.start(options);
 },
 setup: function() {
 // Prevent executing on elements not in the layout flow
 if(this.element.getStyle('display')=='none') { this.cancel(); return; }
 // Disable background image during the effect
 this.oldStyle = {};
 if (!this.options.keepBackgroundImage) {
 this.oldStyle.backgroundImage = this.element.getStyle('background-image');
 this.element.setStyle({backgroundImage: 'none'});
 }
 if(!this.options.endcolor)
 this.options.endcolor = this.element.getStyle('background-color').parseColor('#ffffff');
 if(!this.options.restorecolor)
 this.options.restorecolor = this.element.getStyle('background-color');
 // init color calculations
 this._base = $R(0,2).map(function(i){ return parseInt(this.options.startcolor.slice(i*2+1,i*2+3),16) }.bind(this));
 this._delta = $R(0,2).map(function(i){ return parseInt(this.options.endcolor.slice(i*2+1,i*2+3),16)-this._base[i] }.bind(this));
 },
 update: function(position) {
 this.element.setStyle({backgroundColor: $R(0,2).inject('#',function(m,v,i){
 return m+(Math.round(this._base[i]+(this._delta[i]*position)).toColorPart()); }.bind(this)) });
 },
 finish: function() {
 this.element.setStyle(Object.extend(this.oldStyle, {
 backgroundColor: this.options.restorecolor
 }));
 }
});

Effect.ScrollTo = Class.create();
Object.extend(Object.extend(Effect.ScrollTo.prototype, Effect.Base.prototype), {
 initialize: function(element) {
 this.element = $(element);
 this.start(arguments[1] || {});
 },
 setup: function() {
 Position.prepare();
 var offsets = Position.cumulativeOffset(this.element);
 if(this.options.offset) offsets[1] += this.options.offset;
 var max = window.innerHeight ? 
 window.height - window.innerHeight :
 document.body.scrollHeight - 
 (document.documentElement.clientHeight ? 
 document.documentElement.clientHeight : document.body.clientHeight);
 this.scrollStart = Position.deltaY;
 this.delta = (offsets[1] > max ? max : offsets[1]) - this.scrollStart;
 },
 update: function(position) {
 Position.prepare();
 window.scrollTo(Position.deltaX, 
 this.scrollStart + (position*this.delta));
 }
});

/* ------------- combination effects ------------- */

Effect.Fade = function(element) {
 element = $(element);
 var oldOpacity = element.getInlineOpacity();
 var options = Object.extend({
 from: element.getOpacity() || 1.0,
 to: 0.0,
 afterFinishInternal: function(effect) { 
 if(effect.options.to!=0) return;
 effect.element.hide().setStyle({opacity: oldOpacity}); 
 }}, arguments[1] || {});
 return new Effect.Opacity(element,options);
}

Effect.Appear = function(element) {
 element = $(element);
 var options = Object.extend({
 from: (element.getStyle('display') == 'none' ? 0.0 : element.getOpacity() || 0.0),
 to: 1.0,
 // force Safari to render floated elements properly
 afterFinishInternal: function(effect) {
 effect.element.forceRerendering();
 },
 beforeSetup: function(effect) {
 effect.element.setOpacity(effect.options.from).show(); 
 }}, arguments[1] || {});
 return new Effect.Opacity(element,options);
}

Effect.Puff = function(element) {
 element = $(element);
 var oldStyle = { 
 opacity: element.getInlineOpacity(), 
 position: element.getStyle('position'),
 top: element.style.top,
 left: element.style.left,
 width: element.style.width,
 height: element.style.height
 };
 return new Effect.Parallel(
 [ new Effect.Scale(element, 200, 
 { sync: true, scaleFromCenter: true, scaleContent: true, restoreAfterFinish: true }), 
 new Effect.Opacity(element, { sync: true, to: 0.0 } ) ], 
 Object.extend({ duration: 1.0, 
 beforeSetupInternal: function(effect) {
 Position.absolutize(effect.effects[0].element)
 },
 afterFinishInternal: function(effect) {
 effect.effects[0].element.hide().setStyle(oldStyle); }
 }, arguments[1] || {})
 );
}

Effect.BlindUp = function(element) {
 element = $(element);
 element.makeClipping();
 return new Effect.Scale(element, 0,
 Object.extend({ scaleContent: false, 
 scaleX: false, 
 restoreAfterFinish: true,
 afterFinishInternal: function(effect) {
 effect.element.hide().undoClipping();
 } 
 }, arguments[1] || {})
 );
}

Effect.BlindDown = function(element) {
 element = $(element);
 var elementDimensions = element.getDimensions();
 return new Effect.Scale(element, 100, Object.extend({ 
 scaleContent: false, 
 scaleX: false,
 scaleFrom: 0,
 scaleMode: {originalHeight: elementDimensions.height, originalWidth: elementDimensions.width},
 restoreAfterFinish: true,
 afterSetup: function(effect) {
 effect.element.makeClipping().setStyle({height: '0px'}).show(); 
 }, 
 afterFinishInternal: function(effect) {
 effect.element.undoClipping();
 }
 }, arguments[1] || {}));
}

Effect.SwitchOff = function(element) {
 element = $(element);
 var oldOpacity = element.getInlineOpacity();
 return new Effect.Appear(element, Object.extend({
 duration: 0.4,
 from: 0,
 transition: Effect.Transitions.flicker,
 afterFinishInternal: function(effect) {
 new Effect.Scale(effect.element, 1, { 
 duration: 0.3, scaleFromCenter: true,
 scaleX: false, scaleContent: false, restoreAfterFinish: true,
 beforeSetup: function(effect) { 
 effect.element.makePositioned().makeClipping();
 },
 afterFinishInternal: function(effect) {
 effect.element.hide().undoClipping().undoPositioned().setStyle({opacity: oldOpacity});
 }
 })
 }
 }, arguments[1] || {}));
}

Effect.DropOut = function(element) {
 element = $(element);
 var oldStyle = {
 top: element.getStyle('top'),
 left: element.getStyle('left'),
 opacity: element.getInlineOpacity() };
 return new Effect.Parallel(
 [ new Effect.Move(element, {x: 0, y: 100, sync: true }), 
 new Effect.Opacity(element, { sync: true, to: 0.0 }) ],
 Object.extend(
 { duration: 0.5,
 beforeSetup: function(effect) {
 effect.effects[0].element.makePositioned(); 
 },
 afterFinishInternal: function(effect) {
 effect.effects[0].element.hide().undoPositioned().setStyle(oldStyle);
 } 
 }, arguments[1] || {}));
}

Effect.Shake = function(element) {
 element = $(element);
 var oldStyle = {
 top: element.getStyle('top'),
 left: element.getStyle('left') };
 return new Effect.Move(element, 
 { x: 20, y: 0, duration: 0.05, afterFinishInternal: function(effect) {
 new Effect.Move(effect.element,
 { x: -40, y: 0, duration: 0.1, afterFinishInternal: function(effect) {
 new Effect.Move(effect.element,
 { x: 40, y: 0, duration: 0.1, afterFinishInternal: function(effect) {
 new Effect.Move(effect.element,
 { x: -40, y: 0, duration: 0.1, afterFinishInternal: function(effect) {
 new Effect.Move(effect.element,
 { x: 40, y: 0, duration: 0.1, afterFinishInternal: function(effect) {
 new Effect.Move(effect.element,
 { x: -20, y: 0, duration: 0.05, afterFinishInternal: function(effect) {
 effect.element.undoPositioned().setStyle(oldStyle);
 }}) }}) }}) }}) }}) }});
}

Effect.SlideDown = function(element) {
 element = $(element).cleanWhitespace();
 // SlideDown need to have the content of the element wrapped in a container element with fixed height!
 var oldInnerBottom = element.down().getStyle('bottom');
 var elementDimensions = element.getDimensions();
 return new Effect.Scale(element, 100, Object.extend({ 
 scaleContent: false, 
 scaleX: false, 
 scaleFrom: window.opera ? 0 : 1,
 scaleMode: {originalHeight: elementDimensions.height, originalWidth: elementDimensions.width},
 restoreAfterFinish: true,
 afterSetup: function(effect) {
 effect.element.makePositioned();
 effect.element.down().makePositioned();
 if(window.opera) effect.element.setStyle({top: ''});
 effect.element.makeClipping().setStyle({height: '0px'}).show(); 
 },
 afterUpdateInternal: function(effect) {
 effect.element.down().setStyle({bottom:
 (effect.dims[0] - effect.element.clientHeight) + 'px' }); 
 },
 afterFinishInternal: function(effect) {
 effect.element.undoClipping().undoPositioned();
 effect.element.down().undoPositioned().setStyle({bottom: oldInnerBottom}); }
 }, arguments[1] || {})
 );
}

Effect.SlideUp = function(element) {
 element = $(element).cleanWhitespace();
 var oldInnerBottom = element.down().getStyle('bottom');
 return new Effect.Scale(element, window.opera ? 0 : 1,
 Object.extend({ scaleContent: false, 
 scaleX: false, 
 scaleMode: 'box',
 scaleFrom: 100,
 restoreAfterFinish: true,
 beforeStartInternal: function(effect) {
 effect.element.makePositioned();
 effect.element.down().makePositioned();
 if(window.opera) effect.element.setStyle({top: ''});
 effect.element.makeClipping().show();
 }, 
 afterUpdateInternal: function(effect) {
 effect.element.down().setStyle({bottom:
 (effect.dims[0] - effect.element.clientHeight) + 'px' });
 },
 afterFinishInternal: function(effect) {
 effect.element.hide().undoClipping().undoPositioned().setStyle({bottom: oldInnerBottom});
 effect.element.down().undoPositioned();
 }
 }, arguments[1] || {})
 );
}

// Bug in opera makes the TD containing this element expand for a instance after finish 
Effect.Squish = function(element) {
 return new Effect.Scale(element, window.opera ? 1 : 0, { 
 restoreAfterFinish: true,
 beforeSetup: function(effect) {
 effect.element.makeClipping(); 
 }, 
 afterFinishInternal: function(effect) {
 effect.element.hide().undoClipping(); 
 }
 });
}

Effect.Grow = function(element) {
 element = $(element);
 var options = Object.extend({
 direction: 'center',
 moveTransition: Effect.Transitions.sinoidal,
 scaleTransition: Effect.Transitions.sinoidal,
 opacityTransition: Effect.Transitions.full
 }, arguments[1] || {});
 var oldStyle = {
 top: element.style.top,
 left: element.style.left,
 height: element.style.height,
 width: element.style.width,
 opacity: element.getInlineOpacity() };

 var dims = element.getDimensions(); 
 var initialMoveX, initialMoveY;
 var moveX, moveY;
 
 switch (options.direction) {
 case 'top-left':
 initialMoveX = initialMoveY = moveX = moveY = 0; 
 break;
 case 'top-right':
 initialMoveX = dims.width;
 initialMoveY = moveY = 0;
 moveX = -dims.width;
 break;
 case 'bottom-left':
 initialMoveX = moveX = 0;
 initialMoveY = dims.height;
 moveY = -dims.height;
 break;
 case 'bottom-right':
 initialMoveX = dims.width;
 initialMoveY = dims.height;
 moveX = -dims.width;
 moveY = -dims.height;
 break;
 case 'center':
 initialMoveX = dims.width / 2;
 initialMoveY = dims.height / 2;
 moveX = -dims.width / 2;
 moveY = -dims.height / 2;
 break;
 }
 
 return new Effect.Move(element, {
 x: initialMoveX,
 y: initialMoveY,
 duration: 0.01, 
 beforeSetup: function(effect) {
 effect.element.hide().makeClipping().makePositioned();
 },
 afterFinishInternal: function(effect) {
 new Effect.Parallel(
 [ new Effect.Opacity(effect.element, { sync: true, to: 1.0, from: 0.0, transition: options.opacityTransition }),
 new Effect.Move(effect.element, { x: moveX, y: moveY, sync: true, transition: options.moveTransition }),
 new Effect.Scale(effect.element, 100, {
 scaleMode: { originalHeight: dims.height, originalWidth: dims.width }, 
 sync: true, scaleFrom: window.opera ? 1 : 0, transition: options.scaleTransition, restoreAfterFinish: true})
 ], Object.extend({
 beforeSetup: function(effect) {
 effect.effects[0].element.setStyle({height: '0px'}).show(); 
 },
 afterFinishInternal: function(effect) {
 effect.effects[0].element.undoClipping().undoPositioned().setStyle(oldStyle); 
 }
 }, options)
 )
 }
 });
}

Effect.Shrink = function(element) {
 element = $(element);
 var options = Object.extend({
 direction: 'center',
 moveTransition: Effect.Transitions.sinoidal,
 scaleTransition: Effect.Transitions.sinoidal,
 opacityTransition: Effect.Transitions.none
 }, arguments[1] || {});
 var oldStyle = {
 top: element.style.top,
 left: element.style.left,
 height: element.style.height,
 width: element.style.width,
 opacity: element.getInlineOpacity() };

 var dims = element.getDimensions();
 var moveX, moveY;
 
 switch (options.direction) {
 case 'top-left':
 moveX = moveY = 0;
 break;
 case 'top-right':
 moveX = dims.width;
 moveY = 0;
 break;
 case 'bottom-left':
 moveX = 0;
 moveY = dims.height;
 break;
 case 'bottom-right':
 moveX = dims.width;
 moveY = dims.height;
 break;
 case 'center': 
 moveX = dims.width / 2;
 moveY = dims.height / 2;
 break;
 }
 
 return new Effect.Parallel(
 [ new Effect.Opacity(element, { sync: true, to: 0.0, from: 1.0, transition: options.opacityTransition }),
 new Effect.Scale(element, window.opera ? 1 : 0, { sync: true, transition: options.scaleTransition, restoreAfterFinish: true}),
 new Effect.Move(element, { x: moveX, y: moveY, sync: true, transition: options.moveTransition })
 ], Object.extend({ 
 beforeStartInternal: function(effect) {
 effect.effects[0].element.makePositioned().makeClipping(); 
 },
 afterFinishInternal: function(effect) {
 effect.effects[0].element.hide().undoClipping().undoPositioned().setStyle(oldStyle); }
 }, options)
 );
}

Effect.Pulsate = function(element) {
 element = $(element);
 var options = arguments[1] || {};
 var oldOpacity = element.getInlineOpacity();
 var transition = options.transition || Effect.Transitions.sinoidal;
 var reverser = function(pos){ return transition(1-Effect.Transitions.pulse(pos, options.pulses)) };
 reverser.bind(transition);
 return new Effect.Opacity(element, 
 Object.extend(Object.extend({ duration: 2.0, from: 0,
 afterFinishInternal: function(effect) { effect.element.setStyle({opacity: oldOpacity}); }
 }, options), {transition: reverser}));
}

Effect.Fold = function(element) {
 element = $(element);
 var oldStyle = {
 top: element.style.top,
 left: element.style.left,
 width: element.style.width,
 height: element.style.height };
 element.makeClipping();
 return new Effect.Scale(element, 5, Object.extend({ 
 scaleContent: false,
 scaleX: false,
 afterFinishInternal: function(effect) {
 new Effect.Scale(element, 1, { 
 scaleContent: false, 
 scaleY: false,
 afterFinishInternal: function(effect) {
 effect.element.hide().undoClipping().setStyle(oldStyle);
 } });
 }}, arguments[1] || {}));
};

Effect.Morph = Class.create();
Object.extend(Object.extend(Effect.Morph.prototype, Effect.Base.prototype), {
 initialize: function(element) {
 this.element = $(element);
 if(!this.element) throw(Effect._elementDoesNotExistError);
 var options = Object.extend({
 style: {}
 }, arguments[1] || {});
 if (typeof options.style == 'string') {
 if(options.style.indexOf(':') == -1) {
 var cssText = '', selector = '.' + options.style;
 $A(document.styleSheets).reverse().each(function(styleSheet) {
 if (styleSheet.cssRules) cssRules = styleSheet.cssRules;
 else if (styleSheet.rules) cssRules = styleSheet.rules;
 $A(cssRules).reverse().each(function(rule) {
 if (selector == rule.selectorText) {
 cssText = rule.style.cssText;
 throw $break;
 }
 });
 if (cssText) throw $break;
 });
 this.style = cssText.parseStyle();
 options.afterFinishInternal = function(effect){
 effect.element.addClassName(effect.options.style);
 effect.transforms.each(function(transform) {
 if(transform.style != 'opacity')
 effect.element.style[transform.style] = '';
 });
 }
 } else this.style = options.style.parseStyle();
 } else this.style = $H(options.style)
 this.start(options);
 },
 setup: function(){
 function parseColor(color){
 if(!color || ['rgba(0, 0, 0, 0)','transparent'].include(color)) color = '#ffffff';
 color = color.parseColor();
 return $R(0,2).map(function(i){
 return parseInt( color.slice(i*2+1,i*2+3), 16 ) 
 });
 }
 this.transforms = this.style.map(function(pair){
 var property = pair[0], value = pair[1], unit = null;

 if(value.parseColor('#zzzzzz') != '#zzzzzz') {
 value = value.parseColor();
 unit = 'color';
 } else if(property == 'opacity') {
 value = parseFloat(value);
 if(Prototype.Browser.IE && (!this.element.currentStyle.hasLayout))
 this.element.setStyle({zoom: 1});
 } else if(Element.CSS_LENGTH.test(value)) {
 var components = value.match(/^([\+\-]?[0-9\.]+)(.*)$/);
 value = parseFloat(components[1]);
 unit = (components.length == 3) ? components[2] : null;
 }

 var originalValue = this.element.getStyle(property);
 return { 
 style: property.camelize(), 
 originalValue: unit=='color' ? parseColor(originalValue) : parseFloat(originalValue || 0), 
 targetValue: unit=='color' ? parseColor(value) : value,
 unit: unit
 };
 }.bind(this)).reject(function(transform){
 return (
 (transform.originalValue == transform.targetValue) ||
 (
 transform.unit != 'color' &&
 (isNaN(transform.originalValue) || isNaN(transform.targetValue))
 )
 )
 });
 },
 update: function(position) {
 var style = {}, transform, i = this.transforms.length;
 while(i--)
 style[(transform = this.transforms[i]).style] = 
 transform.unit=='color' ? '#'+
 (Math.round(transform.originalValue[0]+
 (transform.targetValue[0]-transform.originalValue[0])*position)).toColorPart() +
 (Math.round(transform.originalValue[1]+
 (transform.targetValue[1]-transform.originalValue[1])*position)).toColorPart() +
 (Math.round(transform.originalValue[2]+
 (transform.targetValue[2]-transform.originalValue[2])*position)).toColorPart() :
 transform.originalValue + Math.round(
 ((transform.targetValue - transform.originalValue) * position) * 1000)/1000 + transform.unit;
 this.element.setStyle(style, true);
 }
});

Effect.Transform = Class.create();
Object.extend(Effect.Transform.prototype, {
 initialize: function(tracks){
 this.tracks = [];
 this.options = arguments[1] || {};
 this.addTracks(tracks);
 },
 addTracks: function(tracks){
 tracks.each(function(track){
 var data = $H(track).values().first();
 this.tracks.push($H({
 ids: $H(track).keys().first(),
 effect: Effect.Morph,
 options: { style: data }
 }));
 }.bind(this));
 return this;
 },
 play: function(){
 return new Effect.Parallel(
 this.tracks.map(function(track){
 var elements = [$(track.ids) || $$(track.ids)].flatten();
 return elements.map(function(e){ return new track.effect(e, Object.extend({ sync:true }, track.options)) });
 }).flatten(),
 this.options
 );
 }
});

Element.CSS_PROPERTIES = $w(
 'backgroundColor backgroundPosition borderBottomColor borderBottomStyle ' + 
 'borderBottomWidth borderLeftColor borderLeftStyle borderLeftWidth ' +
 'borderRightColor borderRightStyle borderRightWidth borderSpacing ' +
 'borderTopColor borderTopStyle borderTopWidth bottom clip color ' +
 'fontSize fontWeight height left letterSpacing lineHeight ' +
 'marginBottom marginLeft marginRight marginTop markerOffset maxHeight '+
 'maxWidth minHeight minWidth opacity outlineColor outlineOffset ' +
 'outlineWidth paddingBottom paddingLeft paddingRight paddingTop ' +
 'right textIndent top width wordSpacing zIndex');
 
Element.CSS_LENGTH = /^(([\+\-]?[0-9\.]+)(em|ex|px|in|cm|mm|pt|pc|\%))|0$/;

String.prototype.parseStyle = function(){
 var element = document.createElement('div');
 element.innerHTML = '<div style="' + this + '"></div>';
 var style = element.childNodes[0].style, styleRules = $H();
 
 Element.CSS_PROPERTIES.each(function(property){
 if(style[property]) styleRules[property] = style[property]; 
 });
 if(Prototype.Browser.IE && this.indexOf('opacity') > -1) {
 styleRules.opacity = this.match(/opacity:\s*((?:0|1)?(?:\.\d*)?)/)[1];
 }
 return styleRules;
};

Element.morph = function(element, style) {
 new Effect.Morph(element, Object.extend({ style: style }, arguments[2] || {}));
 return element;
};

['getInlineOpacity','forceRerendering','setContentZoom',
 'collectTextNodes','collectTextNodesIgnoreClass','morph'].each( 
 function(f) { Element.Methods[f] = Element[f]; }
);

Element.Methods.visualEffect = function(element, effect, options) {
 s = effect.dasherize().camelize();
 effect_class = s.charAt(0).toUpperCase() + s.substring(1);
 new Effect[effect_class](element, options);
 return $(element);
};

Element.addMethods();
// script.aculo.us dragdrop.js v1.7.1_beta3, Fri May 25 17:19:41 +0200 2007

// Copyright (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
// (c) 2005-2007 Sammi Williams (http://www.oriontransfer.co.nz, sammi@oriontransfer.co.nz)
//
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/

if(typeof Effect == 'undefined')
 throw("dragdrop.js requires including script.aculo.us' effects.js library");

var Droppables = {
 drops: [],

 remove: function(element) {
 this.drops = this.drops.reject(function(d) { return d.element==$(element) });
 },

 add: function(element) {
 element = $(element);
 var options = Object.extend({
 greedy: true,
 hoverclass: null,
 tree: false
 }, arguments[1] || {});

 // cache containers
 if(options.containment) {
 options._containers = [];
 var containment = options.containment;
 if((typeof containment == 'object') &&
 (containment.constructor == Array)) {
 containment.each( function(c) { options._containers.push($(c)) });
 } else {
 options._containers.push($(containment));
 }
 }

 if(options.accept) options.accept = [options.accept].flatten();

 Element.makePositioned(element); // fix IE
 options.element = element;

 this.drops.push(options);
 },

 findDeepestChild: function(drops) {
 deepest = drops[0];

 for (i = 1; i < drops.length; ++i)
 if (Element.isParent(drops[i].element, deepest.element))
 deepest = drops[i];

 return deepest;
 },

 isContained: function(element, drop) {
 var containmentNode;
 if(drop.tree) {
 containmentNode = element.treeNode;
 } else {
 containmentNode = element.parentNode;
 }
 return drop._containers.detect(function(c) { return containmentNode == c });
 },

 isAffected: function(point, element, drop) {
 return (
 (drop.element!=element) &&
 ((!drop._containers) ||
 this.isContained(element, drop)) &&
 ((!drop.accept) ||
 (Element.classNames(element).detect(
 function(v) { return drop.accept.include(v) } ) )) &&
 Position.within(drop.element, point[0], point[1]) );
 },

 deactivate: function(drop) {
 if(drop.hoverclass)
 Element.removeClassName(drop.element, drop.hoverclass);
 this.last_active = null;
 },

 activate: function(drop) {
 if(drop.hoverclass)
 Element.addClassName(drop.element, drop.hoverclass);
 this.last_active = drop;
 },

 show: function(point, element) {
 if(!this.drops.length) return;
 var affected = [];

 if(this.last_active) this.deactivate(this.last_active);
 this.drops.each( function(drop) {
 if(Droppables.isAffected(point, element, drop))
 affected.push(drop);
 });

 if(affected.length>0) {
 drop = Droppables.findDeepestChild(affected);
 Position.within(drop.element, point[0], point[1]);
 if(drop.onHover)
 drop.onHover(element, drop.element, Position.overlap(drop.overlap, drop.element));

 Droppables.activate(drop);
 }
 },

 fire: function(event, element) {
 if(!this.last_active) return;
 Position.prepare();

 if (this.isAffected([Event.pointerX(event), Event.pointerY(event)], element, this.last_active))
 if (this.last_active.onDrop) {
 this.last_active.onDrop(element, this.last_active.element, event);
 return true;
 }
 },

 reset: function() {
 if(this.last_active)
 this.deactivate(this.last_active);
 }
}

var Draggables = {
 drags: [],
 observers: [],

 register: function(draggable) {
 if(this.drags.length == 0) {
 this.eventMouseUp = this.endDrag.bindAsEventListener(this);
 this.eventMouseMove = this.updateDrag.bindAsEventListener(this);
 this.eventKeypress = this.keyPress.bindAsEventListener(this);

 Event.observe(document, "mouseup", this.eventMouseUp);
 Event.observe(draggable.element, "mousemove", this.eventMouseMove);
 Event.observe(document, "keypress", this.eventKeypress);
 }
 this.drags.push(draggable);
 },

 unregister: function(draggable) {
 this.drags = this.drags.reject(function(d) { return d==draggable });
 if(this.drags.length == 0) {
 Event.stopObserving(document, "mouseup", this.eventMouseUp);
 Event.stopObserving(draggable.element, "mousemove", this.eventMouseMove);
 Event.stopObserving(document, "keypress", this.eventKeypress);
 }
 },

 activate: function(draggable) {
 if(draggable.options.delay) {
 this._timeout = setTimeout(function() {
 Draggables._timeout = null;
 window.focus();
 Draggables.activeDraggable = draggable;
 }.bind(this), draggable.options.delay);
 } else {
 window.focus(); // allows keypress events if window isn't currently focused, fails for Safari
 this.activeDraggable = draggable;
 }
 },

 deactivate: function() {
 this.activeDraggable = null;
 },

 updateDrag: function(event) {
 if(!this.activeDraggable) return;
 var pointer = [Event.pointerX(event), Event.pointerY(event)];
 // Mozilla-based browsers fire successive mousemove events with
 // the same coordinates, prevent needless redrawing (moz bug?)
 if(this._lastPointer && (this._lastPointer.inspect() == pointer.inspect())) return;
 this._lastPointer = pointer;

 this.activeDraggable.updateDrag(event, pointer);
 },

 endDrag: function(event) {
 if(this._timeout) {
 clearTimeout(this._timeout);
 this._timeout = null;
 }
 if(!this.activeDraggable) return;
 this._lastPointer = null;
 this.activeDraggable.endDrag(event);
 this.activeDraggable = null;
 },

 keyPress: function(event) {
 if(this.activeDraggable)
 this.activeDraggable.keyPress(event);
 },

 addObserver: function(observer) {
 this.observers.push(observer);
 this._cacheObserverCallbacks();
 },

 removeObserver: function(element) { // element instead of observer fixes mem leaks
 this.observers = this.observers.reject( function(o) { return o.element==element });
 this._cacheObserverCallbacks();
 },

 notify: function(eventName, draggable, event) { // 'onStart', 'onEnd', 'onDrag'
 if(this[eventName+'Count'] > 0)
 this.observers.each( function(o) {
 if(o[eventName]) o[eventName](eventName, draggable, event);
 });
 if(draggable.options[eventName]) draggable.options[eventName](draggable, event);
 },

 _cacheObserverCallbacks: function() {
 ['onStart','onEnd','onDrag'].each( function(eventName) {
 Draggables[eventName+'Count'] = Draggables.observers.select(
 function(o) { return o[eventName]; }
 ).length;
 });
 }
}

/*--------------------------------------------------------------------------*/

var Draggable = Class.create();
Draggable._dragging = {};

Draggable.prototype = {
 initialize: function(element) {
 var defaults = {
 handle: false,
 reverteffect: function(element, top_offset, left_offset) {
 var dur = Math.sqrt(Math.abs(top_offset^2)+Math.abs(left_offset^2))*0.02;
 new Effect.Move(element, { x: -left_offset, y: -top_offset, duration: dur,
 queue: {scope:'_draggable', position:'end'}
 });
 },
 endeffect: function(element) {
 var toOpacity = typeof element._opacity == 'number' ? element._opacity : 1.0;
 new Effect.Opacity(element, {duration:0.2, from:0.7, to:toOpacity,
 queue: {scope:'_draggable', position:'end'},
 afterFinish: function(){
 Draggable._dragging[element] = false
 }
 });
 },
 zindex: 1000,
 revert: false,
 quiet: false,
 scroll: false,
 scrollSensitivity: 20,
 scrollSpeed: 15,
 snap: false, // false, or xy or [x,y] or function(x,y){ return [x,y] }
 delay: 0
 };

 if(!arguments[1] || typeof arguments[1].endeffect == 'undefined')
 Object.extend(defaults, {
 starteffect: function(element) {
 element._opacity = Element.getOpacity(element);
 Draggable._dragging[element] = true;
 new Effect.Opacity(element, {duration:0.2, from:element._opacity, to:0.7});
 }
 });

 var options = Object.extend(defaults, arguments[1] || {});

 this.element = $(element);

 if(options.handle && (typeof options.handle == 'string'))
 this.handle = this.element.down('.'+options.handle, 0);

 if(!this.handle) this.handle = $(options.handle);
 if(!this.handle) this.handle = this.element;

 if(options.scroll && !options.scroll.scrollTo && !options.scroll.outerHTML) {
 options.scroll = $(options.scroll);
 this._isScrollChild = Element.childOf(this.element, options.scroll);
 }

 Element.makePositioned(this.element); // fix IE

 this.delta = this.currentDelta();
 this.options = options;
 this.dragging = false;

 this.eventMouseDown = this.initDrag.bindAsEventListener(this);
 Event.observe(this.handle, "mousedown", this.eventMouseDown);

 Draggables.register(this);
 },

 destroy: function() {
 Event.stopObserving(this.handle, "mousedown", this.eventMouseDown);
 Draggables.unregister(this);
 },

 currentDelta: function() {
 return([
 parseInt(Element.getStyle(this.element,'left') || '0'),
 parseInt(Element.getStyle(this.element,'top') || '0')]);
 },

 initDrag: function(event) {
 if(typeof Draggable._dragging[this.element] != 'undefined' &&
 Draggable._dragging[this.element]) return;
 if(Event.isLeftClick(event)) {
 // abort on form elements, fixes a Firefox issue
 var src = Event.element(event);
 if((tag_name = src.tagName.toUpperCase()) && (
 tag_name=='INPUT' ||
 tag_name=='SELECT' ||
 tag_name=='OPTION' ||
 tag_name=='BUTTON' ||
 tag_name=='TEXTAREA')) return;

 var pointer = [Event.pointerX(event), Event.pointerY(event)];
 var pos = Position.cumulativeOffset(this.element);
 this.offset = [0,1].map( function(i) { return (pointer[i] - pos[i]) });

 Draggables.activate(this);
 Event.stop(event);
 }
 },

 startDrag: function(event) {
 this.dragging = true;

 if(this.options.zindex) {
 this.originalZ = parseInt(Element.getStyle(this.element,'z-index') || 0);
 this.element.style.zIndex = this.options.zindex;
 }

 if(this.options.ghosting) {
 this._clone = this.element.cloneNode(true);
 Position.absolutize(this.element);
 this.element.parentNode.insertBefore(this._clone, this.element);
 }

 if(this.options.scroll) {
 if (this.options.scroll == window) {
 var where = this._getWindowScroll(this.options.scroll);
 this.originalScrollLeft = where.left;
 this.originalScrollTop = where.top;
 } else {
 this.originalScrollLeft = this.options.scroll.scrollLeft;
 this.originalScrollTop = this.options.scroll.scrollTop;
 }
 }

 Draggables.notify('onStart', this, event);

 if(this.options.starteffect) this.options.starteffect(this.element);
 },

 updateDrag: function(event, pointer) {
 if(!this.dragging) this.startDrag(event);

 if(!this.options.quiet){
 Position.prepare();
 Droppables.show(pointer, this.element);
 }

 Draggables.notify('onDrag', this, event);

 this.draw(pointer);
 if(this.options.change) this.options.change(this);

 if(this.options.scroll) {
 this.stopScrolling();

 var p;
 if (this.options.scroll == window) {
 with(this._getWindowScroll(this.options.scroll)) { p = [ left, top, left+width, top+height ]; }
 } else {
 p = Position.page(this.options.scroll);
 p[0] += this.options.scroll.scrollLeft + Position.deltaX;
 p[1] += this.options.scroll.scrollTop + Position.deltaY;
 p.push(p[0]+this.options.scroll.offsetWidth);
 p.push(p[1]+this.options.scroll.offsetHeight);
 }
 var speed = [0,0];
 if(pointer[0] < (p[0]+this.options.scrollSensitivity)) speed[0] = pointer[0]-(p[0]+this.options.scrollSensitivity);
 if(pointer[1] < (p[1]+this.options.scrollSensitivity)) speed[1] = pointer[1]-(p[1]+this.options.scrollSensitivity);
 if(pointer[0] > (p[2]-this.options.scrollSensitivity)) speed[0] = pointer[0]-(p[2]-this.options.scrollSensitivity);
 if(pointer[1] > (p[3]-this.options.scrollSensitivity)) speed[1] = pointer[1]-(p[3]-this.options.scrollSensitivity);
 this.startScrolling(speed);
 }

 // fix AppleWebKit rendering
 if(Prototype.Browser.WebKit) window.scrollBy(0,0);

 Event.stop(event);
 },

 finishDrag: function(event, success) {
 this.dragging = false;

 if(this.options.quiet){
 Position.prepare();
 var pointer = [Event.pointerX(event), Event.pointerY(event)];
 Droppables.show(pointer, this.element);
 }

 if(this.options.ghosting) {
 Position.relativize(this.element);
 Element.remove(this._clone);
 this._clone = null;
 }

 var dropped = false;
 if(success) {
 dropped = Droppables.fire(event, this.element);
 if (!dropped) dropped = false;
 }
 if(dropped && this.options.onDropped) this.options.onDropped(this.element);
 Draggables.notify('onEnd', this, event);

 var revert = this.options.revert;
 if(revert && typeof revert == 'function') revert = revert(this.element);

 var d = this.currentDelta();
 if(revert && this.options.reverteffect) {
 if (dropped == 0 || revert != 'failure')
 this.options.reverteffect(this.element,
 d[1]-this.delta[1], d[0]-this.delta[0]);
 } else {
 this.delta = d;
 }

 if(this.options.zindex)
 this.element.style.zIndex = this.originalZ;

 if(this.options.endeffect)
 this.options.endeffect(this.element);

 Draggables.deactivate(this);
 Droppables.reset();
 },

 keyPress: function(event) {
 if(event.keyCode!=Event.KEY_ESC) return;
 this.finishDrag(event, false);
 Event.stop(event);
 },

 endDrag: function(event) {
 if(!this.dragging) return;
 this.stopScrolling();
 this.finishDrag(event, true);
 Event.stop(event);
 },

 draw: function(point) {
 var pos = Position.cumulativeOffset(this.element);
 if(this.options.ghosting) {
 var r = Position.realOffset(this.element);
 pos[0] += r[0] - Position.deltaX; pos[1] += r[1] - Position.deltaY;
 }

 var d = this.currentDelta();
 pos[0] -= d[0]; pos[1] -= d[1];

 if(this.options.scroll && (this.options.scroll != window && this._isScrollChild)) {
 pos[0] -= this.options.scroll.scrollLeft-this.originalScrollLeft;
 pos[1] -= this.options.scroll.scrollTop-this.originalScrollTop;
 }

 var p = [0,1].map(function(i){
 return (point[i]-pos[i]-this.offset[i])
 }.bind(this));

 if(this.options.snap) {
 if(typeof this.options.snap == 'function') {
 p = this.options.snap(p[0],p[1],this);
 } else {
 if(this.options.snap instanceof Array) {
 p = p.map( function(v, i) {
 return Math.round(v/this.options.snap[i])*this.options.snap[i] }.bind(this))
 } else {
 p = p.map( function(v) {
 return Math.round(v/this.options.snap)*this.options.snap }.bind(this))
 }
 }}

 var style = this.element.style;
 if((!this.options.constraint) || (this.options.constraint=='horizontal'))
 style.left = p[0] + "px";
 if((!this.options.constraint) || (this.options.constraint=='vertical'))
 style.top = p[1] + "px";

 if(style.visibility=="hidden") style.visibility = ""; // fix gecko rendering
 },

 stopScrolling: function() {
 if(this.scrollInterval) {
 clearInterval(this.scrollInterval);
 this.scrollInterval = null;
 Draggables._lastScrollPointer = null;
 }
 },

 startScrolling: function(speed) {
 if(!(speed[0] || speed[1])) return;
 this.scrollSpeed = [speed[0]*this.options.scrollSpeed,speed[1]*this.options.scrollSpeed];
 this.lastScrolled = new Date();
 this.scrollInterval = setInterval(this.scroll.bind(this), 10);
 },

 scroll: function() {
 var current = new Date();
 var delta = current - this.lastScrolled;
 this.lastScrolled = current;
 if(this.options.scroll == window) {
 with (this._getWindowScroll(this.options.scroll)) {
 if (this.scrollSpeed[0] || this.scrollSpeed[1]) {
 var d = delta / 1000;
 this.options.scroll.scrollTo( left + d*this.scrollSpeed[0], top + d*this.scrollSpeed[1] );
 }
 }
 } else {
 this.options.scroll.scrollLeft += this.scrollSpeed[0] * delta / 1000;
 this.options.scroll.scrollTop += this.scrollSpeed[1] * delta / 1000;
 }

 Position.prepare();
 Droppables.show(Draggables._lastPointer, this.element);
 Draggables.notify('onDrag', this);
 if (this._isScrollChild) {
 Draggables._lastScrollPointer = Draggables._lastScrollPointer || $A(Draggables._lastPointer);
 Draggables._lastScrollPointer[0] += this.scrollSpeed[0] * delta / 1000;
 Draggables._lastScrollPointer[1] += this.scrollSpeed[1] * delta / 1000;
 if (Draggables._lastScrollPointer[0] < 0)
 Draggables._lastScrollPointer[0] = 0;
 if (Draggables._lastScrollPointer[1] < 0)
 Draggables._lastScrollPointer[1] = 0;
 this.draw(Draggables._lastScrollPointer);
 }

 if(this.options.change) this.options.change(this);
 },

 _getWindowScroll: function(w) {
 var T, L, W, H;
 with (w.document) {
 if (w.document.documentElement && documentElement.scrollTop) {
 T = documentElement.scrollTop;
 L = documentElement.scrollLeft;
 } else if (w.document.body) {
 T = body.scrollTop;
 L = body.scrollLeft;
 }
 if (w.innerWidth) {
 W = w.innerWidth;
 H = w.innerHeight;
 } else if (w.document.documentElement && documentElement.clientWidth) {
 W = documentElement.clientWidth;
 H = documentElement.clientHeight;
 } else {
 W = body.offsetWidth;
 H = body.offsetHeight
 }
 }
 return { top: T, left: L, width: W, height: H };
 }
}

/*--------------------------------------------------------------------------*/

var SortableObserver = Class.create();
SortableObserver.prototype = {
 initialize: function(element, observer) {
 this.element = $(element);
 this.observer = observer;
 this.lastValue = Sortable.serialize(this.element);
 },

 onStart: function() {
 this.lastValue = Sortable.serialize(this.element);
 },

 onEnd: function() {
 Sortable.unmark();
 if(this.lastValue != Sortable.serialize(this.element))
 this.observer(this.element)
 }
}

var Sortable = {
 SERIALIZE_RULE: /^[^_\-](?:[A-Za-z0-9\-\_]*)[_](.*)$/,

 sortables: {},

 _findRootElement: function(element) {
 while (element.tagName.toUpperCase() != "BODY") {
 if(element.id && Sortable.sortables[element.id]) return element;
 element = element.parentNode;
 }
 },

 options: function(element) {
 element = Sortable._findRootElement($(element));
 if(!element) return;
 return Sortable.sortables[element.id];
 },

 destroy: function(element){
 var s = Sortable.options(element);

 if(s) {
 Draggables.removeObserver(s.element);
 s.droppables.each(function(d){ Droppables.remove(d) });
 s.draggables.invoke('destroy');

 delete Sortable.sortables[s.element.id];
 }
 },

 create: function(element) {
 element = $(element);
 var options = Object.extend({
 element: element,
 tag: 'li', // assumes li children, override with tag: 'tagname'
 dropOnEmpty: false,
 tree: false,
 treeTag: 'ul',
 overlap: 'vertical', // one of 'vertical', 'horizontal'
 constraint: 'vertical', // one of 'vertical', 'horizontal', false
 containment: element, // also takes array of elements (or id's); or false
 handle: false, // or a CSS class
 only: false,
 delay: 0,
 hoverclass: null,
 ghosting: false,
 quiet: false,
 scroll: false,
 scrollSensitivity: 20,
 scrollSpeed: 15,
 format: this.SERIALIZE_RULE,

 // these take arrays of elements or ids and can be
 // used for better initialization performance
 elements: false,
 handles: false,

 onChange: Prototype.emptyFunction,
 onUpdate: Prototype.emptyFunction
 }, arguments[1] || {});

 // clear any old sortable with same element
 this.destroy(element);

 // build options for the draggables
 var options_for_draggable = {
 revert: true,
 quiet: options.quiet,
 scroll: options.scroll,
 scrollSpeed: options.scrollSpeed,
 scrollSensitivity: options.scrollSensitivity,
 delay: options.delay,
 ghosting: options.ghosting,
 constraint: options.constraint,
 handle: options.handle };

 if(options.starteffect)
 options_for_draggable.starteffect = options.starteffect;

 if(options.reverteffect)
 options_for_draggable.reverteffect = options.reverteffect;
 else
 if(options.ghosting) options_for_draggable.reverteffect = function(element) {
 element.style.top = 0;
 element.style.left = 0;
 };

 if(options.endeffect)
 options_for_draggable.endeffect = options.endeffect;

 if(options.zindex)
 options_for_draggable.zindex = options.zindex;

 // build options for the droppables
 var options_for_droppable = {
 overlap: options.overlap,
 containment: options.containment,
 tree: options.tree,
 hoverclass: options.hoverclass,
 onHover: Sortable.onHover
 }

 var options_for_tree = {
 onHover: Sortable.onEmptyHover,
 overlap: options.overlap,
 containment: options.containment,
 hoverclass: options.hoverclass
 }

 // fix for gecko engine
 Element.cleanWhitespace(element);

 options.draggables = [];
 options.droppables = [];

 // drop on empty handling
 if(options.dropOnEmpty || options.tree) {
 Droppables.add(element, options_for_tree);
 options.droppables.push(element);
 }

 (options.elements || this.findElements(element, options) || []).each( function(e,i) {
 var handle = options.handles ? $(options.handles[i]) :
 (options.handle ? $(e).getElementsByClassName(options.handle)[0] : e);
 options.draggables.push(
 new Draggable(e, Object.extend(options_for_draggable, { handle: handle })));
 Droppables.add(e, options_for_droppable);
 if(options.tree) e.treeNode = element;
 options.droppables.push(e);
 });

 if(options.tree) {
 (Sortable.findTreeElements(element, options) || []).each( function(e) {
 Droppables.add(e, options_for_tree);
 e.treeNode = element;
 options.droppables.push(e);
 });
 }

 // keep reference
 this.sortables[element.id] = options;

 // for onupdate
 Draggables.addObserver(new SortableObserver(element, options.onUpdate));

 },

 // return all suitable-for-sortable elements in a guaranteed order
 findElements: function(element, options) {
 return Element.findChildren(
 element, options.only, options.tree ? true : false, options.tag);
 },

 findTreeElements: function(element, options) {
 return Element.findChildren(
 element, options.only, options.tree ? true : false, options.treeTag);
 },

 onHover: function(element, dropon, overlap) {
 if(Element.isParent(dropon, element)) return;

 if(overlap > .33 && overlap < .66 && Sortable.options(dropon).tree) {
 return;
 } else if(overlap>0.5) {
 Sortable.mark(dropon, 'before');
 if(dropon.previousSibling != element) {
 var oldParentNode = element.parentNode;
 element.style.visibility = "hidden"; // fix gecko rendering
 dropon.parentNode.insertBefore(element, dropon);
 if(dropon.parentNode!=oldParentNode)
 Sortable.options(oldParentNode).onChange(element);
 Sortable.options(dropon.parentNode).onChange(element);
 }
 } else {
 Sortable.mark(dropon, 'after');
 var nextElement = dropon.nextSibling || null;
 if(nextElement != element) {
 var oldParentNode = element.parentNode;
 element.style.visibility = "hidden"; // fix gecko rendering
 dropon.parentNode.insertBefore(element, nextElement);
 if(dropon.parentNode!=oldParentNode)
 Sortable.options(oldParentNode).onChange(element);
 Sortable.options(dropon.parentNode).onChange(element);
 }
 }
 },

 onEmptyHover: function(element, dropon, overlap) {
 var oldParentNode = element.parentNode;
 var droponOptions = Sortable.options(dropon);

 if(!Element.isParent(dropon, element)) {
 var index;

 var children = Sortable.findElements(dropon, {tag: droponOptions.tag, only: droponOptions.only});
 var child = null;

 if(children) {
 var offset = Element.offsetSize(dropon, droponOptions.overlap) * (1.0 - overlap);

 for (index = 0; index < children.length; index += 1) {
 if (offset - Element.offsetSize (children[index], droponOptions.overlap) >= 0) {
 offset -= Element.offsetSize (children[index], droponOptions.overlap);
 } else if (offset - (Element.offsetSize (children[index], droponOptions.overlap) / 2) >= 0) {
 child = index + 1 < children.length ? children[index + 1] : null;
 break;
 } else {
 child = children[index];
 break;
 }
 }
 }

 dropon.insertBefore(element, child);

 Sortable.options(oldParentNode).onChange(element);
 droponOptions.onChange(element);
 }
 },

 unmark: function() {
 if(Sortable._marker) Sortable._marker.hide();
 },

 mark: function(dropon, position) {
 // mark on ghosting only
 var sortable = Sortable.options(dropon.parentNode);
 if(sortable && !sortable.ghosting) return;

 if(!Sortable._marker) {
 Sortable._marker =
 ($('dropmarker') || Element.extend(document.createElement('DIV'))).
 hide().addClassName('dropmarker').setStyle({position:'absolute'});
 document.getElementsByTagName("body").item(0).appendChild(Sortable._marker);
 }
 var offsets = Position.cumulativeOffset(dropon);
 Sortable._marker.setStyle({left: offsets[0]+'px', top: offsets[1] + 'px'});

 if(position=='after')
 if(sortable.overlap == 'horizontal')
 Sortable._marker.setStyle({left: (offsets[0]+dropon.clientWidth) + 'px'});
 else
 Sortable._marker.setStyle({top: (offsets[1]+dropon.clientHeight) + 'px'});

 Sortable._marker.show();
 },

 _tree: function(element, options, parent) {
 var children = Sortable.findElements(element, options) || [];

 for (var i = 0; i < children.length; ++i) {
 var match = children[i].id.match(options.format);

 if (!match) continue;

 var child = {
 id: encodeURIComponent(match ? match[1] : null),
 element: element,
 parent: parent,
 children: [],
 position: parent.children.length,
 container: $(children[i]).down(options.treeTag)
 }

 /* Get the element containing the children and recurse over it */
 if (child.container)
 this._tree(child.container, options, child)

 parent.children.push (child);
 }

 return parent;
 },

 tree: function(element) {
 element = $(element);
 var sortableOptions = this.options(element);
 var options = Object.extend({
 tag: sortableOptions.tag,
 treeTag: sortableOptions.treeTag,
 only: sortableOptions.only,
 name: element.id,
 format: sortableOptions.format
 }, arguments[1] || {});

 var root = {
 id: null,
 parent: null,
 children: [],
 container: element,
 position: 0
 }

 return Sortable._tree(element, options, root);
 },

 /* Construct a [i] index for a particular node */
 _constructIndex: function(node) {
 var index = '';
 do {
 if (node.id) index = '[' + node.position + ']' + index;
 } while ((node = node.parent) != null);
 return index;
 },

 sequence: function(element) {
 element = $(element);
 var options = Object.extend(this.options(element), arguments[1] || {});

 return $(this.findElements(element, options) || []).map( function(item) {
 return item.id.match(options.format) ? item.id.match(options.format)[1] : '';
 });
 },

 setSequence: function(element, new_sequence) {
 element = $(element);
 var options = Object.extend(this.options(element), arguments[2] || {});

 var nodeMap = {};
 this.findElements(element, options).each( function(n) {
 if (n.id.match(options.format))
 nodeMap[n.id.match(options.format)[1]] = [n, n.parentNode];
 n.parentNode.removeChild(n);
 });

 new_sequence.each(function(ident) {
 var n = nodeMap[ident];
 if (n) {
 n[1].appendChild(n[0]);
 delete nodeMap[ident];
 }
 });
 },

 serialize: function(element) {
 element = $(element);
 var options = Object.extend(Sortable.options(element), arguments[1] || {});
 var name = encodeURIComponent(
 (arguments[1] && arguments[1].name) ? arguments[1].name : element.id);

 if (options.tree) {
 return Sortable.tree(element, arguments[1]).children.map( function (item) {
 return [name + Sortable._constructIndex(item) + "[id]=" +
 encodeURIComponent(item.id)].concat(item.children.map(arguments.callee));
 }).flatten().join('&');
 } else {
 return Sortable.sequence(element, arguments[1]).map( function(item) {
 return name + "[]=" + encodeURIComponent(item);
 }).join('&');
 }
 }
}

// Returns true if child is contained within element
Element.isParent = function(child, element) {
 if (!child.parentNode || child == element) return false;
 if (child.parentNode == element) return true;
 return Element.isParent(child.parentNode, element);
}

Element.findChildren = function(element, only, recursive, tagName) {
 if(!element.hasChildNodes()) return null;
 tagName = tagName.toUpperCase();
 if(only) only = [only].flatten();
 var elements = [];
 $A(element.childNodes).each( function(e) {
 if(e.tagName && e.tagName.toUpperCase()==tagName &&
 (!only || (Element.classNames(e).detect(function(v) { return only.include(v) }))))
 elements.push(e);
 if(recursive) {
 var grandchildren = Element.findChildren(e, only, recursive, tagName);
 if(grandchildren) elements.push(grandchildren);
 }
 });

 return (elements.length>0 ? elements.flatten() : []);
}

Element.offsetSize = function (element, type) {
 return element['offset' + ((type=='vertical' || type=='height') ? 'Height' : 'Width')];
}

// script.aculo.us controls.js v1.7.1_beta3, Fri May 25 17:19:41 +0200 2007

// Copyright (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
// (c) 2005-2007 Ivan Krstic (http://blogs.law.harvard.edu/ivan)
// (c) 2005-2007 Jon Tirsen (http://www.tirsen.com)
// Contributors:
// Richard Livsey
// Rahul Bhargava
// Rob Wills
// 
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/

// Autocompleter.Base handles all the autocompletion functionality 
// that's independent of the data source for autocompletion. This
// includes drawing the autocompletion menu, observing keyboard
// and mouse events, and similar.
//
// Specific autocompleters need to provide, at the very least, 
// a getUpdatedChoices function that will be invoked every time
// the text inside the monitored textbox changes. This method 
// should get the text for which to provide autocompletion by
// invoking this.getToken(), NOT by directly accessing
// this.element.value. This is to allow incremental tokenized
// autocompletion. Specific auto-completion logic (AJAX, etc)
// belongs in getUpdatedChoices.
//
// Tokenized incremental autocompletion is enabled automatically
// when an autocompleter is instantiated with the 'tokens' option
// in the options parameter, e.g.:
// new Ajax.Autocompleter('id','upd', '/url/', { tokens: ',' });
// will incrementally autocomplete with a comma as the token.
// Additionally, ',' in the above example can be replaced with
// a token array, e.g. { tokens: [',', '\n'] } which
// enables autocompletion on multiple tokens. This is most 
// useful when one of the tokens is \n (a newline), as it 
// allows smart autocompletion after linebreaks.

if(typeof Effect == 'undefined')
 throw("controls.js requires including script.aculo.us' effects.js library");

var Autocompleter = {}
Autocompleter.Base = function() {};
Autocompleter.Base.prototype = {
 baseInitialize: function(element, update, options) {
 element = $(element)
 this.element = element; 
 this.update = $(update); 
 this.hasFocus = false; 
 this.changed = false; 
 this.active = false; 
 this.index = 0; 
 this.entryCount = 0;

 if(this.setOptions)
 this.setOptions(options);
 else
 this.options = options || {};

 this.options.paramName = this.options.paramName || this.element.name;
 this.options.tokens = this.options.tokens || [];
 this.options.frequency = this.options.frequency || 0.4;
 this.options.minChars = this.options.minChars || 1;
 this.options.onShow = this.options.onShow || 
 function(element, update){ 
 if(!update.style.position || update.style.position=='absolute') {
 update.style.position = 'absolute';
 Position.clone(element, update, {
 setHeight: false, 
 offsetTop: element.offsetHeight
 });
 }
 Effect.Appear(update,{duration:0.15});
 };
 this.options.onHide = this.options.onHide || 
 function(element, update){ new Effect.Fade(update,{duration:0.15}) };

 if(typeof(this.options.tokens) == 'string') 
 this.options.tokens = new Array(this.options.tokens);

 this.observer = null;
 
 this.element.setAttribute('autocomplete','off');

 Element.hide(this.update);

 Event.observe(this.element, 'blur', this.onBlur.bindAsEventListener(this));
 Event.observe(this.element, 'keypress', this.onKeyPress.bindAsEventListener(this));

 // Turn autocomplete back on when the user leaves the page, so that the
 // field's value will be remembered on Mozilla-based browsers.
 Event.observe(window, 'beforeunload', function(){ 
 element.setAttribute('autocomplete', 'on'); 
 });
 },

 show: function() {
 if(Element.getStyle(this.update, 'display')=='none') this.options.onShow(this.element, this.update);
 if(!this.iefix && 
 (Prototype.Browser.IE) &&
 (Element.getStyle(this.update, 'position')=='absolute')) {
 new Insertion.After(this.update, 
 '<iframe id="' + this.update.id + '_iefix" '+
 'style="display:none;position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);" ' +
 'src="javascript:false;" frameborder="0" scrolling="no"></iframe>');
 this.iefix = $(this.update.id+'_iefix');
 }
 if(this.iefix) setTimeout(this.fixIEOverlapping.bind(this), 50);
 },
 
 fixIEOverlapping: function() {
 Position.clone(this.update, this.iefix, {setTop:(!this.update.style.height)});
 this.iefix.style.zIndex = 1;
 this.update.style.zIndex = 2;
 Element.show(this.iefix);
 },

 hide: function() {
 this.stopIndicator();
 if(Element.getStyle(this.update, 'display')!='none') this.options.onHide(this.element, this.update);
 if(this.iefix) Element.hide(this.iefix);
 },

 startIndicator: function() {
 if(this.options.indicator) Element.show(this.options.indicator);
 },

 stopIndicator: function() {
 if(this.options.indicator) Element.hide(this.options.indicator);
 },

 onKeyPress: function(event) {
 if(this.active)
 switch(event.keyCode) {
 case Event.KEY_TAB:
 case Event.KEY_RETURN:
 this.selectEntry();
 Event.stop(event);
 case Event.KEY_ESC:
 this.hide();
 this.active = false;
 Event.stop(event);
 return;
 case Event.KEY_LEFT:
 case Event.KEY_RIGHT:
 return;
 case Event.KEY_UP:
 this.markPrevious();
 this.render();
 if(Prototype.Browser.WebKit) Event.stop(event);
 return;
 case Event.KEY_DOWN:
 this.markNext();
 this.render();
 if(Prototype.Browser.WebKit) Event.stop(event);
 return;
 }
 else 
 if(event.keyCode==Event.KEY_TAB || event.keyCode==Event.KEY_RETURN || 
 (Prototype.Browser.WebKit > 0 && event.keyCode == 0)) return;

 this.changed = true;
 this.hasFocus = true;

 if(this.observer) clearTimeout(this.observer);
 this.observer = 
 setTimeout(this.onObserverEvent.bind(this), this.options.frequency*1000);
 },

 activate: function() {
 this.changed = false;
 this.hasFocus = true;
 this.getUpdatedChoices();
 },

 onHover: function(event) {
 var element = Event.findElement(event, 'LI');
 if(this.index != element.autocompleteIndex) 
 {
 this.index = element.autocompleteIndex;
 this.render();
 }
 Event.stop(event);
 },
 
 onClick: function(event) {
 var element = Event.findElement(event, 'LI');
 this.index = element.autocompleteIndex;
 this.selectEntry();
 this.hide();
 },
 
 onBlur: function(event) {
 // needed to make click events working
 setTimeout(this.hide.bind(this), 250);
 this.hasFocus = false;
 this.active = false; 
 }, 
 
 render: function() {
 if(this.entryCount > 0) {
 for (var i = 0; i < this.entryCount; i++)
 this.index==i ? 
 Element.addClassName(this.getEntry(i),"selected") : 
 Element.removeClassName(this.getEntry(i),"selected");
 if(this.hasFocus) { 
 this.show();
 this.active = true;
 }
 } else {
 this.active = false;
 this.hide();
 }
 },
 
 markPrevious: function() {
 if(this.index > 0) this.index--
 else this.index = this.entryCount-1;
 this.getEntry(this.index).scrollIntoView(true);
 },
 
 markNext: function() {
 if(this.index < this.entryCount-1) this.index++
 else this.index = 0;
 this.getEntry(this.index).scrollIntoView(false);
 },
 
 getEntry: function(index) {
 return this.update.firstChild.childNodes[index];
 },
 
 getCurrentEntry: function() {
 return this.getEntry(this.index);
 },
 
 selectEntry: function() {
 this.active = false;
 this.updateElement(this.getCurrentEntry());
 },

 updateElement: function(selectedElement) {
 if (this.options.updateElement) {
 this.options.updateElement(selectedElement);
 return;
 }
 var value = '';
 if (this.options.select) {
 var nodes = document.getElementsByClassName(this.options.select, selectedElement) || [];
 if(nodes.length>0) value = Element.collectTextNodes(nodes[0], this.options.select);
 } else
 value = Element.collectTextNodesIgnoreClass(selectedElement, 'informal');
 
 var lastTokenPos = this.findLastToken();
 if (lastTokenPos != -1) {
 var newValue = this.element.value.substr(0, lastTokenPos + 1);
 var whitespace = this.element.value.substr(lastTokenPos + 1).match(/^\s+/);
 if (whitespace)
 newValue += whitespace[0];
 this.element.value = newValue + value;
 } else {
 this.element.value = value;
 }
 this.element.focus();
 
 if (this.options.afterUpdateElement)
 this.options.afterUpdateElement(this.element, selectedElement);
 },

 updateChoices: function(choices) {
 if(!this.changed && this.hasFocus) {
 this.update.innerHTML = choices;
 Element.cleanWhitespace(this.update);
 Element.cleanWhitespace(this.update.down());

 if(this.update.firstChild && this.update.down().childNodes) {
 this.entryCount = 
 this.update.down().childNodes.length;
 for (var i = 0; i < this.entryCount; i++) {
 var entry = this.getEntry(i);
 entry.autocompleteIndex = i;
 this.addObservers(entry);
 }
 } else { 
 this.entryCount = 0;
 }

 this.stopIndicator();
 this.index = 0;
 
 if(this.entryCount==1 && this.options.autoSelect) {
 this.selectEntry();
 this.hide();
 } else {
 this.render();
 }
 }
 },

 addObservers: function(element) {
 Event.observe(element, "mouseover", this.onHover.bindAsEventListener(this));
 Event.observe(element, "click", this.onClick.bindAsEventListener(this));
 },

 onObserverEvent: function() {
 this.changed = false; 
 if(this.getToken().length>=this.options.minChars) {
 this.getUpdatedChoices();
 } else {
 this.active = false;
 this.hide();
 }
 },

 getToken: function() {
 var tokenPos = this.findLastToken();
 if (tokenPos != -1)
 var ret = this.element.value.substr(tokenPos + 1).replace(/^\s+/,'').replace(/\s+$/,'');
 else
 var ret = this.element.value;

 return /\n/.test(ret) ? '' : ret;
 },

 findLastToken: function() {
 var lastTokenPos = -1;

 for (var i=0; i<this.options.tokens.length; i++) {
 var thisTokenPos = this.element.value.lastIndexOf(this.options.tokens[i]);
 if (thisTokenPos > lastTokenPos)
 lastTokenPos = thisTokenPos;
 }
 return lastTokenPos;
 }
}

Ajax.Autocompleter = Class.create();
Object.extend(Object.extend(Ajax.Autocompleter.prototype, Autocompleter.Base.prototype), {
 initialize: function(element, update, url, options) {
 this.baseInitialize(element, update, options);
 this.options.asynchronous = true;
 this.options.onComplete = this.onComplete.bind(this);
 this.options.defaultParams = this.options.parameters || null;
 this.url = url;
 },

 getUpdatedChoices: function() {
 this.startIndicator();
 
 var entry = encodeURIComponent(this.options.paramName) + '=' + 
 encodeURIComponent(this.getToken());

 this.options.parameters = this.options.callback ?
 this.options.callback(this.element, entry) : entry;

 if(this.options.defaultParams) 
 this.options.parameters += '&' + this.options.defaultParams;
 
 new Ajax.Request(this.url, this.options);
 },

 onComplete: function(request) {
 this.updateChoices(request.responseText);
 }

});

// The local array autocompleter. Used when you'd prefer to
// inject an array of autocompletion options into the page, rather
// than sending out Ajax queries, which can be quite slow sometimes.
//
// The constructor takes four parameters. The first two are, as usual,
// the id of the monitored textbox, and id of the autocompletion menu.
// The third is the array you want to autocomplete from, and the fourth
// is the options block.
//
// Extra local autocompletion options:
// - choices - How many autocompletion choices to offer
//
// - partialSearch - If false, the autocompleter will match entered
// text only at the beginning of strings in the 
// autocomplete array. Defaults to true, which will
// match text at the beginning of any *word* in the
// strings in the autocomplete array. If you want to
// search anywhere in the string, additionally set
// the option fullSearch to true (default: off).
//
// - fullSsearch - Search anywhere in autocomplete array strings.
//
// - partialChars - How many characters to enter before triggering
// a partial match (unlike minChars, which defines
// how many characters are required to do any match
// at all). Defaults to 2.
//
// - ignoreCase - Whether to ignore case when autocompleting.
// Defaults to true.
//
// It's possible to pass in a custom function as the 'selector' 
// option, if you prefer to write your own autocompletion logic.
// In that case, the other options above will not apply unless
// you support them.

Autocompleter.Local = Class.create();
Autocompleter.Local.prototype = Object.extend(new Autocompleter.Base(), {
 initialize: function(element, update, array, options) {
 this.baseInitialize(element, update, options);
 this.options.array = array;
 },

 getUpdatedChoices: function() {
 this.updateChoices(this.options.selector(this));
 },

 setOptions: function(options) {
 this.options = Object.extend({
 choices: 10,
 partialSearch: true,
 partialChars: 2,
 ignoreCase: true,
 fullSearch: false,
 selector: function(instance) {
 var ret = []; // Beginning matches
 var partial = []; // Inside matches
 var entry = instance.getToken();
 var count = 0;

 for (var i = 0; i < instance.options.array.length && 
 ret.length < instance.options.choices ; i++) { 

 var elem = instance.options.array[i];
 var foundPos = instance.options.ignoreCase ? 
 elem.toLowerCase().indexOf(entry.toLowerCase()) : 
 elem.indexOf(entry);

 while (foundPos != -1) {
 if (foundPos == 0 && elem.length != entry.length) { 
 ret.push("<li><strong>" + elem.substr(0, entry.length) + "</strong>" + 
 elem.substr(entry.length) + "</li>");
 break;
 } else if (entry.length >= instance.options.partialChars && 
 instance.options.partialSearch && foundPos != -1) {
 if (instance.options.fullSearch || /\s/.test(elem.substr(foundPos-1,1))) {
 partial.push("<li>" + elem.substr(0, foundPos) + "<strong>" +
 elem.substr(foundPos, entry.length) + "</strong>" + elem.substr(
 foundPos + entry.length) + "</li>");
 break;
 }
 }

 foundPos = instance.options.ignoreCase ? 
 elem.toLowerCase().indexOf(entry.toLowerCase(), foundPos + 1) : 
 elem.indexOf(entry, foundPos + 1);

 }
 }
 if (partial.length)
 ret = ret.concat(partial.slice(0, instance.options.choices - ret.length))
 return "<ul>" + ret.join('') + "</ul>";
 }
 }, options || {});
 }
});

// AJAX in-place editor
//
// see documentation on http://wiki.script.aculo.us/scriptaculous/show/Ajax.InPlaceEditor

// Use this if you notice weird scrolling problems on some browsers,
// the DOM might be a bit confused when this gets called so do this
// waits 1 ms (with setTimeout) until it does the activation
Field.scrollFreeActivate = function(field) {
 setTimeout(function() {
 Field.activate(field);
 }, 1);
}

Ajax.InPlaceEditor = Class.create();
Ajax.InPlaceEditor.defaultHighlightColor = "#FFFF99";
Ajax.InPlaceEditor.prototype = {
 initialize: function(element, url, options) {
 this.url = url;
 this.element = $(element);

 this.options = Object.extend({
 paramName: "value",
 okButton: true,
 okLink: false,
 okText: "ok",
 cancelButton: false,
 cancelLink: true,
 cancelText: "cancel",
 textBeforeControls: '',
 textBetweenControls: '',
 textAfterControls: '',
 savingText: "Saving...",
 clickToEditText: "Click to edit",
 okText: "ok",
 rows: 1,
 onComplete: function(transport, element) {
 new Effect.Highlight(element, {startcolor: this.options.highlightcolor});
 },
 onFailure: function(transport) {
 alert("Error communicating with the server: " + transport.responseText.stripTags());
 },
 callback: function(form) {
 return Form.serialize(form);
 },
 handleLineBreaks: true,
 loadingText: 'Loading...',
 savingClassName: 'inplaceeditor-saving',
 loadingClassName: 'inplaceeditor-loading',
 formClassName: 'inplaceeditor-form',
 highlightcolor: Ajax.InPlaceEditor.defaultHighlightColor,
 highlightendcolor: "#FFFFFF",
 externalControl: null,
 submitOnBlur: false,
 ajaxOptions: {},
 evalScripts: false
 }, options || {});

 if(!this.options.formId && this.element.id) {
 this.options.formId = this.element.id + "-inplaceeditor";
 if ($(this.options.formId)) {
 // there's already a form with that name, don't specify an id
 this.options.formId = null;
 }
 }
 
 if (this.options.externalControl) {
 this.options.externalControl = $(this.options.externalControl);
 }
 
 this.originalBackground = Element.getStyle(this.element, 'background-color');
 if (!this.originalBackground) {
 this.originalBackground = "transparent";
 }
 
 this.element.title = this.options.clickToEditText;
 
 this.onclickListener = this.enterEditMode.bindAsEventListener(this);
 this.mouseoverListener = this.enterHover.bindAsEventListener(this);
 this.mouseoutListener = this.leaveHover.bindAsEventListener(this);
 Event.observe(this.element, 'click', this.onclickListener);
 Event.observe(this.element, 'mouseover', this.mouseoverListener);
 Event.observe(this.element, 'mouseout', this.mouseoutListener);
 if (this.options.externalControl) {
 Event.observe(this.options.externalControl, 'click', this.onclickListener);
 Event.observe(this.options.externalControl, 'mouseover', this.mouseoverListener);
 Event.observe(this.options.externalControl, 'mouseout', this.mouseoutListener);
 }
 },
 enterEditMode: function(evt) {
 if (this.saving) return;
 if (this.editing) return;
 this.editing = true;
 this.onEnterEditMode();
 if (this.options.externalControl) {
 Element.hide(this.options.externalControl);
 }
 Element.hide(this.element);
 this.createForm();
 this.element.parentNode.insertBefore(this.form, this.element);
 if (!this.options.loadTextURL) Field.scrollFreeActivate(this.editField);
 // stop the event to avoid a page refresh in Safari
 if (evt) {
 Event.stop(evt);
 }
 return false;
 },
 createForm: function() {
 this.form = document.createElement("form");
 this.form.id = this.options.formId;
 Element.addClassName(this.form, this.options.formClassName)
 this.form.onsubmit = this.onSubmit.bind(this);

 this.createEditField();

 if (this.options.textarea) {
 var br = document.createElement("br");
 this.form.appendChild(br);
 }
 
 if (this.options.textBeforeControls)
 this.form.appendChild(document.createTextNode(this.options.textBeforeControls));

 if (this.options.okButton) {
 var okButton = document.createElement("input");
 okButton.type = "submit";
 okButton.value = this.options.okText;
 okButton.className = 'editor_ok_button';
 this.form.appendChild(okButton);
 }
 
 if (this.options.okLink) {
 var okLink = document.createElement("a");
 okLink.href = "#";
 okLink.appendChild(document.createTextNode(this.options.okText));
 okLink.onclick = this.onSubmit.bind(this);
 okLink.className = 'editor_ok_link';
 this.form.appendChild(okLink);
 }
 
 if (this.options.textBetweenControls && 
 (this.options.okLink || this.options.okButton) && 
 (this.options.cancelLink || this.options.cancelButton))
 this.form.appendChild(document.createTextNode(this.options.textBetweenControls));
 
 if (this.options.cancelButton) {
 var cancelButton = document.createElement("input");
 cancelButton.type = "submit";
 cancelButton.value = this.options.cancelText;
 cancelButton.onclick = this.onclickCancel.bind(this);
 cancelButton.className = 'editor_cancel_button';
 this.form.appendChild(cancelButton);
 }

 if (this.options.cancelLink) {
 var cancelLink = document.createElement("a");
 cancelLink.href = "#";
 cancelLink.appendChild(document.createTextNode(this.options.cancelText));
 cancelLink.onclick = this.onclickCancel.bind(this);
 cancelLink.className = 'editor_cancel editor_cancel_link'; 
 this.form.appendChild(cancelLink);
 }
 
 if (this.options.textAfterControls)
 this.form.appendChild(document.createTextNode(this.options.textAfterControls));
 },
 hasHTMLLineBreaks: function(string) {
 if (!this.options.handleLineBreaks) return false;
 return string.match(/<br/i) || string.match(/<p>/i);
 },
 convertHTMLLineBreaks: function(string) {
 return string.replace(/<br>/gi, "\n").replace(/<br\/>/gi, "\n").replace(/<\/p>/gi, "\n").replace(/<p>/gi, "");
 },
 createEditField: function() {
 var text;
 if(this.options.loadTextURL) {
 text = this.options.loadingText;
 } else {
 text = this.getText();
 }

 var obj = this;
 
 if (this.options.rows == 1 && !this.hasHTMLLineBreaks(text)) {
 this.options.textarea = false;
 var textField = document.createElement("input");
 textField.obj = this;
 textField.type = "text";
 textField.name = this.options.paramName;
 textField.value = text;
 textField.style.backgroundColor = this.options.highlightcolor;
 textField.className = 'editor_field';
 var size = this.options.size || this.options.cols || 0;
 if (size != 0) textField.size = size;
 if (this.options.submitOnBlur)
 textField.onblur = this.onSubmit.bind(this);
 this.editField = textField;
 } else {
 this.options.textarea = true;
 var textArea = document.createElement("textarea");
 textArea.obj = this;
 textArea.name = this.options.paramName;
 textArea.value = this.convertHTMLLineBreaks(text);
 textArea.rows = this.options.rows;
 textArea.cols = this.options.cols || 40;
 textArea.className = 'editor_field'; 
 if (this.options.submitOnBlur)
 textArea.onblur = this.onSubmit.bind(this);
 this.editField = textArea;
 }
 
 if(this.options.loadTextURL) {
 this.loadExternalText();
 }
 this.form.appendChild(this.editField);
 },
 getText: function() {
 return this.element.innerHTML;
 },
 loadExternalText: function() {
 Element.addClassName(this.form, this.options.loadingClassName);
 this.editField.disabled = true;
 new Ajax.Request(
 this.options.loadTextURL,
 Object.extend({
 asynchronous: true,
 onComplete: this.onLoadedExternalText.bind(this)
 }, this.options.ajaxOptions)
 );
 },
 onLoadedExternalText: function(transport) {
 Element.removeClassName(this.form, this.options.loadingClassName);
 this.editField.disabled = false;
 this.editField.value = transport.responseText.stripTags();
 Field.scrollFreeActivate(this.editField);
 },
 onclickCancel: function() {
 this.onComplete();
 this.leaveEditMode();
 return false;
 },
 onFailure: function(transport) {
 this.options.onFailure(transport);
 if (this.oldInnerHTML) {
 this.element.innerHTML = this.oldInnerHTML;
 this.oldInnerHTML = null;
 }
 return false;
 },
 onSubmit: function() {
 // onLoading resets these so we need to save them away for the Ajax call
 var form = this.form;
 var value = this.editField.value;
 
 // do this first, sometimes the ajax call returns before we get a chance to switch on Saving...
 // which means this will actually switch on Saving... *after* we've left edit mode causing Saving...
 // to be displayed indefinitely
 this.onLoading();
 
 if (this.options.evalScripts) {
 new Ajax.Request(
 this.url, Object.extend({
 parameters: this.options.callback(form, value),
 onComplete: this.onComplete.bind(this),
 onFailure: this.onFailure.bind(this),
 asynchronous:true, 
 evalScripts:true
 }, this.options.ajaxOptions));
 } else {
 new Ajax.Updater(
 { success: this.element,
 // don't update on failure (this could be an option)
 failure: null }, 
 this.url, Object.extend({
 parameters: this.options.callback(form, value),
 onComplete: this.onComplete.bind(this),
 onFailure: this.onFailure.bind(this)
 }, this.options.ajaxOptions));
 }
 // stop the event to avoid a page refresh in Safari
 if (arguments.length > 1) {
 Event.stop(arguments[0]);
 }
 return false;
 },
 onLoading: function() {
 this.saving = true;
 this.removeForm();
 this.leaveHover();
 this.showSaving();
 },
 showSaving: function() {
 this.oldInnerHTML = this.element.innerHTML;
 this.element.innerHTML = this.options.savingText;
 Element.addClassName(this.element, this.options.savingClassName);
 this.element.style.backgroundColor = this.originalBackground;
 Element.show(this.element);
 },
 removeForm: function() {
 if(this.form) {
 if (this.form.parentNode) Element.remove(this.form);
 this.form = null;
 }
 },
 enterHover: function() {
 if (this.saving) return;
 this.element.style.backgroundColor = this.options.highlightcolor;
 if (this.effect) {
 this.effect.cancel();
 }
 Element.addClassName(this.element, this.options.hoverClassName)
 },
 leaveHover: function() {
 if (this.options.backgroundColor) {
 this.element.style.backgroundColor = this.oldBackground;
 }
 Element.removeClassName(this.element, this.options.hoverClassName)
 if (this.saving) return;
 this.effect = new Effect.Highlight(this.element, {
 startcolor: this.options.highlightcolor,
 endcolor: this.options.highlightendcolor,
 restorecolor: this.originalBackground
 });
 },
 leaveEditMode: function() {
 Element.removeClassName(this.element, this.options.savingClassName);
 this.removeForm();
 this.leaveHover();
 this.element.style.backgroundColor = this.originalBackground;
 Element.show(this.element);
 if (this.options.externalControl) {
 Element.show(this.options.externalControl);
 }
 this.editing = false;
 this.saving = false;
 this.oldInnerHTML = null;
 this.onLeaveEditMode();
 },
 onComplete: function(transport) {
 this.leaveEditMode();
 this.options.onComplete.bind(this)(transport, this.element);
 },
 onEnterEditMode: function() {},
 onLeaveEditMode: function() {},
 dispose: function() {
 if (this.oldInnerHTML) {
 this.element.innerHTML = this.oldInnerHTML;
 }
 this.leaveEditMode();
 Event.stopObserving(this.element, 'click', this.onclickListener);
 Event.stopObserving(this.element, 'mouseover', this.mouseoverListener);
 Event.stopObserving(this.element, 'mouseout', this.mouseoutListener);
 if (this.options.externalControl) {
 Event.stopObserving(this.options.externalControl, 'click', this.onclickListener);
 Event.stopObserving(this.options.externalControl, 'mouseover', this.mouseoverListener);
 Event.stopObserving(this.options.externalControl, 'mouseout', this.mouseoutListener);
 }
 }
};

Ajax.InPlaceCollectionEditor = Class.create();
Object.extend(Ajax.InPlaceCollectionEditor.prototype, Ajax.InPlaceEditor.prototype);
Object.extend(Ajax.InPlaceCollectionEditor.prototype, {
 createEditField: function() {
 if (!this.cached_selectTag) {
 var selectTag = document.createElement("select");
 var collection = this.options.collection || [];
 var optionTag;
 collection.each(function(e,i) {
 optionTag = document.createElement("option");
 optionTag.value = (e instanceof Array) ? e[0] : e;
 if((typeof this.options.value == 'undefined') && 
 ((e instanceof Array) ? this.element.innerHTML == e[1] : e == optionTag.value)) optionTag.selected = true;
 if(this.options.value==optionTag.value) optionTag.selected = true;
 optionTag.appendChild(document.createTextNode((e instanceof Array) ? e[1] : e));
 selectTag.appendChild(optionTag);
 }.bind(this));
 this.cached_selectTag = selectTag;
 }

 this.editField = this.cached_selectTag;
 if(this.options.loadTextURL) this.loadExternalText();
 this.form.appendChild(this.editField);
 this.options.callback = function(form, value) {
 return "value=" + encodeURIComponent(value);
 }
 }
});

// Delayed observer, like Form.Element.Observer, 
// but waits for delay after last key input
// Ideal for live-search fields

Form.Element.DelayedObserver = Class.create();
Form.Element.DelayedObserver.prototype = {
 initialize: function(element, delay, callback) {
 this.delay = delay || 0.5;
 this.element = $(element);
 this.callback = callback;
 this.timer = null;
 this.lastValue = $F(this.element); 
 Event.observe(this.element,'keyup',this.delayedListener.bindAsEventListener(this));
 },
 delayedListener: function(event) {
 if(this.lastValue == $F(this.element)) return;
 if(this.timer) clearTimeout(this.timer);
 this.timer = setTimeout(this.onTimerEvent.bind(this), this.delay * 1000);
 this.lastValue = $F(this.element);
 },
 onTimerEvent: function() {
 this.timer = null;
 this.callback(this.element, $F(this.element));
 }
};

// script.aculo.us slider.js v1.7.1_beta3, Fri May 25 17:19:41 +0200 2007

// Copyright (c) 2005-2007 Marty Haught, Thomas Fuchs
//
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/

if(!Control) var Control = {};
Control.Slider = Class.create();

// options:
// axis: 'vertical', or 'horizontal' (default)
//
// callbacks:
// onChange(value)
// onSlide(value)
Control.Slider.prototype = {
 initialize: function(handle, track, options) {
 var slider = this;

 if(handle instanceof Array) {
 this.handles = handle.collect( function(e) { return $(e) });
 } else {
 this.handles = [$(handle)];
 }

 this.track = $(track);
 this.options = options || {};

 this.axis = this.options.axis || 'horizontal';
 this.increment = this.options.increment || 1;
 this.step = parseInt(this.options.step || '1');
 this.range = this.options.range || $R(0,1);

 this.value = 0; // assure backwards compat
 this.values = this.handles.map( function() { return 0 });
 this.spans = this.options.spans ? this.options.spans.map(function(s){ return $(s) }) : false;
 this.options.startSpan = $(this.options.startSpan || null);
 this.options.endSpan = $(this.options.endSpan || null);

 this.restricted = this.options.restricted || false;

 this.maximum = this.options.maximum || this.range.end;
 this.minimum = this.options.minimum || this.range.start;

 // Will be used to align the handle onto the track, if necessary
 this.alignX = parseInt(this.options.alignX || '0');
 this.alignY = parseInt(this.options.alignY || '0');

 this.trackLength = this.maximumOffset() - this.minimumOffset();

 this.handleLength = this.isVertical() ?
 (this.handles[0].offsetHeight != 0 ?
 this.handles[0].offsetHeight : this.handles[0].style.height.replace(/px$/,"")) :
 (this.handles[0].offsetWidth != 0 ? this.handles[0].offsetWidth :
 this.handles[0].style.width.replace(/px$/,""));

 this.active = false;
 this.dragging = false;
 this.disabled = false;

 if(this.options.disabled) this.setDisabled();

 // Allowed values array
 this.allowedValues = this.options.values ? this.options.values.sortBy(Prototype.K) : false;
 if(this.allowedValues) {
 this.minimum = this.allowedValues.min();
 this.maximum = this.allowedValues.max();
 }

 this.eventMouseDown = this.startDrag.bindAsEventListener(this);
 this.eventMouseUp = this.endDrag.bindAsEventListener(this);
 this.eventMouseMove = this.update.bindAsEventListener(this);

 // Initialize handles in reverse (make sure first handle is active)
 this.handles.each( function(h,i) {
 i = slider.handles.length-1-i;
 slider.setValue(parseFloat(
 (slider.options.sliderValue instanceof Array ?
 slider.options.sliderValue[i] : slider.options.sliderValue) ||
 slider.range.start), i);
 Element.makePositioned(h); // fix IE
 Event.observe(h, "mousedown", slider.eventMouseDown);
 });

 Event.observe(this.track, "mousedown", this.eventMouseDown);
 Event.observe(document, "mouseup", this.eventMouseUp);
 Event.observe(this.track.parentNode.parentNode, "mousemove", this.eventMouseMove);

 this.initialized = true;
 },
 dispose: function() {
 var slider = this;
 Event.stopObserving(this.track, "mousedown", this.eventMouseDown);
 Event.stopObserving(document, "mouseup", this.eventMouseUp);
 Event.stopObserving(this.track.parentNode.parentNode, "mousemove", this.eventMouseMove);
 this.handles.each( function(h) {
 Event.stopObserving(h, "mousedown", slider.eventMouseDown);
 });
 },
 setDisabled: function(){
 this.disabled = true;
 },
 setEnabled: function(){
 this.disabled = false;
 },
 getNearestValue: function(value){
 if(this.allowedValues){
 if(value >= this.allowedValues.max()) return(this.allowedValues.max());
 if(value <= this.allowedValues.min()) return(this.allowedValues.min());

 var offset = Math.abs(this.allowedValues[0] - value);
 var newValue = this.allowedValues[0];
 this.allowedValues.each( function(v) {
 var currentOffset = Math.abs(v - value);
 if(currentOffset <= offset){
 newValue = v;
 offset = currentOffset;
 }
 });
 return newValue;
 }
 if(value > this.range.end) return this.range.end;
 if(value < this.range.start) return this.range.start;
 return value;
 },
 setValue: function(sliderValue, handleIdx){
 if(!this.active) {
 this.activeHandleIdx = handleIdx || 0;
 this.activeHandle = this.handles[this.activeHandleIdx];
 this.updateStyles();
 }
 handleIdx = handleIdx || this.activeHandleIdx || 0;
 if(this.initialized && this.restricted) {
 if((handleIdx>0) && (sliderValue<this.values[handleIdx-1]))
 sliderValue = this.values[handleIdx-1];
 if((handleIdx < (this.handles.length-1)) && (sliderValue>this.values[handleIdx+1]))
 sliderValue = this.values[handleIdx+1];
 }
 sliderValue = this.getNearestValue(sliderValue);
 this.values[handleIdx] = sliderValue;
 this.value = this.values[0]; // assure backwards compat

 this.handles[handleIdx].style[this.isVertical() ? 'top' : 'left'] =
 this.translateToPx(sliderValue);

 this.drawSpans();
 if(!this.dragging || !this.event) this.updateFinished();
 },
 setValueBy: function(delta, handleIdx) {
 this.setValue(this.values[handleIdx || this.activeHandleIdx || 0] + delta,
 handleIdx || this.activeHandleIdx || 0);
 },
 translateToPx: function(value) {
 return Math.round(
 ((this.trackLength-this.handleLength)/(this.range.end-this.range.start)) *
 (value - this.range.start)) + "px";
 },
 translateToValue: function(offset) {
 return ((offset/(this.trackLength-this.handleLength) *
 (this.range.end-this.range.start)) + this.range.start);
 },
 getRange: function(range) {
 var v = this.values.sortBy(Prototype.K);
 range = range || 0;
 return $R(v[range],v[range+1]);
 },
 minimumOffset: function(){
 return(this.isVertical() ? this.alignY : this.alignX);
 },
 maximumOffset: function(){
 return(this.isVertical() ?
 (this.track.offsetHeight != 0 ? this.track.offsetHeight :
 this.track.style.height.replace(/px$/,"")) - this.alignY :
 (this.track.offsetWidth != 0 ? this.track.offsetWidth :
 this.track.style.width.replace(/px$/,"")) - this.alignY);
 },
 isVertical: function(){
 return (this.axis == 'vertical');
 },
 drawSpans: function() {
 var slider = this;
 if(this.spans)
 $R(0, this.spans.length-1).each(function(r) { slider.setSpan(slider.spans[r], slider.getRange(r)) });
 if(this.options.startSpan)
 this.setSpan(this.options.startSpan,
 $R(0, this.values.length>1 ? this.getRange(0).min() : this.value ));
 if(this.options.endSpan)
 this.setSpan(this.options.endSpan,
 $R(this.values.length>1 ? this.getRange(this.spans.length-1).max() : this.value, this.maximum));
 },
 setSpan: function(span, range) {
 if(this.isVertical()) {
 span.style.top = this.translateToPx(range.start);
 span.style.height = this.translateToPx(range.end - range.start + this.range.start);
 } else {
 span.style.left = this.translateToPx(range.start);
 span.style.width = this.translateToPx(range.end - range.start + this.range.start);
 }
 },
 updateStyles: function() {
 this.handles.each( function(h){ Element.removeClassName(h, 'selected') });
 Element.addClassName(this.activeHandle, 'selected');
 },
 startDrag: function(event) {
 if(Event.isLeftClick(event)) {
 if(!this.disabled){
 this.active = true;

 var handle = Event.element(event);
 var pointer = [Event.pointerX(event), Event.pointerY(event)];
 var track = handle;
 if(track==this.track) {
 var offsets = Position.cumulativeOffset(this.track);
 this.event = event;
 this.setValue(this.translateToValue(
 (this.isVertical() ? pointer[1]-offsets[1] : pointer[0]-offsets[0])-(this.handleLength/2)
 ));
 var offsets = Position.cumulativeOffset(this.activeHandle);
 this.offsetX = (pointer[0] - offsets[0]);
 this.offsetY = (pointer[1] - offsets[1]);
 } else {
 // find the handle (prevents issues with Safari)
 while((this.handles.indexOf(handle) == -1) && handle.parentNode)
 handle = handle.parentNode;

 if(this.handles.indexOf(handle)!=-1) {
 this.activeHandle = handle;
 this.activeHandleIdx = this.handles.indexOf(this.activeHandle);
 this.updateStyles();

 var offsets = Position.cumulativeOffset(this.activeHandle);
 this.offsetX = (pointer[0] - offsets[0]);
 this.offsetY = (pointer[1] - offsets[1]);
 }
 }
 }
 Event.stop(event);
 }
 },
 update: function(event) {
 if(this.active) {
 if(!this.dragging) this.dragging = true;
 this.draw(event);
 if(Prototype.Browser.WebKit) window.scrollBy(0,0);
 Event.stop(event);
 }
 },
 draw: function(event) {
 var pointer = [Event.pointerX(event), Event.pointerY(event)];
 var offsets = Position.cumulativeOffset(this.track);
 pointer[0] -= this.offsetX + offsets[0];
 pointer[1] -= this.offsetY + offsets[1];
 this.event = event;
 this.setValue(this.translateToValue( this.isVertical() ? pointer[1] : pointer[0] ));
 if(this.initialized && this.options.onSlide)
 this.options.onSlide(this.values.length>1 ? this.values : this.value, this);
 },
 endDrag: function(event) {
 if(this.active && this.dragging) {
 this.finishDrag(event, true);
 Event.stop(event);
 }
 this.active = false;
 this.dragging = false;
 },
 finishDrag: function(event, success) {
 this.active = false;
 this.dragging = false;
 this.updateFinished();
 },
 updateFinished: function() {
 if(this.initialized && this.options.onChange)
 this.options.onChange(this.values.length>1 ? this.values : this.value, this);
 this.event = null;
 }
}
/*
* Really easy field validation with Prototype
* http://tetlaw.id.au/view/javascript/really-easy-field-validation
* Andrew Tetlaw
* Version 1.5.4.1 (2007-01-05)
*
* Copyright (c) 2007 Andrew Tetlaw
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies
* of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
*/
var Validator = Class.create();

Validator.prototype = {
 initialize : function(className, error, test, options) {
 if(typeof test == 'function'){
 this.options = $H(options);
 this._test = test;
 } else {
 this.options = $H(test);
 this._test = function(){return true};
 }
 this.error = error || 'Validation failed.';
 this.className = className;
 },
 test : function(v, elm) {
 return (this._test(v,elm) && this.options.all(function(p){
 return Validator.methods[p.key] ? Validator.methods[p.key](v,elm,p.value) : true;
 }));
 }
}
Validator.methods = {
 pattern : function(v,elm,opt) {return Validation.get('IsEmpty').test(v) || opt.test(v)},
 minLength : function(v,elm,opt) {return v.length >= opt},
 maxLength : function(v,elm,opt) {return v.length <= opt},
 min : function(v,elm,opt) {return v >= parseFloat(opt)},
 max : function(v,elm,opt) {return v <= parseFloat(opt)},
 notOneOf : function(v,elm,opt) {return $A(opt).all(function(value) {
 return v != value;
 })},
 oneOf : function(v,elm,opt) {return $A(opt).any(function(value) {
 return v == value;
 })},
 is : function(v,elm,opt) {return v == opt},
 isNot : function(v,elm,opt) {return v != opt},
 equalToField : function(v,elm,opt) {return v == $F(opt)},
 notEqualToField : function(v,elm,opt) {return v != $F(opt)},
 include : function(v,elm,opt) {return $A(opt).all(function(value) {
 return Validation.get(value).test(v,elm);
 })}
}

var Validation = Class.create();

Validation.prototype = {
 initialize : function(form, options){
 this.form = $(form);
 if (!this.form) {
 return;
 }
 this.options = Object.extend({
 onSubmit : true,
 stopOnFirst : false,
 immediate : false,
 focusOnError : true,
 useTitles : false,
 onFormValidate : function(result, form) {},
 onElementValidate : function(result, elm) {}
 }, options || {});
 if(this.options.onSubmit) Event.observe(this.form,'submit',this.onSubmit.bind(this),false);
 if(this.options.immediate) {
 var useTitles = this.options.useTitles;
 var callback = this.options.onElementValidate;
 Form.getElements(this.form).each(function(input) { // Thanks Mike!
 Event.observe(input, 'blur', function(ev) { Validation.validate(Event.element(ev),{useTitle : useTitles, onElementValidate : callback}); });
 });
 }
 },
 onSubmit : function(ev){
 if(!this.validate()) Event.stop(ev);
 },
 validate : function() {
 var result = false;
 var useTitles = this.options.useTitles;
 var callback = this.options.onElementValidate;
 try {
 if(this.options.stopOnFirst) {
 result = Form.getElements(this.form).all(function(elm) { return Validation.validate(elm,{useTitle : useTitles, onElementValidate : callback}); });
 } else {
 result = Form.getElements(this.form).collect(function(elm) { return Validation.validate(elm,{useTitle : useTitles, onElementValidate : callback}); }).all();
 }
 } catch (e) {

 }
 if(!result && this.options.focusOnError) {
 try{
 Form.getElements(this.form).findAll(function(elm){return $(elm).hasClassName('validation-failed')}).first().focus()
 }
 catch(e){

 }
 }
 this.options.onFormValidate(result, this.form);
 return result;
 },
 reset : function() {
 Form.getElements(this.form).each(Validation.reset);
 }
}

Object.extend(Validation, {
 validate : function(elm, options){
 options = Object.extend({
 useTitle : false,
 onElementValidate : function(result, elm) {}
 }, options || {});
 elm = $(elm);

 var cn = $w(elm.className);
 return result = cn.all(function(value) {
 var test = Validation.test(value,elm,options.useTitle);
 options.onElementValidate(test, elm);
 return test;
 });
 },
 insertAdvice : function(elm, advice){
 var container = $(elm).up('.field-row');
 if(container){
 Element.insert(container, {after: advice});
 }
 else if (elm.advaiceContainer && $(elm.advaiceContainer)) {
 $(elm.advaiceContainer).update(advice);
 }
 else {
 switch (elm.type.toLowerCase()) {
 case 'checkbox':
 case 'radio':
 var p = elm.parentNode;
 if(p) {
 Element.insert(p, {'bottom': advice});
 } else {
 Element.insert(elm, {'after': advice});
 }
 break;
 default:
 Element.insert(elm, {'after': advice});
 }
 }
 },
 showAdvice : function(elm, advice, adviceName){
 if(!elm.advices){
 elm.advices = new Hash();
 }
 else{
 elm.advices.each(function(pair){
 this.hideAdvice(elm, pair.value);
 }.bind(this));
 }
 elm.advices.set(adviceName, advice);
 if(typeof Effect == 'undefined') {
 advice.style.display = 'block';
 } else {
 if(!advice._adviceAbsolutize) {
 new Effect.Appear(advice, {duration : 1 });
 } else {
 Position.absolutize(advice);
 advice.show();
 advice.setStyle({
 'top':advice._adviceTop,
 'left': advice._adviceLeft,
 'width': advice._adviceWidth,
 'z-index': 1000
 });
 advice.addClassName('advice-absolute');
 }
 }
 },
 hideAdvice : function(elm, advice){
 if(advice != null) advice.hide();
 },
 updateCallback : function(elm, status) {
 if (typeof elm.callbackFunction != 'undefined') {
 eval(elm.callbackFunction+'(\''+elm.id+'\',\''+status+'\')');
 }
 },
 ajaxError : function(elm, errorMsg) {
 var name = 'validate-ajax';
 var advice = Validation.getAdvice(name, elm);
 if (advice == null) {
 advice = this.createAdvice(name, elm, false, errorMsg);
 }
 this.showAdvice(elm, advice, 'validate-ajax');
 this.updateCallback(elm, 'failed');

 elm.addClassName('validation-failed');
 elm.addClassName('validate-ajax');
 },
 test : function(name, elm, useTitle) {
 var v = Validation.get(name);
 var prop = '__advice'+name.camelize();
 try {
 if(Validation.isVisible(elm) && !v.test($F(elm), elm)) {
 //if(!elm[prop]) {
 var advice = Validation.getAdvice(name, elm);
 if (advice == null) {
 advice = this.createAdvice(name, elm, useTitle);
 }
 this.showAdvice(elm, advice, name);
 this.updateCallback(elm, 'failed');
 //}
 elm[prop] = 1;
 if (!elm.advaiceContainer) {
 elm.removeClassName('validation-passed');
 elm.addClassName('validation-failed');
 }
 return false;
 } else {
 var advice = Validation.getAdvice(name, elm);
 this.hideAdvice(elm, advice);
 this.updateCallback(elm, 'passed');
 elm[prop] = '';
 elm.removeClassName('validation-failed');
 elm.addClassName('validation-passed');
 return true;
 }
 } catch(e) {
 throw(e)
 }
 },
 isVisible : function(elm) {
 while(elm.tagName != 'BODY') {
 if(!$(elm).visible()) return false;
 elm = elm.parentNode;
 }
 return true;
 },
 getAdvice : function(name, elm) {
 return $('advice-' + name + '-' + Validation.getElmID(elm)) || $('advice-' + Validation.getElmID(elm));
 },
 createAdvice : function(name, elm, useTitle, customError) {
 var v = Validation.get(name);
 var errorMsg = useTitle ? ((elm && elm.title) ? elm.title : v.error) : v.error;
 if (customError) {
 errorMsg = customError;
 }
 try {
 if (Translator){
 errorMsg = Translator.translate(errorMsg);
 }
 }
 catch(e){}

 advice = '<div class="validation-advice" id="advice-' + name + '-' + Validation.getElmID(elm) +'" style="display:none">' + errorMsg + '</div>'


 Validation.insertAdvice(elm, advice);
 advice = Validation.getAdvice(name, elm);
 if($(elm).hasClassName('absolute-advice')) {
 var dimensions = $(elm).getDimensions();
 var originalPosition = Position.cumulativeOffset(elm);

 advice._adviceTop = (originalPosition[1] + dimensions.height) + 'px';
 advice._adviceLeft = (originalPosition[0]) + 'px';
 advice._adviceWidth = (dimensions.width) + 'px';
 advice._adviceAbsolutize = true;
 }
 return advice;
 },
 getElmID : function(elm) {
 return elm.id ? elm.id : elm.name;
 },
 reset : function(elm) {
 elm = $(elm);
 var cn = $w(elm.className);
 cn.each(function(value) {
 var prop = '__advice'+value.camelize();
 if(elm[prop]) {
 var advice = Validation.getAdvice(value, elm);
 advice.hide();
 elm[prop] = '';
 }
 elm.removeClassName('validation-failed');
 elm.removeClassName('validation-passed');
 });
 },
 add : function(className, error, test, options) {
 var nv = {};
 nv[className] = new Validator(className, error, test, options);
 Object.extend(Validation.methods, nv);
 },
 addAllThese : function(validators) {
 var nv = {};
 $A(validators).each(function(value) {
 nv[value[0]] = new Validator(value[0], value[1], value[2], (value.length > 3 ? value[3] : {}));
 });
 Object.extend(Validation.methods, nv);
 },
 get : function(name) {
 return Validation.methods[name] ? Validation.methods[name] : Validation.methods['_LikeNoIDIEverSaw_'];
 },
 methods : {
 '_LikeNoIDIEverSaw_' : new Validator('_LikeNoIDIEverSaw_','',{})
 }
});

Validation.add('IsEmpty', '', function(v) {
 return (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)); // || /^\s+$/.test(v));
});

Validation.addAllThese([
 ['validate-select', 'Please select an option.', function(v) {
 return ((v != "none") && (v != null) && (v.length != 0));
 }],
 ['required-entry', 'This is a required field.', function(v) {
 return !Validation.get('IsEmpty').test(v);
 }],
 ['validate-number', 'Please enter a valid number in this field.', function(v) {
 return Validation.get('IsEmpty').test(v) || (!isNaN(parseNumber(v)) && !/^\s+$/.test(parseNumber(v)));
 }],
 ['validate-digits', 'Please use numbers only in this field. please avoid spaces or other characters such as dots or commas.', function(v) {
 return Validation.get('IsEmpty').test(v) || !/[^\d]/.test(v);
 }],
 ['validate-alpha', 'Please use letters only (a-z or A-Z) in this field.', function (v) {
 return Validation.get('IsEmpty').test(v) || /^[a-zA-Z]+$/.test(v)
 }],
 ['validate-code', 'Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.', function (v) {
 return Validation.get('IsEmpty').test(v) || /^[a-z]+[a-z0-9_]+$/.test(v)
 }],
 ['validate-alphanum', 'Please use only letters (a-z or A-Z) or numbers (0-9) only in this field. No spaces or other characters are allowed.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^[a-zA-Z0-9]+$/.test(v) /*!/\W/.test(v)*/
 }],
 ['validate-street', 'Please use only letters (a-z or A-Z) or numbers (0-9) or spaces and # only in this field.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^[ \w]{3,}([A-Za-z]\.)?([ \w]*\#\d+)?(\r\n| )[ \w]{3,}/.test(v)
 }],
 ['validate-phoneStrict', 'Please enter a valid phone number. For example (123) 456-7890 or 123-456-7890.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^(\()?\d{3}(\))?(-|\s)?\d{3}(-|\s)\d{4}$/.test(v);
 }],
 ['validate-phoneLax', 'Please enter a valid phone number. For example (123) 456-7890 or 123-456-7890.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^((\d[-. ]?)?((\(\d{3}\))|\d{3}))?[-. ]?\d{3}[-. ]?\d{4}$/.test(v);
 }],
 ['validate-fax', 'Please enter a valid fax number. For example (123) 456-7890 or 123-456-7890.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^(\()?\d{3}(\))?(-|\s)?\d{3}(-|\s)\d{4}$/.test(v);
 }],
 ['validate-date', 'Please enter a valid date.', function(v) {
 var test = new Date(v);
 return Validation.get('IsEmpty').test(v) || !isNaN(test);
 }],
 ['validate-email', 'Please enter a valid email address. For example johndoe@domain.com.', function (v) {
 //return Validation.get('IsEmpty').test(v) || /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/.test(v)
 //return Validation.get('IsEmpty').test(v) || /^[\!\#$%\*/?|\^\{\}`~&\'\+\-=_a-z0-9][\!\#$%\*/?|\^\{\}`~&\'\+\-=_a-z0-9\.]{1,30}[\!\#$%\*/?|\^\{\}`~&\'\+\-=_a-z0-9]@([a-z0-9_-]{1,30}\.){1,5}[a-z]{2,4}$/i.test(v)
 return Validation.get('IsEmpty').test(v) || /^[a-z0-9,!\#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})/i.test(v)
 }],
 ['validate-password', 'Please enter 6 or more characters. Leading or trailing spaces will be ignored.', function(v) {
 var pass=v.strip(); /*strip leading and trailing spaces*/
 return !(pass.length>0 && pass.length < 6);
 }],
 ['validate-cpassword', 'Please make sure your passwords match.', function(v) {
 var pass = $('password') ? $('password') : $$('.validate-password')[0];
 var conf = $('confirmation') ? $('confirmation') : $$('.validate-cpassword')[0];
 return (pass.value == conf.value);
 }],
 ['validate-url', 'Please enter a valid URL. http:// is required', function (v) {
 return Validation.get('IsEmpty').test(v) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(v)
 }],
 ['validate-clean-url', 'Please enter a valid URL. For example http://www.example.com or www.example.com', function (v) {
 return Validation.get('IsEmpty').test(v) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i.test(v) || /^(www)((\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i.test(v)
 }],
 ['validate-identifier', 'Please enter a valid Identifier. For example example-page, example-page.html or anotherlevel/example-page', function (v) {
 return Validation.get('IsEmpty').test(v) || /^[A-Z0-9][A-Z0-9_\/-]+(\.[A-Z0-9_-]+)*$/i.test(v)
 }],
 ['validate-xml-identifier', 'Please enter a valid XML-identifier. For example something_1, block5, id-4', function (v) {
 return Validation.get('IsEmpty').test(v) || /^[A-Z][A-Z0-9_\/-]*$/i.test(v)
 }],
 ['validate-ssn', 'Please enter a valid social security number. For example 123-45-6789.', function(v) {
 return Validation.get('IsEmpty').test(v) || /^\d{3}-?\d{2}-?\d{4}$/.test(v);
 }],
 ['validate-zip', 'Please enter a valid zip code. For example 90602 or 90602-1234.', function(v) {
 return Validation.get('IsEmpty').test(v) || /(^\d{5}$)|(^\d{5}-\d{4}$)/.test(v);
 }],
 ['validate-zip-international', 'Please enter a valid zip code.', function(v) {
 //return Validation.get('IsEmpty').test(v) || /(^[A-z0-9]{2,10}([\s]{0,1}|[\-]{0,1})[A-z0-9]{2,10}$)/.test(v);
 return true;
 }],
 ['validate-date-au', 'Please use this date format: dd/mm/yyyy. For example 17/03/2006 for the 17th of March, 2006.', function(v) {
 if(Validation.get('IsEmpty').test(v)) return true;
 var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
 if(!regex.test(v)) return false;
 var d = new Date(v.replace(regex, '$2/$1/$3'));
 return ( parseInt(RegExp.$2, 10) == (1+d.getMonth()) ) &&
 (parseInt(RegExp.$1, 10) == d.getDate()) &&
 (parseInt(RegExp.$3, 10) == d.getFullYear() );
 }],
 ['validate-currency-dollar', 'Please enter a valid $ amount. For example $100.00.', function(v) {
 // [$]1[##][,###]+[.##]
 // [$]1###+[.##]
 // [$]0.##
 // [$].##
 return Validation.get('IsEmpty').test(v) || /^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/.test(v)
 }],
 ['validate-one-required', 'Please select one of the above options.', function (v,elm) {
 var p = elm.parentNode;
 var options = p.getElementsByTagName('INPUT');
 return $A(options).any(function(elm) {
 return $F(elm);
 });
 }],
 ['validate-one-required-by-name', 'Please select one of the options.', function (v,elm) {
 var inputs = $$('input');
 var error = 1;
 for( i in inputs ) {
 if( inputs[i].checked == true && inputs[i].name == elm.name ) {
 error = 0;
 }
 }

 if( error == 0 ) {
 return true;
 } else {
 return false;
 }
 }],
 ['validate-not-negative-number', 'Please enter a valid number in this field.', function(v) {
 v = parseNumber(v);
 return (!isNaN(v) && v>=0);
 }],
 ['validate-state', 'Please select State/Province.', function(v) {
 return (v!=0 || v == '');
 }],

 ['validate-new-password', 'Please enter 6 or more characters. Leading or trailing spaces will be ignored.', function(v) {
 if (!Validation.get('validate-password').test(v)) return false;
 if (Validation.get('IsEmpty').test(v) && v != '') return false;
 return true;
 }],
 ['validate-greater-than-zero', 'Please enter a number greater than 0 in this field.', function(v) {
 if(v.length)
 return parseFloat(v) > 0;
 else
 return true;
 }],
 ['validate-zero-or-greater', 'Please enter a number 0 or greater in this field.', function(v) {
 if(v.length)
 return parseFloat(v) >= 0;
 else
 return true;
 }],
 ['validate-cc-number', 'Please enter a valid credit card number.', function(v, elm) {
 // remove non-numerics
 var ccTypeContainer = $(elm.id.substr(0,elm.id.indexOf('_cc_number')) + '_cc_type');
 if (ccTypeContainer && typeof Validation.creditCartTypes.get(ccTypeContainer.value) != 'undefined'
 && Validation.creditCartTypes.get(ccTypeContainer.value)[2] == false) {
 if (!Validation.get('IsEmpty').test(v) && Validation.get('validate-digits').test(v)) {
 return true;
 } else {
 return false;
 }
 }
 return validateCreditCard(v);
 }],
 ['validate-cc-type', 'Credit card number doesn\'t match credit card type', function(v, elm) {
 // remove credit card number delimiters such as "-" and space
 elm.value = removeDelimiters(elm.value);
 v = removeDelimiters(v);

 var ccTypeContainer = $(elm.id.substr(0,elm.id.indexOf('_cc_number')) + '_cc_type');
 if (!ccTypeContainer) {
 return true;
 }
 var ccType = ccTypeContainer.value;

 if (typeof Validation.creditCartTypes.get(ccType) == 'undefined') {
 return false;
 }

 // Other card type or switch or solo card
 if (Validation.creditCartTypes.get(ccType)[0]==false) {
 return true;
 }

 // Matched credit card type
 var ccMatchedType = '';

 Validation.creditCartTypes.each(function (pair) {
 if (pair.value[0] && v.match(pair.value[0])) {
 ccMatchedType = pair.key;
 throw $break;
 }
 });

 if(ccMatchedType != ccType) {
 return false;
 }

 return true;
 }],
 ['validate-cc-type-select', 'Card type doesn\'t match credit card number', function(v, elm) {
 var ccNumberContainer = $(elm.id.substr(0,elm.id.indexOf('_cc_type')) + '_cc_number');
 return Validation.get('validate-cc-type').test(ccNumberContainer.value, ccNumberContainer);
 }],
 ['validate-cc-exp', 'Incorrect credit card expiration date', function(v, elm) {
 var ccExpMonth = v;
 var ccExpYear = $('ccsave_expiration_yr').value;
 var currentTime = new Date();
 var currentMonth = currentTime.getMonth() + 1;
 var currentYear = currentTime.getFullYear();
 if (ccExpMonth < currentMonth && ccExpYear == currentYear) {
 return false;
 }
 return true;
 }],
 ['validate-cc-cvn', 'Please enter a valid credit card verification number.', function(v, elm) {
 var ccTypeContainer = $(elm.id.substr(0,elm.id.indexOf('_cc_cid')) + '_cc_type');
 if (!ccTypeContainer) {
 return true;
 }
 var ccType = ccTypeContainer.value;

 if (typeof Validation.creditCartTypes.get(ccType) == 'undefined') {
 return false;
 }

 var re = Validation.creditCartTypes.get(ccType)[1];

 if (v.match(re)) {
 return true;
 }

 return false;
 }],
 ['validate-ajax', '', function(v, elm) { return true; }],
 ['validate-data', 'Please use only letters (a-z or A-Z), numbers (0-9) or underscore(_) in this field, first character should be a letter.', function (v) {
 if(v != '' && v) {
 return /^[A-Za-z]+[A-Za-z0-9_]+$/.test(v);
 }
 return true;
 }],
 ['validate-css-length', 'Please input a valid CSS-length. For example 100px or 77pt or 20em or .5ex or 50%', function (v) {
 if (v != '' && v) {
 return /^[0-9\.]+(px|pt|em|ex|%)?$/.test(v) && (!(/\..*\./.test(v))) && !(/\.$/.test(v));
 }
 return true;
 }],
 ['validate-length', 'Maximum length exceeded.', function (v, elm) {
 var re = new RegExp(/^maximum-length-[0-9]+$/);
 var result = true;
 $w(elm.className).each(function(name, index) {
 if (name.match(re) && result) {
 var length = name.split('-')[2];
 result = (v.length <= length);
 }
 });
 return result;
 }]
]);


// Credit Card Validation Javascript
// copyright 12th May 2003, by Stephen Chapman, Felgall Pty Ltd

// You have permission to copy and use this javascript provided that
// the content of the script is not changed in any way.

function validateCreditCard(s) {
 // remove non-numerics
 var v = "0123456789";
 var w = "";
 for (i=0; i < s.length; i++) {
 x = s.charAt(i);
 if (v.indexOf(x,0) != -1)
 w += x;
 }
 // validate number
 j = w.length / 2;
 k = Math.floor(j);
 m = Math.ceil(j) - k;
 c = 0;
 for (i=0; i<k; i++) {
 a = w.charAt(i*2+m) * 2;
 c += a > 9 ? Math.floor(a/10 + a%10) : a;
 }
 for (i=0; i<k+m; i++) c += w.charAt(i*2+1-m) * 1;
 return (c%10 == 0);
}

function removeDelimiters (v) {
 v = v.replace(/\s/g, '');
 v = v.replace(/\-/g, '');
 return v;
}

function parseNumber(v)
{
 if (typeof v != 'string') {
 return parseFloat(v);
 }

 var isDot = v.indexOf('.');
 var isComa = v.indexOf(',');

 if (isDot != -1 && isComa != -1) {
 if (isComa > isDot) {
 v = v.replace('.', '').replace(',', '.');
 }
 else {
 v = v.replace(',', '');
 }
 }
 else if (isComa != -1) {
 v = v.replace(',', '.');
 }

 return parseFloat(v);
}

/**
 * Hash with credit card types wich can be simply extended in payment modules
 * 0 - regexp for card number
 * 1 - regexp for cvn
 * 2 - check or not credit card number trough Luhn algorithm by
 * function validateCreditCard wich you can find above in this file
 */
Validation.creditCartTypes = $H({
 'VI': [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true],
 'MC': [new RegExp('^5[1-5][0-9]{14}$'), new RegExp('^[0-9]{3}$'), true],
 'AE': [new RegExp('^3[47][0-9]{13}$'), new RegExp('^[0-9]{4}$'), true],
 'DI': [new RegExp('^6011[0-9]{12}$'), new RegExp('^[0-9]{3}$'), true],
 'SS': [new RegExp('^((6759[0-9]{12})|(49[013][1356][0-9]{13})|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$'), new RegExp('^([0-9]{3}|[0-9]{4})?$'), true],
 'OT': [false, new RegExp('^([0-9]{3}|[0-9]{4})?$'), false]
});

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
function popWin(url,win,para) {
 var win = window.open(url,win,para);
 win.focus();
}

function setLocation(url){
 window.location.href = url;
}

function setPLocation(url, setFocus){
 if( setFocus ) {
 window.opener.focus();
 }
 window.opener.location.href = url;
}

function setLanguageCode(code, fromCode){
 //TODO: javascript cookies have different domain and path than php cookies
 var href = window.location.href;
 var after = '', dash;
 if (dash = href.match(/\#(.*)$/)) {
 href = href.replace(/\#(.*)$/, '');
 after = dash[0];
 }

 if (href.match(/[?]/)) {
 var re = /([?&]store=)[a-z0-9_]*/;
 if (href.match(re)) {
 href = href.replace(re, '$1'+code);
 } else {
 href += '&store='+code;
 }

 var re = /([?&]from_store=)[a-z0-9_]*/;
 if (href.match(re)) {
 href = href.replace(re, '');
 }
 } else {
 href += '?store='+code;
 }
 if (typeof(fromCode) != 'undefined') {
 href += '&from_store='+fromCode;
 }
 href += after;

 setLocation(href);
}

/**
 * Add classes to specified elements.
 * Supported classes are: 'odd', 'even', 'first', 'last'
 *
 * @param elements - array of elements to be decorated
 * [@param decorateParams] - array of classes to be set. If omitted, all available will be used
 */
function decorateGeneric(elements, decorateParams)
{
 var allSupportedParams = ['odd', 'even', 'first', 'last'];
 var _decorateParams = {};
 var total = elements.length;

 if (total) {
 // determine params called
 if (typeof(decorateParams) == 'undefined') {
 decorateParams = allSupportedParams;
 }
 if (!decorateParams.length) {
 return;
 }
 for (var k in allSupportedParams) {
 _decorateParams[allSupportedParams[k]] = false;
 }
 for (var k in decorateParams) {
 _decorateParams[decorateParams[k]] = true;
 }

 // decorate elements
 // elements[0].addClassName('first'); // will cause bug in IE (#5587)
 if (_decorateParams.first) {
 Element.addClassName(elements[0], 'first');
 }
 if (_decorateParams.last) {
 Element.addClassName(elements[total-1], 'last');
 }
 for (var i = 0; i < total; i++) {
 if ((i + 1) % 2 == 0) {
 if (_decorateParams.even) {
 Element.addClassName(elements[i], 'even');
 }
 }
 else {
 if (_decorateParams.odd) {
 Element.addClassName(elements[i], 'odd');
 }
 }
 }
 }
}

/**
 * Decorate table rows and cells, tbody etc
 * @see decorateGeneric()
 */
function decorateTable(table, options) {
 var table = $(table);
 if (table) {
 // set default options
 var _options = {
 'tbody' : false,
 'tbody tr' : ['odd', 'even', 'first', 'last'],
 'thead tr' : ['first', 'last'],
 'tfoot tr' : ['first', 'last'],
 'tr td' : ['last']
 };
 // overload options
 if (typeof(options) != 'undefined') {
 for (var k in options) {
 _options[k] = options[k];
 }
 }
 // decorate
 if (_options['tbody']) {
 decorateGeneric(table.select('tbody'), _options['tbody']);
 }
 if (_options['tbody tr']) {
 decorateGeneric(table.select('tbody tr'), _options['tbody tr']);
 }
 if (_options['thead tr']) {
 decorateGeneric(table.select('thead tr'), _options['thead tr']);
 }
 if (_options['tfoot tr']) {
 decorateGeneric(table.select('tfoot tr'), _options['tfoot tr']);
 }
 if (_options['tr td']) {
 var allRows = table.select('tr');
 if (allRows.length) {
 for (var i = 0; i < allRows.length; i++) {
 decorateGeneric(allRows[i].getElementsByTagName('TD'), _options['tr td']);
 }
 }
 }
 }
}

/**
 * Set "odd", "even" and "last" CSS classes for list items
 * @see decorateGeneric()
 */
function decorateList(list, nonRecursive) {
 if ($(list)) {
 if (typeof(nonRecursive) == 'undefined') {
 var items = $(list).select('li')
 }
 else {
 var items = $(list).childElements();
 }
 decorateGeneric(items, ['odd', 'even', 'last']);
 }
}

/**
 * Set "odd", "even" and "last" CSS classes for list items
 * @see decorateGeneric()
 */
function decorateDataList(list) {
 list = $(list);
 if (list) {
 decorateGeneric(list.select('dt'), ['odd', 'even', 'last']);
 decorateGeneric(list.select('dd'), ['odd', 'even', 'last']);
 }
}

/**
 * Formats currency using patern
 * format - JSON (pattern, decimal, decimalsDelimeter, groupsDelimeter)
 * showPlus - true (always show '+'or '-'),
 * false (never show '-' even if number is negative)
 * null (show '-' if number is negative)
 */

function formatCurrency(price, format, showPlus){
 precision = isNaN(format.precision = Math.abs(format.precision)) ? 2 : format.precision;
 requiredPrecision = isNaN(format.requiredPrecision = Math.abs(format.requiredPrecision)) ? 2 : format.requiredPrecision;

 //precision = (precision > requiredPrecision) ? precision : requiredPrecision;
 //for now we don't need this difference so precision is requiredPrecision
 precision = requiredPrecision;

 integerRequired = isNaN(format.integerRequired = Math.abs(format.integerRequired)) ? 1 : format.integerRequired;

 decimalSymbol = format.decimalSymbol == undefined ? "," : format.decimalSymbol;
 groupSymbol = format.groupSymbol == undefined ? "." : format.groupSymbol;
 groupLength = format.groupLength == undefined ? 3 : format.groupLength;

 if (showPlus == undefined || showPlus == true) {
 s = price < 0 ? "-" : ( showPlus ? "+" : "");
 } else if (showPlus == false) {
 s = '';
 }

 i = parseInt(price = Math.abs(+price || 0).toFixed(precision)) + "";
 pad = (i.length < integerRequired) ? (integerRequired - i.length) : 0;
 while (pad) { i = '0' + i; pad--; }

 j = (j = i.length) > groupLength ? j % groupLength : 0;
 re = new RegExp("(\\d{" + groupLength + "})(?=\\d)", "g");

 /**
 * replace(/-/, 0) is only for fixing Safari bug which appears
 * when Math.abs(0).toFixed() executed on "0" number.
 * Result is "0.-0" :(
 */
 r = (j ? i.substr(0, j) + groupSymbol : "") + i.substr(j).replace(re, "$1" + groupSymbol) + (precision ? decimalSymbol + Math.abs(price - i).toFixed(precision).replace(/-/, 0).slice(2) : "")

 if (format.pattern.indexOf('{sign}') == -1) {
 pattern = s + format.pattern;
 } else {
 pattern = format.pattern.replace('{sign}', s);
 }

 return pattern.replace('%s', r).replace(/^\s\s*/, '').replace(/\s\s*$/, '');
};

function expandDetails(el, childClass) {
 if (Element.hasClassName(el,'show-details')) {
 $$(childClass).each(function(item){item.hide()});
 Element.removeClassName(el,'show-details');
 }
 else {
 $$(childClass).each(function(item){item.show()});
 Element.addClassName(el,'show-details');
 }
}

// Version 1.0
var isIE = navigator.appVersion.match(/MSIE/) == "MSIE";

if (!window.Varien)
 var Varien = new Object();

Varien.showLoading = function(){
 Element.show('loading-process');
}
Varien.hideLoading = function(){
 Element.hide('loading-process');
}
Varien.GlobalHandlers = {
 onCreate: function() {
 Varien.showLoading();
 },

 onComplete: function() {
 if(Ajax.activeRequestCount == 0) {
 Varien.hideLoading();
 }
 }
};

Ajax.Responders.register(Varien.GlobalHandlers);

/**
 * Quick Search form client model
 */
Varien.searchForm = Class.create();
Varien.searchForm.prototype = {
 initialize : function(form, field, emptyText){
 this.form = $(form);
 this.field = $(field);
 this.emptyText = emptyText;

 Event.observe(this.form, 'submit', this.submit.bind(this));
 Event.observe(this.field, 'focus', this.focus.bind(this));
 Event.observe(this.field, 'blur', this.blur.bind(this));
 this.blur();
 },

 submit : function(event){
 if (this.field.value == this.emptyText || this.field.value == ''){
 Event.stop(event);
 return false;
 }
 return true;
 },

 focus : function(event){
 if(this.field.value==this.emptyText){
 this.field.value='';
 }

 },

 blur : function(event){
 if(this.field.value==''){
 this.field.value=this.emptyText;
 }
 },

 initAutocomplete : function(url, destinationElement){
 new Ajax.Autocompleter(
 this.field,
 destinationElement,
 url,
 {
 paramName: this.field.name,
 minChars: 2,
 updateElement: this._selectAutocompleteItem.bind(this)
 }
 );
 },

 _selectAutocompleteItem : function(element){
 if(element.title){
 this.field.value = element.title;
 }
 this.submit();
 }
}

Varien.Tabs = Class.create();
Varien.Tabs.prototype = {
 initialize: function(selector) {
 var self=this;
 $$(selector+' a').each(this.initTab.bind(this));
 },

 initTab: function(el) {
 el.href = 'javascript:void(0)';
 if ($(el.parentNode).hasClassName('active')) {
 this.showContent(el);
 }
 el.observe('click', this.showContent.bind(this, el));
 },

 showContent: function(a) {
 var li = $(a.parentNode), ul = $(li.parentNode);
 ul.getElementsBySelector('li', 'ol').each(function(el){
 var contents = $(el.id+'_contents');
 if (el==li) {
 el.addClassName('active');
 contents.show();
 } else {
 el.removeClassName('active');
 contents.hide();
 }
 });
 }
}

Varien.DOB = Class.create();
Varien.DOB.prototype = {
 initialize: function(selector, required, format) {
 var el = $$(selector)[0];
 this.day = Element.select($(el), '.dob-day input')[0];
 this.month = Element.select($(el), '.dob-month input')[0];
 this.year = Element.select($(el), '.dob-year input')[0];
 this.dob = Element.select($(el), '.dob-full input')[0];
 this.advice = Element.select($(el), '.validation-advice')[0];
 this.required = required;
 this.format = format;

 this.day.validate = this.validate.bind(this);
 this.month.validate = this.validate.bind(this);
 this.year.validate = this.validate.bind(this);

 this.advice.hide();
 },

 validate: function() {
 var error = false;

 if (this.day.value=='' && this.month.value=='' && this.year.value=='') {
 if (this.required) {
 error = 'This date is a required value.';
 } else {
 this.dob.value = '';
 }
 } else if (this.day.value=='' || this.month.value=='' || this.year.value=='') {
 error = 'Please enter a valid full date.';
 } else {
 var date = new Date();
 if (this.day.value<1 || this.day.value>31) {
 error = 'Please enter a valid day (1-31).';
 } else if (this.month.value<1 || this.month.value>12) {
 error = 'Please enter a valid month (1-12).';
 } else if (this.year.value<1900 || this.year.value>date.getFullYear()) {
 error = 'Please enter a valid year (1900-'+date.getFullYear()+').';
 } else {
 this.dob.value = this.format.replace(/(%m|%b)/i, this.month.value).replace(/(%d|%e)/i, this.day.value).replace(/%y/i, this.year.value);
 var testDOB = this.month.value + '/' + this.day.value + '/'+ this.year.value;
 var test = new Date(testDOB);
 if (isNaN(test)) {
 error = 'Please enter a valid date.';
 }
 }
 }

 if (error !== false) {
 try {
 this.advice.innerHTML = Translator.translate(error);
 }
 catch (e) {
 this.advice.innerHTML = error;
 }
 this.advice.show();
 return false;
 }

 this.advice.hide();
 return true;
 }
}

Validation.addAllThese([
 ['validate-custom', ' ', function(v,elm) {
 return elm.validate();
 }]
]);

function truncateOptions() {
 $$('.truncated').each(function(element){
 Event.observe(element, 'mouseover', function(){
 if (element.down('div.truncated_full_value')) {
 element.down('div.truncated_full_value').addClassName('show')
 }
 });
 Event.observe(element, 'mouseout', function(){
 if (element.down('div.truncated_full_value')) {
 element.down('div.truncated_full_value').removeClassName('show')
 }
 });

 });
}
Event.observe(window, 'load', function(){
 truncateOptions();
});
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category Mage
 * @package Js
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

var Translate = Class.create();
Translate.prototype = {
 initialize: function(data){
 this.data = $H(data);
 },

 translate : function(){
 var args = arguments;
 var text = arguments[0];

 if(this.data.get(text)){
 return this.data.get(text);
 }
 return text;
 },
 add : function() {
 if (arguments.length > 1) {
 this.data.set(arguments[0], arguments[1]);
 } else if (typeof arguments[0] =='object') {
 $H(arguments[0]).each(function (pair){
 this.data.set(pair.key, pair.value);
 }.bind(this));
 }
 }
}
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
/*
 * Caudium - An extensible World Wide Web server
 * Copyright C 2002 The Caudium Group
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 */

/*
 * base64.js - a JavaScript implementation of the base64 algorithm,
 * (mostly) as defined in RFC 2045.
 *
 * This is a direct JavaScript reimplementation of the original C code
 * as found in the Exim mail transport agent, by Philip Hazel.
 *
 * $Id: base64.js,v 1.7 2002/07/16 17:21:23 kazmer Exp $
 *
 */


function encode_base64( what )
{
 var base64_encodetable = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
 var result = "";
 var len = what.length;
 var x, y;
 var ptr = 0;

 while( len-- > 0 )
 {
 x = what.charCodeAt( ptr++ );
 result += base64_encodetable.charAt( ( x >> 2 ) & 63 );

 if( len-- <= 0 )
 {
 result += base64_encodetable.charAt( ( x << 4 ) & 63 );
 result += "==";
 break;
 }

 y = what.charCodeAt( ptr++ );
 result += base64_encodetable.charAt( ( ( x << 4 ) | ( ( y >> 4 ) & 15 ) ) & 63 );

 if ( len-- <= 0 )
 {
 result += base64_encodetable.charAt( ( y << 2 ) & 63 );
 result += "=";
 break;
 }

 x = what.charCodeAt( ptr++ );
 result += base64_encodetable.charAt( ( ( y << 2 ) | ( ( x >> 6 ) & 3 ) ) & 63 );
 result += base64_encodetable.charAt( x & 63 );

 }

 return result;
}


function decode_base64( what )
{
 var base64_decodetable = new Array (
 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255,
 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255,
 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 62, 255, 255, 255, 63,
 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 255, 255, 255, 255, 255, 255,
 255, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 255, 255, 255, 255, 255,
 255, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 255, 255, 255, 255, 255
 );
 var result = "";
 var len = what.length;
 var x, y;
 var ptr = 0;

 while( !isNaN( x = what.charCodeAt( ptr++ ) ) )
 {
 if( x == 13 || x == 10 )
 continue;

 if( ( x > 127 ) || (( x = base64_decodetable[x] ) == 255) )
 return false;
 if( ( isNaN( y = what.charCodeAt( ptr++ ) ) ) || (( y = base64_decodetable[y] ) == 255) )
 return false;

 result += String.fromCharCode( (x << 2) | (y >> 4) );

 if( (x = what.charCodeAt( ptr++ )) == 61 )
 {
 if( (what.charCodeAt( ptr++ ) != 61) || (!isNaN(what.charCodeAt( ptr ) ) ) )
 return false;
 }
 else
 {
 if( ( x > 127 ) || (( x = base64_decodetable[x] ) == 255) )
 return false;
 result += String.fromCharCode( (y << 4) | (x >> 2) );
 if( (y = what.charCodeAt( ptr++ )) == 61 )
 {
 if( !isNaN(what.charCodeAt( ptr ) ) )
 return false;
 }
 else
 {
 if( (y > 127) || ((y = base64_decodetable[y]) == 255) )
 return false;
 result += String.fromCharCode( (x << 6) | y );
 }
 }
 }
 return result;
}

function wrap76( what )
{
 var result = "";
 var i;

 for(i=0; i < what.length; i+=76)
 {
 result += what.substring(i, i+76) + String.fromCharCode(13) + String.fromCharCode(10);
 }
 return result;
}

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
// from http://www.someelement.com/2007/03/eventpublisher-custom-events-la-pubsub.html
varienEvents = Class.create();

varienEvents.prototype = {
 initialize: function() {
 this.arrEvents = {};
 this.eventPrefix = '';
 },

 /**
 * Attaches a {handler} function to the publisher's {eventName} event for execution upon the event firing
 * @param {String} eventName
 * @param {Function} handler
 * @param {Boolean} asynchFlag [optional] Defaults to false if omitted. Indicates whether to execute {handler} asynchronously (true) or not (false).
 */
 attachEventHandler : function(eventName, handler) {
 if ((typeof handler == 'undefined') || (handler == null)) {
 return;
 }
 eventName = eventName + this.eventPrefix;
 // using an event cache array to track all handlers for proper cleanup
 if (this.arrEvents[eventName] == null){
 this.arrEvents[eventName] = [];
 }
 //create a custom object containing the handler method and the asynch flag
 var asynchVar = arguments.length > 2 ? arguments[2] : false;
 var handlerObj = {
 method: handler,
 asynch: asynchVar
 };
 this.arrEvents[eventName].push(handlerObj);
 },

 /**
 * Removes a single handler from a specific event
 * @param {String} eventName The event name to clear the handler from
 * @param {Function} handler A reference to the handler function to un-register from the event
 */
 removeEventHandler : function(eventName, handler) {
 eventName = eventName + this.eventPrefix;
 if (this.arrEvents[eventName] != null){
 this.arrEvents[eventName] = this.arrEvents[eventName].reject(function(obj) { return obj.method == handler; });
 }
 },

 /**
 * Removes all handlers from a single event
 * @param {String} eventName The event name to clear handlers from
 */
 clearEventHandlers : function(eventName) {
 eventName = eventName + this.eventPrefix;
 this.arrEvents[eventName] = null;
 },

 /**
 * Removes all handlers from ALL events
 */
 clearAllEventHandlers : function() {
 this.arrEvents = {};
 },

 /**
 * Fires the event {eventName}, resulting in all registered handlers to be executed.
 * It also collects and returns results of all non-asynchronous handlers
 * @param {String} eventName The name of the event to fire
 * @params {Object} args [optional] Any object, will be passed into the handler function as the only argument
 * @return {Array}
 */
 fireEvent : function(eventName) {
 var evtName = eventName + this.eventPrefix;
 var results = [];
 var result;
 if (this.arrEvents[evtName] != null) {
 var len = this.arrEvents[evtName].length; //optimization
 for (var i = 0; i < len; i++) {
 try {
 if (arguments.length > 1) {
 if (this.arrEvents[evtName][i].asynch) {
 var eventArgs = arguments[1];
 var method = this.arrEvents[evtName][i].method.bind(this);
 setTimeout(function() { method(eventArgs) }.bind(this), 10);
 }
 else{
 result = this.arrEvents[evtName][i].method(arguments[1]);
 }
 }
 else {
 if (this.arrEvents[evtName][i].asynch) {
 var eventHandler = this.arrEvents[evtName][i].method;
 setTimeout(eventHandler, 1);
 }
 else if (this.arrEvents && this.arrEvents[evtName] && this.arrEvents[evtName][i] && this.arrEvents[evtName][i].method){
 result = this.arrEvents[evtName][i].method();
 }
 }
 results.push(result);
 }
 catch (e) {
 if (this.id){
 alert("error: error in " + this.id + ".fireEvent():\n\nevent name: " + eventName + "\n\nerror message: " + e.message);
 }
 else {
 alert("error: error in [unknown object].fireEvent():\n\nevent name: " + eventName + "\n\nerror message: " + e.message);
 }
 }
 }
 }
 return results;
 }
};

varienGlobalEvents = new varienEvents();

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

var SessionError = Class.create();
SessionError.prototype = {
 initialize: function(errorText) {
 this.errorText = errorText;
 },
 toString: function()
 {
 return 'Session Error:' + this.errorText;
 }
};

Ajax.Request.addMethods({
 initialize: function($super, url, options){
 $super(options);
 this.transport = Ajax.getTransport();
 if (!url.match(new RegExp('[?&]isAjax=true',''))) {
 url = url.match(new RegExp('\\?',"g")) ? url + '&isAjax=true' : url + '?isAjax=true';
 }
 if (!this.options.parameters) {
 this.options.parameters = {
 form_key: FORM_KEY
 };
 }
 if (!this.options.parameters.form_key) {
 this.options.parameters.form_key = FORM_KEY;
 }
 
 this.request(url);
 },
 respondToReadyState: function(readyState) {
 var state = Ajax.Request.Events[readyState], response = new Ajax.Response(this);

 if (state == 'Complete') {
 try {
 this._complete = true;
 if (response.responseJSON && typeof(response.responseJSON) == 'object') {
 if (response.responseJSON.ajaxExpired && response.responseJSON.ajaxRedirect) {
 window.location.replace(response.responseJSON.ajaxRedirect);
 throw new SessionError('session expired');
 }
 }

 (this.options['on' + response.status]
 || this.options['on' + (this.success() ? 'Success' : 'Failure')]
 || Prototype.emptyFunction)(response, response.headerJSON);
 } catch (e) {
 this.dispatchException(e);
 if (e instanceof SessionError) {
 return;
 }
 }

 var contentType = response.getHeader('Content-type');
 if (this.options.evalJS == 'force'
 || (this.options.evalJS && this.isSameOrigin() && contentType
 && contentType.match(/^\s*(text|application)\/(x-)?(java|ecma)script(;.*)?\s*$/i))) {
 this.evalResponse();
 }
 }

 try {
 (this.options['on' + state] || Prototype.emptyFunction)(response, response.headerJSON);
 Ajax.Responders.dispatch('on' + state, this, response, response.headerJSON);
 } catch (e) {
 this.dispatchException(e);
 }

 if (state == 'Complete') {
 // avoid memory leak in MSIE: clean up
 this.transport.onreadystatechange = Prototype.emptyFunction;
 }
 }
});

Ajax.Updater.respondToReadyState = Ajax.Request.respondToReadyState;
//Ajax.Updater = Object.extend(Ajax.Updater, {
// initialize: function($super, container, url, options) {
// this.container = {
// success: (container.success || container),
// failure: (container.failure || (container.success ? null : container))
// };
//
// options = Object.clone(options);
// var onComplete = options.onComplete;
// options.onComplete = (function(response, json) {
// this.updateContent(response.responseText);
// if (Object.isFunction(onComplete)) onComplete(response, json);
// }).bind(this);
//
// $super((url.match(new RegExp('\\?',"g")) ? url + '&isAjax=1' : url + '?isAjax=1'), options);
// }
//});

var varienLoader = new Class.create();

varienLoader.prototype = {
 initialize : function(caching){
 this.callback= false;
 this.cache = $H();
 this.caching = caching || false;
 this.url = false;
 },

 getCache : function(url){
 if(this.cache.get(url)){
 return this.cache.get(url)
 }
 return false;
 },

 load : function(url, params, callback){
 this.url = url;
 this.callback = callback;

 if(this.caching){
 var transport = this.getCache(url);
 if(transport){
 this.processResult(transport);
 return;
 }
 }

 if (typeof(params.updaterId) != 'undefined') {
 new Ajax.Updater(params.updaterId, url, {
 evalScripts : true,
 onComplete: this.processResult.bind(this),
 onFailure: this._processFailure.bind(this)
 });
 }
 else {
 new Ajax.Request(url,{
 method: 'post',
 parameters: params || {},
 onComplete: this.processResult.bind(this),
 onFailure: this._processFailure.bind(this)
 });
 }
 },

 _processFailure : function(transport){
 location.href = BASE_URL;
 },

 processResult : function(transport){
 if(this.caching){
 this.cache.set(this.url, transport);
 }
 if(this.callback){
 this.callback(transport.responseText);
 }
 }
}

if (!window.varienLoaderHandler)
 var varienLoaderHandler = new Object();

varienLoaderHandler.handler = {
 onCreate: function(request) {
 if(request.options.loaderArea===false){
 return;
 }

 request.options.loaderArea = $$('#html-body .wrapper')[0]; // Blocks all page

 if(request && request.options.loaderArea){
 Element.clonePosition($('loading-mask'), $(request.options.loaderArea), {offsetLeft:-2})
 toggleSelectsUnderBlock($('loading-mask'), false);
 Element.show('loading-mask');
 setLoaderPosition();
 if(request.options.loaderArea=='html-body'){
 //Element.show('loading-process');
 }
 }
 else{
 //Element.show('loading-process');
 }
 },

 onComplete: function(transport) {
 if(Ajax.activeRequestCount == 0) {
 //Element.hide('loading-process');
 toggleSelectsUnderBlock($('loading-mask'), true);
 Element.hide('loading-mask');
 }
 }
};

/**
 * @todo need calculate middle of visible area and scroll bind
 */
function setLoaderPosition(){
 var elem = $('loading_mask_loader');
 if (elem && Prototype.Browser.IE) {
 var middle = parseInt(document.body.clientHeight/2)+document.body.scrollTop;
 elem.style.position = 'absolute';
 elem.style.top = middle;
 }
}

/*function getRealHeight() {
 var body = document.body;
 if (window.innerHeight && window.scrollMaxY) {
 return window.innerHeight + window.scrollMaxY;
 }
 return Math.max(body.scrollHeight, body.offsetHeight);
}*/



function toggleSelectsUnderBlock(block, flag){
 if(Prototype.Browser.IE){
 var selects = document.getElementsByTagName("select");
 for(var i=0; i<selects.length; i++){
 /**
 * @todo: need check intersection
 */
 if(flag){
 if(selects[i].needShowOnSuccess){
 selects[i].needShowOnSuccess = false;
 // Element.show(selects[i])
 selects[i].style.visibility = '';
 }
 }
 else{
 if(Element.visible(selects[i])){
 // Element.hide(selects[i]);
 selects[i].style.visibility = 'hidden';
 selects[i].needShowOnSuccess = true;
 }
 }
 }
 }
}

Ajax.Responders.register(varienLoaderHandler.handler);

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
var varienGrid = new Class.create();

varienGrid.prototype = {
 initialize : function(containerId, url, pageVar, sortVar, dirVar, filterVar){
 this.containerId = containerId;
 this.url = url;
 this.pageVar = pageVar || false;
 this.sortVar = sortVar || false;
 this.dirVar = dirVar || false;
 this.filterVar = filterVar || false;
 this.tableSufix = '_table';
 this.useAjax = false;
 this.rowClickCallback = false;
 this.checkboxCheckCallback = false;
 this.preInitCallback = false;
 this.initCallback = false;
 this.initRowCallback = false;
 this.doFilterCallback = false;

 this.reloadParams = false;

 this.trOnMouseOver = this.rowMouseOver.bindAsEventListener(this);
 this.trOnMouseOut = this.rowMouseOut.bindAsEventListener(this);
 this.trOnClick = this.rowMouseClick.bindAsEventListener(this);
 this.trOnDblClick = this.rowMouseDblClick.bindAsEventListener(this);
 this.trOnKeyPress = this.keyPress.bindAsEventListener(this);

 this.thLinkOnClick = this.doSort.bindAsEventListener(this);
 this.initGrid();
 },
 initGrid : function(){
 if(this.preInitCallback){
 this.preInitCallback(this);
 }
 if($(this.containerId+this.tableSufix)){
 this.rows = $$('#'+this.containerId+this.tableSufix+' tbody tr');
 for (var row=0; row<this.rows.length; row++) {
 if(row%2==0){
 Element.addClassName(this.rows[row], 'even');
 }

 Event.observe(this.rows[row],'mouseover',this.trOnMouseOver);
 Event.observe(this.rows[row],'mouseout',this.trOnMouseOut);
 Event.observe(this.rows[row],'click',this.trOnClick);
 Event.observe(this.rows[row],'dblclick',this.trOnDblClick);

 if(this.initRowCallback){
 try {
 this.initRowCallback(this, this.rows[row]);
 } catch (e) {
 if(console) {
 console.log(e);
 }
 }
 }
 }
 }
 if(this.sortVar && this.dirVar){
 var columns = $$('#'+this.containerId+this.tableSufix+' thead a');

 for(var col=0; col<columns.length; col++){
 Event.observe(columns[col],'click',this.thLinkOnClick);
 }
 }
 this.bindFilterFields();
 this.bindFieldsChange();
 if(this.initCallback){
 try {
 this.initCallback(this);
 }
 catch (e) {
 if(console) {
 console.log(e);
 }
 }
 }
 },
 getContainerId : function(){
 return this.containerId;
 },
 rowMouseOver : function(event){
 var element = Event.findElement(event, 'tr');
 Element.addClassName(element, 'on-mouse');

 if (!Element.hasClassName('pointer')
 && (this.rowClickCallback !== openGridRow || element.title)) {
 if (element.title) {
 Element.addClassName(element, 'pointer');
 }
 }
 },
 rowMouseOut : function(event){
 var element = Event.findElement(event, 'tr');
 Element.removeClassName(element, 'on-mouse');
 },
 rowMouseClick : function(event){
 if(this.rowClickCallback){
 try{
 this.rowClickCallback(this, event);
 }
 catch(e){}
 }
 varienGlobalEvents.fireEvent('gridRowClick', event);
 },
 rowMouseDblClick : function(event){
 varienGlobalEvents.fireEvent('gridRowDblClick', event);
 },
 keyPress : function(event){

 },
 doSort : function(event){
 var element = Event.findElement(event, 'a');

 if(element.name && element.title){
 this.addVarToUrl(this.sortVar, element.name);
 this.addVarToUrl(this.dirVar, element.title);
 this.reload(this.url);
 }
 Event.stop(event);
 return false;
 },
 loadByElement : function(element){
 if(element && element.name){
 this.reload(this.addVarToUrl(element.name, element.value));
 }
 },
 reload : function(url){
 if (!this.reloadParams) {
 this.reloadParams = {form_key: FORM_KEY};
 }
 else {
 this.reloadParams.form_key = FORM_KEY;
 }
 url = url || this.url;
 if(this.useAjax){
 new Ajax.Updater(
 this.containerId,
 url + (url.match(new RegExp('\\?')) ? '&ajax=true' : '?ajax=true' ),
 {
 onComplete:this.initGrid.bind(this),
 onFailure:this._processFailure.bind(this),
 evalScripts:true,
 parameters:this.reloadParams || {},
 loaderArea: this.containerId
 }
 );
 return;
 }
 else{
 if(this.reloadParams){
 $H(this.reloadParams).each(function(pair){
 url = this.addVarToUrl(pair.key, pair.value);
 }.bind(this));
 }
 location.href = url;
 }
 },
 /*_processComplete : function(transport){
 console.log(transport);
 if (transport && transport.responseText){
 try{
 response = eval('(' + transport.responseText + ')');
 }
 catch (e) {
 response = {};
 }
 }
 if (response.ajaxExpired && response.ajaxRedirect) {
 location.href = response.ajaxRedirect;
 return false;
 }
 this.initGrid();
 },*/
 _processFailure : function(transport){
 location.href = BASE_URL;
 },
 addVarToUrl : function(varName, varValue){
 var re = new RegExp('\/('+varName+'\/.*?\/)');
 var parts = this.url.split(new RegExp('\\?'));
 this.url = parts[0].replace(re, '/');
 this.url+= varName+'/'+varValue+'/';
 if(parts.size()>1) {
 this.url+= '?' + parts[1];
 }
 //this.url = this.url.replace(/([^:])\/{2,}/g, '$1/');
 return this.url;
 },
 doExport : function(){
 if($(this.containerId+'_export')){
 location.href = $(this.containerId+'_export').value;
 }
 },
 bindFilterFields : function(){
 var filters = $$('#'+this.containerId+' .filter input', '#'+this.containerId+' .filter select');
 for (var i=0; i<filters.length; i++) {
 Event.observe(filters[i],'keypress',this.filterKeyPress.bind(this));
 }
 },
 bindFieldsChange : function(){
 if (!$(this.containerId)) {
 return;
 }
// var dataElements = $(this.containerId+this.tableSufix).down('.data tbody').select('input', 'select');
 var dataElements = $(this.containerId+this.tableSufix).down('tbody').select('input', 'select');
 for(var i=0; i<dataElements.length;i++){
 Event.observe(dataElements[i], 'change', dataElements[i].setHasChanges.bind(dataElements[i]));
 }
 },
 filterKeyPress : function(event){
 if(event.keyCode==Event.KEY_RETURN){
 this.doFilter();
 }
 },
 doFilter : function(){
 var filters = $$('#'+this.containerId+' .filter input', '#'+this.containerId+' .filter select');
 var elements = [];
 for(var i in filters){
 if(filters[i].value && filters[i].value.length) elements.push(filters[i]);
 }
 if (!this.doFilterCallback || (this.doFilterCallback && this.doFilterCallback())) {
 this.reload(this.addVarToUrl(this.filterVar, encode_base64(Form.serializeElements(elements))));
 }
 },
 resetFilter : function(){
 this.reload(this.addVarToUrl(this.filterVar, ''));
 },
 checkCheckboxes : function(element){
 elements = Element.select($(this.containerId), 'input[name="'+element.name+'"]');
 for(var i=0; i<elements.length;i++){
 this.setCheckboxChecked(elements[i], element.checked);
 }
 },
 setCheckboxChecked : function(element, checked){
 element.checked = checked;
 element.setHasChanges({});
 if(this.checkboxCheckCallback){
 this.checkboxCheckCallback(this,element,checked);
 }
 },
 inputPage : function(event, maxNum){
 var element = Event.element(event);
 var keyCode = event.keyCode || event.which;
 if(keyCode==Event.KEY_RETURN){
 this.setPage(element.value);
 }
 /*if(keyCode>47 && keyCode<58){

 }
 else{
 Event.stop(event);
 }*/
 },
 setPage : function(pageNumber){
 this.reload(this.addVarToUrl(this.pageVar, pageNumber));
 }
};

function openGridRow(grid, event){
 var element = Event.findElement(event, 'tr');
 if(['a', 'input', 'select', 'option'].indexOf(Event.element(event).tagName.toLowerCase())!=-1) {
 return;
 }

 if(element.title){
 setLocation(element.title);
 }
}

var varienGridMassaction = Class.create();
varienGridMassaction.prototype = {
 /* Predefined vars */
 checkedValues: $H({}),
 checkedString: '',
 oldCallbacks: {},
 errorText:'',
 items: {},
 gridIds: [],
 currentItem: false,
 fieldTemplate: new Template('<input type="hidden" name="#{name}" value="#{value}" />'),
 initialize: function (containerId, grid, checkedValues, formFieldNameInternal, formFieldName) {
 this.setOldCallback('row_click', grid.rowClickCallback);
 this.setOldCallback('init', grid.initCallback);
 this.setOldCallback('init_row', grid.initRowCallback);
 this.setOldCallback('pre_init', grid.preInitCallback);

 this.useAjax = false;
 this.grid = grid;
 this.containerId = containerId;
 this.initMassactionElements();

 this.checkedString = checkedValues;
 this.formFieldName = formFieldName;
 this.formFieldNameInternal = formFieldNameInternal;

 this.grid.initCallback = this.onGridInit.bind(this);
 this.grid.preInitCallback = this.onGridPreInit.bind(this);
 this.grid.initRowCallback = this.onGridRowInit.bind(this);
 this.grid.rowClickCallback = this.onGridRowClick.bind(this);
 this.initCheckboxes();
 this.checkCheckboxes();
 },
 setUseAjax: function(flag) {
 this.useAjax = flag;
 },
 initMassactionElements: function() {
 this.container = $(this.containerId);
 this.form = $(this.containerId + '-form');
 this.count = $(this.containerId + '-count');
 this.validator = new Validation(this.form);
 this.formHiddens = $(this.containerId + '-form-hiddens');
 this.formAdditional = $(this.containerId + '-form-additional');
 this.select = $(this.containerId + '-select');
 this.select.observe('change', this.onSelectChange.bindAsEventListener(this));
 },
 setGridIds: function(gridIds) {
 this.gridIds = gridIds;
 this.updateCount();
 },
 getGridIds: function() {
 return this.gridIds;
 },
 setItems: function(items) {
 this.items = items;
 this.updateCount();
 },
 getItems: function() {
 return this.items;
 },
 getItem: function(itemId) {
 if(this.items[itemId]) {
 return this.items[itemId];
 }
 return false;
 },
 getOldCallback: function (callbackName) {
 return this.oldCallbacks[callbackName] ? this.oldCallbacks[callbackName] : Prototype.emptyFunction;
 },
 setOldCallback: function (callbackName, callback) {
 this.oldCallbacks[callbackName] = callback;
 },
 onGridPreInit: function(grid) {
 this.initMassactionElements();
 this.getOldCallback('pre_init')(grid);
 },
 onGridInit: function(grid) {
 this.initCheckboxes();
 this.checkCheckboxes();
 this.updateCount();
 this.getOldCallback('init')(grid);
 },
 onGridRowInit: function(grid, row) {
 this.getOldCallback('init_row')(grid, row);
 },
 onGridRowClick: function(grid, evt) {
 var tdElement = Event.findElement(evt, 'td');

 if(!$(tdElement).down('input')) {
 if($(tdElement).down('a') || $(tdElement).down('select')) {
 return;
 }
 var trElement = Event.findElement(evt, 'tr');
 if (trElement.title) {
 setLocation(trElement.title);
 }
 return;
 }

 if(Event.element(evt).isMassactionCheckbox) {
 this.setCheckbox(Event.element(evt));
 } else if (checkbox = this.findCheckbox(evt)) {
 checkbox.checked = !checkbox.checked;
 this.setCheckbox(checkbox);
 }
 },
 onSelectChange: function(evt) {
 var item = this.getSelectedItem();
 if(item) {
 this.formAdditional.update($(this.containerId + '-item-' + item.id + '-block').innerHTML);
 } else {
 this.formAdditional.update('');
 }

 this.validator.reset();
 },
 findCheckbox: function(evt) {
 if(['a', 'input', 'select'].indexOf(Event.element(evt).tagName.toLowerCase())!==-1) {
 return false;
 }
 checkbox = false;
 Event.findElement(evt, 'tr').select('.massaction-checkbox').each(function(element){
 if(element.isMassactionCheckbox) {
 checkbox = element;
 }
 }.bind(this));
 return checkbox;
 },
 initCheckboxes: function() {
 this.getCheckboxes().each(function(checkbox) {
 checkbox.isMassactionCheckbox = true;
 }.bind(this));
 },
 checkCheckboxes: function() {
 this.getCheckboxes().each(function(checkbox) {
 checkbox.checked = varienStringArray.has(checkbox.value, this.checkedString);
 }.bind(this));
 },
 selectAll: function() {
 this.setCheckedValues(this.getGridIds());
 this.checkCheckboxes();
 this.updateCount();
 return false;
 },
 unselectAll: function() {
 this.setCheckedValues('');
 this.checkCheckboxes();
 this.updateCount();
 return false;
 },
 selectVisible: function() {
 this.setCheckedValues(this.getCheckboxesValuesAsString());
 this.checkCheckboxes();
 this.updateCount();
 return false;
 },
 unselectVisible: function() {
 this.getCheckboxesValues().each(function(key){
 this.checkedString = varienStringArray.remove(key, this.checkedString);
 }.bind(this));
 this.checkCheckboxes();
 this.updateCount();
 return false;
 },
 setCheckedValues: function(values) {
 this.checkedString = values;
 },
 getCheckedValues: function() {
 return this.checkedString;
 },
 getCheckboxes: function() {
 var result = [];
 this.grid.rows.each(function(row){
 var checkboxes = row.select('.massaction-checkbox');
 checkboxes.each(function(checkbox){
 result.push(checkbox);
 });
 });
 return result;
 },
 getCheckboxesValues: function() {
 var result = [];
 this.getCheckboxes().each(function(checkbox) {
 result.push(checkbox.value);
 }.bind(this));
 return result;
 },
 getCheckboxesValuesAsString: function()
 {
 return this.getCheckboxesValues().join(',');
 },
 setCheckbox: function(checkbox) {
 if(checkbox.checked) {
 this.checkedString = varienStringArray.add(checkbox.value, this.checkedString);
 } else {
 this.checkedString = varienStringArray.remove(checkbox.value, this.checkedString);
 }
 this.updateCount();
 },
 updateCount: function() {
 this.count.update(varienStringArray.count(this.checkedString));
 if(!this.grid.reloadParams) {
 this.grid.reloadParams = {};
 }
 this.grid.reloadParams[this.formFieldNameInternal] = this.checkedString;
 },
 getSelectedItem: function() {
 if(this.getItem(this.select.value)) {
 return this.getItem(this.select.value);
 } else {
 return false;
 }
 },
 apply: function() {
 if(varienStringArray.count(this.checkedString) == 0) {
 alert(this.errorText);
 return;
 }

 var item = this.getSelectedItem();
 if(!item) {
 this.validator.validate();
 return;
 }
 this.currentItem = item;
 var fieldName = (item.field ? item.field : this.formFieldName);
 var fieldsHtml = '';

 if(this.currentItem.confirm && !window.confirm(this.currentItem.confirm)) {
 return;
 }

 this.formHiddens.update('');
 new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({name: fieldName, value: this.checkedString}));
 new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({name: 'massaction_prepare_key', value: fieldName}));

 if(!this.validator.validate()) {
 return;
 }

 if(this.useAjax && item.url) {
 new Ajax.Request(item.url, {
 'method': 'post',
 'parameters': this.form.serialize(true),
 'onComplete': this.onMassactionComplete.bind(this)
 });
 } else if(item.url) {
 this.form.action = item.url;
 this.form.submit();
 }
 },
 onMassactionComplete: function(transport) {
 if(this.currentItem.complete) {
 try {
 var listener = this.getListener(this.currentItem.complete) || Prototype.emptyFunction;
 listener(grid, this, transport);
 } catch (e) {}
 }
 },
 getListener: function(strValue) {
 return eval(strValue);
 }
};

var varienGridAction = {
 execute: function(select) {
 if(!select.value || !select.value.isJSON()) {
 return;
 }

 var config = select.value.evalJSON();
 if(config.confirm && !window.confirm(config.confirm)) {
 select.options[0].selected = true;
 return;
 }

 if(config.popup) {
 var win = window.open(config.href, 'action_window', 'width=500,height=600,resizable=1,scrollbars=1');
 win.focus();
 select.options[0].selected = true;
 } else {
 setLocation(config.href);
 }
 }
};

var varienStringArray = {
 remove: function(str, haystack)
 {
 haystack = ',' + haystack + ',';
 haystack = haystack.replace(new RegExp(',' + str + ',', 'g'), ',');
 return this.trimComma(haystack);
 },
 add: function(str, haystack)
 {
 haystack = ',' + haystack + ',';
 if (haystack.search(new RegExp(',' + str + ',', 'g'), haystack) === -1) {
 haystack += str + ',';
 }
 return this.trimComma(haystack);
 },
 has: function(str, haystack)
 {
 haystack = ',' + haystack + ',';
 if (haystack.search(new RegExp(',' + str + ',', 'g'), haystack) === -1) {
 return false;
 }
 return true;
 },
 count: function(haystack)
 {
 if (typeof haystack != 'string') {
 return 0;
 }
 if (match = haystack.match(new RegExp(',', 'g'))) {
 return match.length + 1;
 } else if (haystack.length != 0) {
 return 1;
 }
 return 0;
 },
 each: function(haystack, fnc)
 {
 var haystack = haystack.split(',');
 for (var i=0; i<haystack.length; i++) {
 fnc(haystack[i]);
 }
 },
 trimComma: function(string)
 {
 string = string.replace(new RegExp('^(,+)','i'), '');
 string = string.replace(new RegExp('(,+)$','i'), '');
 return string;
 }
};

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
var varienTabs = new Class.create();

varienTabs.prototype = {
 initialize : function(containerId, destElementId, activeTabId, shadowTabs){
 this.containerId = containerId;
 this.destElementId = destElementId;
 this.activeTab = null;

 this.tabOnClick = this.tabMouseClick.bindAsEventListener(this);

 this.tabs = $$('#'+this.containerId+' li a.tab-item-link');

 this.hideAllTabsContent();
 for (var tab=0; tab<this.tabs.length; tab++) {
 Event.observe(this.tabs[tab],'click',this.tabOnClick);
 // move tab contents to destination element
 if($(this.destElementId)){
 var tabContentElement = $(this.getTabContentElementId(this.tabs[tab]));
 if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
 $(this.destElementId).appendChild(tabContentElement);
 tabContentElement.container = this;
 tabContentElement.statusBar = this.tabs[tab];
 tabContentElement.tabObject = this.tabs[tab];
 this.tabs[tab].contentMoved = true;
 this.tabs[tab].container = this;
 this.tabs[tab].show = function(){
 this.container.showTabContent(this);
 }
 if(varienGlobalEvents){
 varienGlobalEvents.fireEvent('moveTab', {tab:this.tabs[tab]});
 }
 }
 }
/*
 // this code is pretty slow in IE, so lets do it in tabs*.phtml
 // mark ajax tabs as not loaded
 if (Element.hasClassName($(this.tabs[tab].id), 'ajax')) {
 Element.addClassName($(this.tabs[tab].id), 'notloaded');
 }
*/
 // bind shadow tabs
 if (this.tabs[tab].id && shadowTabs && shadowTabs[this.tabs[tab].id]) {
 this.tabs[tab].shadowTabs = shadowTabs[this.tabs[tab].id];
 }
 }

 this.displayFirst = activeTabId;
 Event.observe(window,'load',this.moveTabContentInDest.bind(this));
 },

 moveTabContentInDest : function(){
 for(var tab=0; tab<this.tabs.length; tab++){
 if($(this.destElementId) && !this.tabs[tab].contentMoved){
 var tabContentElement = $(this.getTabContentElementId(this.tabs[tab]));
 if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
 $(this.destElementId).appendChild(tabContentElement);
 tabContentElement.container = this;
 tabContentElement.statusBar = this.tabs[tab];
 tabContentElement.tabObject = this.tabs[tab];
 this.tabs[tab].container = this;
 this.tabs[tab].show = function(){
 this.container.showTabContent(this);
 }
 if(varienGlobalEvents){
 varienGlobalEvents.fireEvent('moveTab', {tab:this.tabs[tab]});
 }
 }
 }
 }
 if (this.displayFirst) {
 this.showTabContent($(this.displayFirst));
 this.displayFirst = null;
 }
 },

 getTabContentElementId : function(tab){
 if(tab){
 return tab.id+'_content';
 }
 return false;
 },

 tabMouseClick : function(event) {
 var tab = Event.findElement(event, 'a');

 // go directly to specified url or switch tab
 if ((tab.href.indexOf('#') != tab.href.length-1)
 && !(Element.hasClassName(tab, 'ajax'))
 ) {
 location.href = tab.href;
 }
 else {
 this.showTabContent(tab);
 }
 Event.stop(event);
 },

 hideAllTabsContent : function(){
 for(var tab in this.tabs){
 this.hideTabContent(this.tabs[tab]);
 }
 },

 // show tab, ready or not
 showTabContentImmediately : function(tab) {
 this.hideAllTabsContent();
 var tabContentElement = $(this.getTabContentElementId(tab));
 if (tabContentElement) {
 Element.show(tabContentElement);
 Element.addClassName(tab, 'active');
 // load shadow tabs, if any
 if (tab.shadowTabs && tab.shadowTabs.length) {
 for (var k in tab.shadowTabs) {
 this.loadShadowTab($(tab.shadowTabs[k]));
 }
 }
 if (!Element.hasClassName(tab, 'ajax only')) {
 Element.removeClassName(tab, 'notloaded');
 }
 this.activeTab = tab;
 }
 if (varienGlobalEvents) {
 varienGlobalEvents.fireEvent('showTab', {tab:tab});
 }
 },

 // the lazy show tab method
 showTabContent : function(tab) {
 var tabContentElement = $(this.getTabContentElementId(tab));
 if (tabContentElement) {
 if (this.activeTab != tab) {
 if (varienGlobalEvents) {
 if (varienGlobalEvents.fireEvent('tabChangeBefore', $(this.getTabContentElementId(this.activeTab))).indexOf('cannotchange') != -1) {
 return;
 };
 }
 }
 // wait for ajax request, if defined
 var isAjax = Element.hasClassName(tab, 'ajax');
 var isEmpty = tabContentElement.innerHTML=='' && tab.href.indexOf('#')!=tab.href.length-1;
 var isNotLoaded = Element.hasClassName(tab, 'notloaded');

 if ( isAjax && (isEmpty || isNotLoaded) )
 {
 new Ajax.Request(tab.href, {
 parameters: {form_key: FORM_KEY},
 evalScripts: true,
 onSuccess: function(transport) {
 try {
 if (transport.responseText.isJSON()) {
 var response = transport.responseText.evalJSON()
 if (response.error) {
 alert(response.message);
 }
 if(response.ajaxExpired && response.ajaxRedirect) {
 setLocation(response.ajaxRedirect);
 }
 } else {
 $(tabContentElement.id).update(transport.responseText);
 this.showTabContentImmediately(tab)
 }
 }
 catch (e) {
 $(tabContentElement.id).update(transport.responseText);
 this.showTabContentImmediately(tab)
 }
 }.bind(this)
 });
 }
 else {
 this.showTabContentImmediately(tab);
 }
 }
 },

 loadShadowTab : function(tab) {
 var tabContentElement = $(this.getTabContentElementId(tab));
 if (tabContentElement && Element.hasClassName(tab, 'ajax') && Element.hasClassName(tab, 'notloaded')) {
 new Ajax.Request(tab.href, {
 parameters: {form_key: FORM_KEY},
 evalScripts: true,
 onSuccess: function(transport) {
 try {
 if (transport.responseText.isJSON()) {
 var response = transport.responseText.evalJSON()
 if (response.error) {
 alert(response.message);
 }
 if(response.ajaxExpired && response.ajaxRedirect) {
 setLocation(response.ajaxRedirect);
 }
 } else {
 $(tabContentElement.id).update(transport.responseText);
 if (!Element.hasClassName(tab, 'ajax only')) {
 Element.removeClassName(tab, 'notloaded');
 }
 }
 }
 catch (e) {
 $(tabContentElement.id).update(transport.responseText);
 if (!Element.hasClassName(tab, 'ajax only')) {
 Element.removeClassName(tab, 'notloaded');
 }
 }
 }.bind(this)
 });
 }
 },

 hideTabContent : function(tab){
 var tabContentElement = $(this.getTabContentElementId(tab));
 if($(this.destElementId) && tabContentElement){
 Element.hide(tabContentElement);
 Element.removeClassName(tab, 'active');
 }
 if(varienGlobalEvents){
 varienGlobalEvents.fireEvent('hideTab', {tab:tab});
 }
 }
}
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
var varienForm = new Class.create();

varienForm.prototype = {
 initialize : function(formId, validationUrl){
 this.formId = formId;
 this.validationUrl = validationUrl;
 this.submitUrl = false;

 if($(this.formId)){
 this.validator = new Validation(this.formId, {onElementValidate : this.checkErrors.bind(this)});
 }
 this.errorSections = $H({});
 },

 checkErrors : function(result, elm){
 if(!result)
 elm.setHasError(true, this);
 else
 elm.setHasError(false, this);
 },

 submit : function(url){
 this.errorSections = $H({});
 this.canShowError = true;
 this.submitUrl = url;
 if(this.validator && this.validator.validate()){
 if(this.validationUrl){
 this._validate();
 }
 else{
 this._submit();
 }
 return true;
 }
 return false;
 },

 _validate : function(){
 new Ajax.Request(this.validationUrl,{
 method: 'post',
 parameters: $(this.formId).serialize(),
 onComplete: this._processValidationResult.bind(this),
 onFailure: this._processFailure.bind(this)
 });
 },

 _processValidationResult : function(transport){
 var response = transport.responseText.evalJSON();
 if(response.error){
 if($('messages')){
 $('messages').innerHTML = response.message;
 }
 }
 else{
 this._submit();
 }
 },

 _processFailure : function(transport){
 location.href = BASE_URL;
 },

 _submit : function(){
 if(this.submitUrl){
 $(this.formId).action = this.submitUrl;
 }
 $(this.formId).submit();
 }
}

/**
 * redeclare Validation.isVisible function
 *
 * use for not visible elements validation
 */
Validation.isVisible = function(elm){
 while (elm && elm.tagName != 'BODY') {
 if (elm.disabled) return false;
 if ((Element.hasClassName(elm, 'template') && Element.hasClassName(elm, 'no-display'))
 || Element.hasClassName(elm, 'ignore-validate')){
 return false;
 }
 elm = elm.parentNode;
 }
 return true;
}

/**
 * Additional elements methods
 */
var varienElementMethods = {
 setHasChanges : function(element, event){
 if($(element).hasClassName('no-changes')) return;
 var elm = element;
 while(elm && elm.tagName != 'BODY') {
 if(elm.statusBar)
 Element.addClassName($(elm.statusBar), 'changed')
 elm = elm.parentNode;
 }
 },
 setHasError : function(element, flag, form){
 var elm = element;
 while(elm && elm.tagName != 'BODY') {
 if(elm.statusBar){
 if(form.errorSections.keys().indexOf(elm.statusBar.id)<0)
 form.errorSections.set(elm.statusBar.id, flag);
 if(flag){
 Element.addClassName($(elm.statusBar), 'error');
 if(form.canShowError && $(elm.statusBar).show){
 form.canShowError = false;
 $(elm.statusBar).show();
 }
 form.errorSections.set(elm.statusBar.id, flag);
 }
 else if(!form.errorSections.get(elm.statusBar.id)){
 Element.removeClassName($(elm.statusBar), 'error')
 }
 }
 elm = elm.parentNode;
 }
 this.canShowElement = false;
 }
}

Element.addMethods(varienElementMethods);

// Global bind changes
varienWindowOnloadCache = {};
function varienWindowOnload(useCache){
 var dataElements = $$('input', 'select', 'textarea');
 for(var i=0; i<dataElements.length;i++){
 if(dataElements[i] && dataElements[i].id){
 if ((!useCache) || (!varienWindowOnloadCache[dataElements[i].id])) {
 Event.observe(dataElements[i], 'change', dataElements[i].setHasChanges.bind(dataElements[i]));
 if (useCache) {
 varienWindowOnloadCache[dataElements[i].id] = true;
 }
 }
 }
 }
}
Event.observe(window, 'load', varienWindowOnload);

RegionUpdater = Class.create();
RegionUpdater.prototype = {
 initialize: function (countryEl, regionTextEl, regionSelectEl, regions, disableAction, clearRegionValueOnDisable)
 {
 this.countryEl = $(countryEl);
 this.regionTextEl = $(regionTextEl);
 this.regionSelectEl = $(regionSelectEl);
// // clone for select element (#6924)
// this._regionSelectEl = {};
// this.tpl = new Template('<select class="#{className}" name="#{name}" id="#{id}">#{innerHTML}</select>');
 this.regions = regions;
 this.disableAction = (typeof disableAction=='undefined') ? 'hide' : disableAction;
 this.clearRegionValueOnDisable = (typeof clearRegionValueOnDisable == 'undefined') ? false : clearRegionValueOnDisable;

 if (this.regionSelectEl.options.length<=1) {
 this.update();
 }
 else {
 this.lastCountryId = this.countryEl.value;
 }

 this.countryEl.changeUpdater = this.update.bind(this);

 Event.observe(this.countryEl, 'change', this.update.bind(this));
 },

 update: function()
 {
 if (this.regions[this.countryEl.value]) {
// if (!this.regionSelectEl) {
// Element.insert(this.regionTextEl, {after : this.tpl.evaluate(this._regionSelectEl)});
// this.regionSelectEl = $(this._regionSelectEl.id);
// }
 if (this.lastCountryId!=this.countryEl.value) {
 var i, option, region, def;

 if (this.regionTextEl) {
 def = this.regionTextEl.value.toLowerCase();
 this.regionTextEl.value = '';
 }
 if (!def) {
 def = this.regionSelectEl.getAttribute('defaultValue');
 }

 this.regionSelectEl.options.length = 1;
 for (regionId in this.regions[this.countryEl.value]) {
 region = this.regions[this.countryEl.value][regionId];

 option = document.createElement('OPTION');
 option.value = regionId;
 option.text = region.name;

 if (this.regionSelectEl.options.add) {
 this.regionSelectEl.options.add(option);
 } else {
 this.regionSelectEl.appendChild(option);
 }

 if (regionId==def || region.name.toLowerCase()==def || region.code.toLowerCase()==def) {
 this.regionSelectEl.value = regionId;
 }
 }
 }

 if (this.disableAction=='hide') {
 if (this.regionTextEl) {
 this.regionTextEl.style.display = 'none';
 this.regionTextEl.style.disabled = true;
 }
 this.regionSelectEl.style.display = '';
 this.regionSelectEl.disabled = false;
 } else if (this.disableAction=='disable') {
 if (this.regionTextEl) {
 this.regionTextEl.disabled = true;
 }
 this.regionSelectEl.disabled = false;
 }
 this.setMarkDisplay(this.regionSelectEl, true);

 this.lastCountryId = this.countryEl.value;
 } else {
 if (this.disableAction=='hide') {
 if (this.regionTextEl) {
 this.regionTextEl.style.display = '';
 this.regionTextEl.style.disabled = false;
 }
 this.regionSelectEl.style.display = 'none';
 this.regionSelectEl.disabled = true;
 } else if (this.disableAction=='disable') {
 if (this.regionTextEl) {
 this.regionTextEl.disabled = false;
 }
 this.regionSelectEl.disabled = true;
 if (this.clearRegionValueOnDisable) {
 this.regionSelectEl.value = '';
 }
 } else if (this.disableAction=='nullify') {
 this.regionSelectEl.options.length = 1;
 this.regionSelectEl.value = '';
 this.regionSelectEl.selectedIndex = 0;
 this.lastCountryId = '';
 }
 this.setMarkDisplay(this.regionSelectEl, false);

// // clone required stuff from select element and then remove it
// this._regionSelectEl.className = this.regionSelectEl.className;
// this._regionSelectEl.name = this.regionSelectEl.name;
// this._regionSelectEl.id = this.regionSelectEl.id;
// this._regionSelectEl.innerHTML = this.regionSelectEl.innerHTML;
// Element.remove(this.regionSelectEl);
// this.regionSelectEl = null;
 }
 },

 setMarkDisplay: function(elem, display){
 if(elem.parentNode.parentNode){
 var marks = Element.select(elem.parentNode.parentNode, '.required');
 if(marks[0]){
 display ? marks[0].show() : marks[0].hide();
 }
 }
 }
}

regionUpdater = RegionUpdater;

/**
 * Fix errorrs in IE
 */
Event.pointerX = function(event){
 try{
 return event.pageX || (event.clientX +(document.documentElement.scrollLeft || document.body.scrollLeft));
 }
 catch(e){

 }
}
Event.pointerY = function(event){
 try{
 return event.pageY || (event.clientY +(document.documentElement.scrollTop || document.body.scrollTop));
 }
 catch(e){

 }
}

SelectUpdater = Class.create();
SelectUpdater.prototype = {
 initialize: function (firstSelect, secondSelect, selectFirstMessage, noValuesMessage, values, selected)
 {
 this.first = $(firstSelect);
 this.second = $(secondSelect);
 this.message = selectFirstMessage;
 this.values = values;
 this.noMessage = noValuesMessage;
 this.selected = selected;

 this.update();

 Event.observe(this.first, 'change', this.update.bind(this));
 },

 update: function()
 {
 this.second.length = 0;
 this.second.value = '';

 if (this.first.value && this.values[this.first.value]) {
 for (optionValue in this.values[this.first.value]) {
 optionTitle = this.values[this.first.value][optionValue];

 this.addOption(this.second, optionValue, optionTitle);
 }
 this.second.disabled = false;
 } else if (this.first.value && !this.values[this.first.value]) {
 this.addOption(this.second, '', this.noMessage);
 } else {
 this.addOption(this.second, '', this.message);
 this.second.disabled = true;
 }
 },

 addOption: function(select, value, text)
 {
 option = document.createElement('OPTION');
 option.value = value;
 option.text = text;

 if (this.selected && option.value == this.selected) {
 option.selected = true;
 this.selected = false;
 }

 if (select.options.add) {
 select.options.add(option);
 } else {
 select.appendChild(option);
 }
 }
}
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
var varienAccordion = new Class.create();
varienAccordion.prototype = {
 initialize : function(containerId, activeOnlyOne){
 this.containerId = containerId;
 this.activeOnlyOne = activeOnlyOne || false;
 this.container = $(this.containerId);
 this.items = $$('#'+this.containerId+' dt');
 this.loader = new varienLoader(true);

 var links = $$('#'+this.containerId+' dt a');
 for(var i in links){
 if(links[i].href){
 Event.observe(links[i],'click',this.clickItem.bind(this));
 this.items[i].dd = this.items[i].next('dd');
 this.items[i].link = links[i];
 }
 }

 this.initFromCookie();
 },
 initFromCookie : function () {
 var activeItemId, visibility;
 if (this.activeOnlyOne &&
 (activeItemId = Cookie.read(this.cookiePrefix() + 'active-item')) !== null) {
 this.hideAllItems();
 this.showItem(this.getItemById(activeItemId));
 } else if(!this.activeOnlyOne) {
 this.items.each(function(item){
 if((visibility = Cookie.read(this.cookiePrefix() + item.id)) !== null) {
 if(visibility == 0) {
 this.hideItem(item);
 } else {
 this.showItem(item);
 }
 }
 }.bind(this));
 }
 },
 cookiePrefix: function () {
 return 'accordion-' + this.containerId + '-';
 },
 getItemById : function (itemId) {
 var result = null;

 this.items.each(function(item){
 if (item.id == itemId) {
 result = item;
 throw $break;
 }
 });

 return result;
 },
 clickItem : function(event){
 var item = Event.findElement(event, 'dt');
 if(this.activeOnlyOne){
 this.hideAllItems();
 this.showItem(item);
 Cookie.write(this.cookiePrefix() + 'active-item', item.id, 30*24*60*60);
 }
 else{
 if(this.isItemVisible(item)){
 this.hideItem(item);
 Cookie.write(this.cookiePrefix() + item.id, 0, 30*24*60*60);
 }
 else {
 this.showItem(item);
 Cookie.write(this.cookiePrefix() + item.id, 1, 30*24*60*60);
 }
 }
 Event.stop(event);
 },
 showItem : function(item){
 if(item && item.link){
 if(item.link.href){
 this.loadContent(item);
 }

 Element.addClassName(item, 'open');
 Element.addClassName(item.dd, 'open');
 }
 },
 hideItem : function(item){
 Element.removeClassName(item, 'open');
 Element.removeClassName(item.dd, 'open');
 },
 isItemVisible : function(item){
 return Element.hasClassName(item, 'open');
 },
 loadContent : function(item){
 if(item.link.href.indexOf('#') == item.link.href.length-1){
 return;
 }
 if (Element.hasClassName(item.link, 'ajax')) {
 this.loadingItem = item;
 this.loader.load(item.link.href, {updaterId : this.loadingItem.dd.id}, this.setItemContent.bind(this));
 return;
 }
 location.href = item.link.href;
 },
 setItemContent : function(content){
 this.loadingItem.dd.innerHTML = content;
 },
 hideAllItems : function(){
 for(var i in this.items){
 if(this.items[i].id){
 Element.removeClassName(this.items[i], 'open');
 Element.removeClassName(this.items[i].dd, 'open');
 }
 }
 }
}
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
function setLocation(url){
 window.location.href = url;
}

function confirmSetLocation(message, url){
 if( confirm(message) ) {
 setLocation(url);
 }
 return false;
}

function deleteConfirm(message, url) {
 confirmSetLocation(message, url);
}

function setElementDisable(element, disable){
 if($(element)){
 $(element).disabled = disable;
 }
}

function toggleParentVis(obj) {
 obj = $(obj).parentNode;
 if( obj.style.display == 'none' ) {
 obj.style.display = '';
 } else {
 obj.style.display = 'none';
 }
}

// to fix new app/design/adminhtml/default/default/template/widget/form/renderer/fieldset.phtml
// with toggleParentVis
function toggleFieldsetVis(obj) {
 id = obj;
 obj = $(obj);
 if( obj.style.display == 'none' ) {
 obj.style.display = '';
 } else {
 obj.style.display = 'none';
 }
 obj = obj.parentNode.childElements();
 for (var i = 0; i < obj.length; i++) {
 if (obj[i].id != undefined
 && obj[i].id == id
 && obj[(i-1)].classNames() == 'entry-edit-head')
 {
 if (obj[i-1].style.display == 'none') {
 obj[i-1].style.display = '';
 } else {
 obj[i-1].style.display = 'none';
 }
 }
 }
}

function toggleVis(obj) {
 obj = $(obj);
 if( obj.style.display == 'none' ) {
 obj.style.display = '';
 } else {
 obj.style.display = 'none';
 }
}

function imagePreview(element){
 if($(element)){
 var win = window.open('', 'preview', 'width=400,height=400,resizable=1,scrollbars=1');
 win.document.open();
 win.document.write('<body style="padding:0;margin:0"><img src="'+$(element).src+'" id="image_preview"/></body>');
 win.document.close();
 Event.observe(win, 'load', function(){
 var img = win.document.getElementById('image_preview');
 win.resizeTo(img.width+40, img.height+80)
 });
 }
}

function toggleValueElements(checkbox, container){
 if(container && checkbox){
 //var elems = container.select('select', 'input');
 var elems = Element.select(container, ['select', 'input', 'textarea', 'button', 'img']);
 elems.each(function (elem) {
 if(elem!=checkbox) {
 elem.disabled=checkbox.checked;
 if (checkbox.checked) {
 elem.addClassName('disabled');
 } else {
 elem.removeClassName('disabled');
 }
 if(elem.tagName == 'IMG') {
 checkbox.checked ? elem.hide() : elem.show();
 }
 };
 })
 }
}

/**
 * @todo add validation for fields
 */
function submitAndReloadArea(area, url) {
 if($(area)) {
 var fields = $(area).select('input', 'select', 'textarea');
 var data = Form.serializeElements(fields, true);
 url = url + (url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true');
 new Ajax.Request(url, {
 parameters: $H(data),
 loaderArea: area,
 onSuccess: function(transport) {
 try {
 if (transport.responseText.isJSON()) {
 var response = transport.responseText.evalJSON()
 if (response.error) {
 alert(response.message);
 }
 if(response.ajaxExpired && response.ajaxRedirect) {
 setLocation(response.ajaxRedirect);
 }
 } else {
 $(area).update(transport.responseText);
 }
 }
 catch (e) {
 $(area).update(transport.responseText);
 }
 }
 });
 }
}

/********** MESSAGES ***********/
/*
Event.observe(window, 'load', function() {
 $$('.messages .error-msg').each(function(el) {
 new Effect.Highlight(el, {startcolor:'#E13422', endcolor:'#fdf9f8', duration:1});
 });
 $$('.messages .warning-msg').each(function(el) {
 new Effect.Highlight(el, {startcolor:'#E13422', endcolor:'#fdf9f8', duration:1});
 });
 $$('.messages .notice-msg').each(function(el) {
 new Effect.Highlight(el, {startcolor:'#E5B82C', endcolor:'#fbf7e9', duration:1});
 });
 $$('.messages .success-msg').each(function(el) {
 new Effect.Highlight(el, {startcolor:'#507477', endcolor:'#f2fafb', duration:1});
 });
});
*/
function syncOnchangeValue(baseElem, distElem){
 var compare = {baseElem:baseElem, distElem:distElem}
 Event.observe(baseElem, 'change', function(){
 if($(this.baseElem) && $(this.distElem)){
 $(this.distElem).value = $(this.baseElem).value;
 }
 }.bind(compare));
}

/********** Ajax session expiration ***********/


if (!navigator.appVersion.match('MSIE 6.')) {
 var header, header_offset, header_copy;

 Event.observe(window, 'load', function() {
 var headers = $$('.content-header');
 for(var i=0; i<headers.length;i++) {
 if(!headers[i].hasClassName('skip-header')) {
 header = headers[i];
 }
 }

 if (!header) {
; return
 }
 header_offset = Element.cumulativeOffset(header)[1];
 var buttons = $$('.content-buttons')[0];
 if (buttons) {
 Element.insert(buttons, {before: '<div class="content-buttons-placeholder"></div>'});
 buttons.placeholder = buttons.previous('.content-buttons-placeholder');
 buttons.remove();
 buttons.placeholder.appendChild(buttons);

 header_offset = Element.cumulativeOffset(buttons)[1];

 }

 header_copy = document.createElement('div');
 header_copy.appendChild(header.cloneNode(true));
 document.body.appendChild(header_copy);
 $(header_copy).addClassName('content-header-floating');
 if ($(header_copy).down('.content-buttons-placeholder')) {
 $(header_copy).down('.content-buttons-placeholder').remove();
 }
 });

 function floatingTopButtonToolbarToggle() {

 if (!header || !header_copy || !header_copy.parentNode) {
 return;
 }
 var s;
 // scrolling offset calculation via www.quirksmode.org
 if (self.pageYOffset){
 s = self.pageYOffset;
 }else if (document.documentElement && document.documentElement.scrollTop) {
 s = document.documentElement.scrollTop;
 }else if (document.body) {
 s = document.body.scrollTop;
 }


 var buttons = $$('.content-buttons')[0];

 if (s > header_offset) {
 if (buttons) {
 if (!buttons.oldParent) {
 buttons.oldParent = buttons.parentNode;
 buttons.oldBefore = buttons.previous();
 }
 if (buttons.oldParent==buttons.parentNode) {
 var dimensions = buttons.placeholder.getDimensions() // Make static dimens.
 buttons.placeholder.style.width = dimensions.width + 'px';
 buttons.placeholder.style.height = dimensions.height + 'px';

 buttons.hide();
 buttons.remove();
 $(header_copy).down('div').appendChild(buttons);
 buttons.show();
 }
 }

 //header.style.visibility = 'hidden';
 header_copy.style.display = 'block';
 } else {
 if (buttons && buttons.oldParent && buttons.oldParent != buttons.parentNode) {
 buttons.remove();
 buttons.oldParent.insertBefore(buttons, buttons.oldBefore);
 //buttons.placeholder.style.width = undefined;
 //buttons.placeholder.style.height = undefined;
 }
 header.style.visibility = 'visible';
 header_copy.style.display = 'none';

 }
 }

 Event.observe(window, 'scroll', floatingTopButtonToolbarToggle);
 Event.observe(window, 'resize', floatingTopButtonToolbarToggle);
}

/** Cookie Reading And Writing **/

var Cookie = {
 all: function() {
 var pairs = document.cookie.split(';');
 var cookies = {};
 pairs.each(function(item, index) {
 var pair = item.strip().split('=');
 cookies[unescape(pair[0])] = unescape(pair[1]);
 });

 return cookies;
 },
 read: function(cookieName) {
 var cookies = this.all();
 if(cookies[cookieName]) {
 return cookies[cookieName];
 }
 return null;
 },
 write: function(cookieName, cookieValue, cookieLifeTime) {
 var expires = '';
 if (cookieLifeTime) {
 var date = new Date();
 date.setTime(date.getTime()+(cookieLifeTime*1000));
 expires = '; expires='+date.toGMTString();
 }
 var urlPath = '/' + BASE_URL.split('/').slice(3).join('/'); // Get relative path
 document.cookie = escape(cookieName) + "=" + escape(cookieValue) + expires + "; path=" + urlPath;
 },
 clear: function(cookieName) {
 this.write(cookieName, '', -1);
 }
};

var Fieldset = {
 cookiePrefix: 'fh-',
 applyCollapse: function(containerId) {
 //var collapsed = Cookie.read(this.cookiePrefix + containerId);
 //if (collapsed !== null) {
 // Cookie.clear(this.cookiePrefix + containerId);
 //}
 if ($(containerId + '-state')) {
 collapsed = $(containerId + '-state').value == 1 ? 0 : 1;
 } else {
 collapsed = $(containerId + '-head').collapsed;
 }
 if (collapsed==1 || collapsed===undefined) {
 $(containerId + '-head').removeClassName('open');
 $(containerId).hide();
 } else {
 $(containerId + '-head').addClassName('open');
 $(containerId).show();
 }
 },
 toggleCollapse: function(containerId, saveThroughAjax) {
 if ($(containerId + '-state')) {
 collapsed = $(containerId + '-state').value == 1 ? 0 : 1;
 } else {
 collapsed = $(containerId + '-head').collapsed;
 }
 //Cookie.read(this.cookiePrefix + containerId);
 if(collapsed==1 || collapsed===undefined) {
 //Cookie.write(this.cookiePrefix + containerId, 0, 30*24*60*60);
 if ($(containerId + '-state')) {
 $(containerId + '-state').value = 1;
 }
 $(containerId + '-head').collapsed = 0;
 } else {
 //Cookie.clear(this.cookiePrefix + containerId);
 if ($(containerId + '-state')) {
 $(containerId + '-state').value = 0;
 }
 $(containerId + '-head').collapsed = 1;
 }

 this.applyCollapse(containerId);
 if (typeof saveThroughAjax != "undefined") {
 this.saveState(saveThroughAjax, {container: containerId, value: $(containerId + '-state').value});
 }
 },
 addToPrefix: function (value) {
 this.cookiePrefix += value + '-';
 },
 saveState: function(url, parameters) {
 new Ajax.Request(url, {
 method: 'get',
 parameters: Object.toQueryString(parameters),
 loaderArea: false
 });
 }
};
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
/**
 * Convert a single file-input element into a 'multiple' input list
 *
 * Usage:
 *
 * 1. Create a file input element (no name)
 * eg. <input type="file" id="first_file_element">
 *
 * 2. Create a DIV for the output to be written to
 * eg. <div id="files_list"></div>
 *
 * 3. Instantiate a MultiSelector object, passing in the DIV and an (optional) maximum number of files
 * eg. var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 3 );
 *
 * 4. Add the first element
 * eg. multi_selector.addElement( document.getElementById( 'first_file_element' ) );
 *
 * 5. That's it.
 *
 * You might (will) want to play around with the addListRow() method to make the output prettier.
 *
 * You might also want to change the line 
 * element.name = 'file_' + this.count;
 * ...to a naming convention that makes more sense to you.
 * 
 */
function MultiSelector( list_target, field_name, max, new_element_html, delete_text, new_file_input ){

 // Where to write the list
 this.list_target = list_target;
 // Field name
 this.field_name = field_name;
 // How many elements?
 this.count = 0;
 // How many elements?
 this.id = 0;
 // Is there a maximum?
 if( max ){
 this.max = max;
 } else {
 this.max = -1;
 };
 this.new_element_html = new_element_html;
 this.delete_text = delete_text;
 this.new_file_input = new_file_input;
 
 /**
 * Add a new file input element
 */
 this.addElement = function( element ){

 // Make sure it's a file input element
 if( element.tagName == 'INPUT' && element.type == 'file' ){

 // Element name -- what number am I?
 // element.name = 'file_' + this.id++;
 this.id++;
 element.name = this.field_name + '[]';

 // Add reference to this object
 element.multi_selector = this;

 // What to do when a file is selected
 element.onchange = function(){

 // New file input
 var new_element = document.createElement( 'input' );
 new_element.type = 'file';

 // Add new element
 this.parentNode.insertBefore( new_element, this );

 // Apply 'update' to element
 this.multi_selector.addElement( new_element );

 // Update list
 this.multi_selector.addListRow( this );

 // Hide this: we can't use display:none because Safari doesn't like it
 this.style.position = 'absolute';
 this.style.left = '-1000px';

 };
 // If we've reached maximum number, disable input element
 if( this.max != -1 && this.count >= this.max ){
 element.disabled = true;
 };

 // File element counter
 this.count++;
 // Most recent element
 this.current_element = element;
 
 } else {
 // This can only be applied to file input elements!
 alert( 'Error: not a file input element' );
 };

 };

 /**
 * Add a new row to the list of files
 */
 this.addListRow = function( element ){

/*
 // Row div
 var new_row = document.createElement( 'div' );
*/

 // Sort order input
 var new_row_input = document.createElement( 'input' );
 new_row_input.type = 'text';
 new_row_input.name = 'general[position_new][]';
 new_row_input.size = '3';
 new_row_input.value = '0';

 // Delete button
 var new_row_button = document.createElement( 'input' );
 new_row_button.type = 'checkbox';
 new_row_button.value = 'Delete';

 var new_row_span = document.createElement( 'span' );
 new_row_span.innerHTML = this.delete_text;
 
 table = this.list_target;

 // no of rows in the table:
 noOfRows = table.rows.length;

 // no of columns in the pre-last row:
 noOfCols = table.rows[noOfRows-2].cells.length;

 // insert row at pre-last:
 var x=table.insertRow(noOfRows-1);

 // insert cells in row.
 for (var j = 0; j < noOfCols; j++) {

 newCell = x.insertCell(j);
 newCell.align = "center";
 newCell.valign = "middle";

// if (j==0) {
// newCell.innerHTML = this.new_element_html.replace(/%file%/g, element.value).replace(/%id%/g, this.id).replace(/%j%/g, j)
// + this.new_file_input.replace(/%file%/g, element.value).replace(/%id%/g, this.id).replace(/%j%/g, j);
// }
 if (j==3) {
 newCell.appendChild( new_row_input );
 }
 else if (j==4) {
 newCell.appendChild( new_row_button );
 }
 else {
// newCell.innerHTML = this.new_file_input.replace(/%file%/g, element.value).replace(/%id%/g, this.id).replace(/%j%/g, j);
 newCell.innerHTML = this.new_file_input.replace(/%id%/g, this.id).replace(/%j%/g, j);
 }

// newCell.innerHTML="NEW CELL" + j;

 }

 // References
// new_row.element = element;

 // Delete function
 new_row_button.onclick= function(){

 // Remove element from form
 this.parentNode.element.parentNode.removeChild( this.parentNode.element );

 // Remove this row from the list
 this.parentNode.parentNode.removeChild( this.parentNode );

 // Decrement counter
 this.parentNode.element.multi_selector.count--;

 // Re-enable input element (if it's disabled)
 this.parentNode.element.multi_selector.current_element.disabled = false;

 // Appease Safari
 // without it Safari wants to reload the browser window
 // which nixes your already queued uploads
 return false;
 };

 // Set row value
// new_row.innerHTML = this.new_element_html.replace(/%file%/g, element.value).replace(/%id%/g, this.id);

 // Add button
// new_row.appendChild( new_row_button );
// new_row.appendChild( new_row_span );

 // Add it to the list
// this.list_target.appendChild( new_row );
 
 };
 
 // Insert row into table.
 this.insRowLast = function ( table ){

 // noOfRpws in table.
 noOfRows = table.rows.length;
 // no of columns of last row.
 noOfCols = table.rows[noOfRows-1].cells.length;

 // insert row at last.
 var x=table.insertRow(noOfRows);

 // insert cells in row.
 for (var j = 0; j < noOfCols; j++) {
 newCell = x.insertCell(j);
 newCell.innerHTML="NEW CELL" + j;
 }

 };

 //delete row
 this.deleteRow = function ( table, row ){

 table.deleteRow(row);

 };

 //delete last row
 this.deleteRow = function ( table ){

 noOfRows = table.rows.length;
 table.deleteRow(noOfRows-1);

 };


};
