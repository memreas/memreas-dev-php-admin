/*!
 * Fotorama 4.3.0 | http://fotorama.io/license/
 */
! function (a, b, c, d) {
    "use strict";

    function e(a) {
        var b = "bez_" + c.makeArray(arguments).join("_").replace(".", "p");
        if ("function" != typeof c.easing[b]) {
            var d = function (a, b) {
                var c = [null, null],
                    d = [null, null],
                    e = [null, null],
                    f = function (f, g) {
                        return e[g] = 3 * a[g], d[g] = 3 * (b[g] - a[g]) - e[g], c[g] = 1 - e[g] - d[g], f * (e[g] + f * (d[g] + f * c[g]))
                    }, g = function (a) {
                        return e[0] + a * (2 * d[0] + 3 * c[0] * a)
                    }, h = function (a) {
                        for (var b, c = a, d = 0; ++d < 14 && (b = f(c, 0) - a, !(Math.abs(b) < .001));) c -= b / g(c);
                        return c
                    };
                return function (a) {
                    return f(h(a), 1)
                }
            };
            c.easing[b] = function (b, c, e, f, g) {
                return f * d([a[0], a[1]], [a[2], a[3]])(c / g) + e
            }
        }
        return b
    }

    function f() {}

    function g(a, b, c) {
        return Math.max(isNaN(b) ? -1 / 0 : b, Math.min(isNaN(c) ? 1 / 0 : c, a))
    }

    function h(a) {
        return a.match(/ma/) && a.match(/-?\d+(?!d)/g)[a.match(/3d/) ? 12 : 4]
    }

    function i(a) {
        return qc ? +h(a.css("transform")) : +a.css("left").replace("px", "")
    }

    function j(a) {
        var b = {};
        return qc ? b.transform = "translate3d(" + a + "px,0,0)" : b.left = a, b
    }

    function k(a) {
        return {
            "transition-duration": a + "ms"
        }
    }

    function l(a, b) {
        return +String(a).replace(b || "px", "")
    }

    function m(a) {
        return /%$/.test(a) && l(a, "%")
    }

    function n(a) {
        return ( !! l(a) || !! l(a, "%")) && a
    }

    function o(a, b, c, d) {
        return (a - (d || 0)) * (b + (c || 0))
    }

    function p(a, b, c, d) {
        return -Math.round(a / (b + (c || 0)) - (d || 0))
    }

    function q(a) {
        var b = a.data();
        if (!b.tEnd) {
            var c = a[0],
                d = {
                    WebkitTransition: "webkitTransitionEnd",
                    MozTransition: "transitionend",
                    OTransition: "oTransitionEnd otransitionend",
                    msTransition: "MSTransitionEnd",
                    transition: "transitionend"
                };
            c.addEventListener(d[W.prefixed("transition")], function (a) {
                b.tProp && a.propertyName.match(b.tProp) && b.onEndFn()
            }), b.tEnd = !0
        }
    }

    function r(a, b, c, d) {
        var e, f = a.data();
        f && (f.onEndFn = function () {
            e || (e = !0, clearTimeout(f.tT), c())
        }, f.tProp = b, clearTimeout(f.tT), f.tT = setTimeout(function () {
            f.onEndFn()
        }, 1.5 * d), q(a))
    }

    function s(a, b) {
        if (a.length) {
            var c = a.data();
            qc ? (a.css(k(0)), c.onEndFn = f, clearTimeout(c.tT)) : a.stop();
            var d = t(b, function () {
                return i(a)
            });
            return a.css(j(d)), d
        }
    }

    function t() {
        for (var a, b = 0, c = arguments.length; c > b && (a = b ? arguments[b]() : arguments[b], "number" != typeof a); b++);
        return a
    }

    function u(a, b) {
        return Math.round(a + (b - a) / 1.5)
    }

    function v() {
        return v.p = v.p || ("https://" === location.protocol ? "https://" : "http://"), v.p
    }

    function w(a) {
        var c = b.createElement("a");
        return c.href = a, c
    }

    function x(a, b) {
        if ("string" != typeof a) return a;
        a = w(a);
        var c, d;
        if (a.host.match(/youtube\.com/) && a.search) {
            if (c = a.search.split("v=")[1]) {
                var e = c.indexOf("&"); - 1 !== e && (c = c.substring(0, e)), d = "youtube"
            }
        } else a.host.match(/youtube\.com|youtu\.be/) ? (c = a.pathname.replace(/^\/(embed\/|v\/)?/, "").replace(/\/.*/, ""), d = "youtube") : a.host.match(/vimeo\.com/) && (d = "vimeo", c = a.pathname.replace(/^\/(video\/)?/, "").replace(/\/.*/, ""));
        return c && d || !b || (c = a.href, d = "custom"), c ? {
            id: c,
            type: d
        } : !1
    }

    function y(a, b, d) {
        var e, f, g = a.video;
        return "youtube" === g.type ? (f = v() + "img.youtube.com/vi/" + g.id + "/default.jpg", e = f.replace(/\/default.jpg$/, "/hqdefault.jpg"), a.thumbsReady = !0) : "vimeo" === g.type ? c.ajax({
            url: v() + "vimeo.com/api/v2/video/" + g.id + ".json",
            dataType: "jsonp",
            success: function (c) {
                a.thumbsReady = !0, z(b, {
                    img: c[0].thumbnail_large,
                    thumb: c[0].thumbnail_small
                }, a.i, d)
            }
        }) : a.thumbsReady = !0, {
            img: e,
            thumb: f
        }
    }

    function z(a, b, d, e) {
        for (var f = 0, g = a.length; g > f; f++) {
            var h = a[f];
            if (h.i === d && h.thumbsReady) {
                var i = {
                    videoReady: !0
                };
                i[Cc] = i[Ec] = i[Dc] = !1, e.splice(f, 1, c.extend({}, h, i, b));
                break
            }
        }
    }

    function A(a) {
        function b(a, b) {
            var c = a.data(),
                e = a.children("img").eq(0),
                f = a.attr("href"),
                g = a.attr("src"),
                h = e.attr("src"),
                i = c.video,
                j = b ? x(f, i === !0) : !1;
            j ? f = !1 : j = x(i, i);
            var k = c.img || f || g || h,
                m = c.thumb || h || g || f,
                n = k !== m,
                o = l(c.width || a.attr("width")),
                p = l(c.height || a.attr("height")),
                q = l(c.thumbWidth || e.attr("width") || n || o),
                r = l(c.thumbHeight || e.attr("height") || n || p);
            return {
                video: j,
                img: k,
                width: o || d,
                height: p || d,
                thumb: m,
                thumbRatio: q / r || d
            }
        }
        var e = [];
        return a.children().each(function () {
            var a = c(this),
                d = c.extend(a.data(), {
                    id: a.attr("id")
                });
            if (a.is("a, img")) c.extend(d, b(a, !0));
            else {
                if (a.is(":empty")) return;
                c.extend(d, {
                    html: this,
                    _html: a.html()
                })
            }
            e.push(d)
        }), e
    }

    function B(a) {
        return 0 === a.offsetWidth && 0 === a.offsetHeight
    }

    function C(a) {
        return !c.contains(b.documentElement, a)
    }

    function D(a, b, c) {
        a() ? b() : setTimeout(function () {
            D(a, b)
        }, c || 100)
    }

    function E(a) {
        location.replace(location.protocol + "//" + location.host + location.pathname.replace(/^\/?/, "/") + location.search + "#" + a)
    }

    function F(a, b, c) {
        var d = a.data(),
            e = d.measures;
        if (e && (!d.l || d.l.W !== e.width || d.l.H !== e.height || d.l.r !== e.ratio || d.l.w !== b.w || d.l.h !== b.h || d.l.m !== c)) {
            var f = e.width,
                h = e.height,
                i = b.w / b.h,
                j = e.ratio >= i,
                k = "scale-down" === c,
                l = "contain" === c,
                m = "cover" === c;
            j && (k || l) || !j && m ? (f = g(b.w, 0, k ? f : 1 / 0), h = f / e.ratio) : (j && m || !j && (k || l)) && (h = g(b.h, 0, k ? h : 1 / 0), f = h * e.ratio), a.css({
                width: Math.ceil(f),
                height: Math.ceil(h),
                marginLeft: Math.floor(-f / 2),
                marginTop: Math.floor(-h / 2)
            }), d.l = {
                W: e.width,
                H: e.height,
                r: e.ratio,
                w: b.w,
                h: b.h,
                m: c
            }
        }
        return !0
    }

    function G(a, b) {
        var c = a[0];
        c.styleSheet ? c.styleSheet.cssText = b : a.html(b)
    }

    function H(a, b, c) {
        return b === c ? !1 : b >= a ? "left" : a >= c ? "right" : "left right"
    }

    function I(a, b, c) {
        if (!c) return !1;
        if (!isNaN(a)) return a - 1;
        for (var d, e = 0, f = b.length; f > e; e++) {
            var g = b[e];
            if (g.id === a) {
                d = e;
                break
            }
        }
        return d
    }

    function J(a, b, d) {
        d = d || {}, a.each(function () {
            var a, e = c(this),
                g = e.data();
            g.clickOn || (g.clickOn = !0, c.extend(R(e, {
                onStart: function (b) {
                    a = b, (d.onStart || f).call(this, b)
                },
                onMove: d.onMove || f,
                onEnd: function (c) {
                    c.moved || d.tail.checked || b.call(this, a)
                }
            }), d.tail))
        })
    }

    function K(a, b) {
        return '<div class="' + a + '">' + (b || "") + "</div>"
    }

    function L(a) {
        for (var b = a.length; b;) {
            var c = Math.floor(Math.random() * b--),
                d = a[b];
            a[b] = a[c], a[c] = d
        }
        return a
    }

    function M(a) {
        return "[object Array]" == Object.prototype.toString.call(a) && c.map(a, function (a) {
            return c.extend({}, a)
        })
    }

    function N(a, b) {
        mc.scrollLeft(a).scrollTop(b)
    }

    function O(a, b) {
        var d = Math.round(b.pos),
            e = b.onEnd || f;
        "undefined" != typeof b.overPos && b.overPos !== b.pos && (d = b.overPos, e = function () {
            O(a, c.extend({}, b, {
                overPos: b.pos,
                time: Math.max(wc, b.time / 2)
            }))
        });
        var g = c.extend(j(d), {
            width: b.width
        });
        qc ? (a.css(c.extend(k(b.time), g)), b.time > 10 ? r(a, "transform", e, b.time) : e()) : a.stop().animate(g, b.time, Fc, e)
    }

    function P(a, b, d, e) {
        a = a || c(a), b = b || c(b);
        var g = a[0],
            h = b[0],
            i = "crossfade" === e.method,
            j = function () {
                j.done || ((e.onEnd || f)(), j.done = !0)
            }, l = k(e.time),
            m = k(0),
            n = {
                opacity: 0
            }, o = {
                opacity: 1
            };
        d.removeClass(Nb + " " + Mb), a.addClass(Nb), b.addClass(Mb), qc ? (s(a), s(b), i && h && a.css(c.extend(m, n)).width(), a.css(c.extend(i ? l : m, o)), b.css(c.extend(l, n)), e.time > 10 && (g || h) ? (r(a, "opacity", j, e.time), r(b, "opacity", j, e.time)) : j()) : (a.stop(), b.stop(), i && h && a.fadeTo(0, 0), a.fadeTo(i ? e.time : 1, 1, i && j), b.fadeTo(e.time, 0, j), g && i || h || j())
    }

    function Q(a) {
        var b = (a.touches || [])[0] || a;
        a._x = b.pageX, a._y = b.clientY
    }

    function R(d, e) {
        function g(a) {
            return o = c(a.target), u.checked = r = s = !1, m || u.flow || a.touches && a.touches.length > 1 || a.which > 1 || bb && bb.type !== a.type && db || (r = e.select && o.is(e.select, t)) ? r : (q = a.type.match(/^t/), s = o.is("a, a *", t), Q(a), n = bb = a, cb = a.type.replace(/down|start/, "move").replace(/Down/, "Move"), p = u.control, (e.onStart || f).call(t, a, {
                control: p,
                $target: o
            }), m = u.flow = !0, (!q || u.go) && a.preventDefault(), void 0)
        }

        function h(a) {
            if (a.touches && a.touches.length > 1 || uc && !a.isPrimary || cb !== a.type || !m) return m && i(), void 0;
            Q(a);
            var b = Math.abs(a._x - n._x),
                c = Math.abs(a._y - n._y),
                d = b - c,
                g = (u.go || u.x || d >= 0) && !u.noSwipe,
                h = 0 > d;
            q && !u.checked ? (m = g, m && a.preventDefault()) : (a.preventDefault(), (e.onMove || f).call(t, a, {
                touch: q
            })), u.checked = u.checked || g || h
        }

        function i(a) {
            var b = m;
            u.control = m = !1, b && (u.flow = !1), !b || s && !u.checked || (a && a.preventDefault(), db = !0, clearTimeout(eb), eb = setTimeout(function () {
                db = !1
            }, 1e3), (e.onEnd || f).call(t, {
                moved: u.checked,
                $target: o,
                control: p,
                touch: q,
                startEvent: n,
                aborted: !a
            }))
        }

        function j() {
            clearTimeout(l), l = setTimeout(function () {
                u.flow = !0
            }, 10)
        }

        function k() {
            clearTimeout(l), l = setTimeout(function () {
                u.flow = !1
            }, vc)
        }
        var l, m, n, o, p, q, r, s, t = d[0],
            u = {};
        return uc ? (t[Gc]("MSPointerDown", g), b[Gc]("MSPointerMove", h), b[Gc]("MSPointerCancel", i), b[Gc]("MSPointerUp", i)) : (t[Gc] && (t[Gc]("touchstart", g), t[Gc]("touchmove", h), t[Gc]("touchend", i), b[Gc]("touchstart", j), b[Gc]("touchend", k), a[Gc]("scroll", k)), d.on("mousedown", g), nc.on("mousemove", h).on("mouseup", i)), d.on("click", "a", function (a) {
            u.checked && a.preventDefault()
        }), u
    }

    function S(a, b) {
        function d(d) {
            k = l = d._x, q = c.now(), p = [
                [q, k]
            ], m = n = s(a, b.getPos && b.getPos()), (b.onStart || f).call(A, d)
        }

        function e(a, b) {
            t = B.min, v = B.max, w = B.snap, x = a.altKey, z = !1, y = b.control, y || d(a)
        }

        function h(e, g) {
            y && (y = !1, d(e)), C.noSwipe || (l = e._x, p.push([c.now(), l]), n = m - (k - l), o = H(n, t, v), t >= n ? n = u(n, t) : n >= v && (n = u(n, v)), C.noMove || (a.css(j(n)), z || (z = !0, g.touch || uc || a.addClass(ac)), (b.onMove || f).call(A, e, {
                pos: n,
                edge: o
            })))
        }

        function i(d) {
            if (!y) {
                d.touch || uc || a.removeClass(ac), r = (new Date).getTime();
                for (var e, h, i, j, k, o, q, s, u, z = r - vc, B = null, C = wc, D = b.friction, E = p.length - 1; E >= 0; E--) {
                    if (e = p[E][0], h = Math.abs(e - z), null === B || i > h) B = e, j = p[E][1];
                    else if (B === z || h > i) break;
                    i = h
                }
                q = g(n, t, v);
                var F = j - l,
                    G = F >= 0,
                    H = r - B,
                    I = H > vc,
                    J = !I && n !== m && q === n;
                w && (q = g(Math[J ? G ? "floor" : "ceil" : "round"](n / w) * w, t, v), t = v = q), J && (w || q === n) && (u = -(F / H), C *= g(Math.abs(u), b.timeLow, b.timeHigh), k = Math.round(n + u * C / D), w || (q = k), (!G && k > v || G && t > k) && (o = G ? t : v, s = k - o, w || (q = o), s = g(q + .03 * s, o - 50, o + 50), C = Math.abs((n - s) / (u / D)))), C *= x ? 10 : 1, (b.onEnd || f).call(A, c.extend(d, {
                    pos: n,
                    newPos: q,
                    overPos: s,
                    time: C,
                    moved: I && w || d.moved
                }))
            }
        }
        var k, l, m, n, o, p, q, r, t, v, w, x, y, z, A = a[0],
            B = a.data(),
            C = {};
        return C = c.extend(R(b.$wrap, {
            onStart: e,
            onMove: h,
            onEnd: i,
            select: b.select,
            control: b.control
        }), C)
    }

    function T(a) {
        U(!0), Hc.appendTo(a), gb = 0, Ic(), fb = setInterval(Ic, 200)
    }

    function U(a) {
        a || Hc.detach(), clearInterval(fb)
    }
    var V = {}, W = function (a, b, c) {
            function d(a) {
                r.cssText = a
            }

            function e(a, b) {
                return typeof a === b
            }

            function f(a, b) {
                return !!~("" + a).indexOf(b)
            }

            function g(a, b) {
                for (var d in a) {
                    var e = a[d];
                    if (!f(e, "-") && r[e] !== c) return "pfx" == b ? e : !0
                }
                return !1
            }

            function h(a, b, d) {
                for (var f in a) {
                    var g = b[a[f]];
                    if (g !== c) return d === !1 ? a[f] : e(g, "function") ? g.bind(d || b) : g
                }
                return !1
            }

            function i(a, b, c) {
                var d = a.charAt(0).toUpperCase() + a.slice(1),
                    f = (a + " " + u.join(d + " ") + d).split(" ");
                return e(b, "string") || e(b, "undefined") ? g(f, b) : (f = (a + " " + v.join(d + " ") + d).split(" "), h(f, b, c))
            }
            var j, k, l, m = "2.6.2",
                n = {}, o = b.documentElement,
                p = "modernizr",
                q = b.createElement(p),
                r = q.style,
                s = ({}.toString, " -webkit- -moz- -o- -ms- ".split(" ")),
                t = "Webkit Moz O ms",
                u = t.split(" "),
                v = t.toLowerCase().split(" "),
                w = {}, x = [],
                y = x.slice,
                z = function (a, c, d, e) {
                    var f, g, h, i, j = b.createElement("div"),
                        k = b.body,
                        l = k || b.createElement("body");
                    if (parseInt(d, 10))
                        for (; d--;) h = b.createElement("div"), h.id = e ? e[d] : p + (d + 1), j.appendChild(h);
                    return f = ["&#173;", '<style id="s', p, '">', a, "</style>"].join(""), j.id = p, (k ? j : l).innerHTML += f, l.appendChild(j), k || (l.style.background = "", l.style.overflow = "hidden", i = o.style.overflow, o.style.overflow = "hidden", o.appendChild(l)), g = c(j, a), k ? j.parentNode.removeChild(j) : (l.parentNode.removeChild(l), o.style.overflow = i), !! g
                }, A = {}.hasOwnProperty;
            l = e(A, "undefined") || e(A.call, "undefined") ? function (a, b) {
                return b in a && e(a.constructor.prototype[b], "undefined")
            } : function (a, b) {
                return A.call(a, b)
            }, Function.prototype.bind || (Function.prototype.bind = function (a) {
                var b = this;
                if ("function" != typeof b) throw new TypeError;
                var c = y.call(arguments, 1),
                    d = function () {
                        if (this instanceof d) {
                            var e = function () {};
                            e.prototype = b.prototype;
                            var f = new e,
                                g = b.apply(f, c.concat(y.call(arguments)));
                            return Object(g) === g ? g : f
                        }
                        return b.apply(a, c.concat(y.call(arguments)))
                    };
                return d
            }), w.csstransforms3d = function () {
                var a = !! i("perspective");
                return a
            };
            for (var B in w) l(w, B) && (k = B.toLowerCase(), n[k] = w[B](), x.push((n[k] ? "" : "no-") + k));
            return n.addTest = function (a, b) {
                if ("object" == typeof a)
                    for (var d in a) l(a, d) && n.addTest(d, a[d]);
                else {
                    if (a = a.toLowerCase(), n[a] !== c) return n;
                    b = "function" == typeof b ? b() : b, "undefined" != typeof enableClasses && enableClasses && (o.className += " " + (b ? "" : "no-") + a), n[a] = b
                }
                return n
            }, d(""), q = j = null, n._version = m, n._prefixes = s, n._domPrefixes = v, n._cssomPrefixes = u, n.testProp = function (a) {
                return g([a])
            }, n.testAllProps = i, n.testStyles = z, n.prefixed = function (a, b, c) {
                return b ? i(a, b, c) : i(a, "pfx")
            }, n
        }(a, b),
        X = {
            ok: !1,
            is: function () {
                return !1
            },
            request: function () {},
            cancel: function () {},
            event: "",
            prefix: ""
        }, Y = "webkit moz o ms khtml".split(" ");
    if ("undefined" != typeof b.cancelFullScreen) X.ok = !0;
    else
        for (var Z = 0, $ = Y.length; $ > Z; Z++)
            if (X.prefix = Y[Z], "undefined" != typeof b[X.prefix + "CancelFullScreen"]) {
                X.ok = !0;
                break
            } X.ok && (X.event = X.prefix + "fullscreenchange", X.is = function () {
        switch (this.prefix) {
        case "":
            return b.fullScreen;
        case "webkit":
            return b.webkitIsFullScreen;
        default:
            return b[this.prefix + "FullScreen"]
        }
    }, X.request = function (a) {
        return "" === this.prefix ? a.requestFullScreen() : a[this.prefix + "RequestFullScreen"]()
    }, X.cancel = function () {
        return "" === this.prefix ? b.cancelFullScreen() : b[this.prefix + "CancelFullScreen"]()
    });
    var _, ab, bb, cb, db, eb, fb, gb, hb = "fotorama",
        ib = "fullscreen",
        jb = hb + "__wrap",
        kb = jb + "--css3",
        lb = jb + "--video",
        mb = jb + "--fade",
        nb = jb + "--slide",
        ob = jb + "--no-controls",
        pb = jb + "--no-shadows",
        qb = jb + "--pan-y",
        rb = hb + "__stage",
        sb = rb + "__frame",
        tb = sb + "--video",
        ub = rb + "__shaft",
        vb = rb + "--only-active",
        wb = hb + "__grab",
        xb = hb + "__pointer",
        yb = hb + "__arr",
        zb = yb + "--disabled",
        Ab = yb + "--prev",
        Bb = yb + "--next",
        Cb = yb + "__arr",
        Db = hb + "__nav",
        Eb = Db + "-wrap",
        Fb = Db + "__shaft",
        Gb = Db + "--dots",
        Hb = Db + "--thumbs",
        Ib = Db + "__frame",
        Jb = Ib + "--dot",
        Kb = Ib + "--thumb",
        Lb = hb + "__fade",
        Mb = Lb + "-front",
        Nb = Lb + "-rear",
        Ob = hb + "__shadow",
        Pb = Ob + "s",
        Qb = Pb + "--left",
        Rb = Pb + "--right",
        Sb = hb + "__active",
        Tb = hb + "__select",
        Ub = hb + "--hidden",
        Vb = hb + "--fullscreen",
        Wb = hb + "__fullscreen-icon",
        Xb = hb + "__error",
        Yb = hb + "__loading",
        Zb = hb + "__loaded",
        $b = Zb + "--full",
        _b = Zb + "--img",
        ac = hb + "__grabbing",
        bc = hb + "__img",
        cc = bc + "--full",
        dc = hb + "__dot",
        ec = hb + "__thumb",
        fc = ec + "-border",
        gc = hb + "__html",
        hc = hb + "__video",
        ic = hc + "-play",
        jc = hc + "-close",
        kc = hb + "__caption",
        lc = hb + "__oooo",
        mc = c(a),
        nc = c(b),
        oc = "CSS1Compat" === b.compatMode,
        pc = "quirks" === location.hash.replace("#", ""),
        qc = W.csstransforms3d && !pc,
        rc = X.ok,
        sc = navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i),
        tc = !qc || sc,
        uc = a.navigator.msPointerEnabled,
        vc = 250,
        wc = 300,
        xc = 5e3,
        yc = 2,
        zc = 64,
        Ac = 500,
        Bc = 333,
        Cc = "$stageFrame",
        Dc = "$navDotFrame",
        Ec = "$navThumbFrame",
        Fc = e([.1, 0, .25, 1]),
        Gc = "addEventListener",
        Hc = c(K("", K(lc))),
        Ic = function () {
            Hc.attr("class", lc + " " + lc + "--" + gb), gb++, gb > 4 && (gb = 0)
        };
    jQuery.Fotorama = function (a, e) {
        function f() {
            c.each(Xc, function (a, b) {
                if (!b.i) {
                    b.i = Hd++;
                    var c = x(b.video, !0);
                    if (c) {
                        var d = {};
                        b.video = c, b.img || b.thumb ? b.thumbsReady = !0 : d = y(b, Xc, Cd), z(Xc, {
                            img: d.img,
                            thumb: d.thumb
                        }, b.i, Cd)
                    }
                }
            })
        }

        function h(b) {
            b !== h.f && (b ? (a.html("").addClass(Fd).append(Ld).before(Jd).before(Kd), c.Fotorama.size++) : (Ld.detach(), Jd.detach(), Kd.detach(), a.html(Id.urtext).removeClass(Fd), c.Fotorama.size--), h.f = b)
        }

        function i() {
            Xc = Cd.data = Xc || M(e.data) || A(a), Yc = Cd.size = Xc.length, !Wc.ok && e.shuffle && L(Xc), f(), de = w(de), Yc && h(!0)
        }

        function j() {
            var a = 2 > Yc || $c;
            ge.noMove = a || nd, ge.noSwipe = a || !e.swipe, Nd.toggleClass(wb, !ge.noMove && !ge.noSwipe), uc && Ld.toggleClass(qb, !ge.noSwipe)
        }

        function q(a) {
            a === !0 && (a = ""), e.autoplay = Math.max(+a || xc, 1.5 * qd)
        }

        function r(a) {
            return a ? "add" : "remove"
        }

        function u() {
            nd = "crossfade" === e.transition || "dissolve" === e.transition, hd = e.loop && (Yc > 2 || nd), qd = +e.transitionDuration || wc;
            var a = {
                add: [],
                remove: []
            };
            Yc > 1 ? (id = e.nav, kd = "top" === e.navPosition, a.remove.push(Tb), Rd.toggle(e.arrows), Nb()) : (id = !1, Rd.hide()), e.autoplay && q(e.autoplay), od = l(e.thumbWidth) || zc, pd = l(e.thumbHeight) || zc, j(), Gc(e, !0), jd = "thumbs" === id, jd ? (cb(Yc, "navThumb"), Zc = Wd, Bd = Ec, G(Jd, c.Fotorama.jst.style({
                w: od,
                h: pd,
                m: yc,
                s: Ed,
                q: !oc
            })), Td.addClass(Hb).removeClass(Gb)) : "dots" === id ? (cb(Yc, "navDot"), Zc = Vd, Bd = Dc, Td.addClass(Gb).removeClass(Hb)) : (id = !1, Td.removeClass(Hb + " " + Gb)), id && (kd ? Sd.insertBefore(Md) : Sd.insertAfter(Md), gb.nav = !1, gb(Zc, Ud, "nav")), ld = e.allowFullScreen, ld ? ($d.appendTo(Md), md = rc && "native" === ld) : ($d.detach(), md = !1), a[r(nd)].push(mb), a[r(!nd)].push(nb), rd = e.shadows && !tc, a[r(!rd)].push(pb), U(), Ld.addClass(a.add.join(" ")).removeClass(a.remove.join(" ")), ee = c.extend({}, e)
        }

        function v(a) {
            return 0 > a ? (Yc + a % Yc) % Yc : a >= Yc ? a % Yc : a
        }

        function w(a) {
            return g(a, 0, Yc - 1)
        }

        function B(a) {
            return hd ? v(a) : w(a)
        }

        function Q(a) {
            return a > 0 || hd ? a - 1 : !1
        }

        function R(a) {
            return Yc - 1 > a || hd ? a + 1 : !1
        }

        function V() {
            Xd.min = hd ? -1 / 0 : -o(Yc - 1, fe.w, yc, bd), Xd.max = hd ? 1 / 0 : -o(0, fe.w, yc, bd), Xd.snap = fe.w + yc
        }

        function W() {
            Yd.min = Math.min(0, fe.w - Ud.width()), Yd.max = 0, he.noMove = Yd.min === Yd.max, Ud.toggleClass(wb, !he.noMove)
        }

        function Y(a, b, d) {
            if ("number" == typeof a) {
                a = new Array(a);
                var e = !0
            }
            return c.each(a, function (a, c) {
                if (e && (c = a), "number" == typeof c) {
                    var f = Xc[v(c)],
                        g = "$" + b + "Frame",
                        h = f[g];
                    d.call(this, a, c, f, h, g, h && h.data())
                }
            })
        }

        function Z(a, b, c, d) {
            (!sd || "*" === sd && d === gd) && (a = n(e.width) || n(a) || Ac, b = n(e.height) || n(b) || Bc, Cd.resize({
                width: a,
                ratio: e.ratio || c || a / b
            }, 0, d === gd ? !0 : "*"))
        }

        function $(a, b, d, f, g) {
            Y(a, b, function (a, h, i, j, k, l) {
                function m(a) {
                    var b = v(h);
                    Hc(a, {
                        index: b,
                        src: w,
                        frame: Xc[b]
                    })
                }

                function n() {
                    s.remove(), c.Fotorama.cache[w] = "error", i.html && "stage" === b || !x || x === w ? (w && !i.html ? (j.trigger("f:error").removeClass(Yb).addClass(Xb), m("error")) : "stage" === b && (j.trigger("f:load").removeClass(Yb + " " + Xb).addClass(Zb), m("load"), Z()), l.state = "error", !(Yc > 1) || i.html || i.deleted || i.video || q || (i.deleted = !0, Cd.splice(h, 1))) : (i[u] = w = x, $([h], b, d, f, !0))
                }

                function o() {
                    var a = r.width,
                        g = r.height,
                        k = a / g;
                    t.measures = {
                        width: a,
                        height: g,
                        ratio: k
                    }, Z(a, g, k, h), s.off("load error").addClass(bc + (q ? " " + cc : "")).prependTo(j), F(s, d || fe, f || i.fit || e.fit), c.Fotorama.cache[w] = "loaded", l.state = "loaded", setTimeout(function () {
                        j.trigger("f:load").removeClass(Yb + " " + Xb).addClass(Zb + " " + (q ? $b : _b)), "stage" === b && m("load")
                    }, 5)
                }

                function p() {
                    var a = 10;
                    D(function () {
                        return !zd || !a-- && !tc
                    }, function () {
                        o()
                    })
                }
                if (j) {
                    var q = Cd.fullScreen && i.full && !l.$full && "stage" === b;
                    if (!l.$img || g || q) {
                        var r = new Image,
                            s = c(r),
                            t = s.data();
                        l[q ? "$full" : "$img"] = s;
                        var u = "stage" === b ? q ? "full" : "img" : "thumb",
                            w = i[u],
                            x = q ? null : i["stage" === b ? "thumb" : "img"];
                        if ("navThumb" === b && (j = l.$wrap), !w) return n(), void 0;
                        c.Fotorama.cache[w] ? function y() {
                            "error" === c.Fotorama.cache[w] ? n() : "loaded" === c.Fotorama.cache[w] ? setTimeout(p, 0) : setTimeout(y, 100)
                        }() : (c.Fotorama.cache[w] = "*", s.on("load", p).on("error", n)), r.src = w
                    }
                }
            })
        }

        function bb() {
            var a = Cd.activeFrame[Cc];
            a && !a.data().state && (T(a), a.on("f:load f:error", function () {
                a.off("f:load f:error"), U()
            }))
        }

        function cb(a, b) {
            Y(a, b, function (a, d, f, g, h, i) {
                g || (g = f[h] = Ld[h].clone(), i = g.data(), i.data = f, "stage" === b ? (f.html && c('<div class="' + gc + '"></div>').append(f._html ? c(f.html).removeAttr("id").html(f._html) : f.html).appendTo(g), e.captions && f.caption && c('<div class="' + kc + '"></div>').append(f.caption).appendTo(g), f.video && g.addClass(tb).append(ae.clone()), Od = Od.add(g)) : "navDot" === b ? Vd = Vd.add(g) : "navThumb" === b && (i.$wrap = g.children(":first"), Wd = Wd.add(g), f.video && g.append(ae.clone())))
            })
        }

        function db(a, b, c) {
            return a && a.length && F(a, b, c)
        }

        function eb(a) {
            Y(a, "stage", function (a, b, d, f, g, h) {
                if (f) {
                    je[Cc][v(b)] = f.css(c.extend({
                        left: nd ? 0 : o(b, fe.w, yc, bd)
                    }, nd && k(0))), C(f[0]) && (f.appendTo(Nd), Pc(d.$video));
                    var i = d.fit || e.fit;
                    db(h.$img, fe, i), db(h.$full, fe, i)
                }
            })
        }

        function fb(a, b) {
            if ("thumbs" === id && !isNaN(a)) {
                var d = -a,
                    e = -a + fe.w;
                Wd.each(function () {
                    var a = c(this),
                        f = a.data(),
                        g = f.eq,
                        h = {
                            h: pd
                        }, i = "cover";
                    h.w = f.w, f.l + f.w < d || f.l > e || db(f.$img, h, i) || b && $([g], "navThumb", h, i)
                })
            }
        }

        function gb(a, b, d) {
            if (!gb[d]) {
                var e = "nav" === d && jd,
                    f = 0;
                b.append(a.filter(function () {
                    for (var a, b = c(this), d = b.data(), e = 0, f = Xc.length; f > e; e++)
                        if (d.data === Xc[e]) {
                            a = !0, d.eq = e;
                            break
                        }
                    return a || b.remove() && !1
                }).sort(function (a, b) {
                    return c(a).data().eq - c(b).data().eq
                }).each(function () {
                    if (e) {
                        var a = c(this),
                            b = a.data(),
                            d = Math.round(pd * b.data.thumbRatio) || od;
                        b.l = f, b.w = d, a.css({
                            width: d
                        }), f += d + yc
                    }
                })), gb[d] = !0
            }
        }

        function Lb(a) {
            return a - ke > fe.w / 3
        }

        function Mb(a) {
            return !(hd || de + a && de - Yc + a || $c)
        }

        function Nb() {
            Rd.each(function (a) {
                c(this).toggleClass(zb, Mb(a))
            })
        }

        function Ob(a) {
            var b, c, d = a.data();
            return jd ? (b = d.l, c = d.w) : (b = a.position().left, c = a.width()), {
                c: b + c / 2,
                min: -b + 10 * yc,
                max: -b + fe.w - c - 10 * yc
            }
        }

        function ac(a) {
            var b = Cd.activeFrame[Bd].data();
            O(Zd, {
                time: .9 * a,
                pos: b.l,
                width: b.w - 2 * yc
            })
        }

        function hc(a) {
            var b = Xc[a.guessIndex][Bd];
            if (b) {
                var c = Yd.min !== Yd.max,
                    d = c && Ob(Cd.activeFrame[Bd]),
                    e = c && (a.keep && hc.l ? hc.l : g((a.coo || fe.w / 2) - Ob(b).c, d.min, d.max)),
                    f = c && g(e, Yd.min, Yd.max),
                    h = .9 * a.time;
                O(Ud, {
                    time: h,
                    pos: f || 0,
                    onEnd: function () {
                        fb(f, !0)
                    }
                }), h && fb(f), Oc(Td, H(f, Yd.min, Yd.max)), hc.l = e
            }
        }

        function lc() {
            pc(Bd), ie[Bd].push(Cd.activeFrame[Bd].addClass(Sb))
        }

        function pc(a) {
            for (var b = ie[a]; b.length;) b.shift().removeClass(Sb)
        }

        function sc(a) {
            var b = je[a];
            c.each(ad, function (a, c) {
                delete b[c]
            }), c.each(b, function (a, c) {
                delete b[a], c.detach()
            })
        }

        function Fc(a) {
            bd = cd = de;
            var b = Cd.activeFrame,
                c = b[Cc];
            c && (pc(Cc), ie[Cc].push(c.addClass(Sb)), a || Cd.show.onEnd(!0), s(Nd, 0), sc(Cc), eb(ad), V(), W())
        }

        function Gc(a, b) {
            a && c.extend(fe, {
                width: a.width || fe.width,
                height: a.height,
                minWidth: a.minWidth,
                maxWidth: a.maxWidth,
                minHeight: a.minHeight,
                maxHeight: a.maxHeight,
                ratio: function (a) {
                    if (a) {
                        var b = Number(a);
                        return isNaN(b) ? (b = a.split("/"), Number(b[0] / b[1]) || d) : b
                    }
                }(a.ratio)
            }) && !b && c.extend(e, {
                width: fe.width,
                height: fe.height,
                minWidth: fe.minWidth,
                maxWidth: fe.maxWidth,
                minHeight: fe.minHeight,
                maxHeight: fe.maxHeight,
                ratio: fe.ratio
            })
        }

        function Hc(b, c) {
            a.trigger(hb + ":" + b, [Cd, c])
        }

        function Ic() {
            clearTimeout(Kc.t), zd = 1, e.stopAutoplayOnTouch ? Cd.stopAutoplay() : wd = !0
        }

        function Kc() {
            Kc.t = setTimeout(function () {
                zd = 0
            }, wc + vc)
        }

        function Lc() {
            wd = !(!$c && !xd)
        }

        function Mc() {
            if (clearTimeout(Mc.t), !e.autoplay || wd) return Cd.autoplay && (Cd.autoplay = !1, Hc("stopautoplay")), void 0;
            Cd.autoplay || (Cd.autoplay = !0, Hc("startautoplay"));
            var a = de;
            Mc.t = setTimeout(function () {
                var b = Cd.activeFrame[Cc].data();
                D(function () {
                    return b.state || a !== de
                }, function () {
                    wd || a !== de || Cd.show(hd ? ">" : v(de + 1))
                })
            }, e.autoplay)
        }

        function Nc() {
            Cd.fullScreen && (Cd.fullScreen = !1, rc && X.cancel(Gd), ab.removeClass(ib), _.removeClass(ib), a.removeClass(Vb).insertAfter(Kd), fe = c.extend({}, yd), Pc($c, !0, !0), Tc("x", !1), Cd.resize(), $(ad, "stage"), N(ud, td), Hc("fullscreenexit"))
        }

        function Oc(a, b) {
            rd && (a.removeClass(Qb + " " + Rb), b && !$c && a.addClass(b.replace(/^|\s/g, " " + Pb + "--")))
        }

        function Pc(a, b, c) {
            b && (Ld.removeClass(lb), $c = !1, j()), a && a !== $c && (a.remove(), Hc("unloadvideo")), c && (Lc(), Mc())
        }

        function Qc(a) {
            Ld.toggleClass(ob, a)
        }

        function Rc(a) {
            if (!ge.flow) {
                var b = a ? a.pageX : Rc.x,
                    c = !Mb(Lb(b)) && e.click;
                Rc.p === c || !nd && e.swipe || !Md.toggleClass(xb, c) || (Rc.p = c, Rc.x = b)
            }
        }

        function Sc(a, b) {
            var d = a.target,
                f = c(d);
            f.hasClass(ic) ? Cd.playVideo() : d === _d ? Cd[(Cd.fullScreen ? "cancel" : "request") + "FullScreen"]() : $c ? d === ce && Pc($c, !0, !0) : b ? Qc() : e.click && Cd.show({
                index: a.shiftKey || !Lb(a._x) ? "<" : ">",
                slow: a.altKey,
                direct: !0
            })
        }

        function Tc(a, b) {
            ge[a] = he[a] = b
        }

        function Uc(a, b) {
            var d = c(this).data().eq;
            Cd.show({
                index: d,
                slow: a.altKey,
                direct: !0,
                coo: a._x - Td.offset().left,
                time: b
            })
        }

        function Vc() {
            i(), u(), Wc.ok || (e.hash && location.hash && (gd = I(location.hash.replace(/^#/, ""), Xc, 0 === Dd)), de = bd = cd = dd = gd = B(gd) || 0), Yc ? ($c && Pc($c, !0), ad = [], sc(Cc), Cd.show({
                index: de,
                time: 0
            }), Cd.resize()) : Cd.destroy()
        }

        function Wc() {
            Wc.ok || (Wc.ok = !0, Hc("ready"))
        }
        _ = _ || c("html"), ab = ab || c("body");
        var Xc, Yc, Zc, $c, _c, ad, bd, cd, dd, ed, fd, gd, hd, id, jd, kd, ld, md, nd, od, pd, qd, rd, sd, td, ud, vd, wd, xd, yd, zd, Ad, Bd, Cd = this,
            Dd = Jc,
            Ed = c.now(),
            Fd = hb + Ed,
            Gd = a[0],
            Hd = 1,
            Id = a.data(),
            Jd = c("<style></style>"),
            Kd = c(K(Ub)),
            Ld = c(K(jb)),
            Md = c(K(rb)).appendTo(Ld),
            Nd = (Md[0], c(K(ub)).appendTo(Md)),
            Od = c(),
            Pd = c(K(yb + " " + Ab, K(Cb))),
            Qd = c(K(yb + " " + Bb, K(Cb))),
            Rd = Pd.add(Qd).appendTo(Md),
            Sd = c(K(Eb)),
            Td = c(K(Db)).appendTo(Sd),
            Ud = c(K(Fb)).appendTo(Td),
            Vd = c(),
            Wd = c(),
            Xd = Nd.data(),
            Yd = Ud.data(),
            Zd = c(K(fc)).appendTo(Ud),
            $d = c(K(Wb)),
            _d = $d[0],
            ae = c(K(ic)),
            be = c(K(jc)).appendTo(Md),
            ce = be[0],
            de = !1,
            ee = {}, fe = {}, ge = {}, he = {}, ie = {}, je = {}, ke = 0;
        Ld[Cc] = c(K(sb)), Ld[Ec] = c(K(Ib + " " + Kb, K(ec))), Ld[Dc] = c(K(Ib + " " + Jb, K(dc))), ie[Cc] = [], ie[Ec] = [], ie[Dc] = [], je[Cc] = {}, qc && Ld.addClass(kb), Id.fotorama = this, Cd.options = e, Jc++, Cd.startAutoplay = function (a) {
            return Cd.autoplay ? this : (wd = xd = !1, q(a || e.autoplay), Mc(), this)
        }, Cd.stopAutoplay = function () {
            return Cd.autoplay && (wd = xd = !0, Mc()), this
        }, Cd.show = function (a) {
            var b;
            "object" != typeof a ? (b = a, a = {}) : b = a.index, b = ">" === b ? cd + 1 : "<" === b ? cd - 1 : "<<" === b ? 0 : ">>" === b ? Yc - 1 : b, b = isNaN(b) ? I(b, Xc, !0) : b, b = "undefined" == typeof b ? de || 0 : b, Cd.activeIndex = de = B(b), ed = Q(de), fd = R(de), ad = [de, ed, fd], cd = hd ? b : de;
            var c = Math.abs(dd - cd),
                d = t(a.time, function () {
                    return Math.min(qd * (1 + (c - 1) / 12), 2 * qd)
                }),
                f = a.overPos;
            a.slow && (d *= 10), Cd.activeFrame = _c = Xc[de], Pc(!1, _c.i !== Xc[v(bd)].i), cb(ad, "stage"), eb([cd, Q(cd), R(cd)]), Hc("show", a.direct), Tc("go", !0);
            var h = Cd.show.onEnd = function (b) {
                h.ok || (h.ok = !0, bb(), $(ad, "stage"), b || Fc(!0), Hc("showend", a.direct), Tc("go", !1), Rc(), Lc(), Mc())
            };
            if (nd) {
                var i = _c[Cc],
                    j = de !== dd ? Xc[dd][Cc] : null;
                P(i, j, Od, {
                    time: d,
                    method: e.transition,
                    onEnd: h
                })
            } else O(Nd, {
                pos: -o(cd, fe.w, yc, bd),
                overPos: f,
                time: d,
                onEnd: h
            }); if (Nb(), id) {
                lc();
                var k = w(de + g(cd - dd, -1, 1));
                hc({
                    time: d,
                    coo: k !== de && a.coo,
                    guessIndex: "undefined" != typeof a.coo ? k : de
                }), jd && ac(d)
            }
            return vd = "undefined" != typeof dd && dd !== de, dd = de, e.hash && vd && !Cd.eq && E(_c.id || de + 1), this
        }, Cd.requestFullScreen = function () {
            return ld && !Cd.fullScreen && (td = mc.scrollTop(), ud = mc.scrollLeft(), N(0, 0), Tc("x", !0), yd = c.extend({}, fe), a.addClass(Vb).appendTo(ab.addClass(ib)), _.addClass(ib), Pc($c, !0, !0), Cd.fullScreen = !0, md && X.request(Gd), Cd.resize(), $(ad, "stage"), Hc("fullscreenenter")), this
        }, Cd.cancelFullScreen = function () {
            return md && X.is() ? X.cancel(b) : Nc(), this
        }, b.addEventListener && b.addEventListener(X.event, function () {
            X.is() || $c || Nc()
        }), nc.on("keydown", function (a) {
            $c && 27 === a.keyCode ? (a.preventDefault(), Pc($c, !0, !0)) : (Cd.fullScreen || e.keyboard && !Dd) && (27 === a.keyCode ? (a.preventDefault(), Cd.cancelFullScreen()) : 39 === a.keyCode || 40 === a.keyCode && Cd.fullScreen ? (a.preventDefault(), Cd.show({
                index: ">",
                slow: a.altKey,
                direct: !0
            })) : (37 === a.keyCode || 38 === a.keyCode && Cd.fullScreen) && (a.preventDefault(), Cd.show({
                index: "<",
                slow: a.altKey,
                direct: !0
            })))
        }), Dd || nc.on("keydown", "textarea, input, select", function (a) {
            Cd.fullScreen || a.stopPropagation()
        }), Cd.resize = function (a) {
            if (!Xc) return this;
            Gc(Cd.fullScreen ? {
                width: "100%",
                maxWidth: null,
                minWidth: null,
                height: "100%",
                maxHeight: null,
                minHeight: null
            } : a, Cd.fullScreen);
            var b = arguments[1] || 0,
                c = arguments[2],
                d = fe.width,
                e = fe.height,
                f = fe.ratio,
                h = mc.height() - (id ? Td.height() : 0);
            return n(d) && (Ld.css({
                width: d,
                minWidth: fe.minWidth,
                maxWidth: fe.maxWidth
            }), d = fe.w = Ld.width(), e = m(e) / 100 * h || l(e), e = e || f && d / f, e && (d = Math.round(d), e = fe.h = Math.round(g(e, m(fe.minHeight) / 100 * h || l(fe.minHeight), m(fe.maxHeight) / 100 * h || l(fe.maxHeight))), Fc(), Md.addClass(vb).stop().animate({
                width: d,
                height: e
            }, b, function () {
                Md.removeClass(vb)
            }), id && (Td.stop().animate({
                width: d
            }, b), hc({
                guessIndex: de,
                time: b,
                keep: !0
            }), jd && gb.nav && ac(b)), sd = c || !0, Wc())), ke = Md.offset().left, this
        }, Cd.setOptions = function (a) {
            return c.extend(e, a), Vc(), this
        }, Cd.shuffle = function () {
            return Xc && L(Xc) && Vc(), this
        }, Cd.destroy = function () {
            return Cd.cancelFullScreen(), Cd.stopAutoplay(), Xc = Cd.data = null, h(), ad = [], sc(Cc), this
        }, Cd.playVideo = function () {
            var a = Cd.activeFrame,
                b = a.video,
                d = de;
            return "object" == typeof b && a.videoReady && (md && Cd.fullScreen && Cd.cancelFullScreen(), D(function () {
                return !X.is() || d !== de
            }, function () {
                d === de && (a.$video = a.$video || c(c.Fotorama.jst.video(b)), a.$video.appendTo(a[Cc]), Ld.addClass(lb), $c = a.$video, j(), Hc("loadvideo"))
            })), this
        }, Cd.stopVideo = function () {
            return Pc($c, !0, !0), this
        }, Md.on("mousemove", Rc), ge = S(Nd, {
            onStart: Ic,
            onMove: function (a, b) {
                Oc(Md, b.edge)
            },
            onEnd: function (a) {
                Oc(Md), Kc();
                var b = (uc && !Ad || a.touch) && e.arrows;
                if (a.moved || b && a.pos !== a.newPos) {
                    var c = p(a.newPos, fe.w, yc, bd);
                    Cd.show({
                        index: c,
                        time: nd ? qd : a.time,
                        overPos: a.overPos,
                        direct: !0
                    })
                } else a.aborted || Sc(a.startEvent, b)
            },
            getPos: function () {
                return -o(cd, fe.w, yc, bd)
            },
            timeLow: 1,
            timeHigh: 1,
            friction: 2,
            select: "." + Tb + ", ." + Tb + " *",
            $wrap: Md
        }), he = S(Ud, {
            onStart: Ic,
            onMove: function (a, b) {
                Oc(Td, b.edge)
            },
            onEnd: function (a) {
                function b() {
                    hc.l = a.newPos, Lc(), Mc(), fb(a.newPos, !0)
                }
                if (Kc(), a.moved) a.pos !== a.newPos ? (O(Ud, {
                    time: a.time,
                    pos: a.newPos,
                    overPos: a.overPos,
                    onEnd: b
                }), fb(a.newPos), Oc(Td, H(a.newPos, Yd.min, Yd.max))) : b();
                else {
                    var c = a.$target.closest("." + Ib, Ud)[0];
                    c && Uc.call(c, a.startEvent)
                }
            },
            timeLow: .5,
            timeHigh: 2,
            friction: 5,
            $wrap: Td
        }), Ld.hover(function () {
            setTimeout(function () {
                zd || (Ad = !0, Qc(!Ad))
            }, 0)
        }, function () {
            Ad && (Ad = !1, Qc(!Ad))
        }), J(Rd, function (a) {
            a.preventDefault(), $c ? Pc($c, !0, !0) : (Kc(), Cd.show({
                index: Rd.index(this) ? ">" : "<",
                slow: a.altKey,
                direct: !0
            }))
        }, {
            onStart: function () {
                Ic(), ge.control = !0
            },
            tail: ge
        }), c.each("load push pop shift unshift reverse sort splice".split(" "), function (a, b) {
            Cd[b] = function () {
                return Xc = Xc || [], "load" !== b ? Array.prototype[b].apply(Xc, arguments) : arguments[0] && "object" == typeof arguments[0] && arguments[0].length && (Xc = arguments[0]), Vc(), Cd
            }
        }), mc.on("resize", Cd.resize), Vc()
    }, c.fn.fotorama = function (a) {
        return this.each(function () {
            var b = this,
                d = c(this),
                e = d.data(),
                f = e.fotorama;
            f ? f.setOptions(a) : D(function () {
                return !B(b)
            }, function () {
                e.urtext = d.html(), new c.Fotorama(d, c.extend({}, {
                    width: null,
                    minWidth: null,
                    maxWidth: null,
                    height: null,
                    minHeight: null,
                    maxHeight: null,
                    ratio: null,
                    nav: "dots",
                    navPosition: "bottom",
                    thumbWidth: zc,
                    thumbHeight: zc,
                    arrows: !0,
                    click: !0,
                    swipe: !0,
                    allowFullScreen: !1,
                    fit: "contain",
                    transition: "slide",
                    transitionDuration: wc,
                    captions: !0,
                    hash: !1,
                    autoplay: !1,
                    stopAutoplayOnTouch: !0,
                    keyboard: !1,
                    loop: !1,
                    shuffle: !1,
                    shadows: !0
                }, a, e))
            })
        })
    }, c.Fotorama.cache = {};
    var Jc = 0;
    c.Fotorama.size = 0, c(function () {
        c("." + hb + ':not([data-auto="false"])').fotorama()
    }), c = c || {}, c.Fotorama = c.Fotorama || {}, c.Fotorama.jst = c.Fotorama.jst || {}, c.Fotorama.jst.style = function (a) {
        var b, c = "";
        return V.escape, c += ".fotorama" + (null == (b = a.s) ? "" : b) + " .fotorama__nav--thumbs .fotorama__nav__frame{\npadding:" + (null == (b = a.m) ? "" : b) + "px;\nheight:" + (null == (b = a.h) ? "" : b) + "px}\n.fotorama" + (null == (b = a.s) ? "" : b) + " .fotorama__thumb-border{\nheight:" + (null == (b = a.h - a.m * (a.q ? 0 : 2)) ? "" : b) + "px;\nborder-width:" + (null == (b = a.m) ? "" : b) + "px;\nmargin-top:" + (null == (b = a.m) ? "" : b) + "px}"
    }, c.Fotorama.jst.video = function (a) {
        function b() {
            c += d.call(arguments, "")
        }
        var c = "",
            d = (V.escape, Array.prototype.join);
        return c += '<div class="fotorama__video"><iframe src="', b("youtube" == a.type ? "http://youtube.com/embed/" + a.id + "?autoplay=1" : "vimeo" == a.type ? "http://player.vimeo.com/video/" + a.id + "?autoplay=1&amp;badge=0" : a.id), c += '" frameborder="0" allowfullscreen></iframe></div>'
    }
}(window, document, jQuery);