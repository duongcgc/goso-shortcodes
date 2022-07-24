/**
 * Delay load functionality of goso pagespeed plugin.
 * @preserve
 * @copyright asadkn 2021
 */
"use strict";

window.gosoLazyConfig = {"cssDelayType": "interact"};
(() => {
    const r = window.gosoLazyConfig || {};
    const s = true;
    let c = [];
    const d = {
        HTMLDocument: document.addEventListener.bind(document),
        Window: window.addEventListener.bind(window)
    };
    const n = {};
    let a;
    let l = false;
    let u = false;
    let e = false;
    let f = [];
    let t = [];

    function m() {
        w();
        document.addEventListener("gosolazy-load-css", () => w(true));
    }

    function w(e) {
        t = [...document.querySelectorAll("link[data-authow_pagespeed-delay]")];
        if (t.length) {
            y("css", e);
        }
    }

    function y(t, n) {
        t = t || "js";
        const o = n ? "onload" : r[t + "DelayType"] || "onload";
        const a = t === "js" ? p : g;
        if (t === "js") {
            n || o === "onload" ? v() : D(v);
        }
        switch (o) {
            case "onload":
                D(() => a(n));
                break;

            case "interact":
                let e = false;
                const s = ["mousemove", "mousedown", "keydown", "touchstart", "wheel"];
                const c = () => {
                    if (e) {
                        return;
                    }
                    e = true;
                    t === "js" ? O(() => setTimeout(a, 2)) : a();
                };
                s.forEach(e => {
                    document.addEventListener(e, c, {
                        passive: true,
                        once: true
                    });
                });
                break;

            case "custom-delay":
                D(() => {
                    const e = parseInt(element.dataset.customDelay) * 1e3;
                    setTimeout(a, e);
                });
                break;
        }
    }

    function g() {
        t.forEach(e => b(e));
    }

    function p(e) {
        v();
        if (!e) {
            l = true;
            a = document.readyState;
            let t = "loading";
            Object.defineProperty(document, "readyState", {
                configurable: true,
                get() {
                    return t;
                },
                set(e) {
                    return t = e;
                }
            });
        }
        let t;
        const n = new Promise(e => t = e);
        const o = () => {
            if (!f.length) {
                t();
                return;
            }
            const e = b(f.shift());
            e.then(o);
        };
        o();
        n.then(j).catch(e => {
            console.error(e);
            j();
        });
        setTimeout(() => !c.length || j(), 45e3);
    }

    function v(o) {
        if (e) {
            return;
        }
        e = true;
        f.forEach(e => {
            const t = e.src || e.dataset.src;
            if (!t) {
                return;
            }
            const n = document.createElement("link");
            Object.assign(n, {
                rel: o || "preload",
                as: "script",
                href: t,
                ...e.crossOrigin && {
                    crossOrigin: e.crossOrigin
                }
            });
            document.head.append(n);
        });
    }

    function b(t) {
        let e;
        const n = t.dataset.src;
        const o = t => {
            return new Promise(e => {
                t.addEventListener("load", e);
                t.addEventListener("error", e);
            });
        };
        if (n) {
            const s = document.createElement("script");
            e = o(s);
            t.getAttributeNames().forEach(e => {
                e === "src" || (s[e] = t[e]);
            });
            s.async = false;
            s.src = n;
            t.parentNode.replaceChild(s, t);
        } else if (t.type && t.type === "text/goso-script") {
            t.type = t.dataset.type || "text/javascript";
            t.text += " ";
        }
        const a = t.dataset.href;
        if (a) {
            e = o(t);
            t.href = a;
        }
        ["gosoDelay", "src"].forEach(e => {
            t.dataset[e] = "";
            delete t.dataset[e];
        });
        return e || Promise.resolve();
    }

    function L(e) {
        try {
            e.cb.call(e.context, n[e.event], ...e.args);
        } catch (e) {
            console.error(e);
        }
    }

    function j() {
        if (u) {
            return;
        }
        s && console.log("Firing Load Events", c);
        u = true;
        const e = c.filter(e => e.event === "readystatechange");
        document.readyState = "interactive";
        e.forEach(e => L(e));
        for (const t of c) {
            t.event === "DOMContentLoaded" && L(t);
        }
        for (const t of c) {
            t.event === "load" && L(t);
        }
        c = [];
        u = false;
        D(() => {
            document.readyState = "complete";
            setTimeout(() => {
                e.forEach(e => L(e));
            }, 2);
        });
    }

    function D(e) {
        const t = a || document.readyState;
        t === "complete" ? e() : d.Window("load", () => e());
    }

    function O(e) {
        document.readyState !== "loading" ? e() : d.Window("DOMContentLoaded", () => e());
    }

    m();
})();
