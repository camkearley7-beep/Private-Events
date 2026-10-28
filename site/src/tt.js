/* The Bogey Club — site script (production). Vanilla, no dependencies,
   no tracking (CTAs carry data-tt-cta for the site's existing GTM/GA).
   Install once, site-wide: WPCode footer snippet (see 02-script.html). */
(function () {
  "use strict";
  function ready(fn) { if (document.readyState !== "loading") fn(); else document.addEventListener("DOMContentLoaded", fn); }

  ready(function () {
    var html = document.documentElement;
    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* Karaoke booking: open the karaoke-filtered calendar on the current month
       (same behaviour as the /karaoke/ page). */
    var now = new Date(), month = now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0");
    document.querySelectorAll('[data-tt-link="karaoke-booking"]').forEach(function (a) {
      a.href = "https://thebogeyclub.golfbooking.ca/calendar.php?date=" + month + "&bayfilter=4&templateId=50&lang=en";
    });
    document.querySelectorAll("[data-tt-year]").forEach(function (el) { el.textContent = now.getFullYear(); });

    /* A missing photo shows the pine frame, never a broken-image icon. */
    document.querySelectorAll(".tt img").forEach(function (img) {
      function miss() { img.classList.add("tt-missing"); }
      if (img.complete && img.naturalWidth === 0 && img.currentSrc) miss();
      else img.addEventListener("error", miss, { once: true });
    });

    /* Hero video: reveal it only once frames are playing; otherwise keep the still. */
    var hero = document.querySelector(".tt .hero"), vid = hero && hero.querySelector(".hero-video");
    if (vid) {
      var drop = function () { vid.remove(); };
      if (reduce || window.matchMedia("(prefers-reduced-data: reduce)").matches) drop();
      else {
        vid.addEventListener("playing", function () { hero.classList.add("is-playing"); }, { once: true });
        var srcs = vid.querySelectorAll("source");
        if (srcs.length) srcs[srcs.length - 1].addEventListener("error", drop);
        vid.addEventListener("error", drop);
        var p = vid.play && vid.play();
        if (p && p.catch) p.catch(function () { /* autoplay blocked: the still stays */ });
      }
    }

    /* Two bars: roll the golf ball (or use arrow keys) to compare; on desktop it follows the pointer. */
    var duo = document.getElementById("tt-duo");
    if (duo) {
      var range = duo.querySelector(".duo-range");
      var setX = function (v) {
        v = Math.max(0, Math.min(100, v));
        duo.style.setProperty("--x", v + "%"); range.value = Math.round(v);
        duo.style.setProperty("--rot", (v * 7.2).toFixed(1) + "deg"); /* the ball rolls as it moves */
        duo.dataset.side = v > 60 ? "a" : v < 40 ? "b" : "";
      };
      var fromEvent = function (e) { var r = duo.getBoundingClientRect(); return (e.clientX - r.left) / r.width * 100; };
      var used = function () { duo.classList.add("used"); };
      range.addEventListener("input", function () { setX(Number(range.value)); used(); });
      duo.addEventListener("pointerdown", function (e) { duo.classList.add("drag"); setX(fromEvent(e)); used(); });
      duo.addEventListener("pointermove", function (e) {
        if (duo.classList.contains("drag") || e.pointerType === "mouse") { setX(fromEvent(e)); if (e.pointerType === "mouse") used(); }
      });
      ["pointerup", "pointercancel", "pointerleave"].forEach(function (t) { duo.addEventListener(t, function () { duo.classList.remove("drag"); }); });
      /* gentle intro: the handle sweeps once when the section first appears */
      if ("IntersectionObserver" in window && !reduce) {
        var dio = new IntersectionObserver(function (es) {
          if (!es[0].isIntersecting || duo.classList.contains("used")) return;
          dio.disconnect();
          var t0 = performance.now();
          (function step(t) {
            if (duo.classList.contains("used")) return;
            var k = Math.min(1, (t - t0) / 1800);
            setX(50 + Math.sin(k * Math.PI * 2) * 22 * (1 - k));
            if (k < 1) requestAnimationFrame(step);
          })(t0);
        }, { threshold: .5 });
        dio.observe(duo);
      }
      /* count-up for the numbers */
      var nums = document.querySelectorAll(".bars-stats [data-count]");
      if (nums.length && "IntersectionObserver" in window && !reduce) {
        nums.forEach(function (el) { el.textContent = "0"; });
        var nio = new IntersectionObserver(function (es) {
          if (!es[0].isIntersecting) return; nio.disconnect();
          nums.forEach(function (el) {
            var end = Number(el.dataset.count), t0 = performance.now();
            (function tick(t) { var k = Math.min(1, (t - t0) / 900); el.textContent = Math.round(end * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(tick); })(t0);
          });
        }, { threshold: .6 });
        nio.observe(nums[0].parentElement.parentElement);
      }
    }

    /* Club life: hovering/focusing a row wipes its photo into the panel (desktop);
       on phones each row's thumbnail wipes in as it scrolls into view. */
    var lifeRows = document.querySelectorAll("#tt-rows .row"), figs = document.querySelectorAll("#tt-life-media figure");
    if (lifeRows.length && figs.length) {
      var cur = 0, num = document.getElementById("tt-life-n");
      var setRow = function (i) {
        if (i === cur) return;
        figs.forEach(function (f, k) { f.classList.toggle("was", k === cur); f.classList.remove("on"); });
        figs[i].classList.add("on");
        lifeRows.forEach(function (r, k) { r.classList.toggle("on", k === i); });
        if (num) num.textContent = String(i + 1).padStart(2, "0");
        cur = i;
      };
      var hover = window.matchMedia("(hover: hover) and (min-width: 861px)");
      lifeRows.forEach(function (r, i) {
        r.addEventListener("mouseenter", function () { if (hover.matches) setRow(i); });
        r.addEventListener("focus", function () { setRow(i); });
      });
      var rowsBox = document.getElementById("tt-rows");
      if ("IntersectionObserver" in window && !reduce) {
        rowsBox.classList.add("anim");
        var rio = new IntersectionObserver(function (es) {
          es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add("in"); rio.unobserve(e.target); } });
        }, { threshold: .35 });
        lifeRows.forEach(function (r) { rio.observe(r); });
      }
    }

    /* Sponsors: cards rise in, then a spotlight cycles through each sponsor.
       Hovering or focusing a card takes the spotlight; leaving resumes the cycle. */
    var signs = document.getElementById("tt-signs");
    if (signs) {
      var items = signs.querySelectorAll("li"), cards = signs.querySelectorAll(".bay"), lit = -1, timer = null;
      var light = function (i) { lit = i; cards.forEach(function (c, k) { c.classList.toggle("lit", k === i); }); };
      var start = function () { if (reduce || timer) return; timer = setInterval(function () { light((lit + 1) % cards.length); }, 3200); };
      var stop = function () { clearInterval(timer); timer = null; };
      cards.forEach(function (c, i) {
        c.addEventListener("mouseenter", function () { stop(); light(i); });
        c.addEventListener("focus", function () { stop(); light(i); });
      });
      signs.addEventListener("mouseleave", start);
      signs.addEventListener("focusout", function (e) { if (!signs.contains(e.relatedTarget)) start(); });
      if ("IntersectionObserver" in window && !reduce) {
        signs.classList.add("anim");
        var sio = new IntersectionObserver(function (es) {
          es.forEach(function (e) {
            if (e.isIntersecting) { items.forEach(function (li) { li.classList.add("in"); }); if (lit < 0) setTimeout(function () { light(0); start(); }, 900); }
            else stop();
            if (e.isIntersecting && lit >= 0) start();
          });
        }, { threshold: .3 });
        sio.observe(signs);
      }
    }

    /* Mobile menu */
    var btn = document.querySelector(".tt .menu"), sheet = document.getElementById("tt-mnav");
    if (btn && sheet) {
      var setOpen = function (open) {
        btn.setAttribute("aria-expanded", String(open));
        btn.textContent = open ? "Close" : "Menu";
        sheet.classList.toggle("open", open);
        html.classList.toggle("tt-lock", open);
        if (open) { var first = sheet.querySelector("a"); if (first) first.focus(); }
      };
      btn.addEventListener("click", function () { setOpen(btn.getAttribute("aria-expanded") !== "true"); });
      sheet.addEventListener("click", function (e) { if (e.target.closest("a")) setOpen(false); });
      document.addEventListener("keydown", function (e) { if (e.key === "Escape" && sheet.classList.contains("open")) { setOpen(false); btn.focus(); } });
      window.matchMedia("(min-width: 861px)").addEventListener("change", function (mq) { if (mq.matches) setOpen(false); });
    }

    /* From the tee to the tap: vertical scroll drives a horizontal dolly on desktop;
       on mobile the track is a native swipe. */
    var walk = document.getElementById("walk"), track = document.getElementById("tt-track");
    if (walk && track) {
      var desk = window.matchMedia("(min-width: 861px)");
      var prog = document.getElementById("tt-prog"), stops = document.querySelectorAll("#tt-stops li");
      var mark = function (k) {
        prog.style.width = (k * 100) + "%";
        var idx = Math.min(stops.length - 1, Math.round(k * (stops.length - 1)));
        stops.forEach(function (s, i) { s.classList.toggle("on", i === idx); });
      };
      var onScroll = function () {
        if (!desk.matches || reduce) return;
        var travel = track.scrollWidth - window.innerWidth, span = walk.offsetHeight - window.innerHeight;
        if (travel <= 0 || span <= 0) return;
        var k = Math.min(1, Math.max(0, -walk.getBoundingClientRect().top / span));
        track.style.transform = "translate3d(" + (-k * travel) + "px,0,0)";
        mark(k);
      };
      var size = function () {
        if (!desk.matches || reduce) { walk.style.height = ""; track.style.transform = ""; return; }
        walk.style.height = (window.innerHeight + Math.max(0, track.scrollWidth - window.innerWidth)) + "px";
        onScroll();
      };
      track.addEventListener("scroll", function () {
        if (desk.matches) return;
        mark(track.scrollLeft / Math.max(1, track.scrollWidth - track.clientWidth));
      }, { passive: true });
      window.addEventListener("scroll", onScroll, { passive: true });
      window.addEventListener("resize", size);
      window.addEventListener("load", size);
      size();
    }

  });
})();
