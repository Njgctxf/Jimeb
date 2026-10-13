#!/usr/bin/env python3
"""Generate the 13 DTL frame sub-compositions (compositions/frames/NN-*.html).

Each frame is one <template> fragment: shared ground (navy gradient, drifting light blobs,
sparkles) + the frame's own scene. '§' is replaced by the frame prefix (e.g. f04) so ids and
classes never collide once the frames are assembled into one page.
"""
import os, re, sys

sys.path.insert(0, os.path.dirname(__file__))
from icons import *  # noqa

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
W, H = 1080, 1920


def durations():
    sb = open(os.path.join(ROOT, "STORYBOARD.md"), encoding="utf-8").read()
    out = {}
    for m in re.finditer(r"## Frame (\d+) —.*?- duration: ([\d.]+)s.*?- src: (\S+)", sb, re.S):
        out[int(m.group(1))] = (float(m.group(2)), m.group(3))
    return out


FONTS = """
@font-face { font-family: "Anton"; src: url("assets/fonts/Anton-400.woff2") format("woff2"); font-weight: 400; }
@font-face { font-family: "Montserrat"; src: url("assets/fonts/Montserrat-var.woff2") format("woff2"); font-weight: 100 900; }
"""

SHARED_CSS = """
#root { position: absolute; inset: 0; overflow: hidden; container-type: size; font-family: "Montserrat", sans-serif; color: #fff; }
.§-layer { position: absolute; inset: 0; }
.§-bg { position: absolute; inset: 0; background: radial-gradient(120% 80% at 50% 20%, #1B3F8F 0%, #0A1A44 60%, #060F2B 100%); }
.§-blob { position: absolute; border-radius: 50%; filter: blur(70px); }
.§-blobA { left: -200px; top: 120px; width: 760px; height: 760px; background: rgba(47,123,255,.55); }
.§-blobB { right: -260px; top: 1050px; width: 820px; height: 820px; background: rgba(34,211,238,.38); }
.§-spark { position: absolute; width: 46px; height: 46px; }
.§-cam { position: absolute; inset: 0; }
.§-abs { position: absolute; }
.§-center { position: absolute; left: 0; right: 0; display: flex; justify-content: center; }
.§-anton { font-family: "Anton", sans-serif; text-transform: uppercase; line-height: .92; }
.§-hot { color: #F19E14; text-shadow: 0 5px 0 #C9520F, 0 10px 0 #9C3F0B, 0 15px 0 #7A3009, 0 26px 40px rgba(0,0,0,.55); }
.§-white3d { color: #fff; text-shadow: 0 5px 0 #9DB5EE, 0 10px 0 #5A78C4, 0 15px 0 #2A4691, 0 26px 40px rgba(0,0,0,.55); }
.§-glass { background: rgba(255,255,255,.10); border: 2px solid rgba(255,255,255,.28); border-radius: 40px; backdrop-filter: blur(18px); box-shadow: inset 0 2px 0 rgba(255,255,255,.35), 0 30px 80px rgba(0,0,0,.35); }
.§-chip { display: inline-flex; align-items: center; gap: 18px; padding: 22px 40px; border-radius: 999px; font-weight: 800; font-size: 40px; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
.§-chip.hot { background: linear-gradient(135deg, #F19E14 0%, #E2741B 60%, #C9520F 100%); box-shadow: 0 18px 40px rgba(226,116,27,.45), inset 0 2px 0 rgba(255,255,255,.45); }
.§-chip.glass { background: rgba(255,255,255,.12); border: 2px solid rgba(255,255,255,.32); backdrop-filter: blur(14px); }
.§-chip svg { width: 56px; height: 56px; }
.§-label { font-weight: 800; font-size: 34px; letter-spacing: .22em; text-transform: uppercase; color: #B9C6E8; }
.§-dots { position: absolute; top: 150px; left: 0; right: 0; display: flex; justify-content: center; gap: 16px; }
.§-dot { width: 64px; height: 12px; border-radius: 6px; background: rgba(255,255,255,.22); }
.§-dot.on { background: linear-gradient(90deg, #F19E14, #E2741B); box-shadow: 0 0 20px rgba(241,158,20,.8); }
.§-streak { position: absolute; height: 10px; border-radius: 5px; background: linear-gradient(90deg, rgba(241,158,20,0), #F19E14 60%, #E2741B); }
"""

SPARK_SVG = '<svg viewBox="0 0 40 40"><path d="M20 0 C22 14 26 18 40 20 C26 22 22 26 20 40 C18 26 14 22 0 20 C14 18 18 14 20 0Z" fill="{c}"/></svg>'
SPARKS = [(120, 300, "#fff"), (930, 420, "#F19E14"), (860, 1180, "#fff"), (90, 1350, "#7FB2FF"), (520, 210, "#7FB2FF"), (980, 860, "#fff")]


def ground(d):
    sp = "".join(
        f'<div id="§-sp{i}" class="§-spark" style="left:{x}px;top:{y}px">{SPARK_SVG.format(c=c)}</div>'
        for i, (x, y, c) in enumerate(SPARKS)
    )
    return f"""
  <div id="§-ground" class="clip §-layer" data-start="0" data-duration="{d}" data-track-index="0">
    <div class="§-bg"></div>
    <div id="§-blobA" class="§-blob §-blobA"></div>
    <div id="§-blobB" class="§-blob §-blobB"></div>
    {sp}
  </div>"""


def ground_js(d):
    js = [
        f'tl.fromTo("#§-blobA", {{x: -40, y: 0}}, {{x: 120, y: 90, duration: {d}, ease: "none"}}, 0);',
        f'tl.fromTo("#§-blobB", {{x: 40, y: 0}}, {{x: -140, y: -110, duration: {d}, ease: "none"}}, 0);',
        f'tl.fromTo("#§-cam", {{scale: 1}}, {{scale: 1.045, duration: {d}, ease: "none"}}, 0);',
    ]
    for i in range(len(SPARKS)):
        start = 0.15 + i * 0.37
        js.append(
            f'tl.fromTo("#§-sp{i}", {{scale: 0, rotation: -40, opacity: 0}}, {{scale: 1, rotation: 0, opacity: 0.9, duration: 0.5, ease: "back.out(3)"}}, {start % max(d - 0.6, 0.5):.2f});'
        )
        js.append(f'tl.to("#§-sp{i}", {{rotation: 90, duration: {max(d - 0.8, 0.5):.2f}, ease: "none"}}, {start % max(d - 0.6, 0.5) + 0.55:.2f});')
    return "\n    ".join(js)


def svg(inner, vb, cls="", ident="", style=""):
    i = f' id="{ident}"' if ident else ""
    c = f' class="{cls}"' if cls else ""
    s = f' style="{style}"' if style else ""
    return f'<svg{i}{c}{s} viewBox="{vb}" xmlns="http://www.w3.org/2000/svg">{inner}</svg>'


def dots(n):
    return '<div class="§-dots">' + "".join(
        f'<div class="§-dot{" on" if i == n else ""}"></div>' for i in range(1, 8)
    ) + "</div>"


def service_header(num, title, sub=""):
    sub_html = f'<div id="§-sub" class="§-label" style="margin-top:22px;font-size:38px;color:#fff">{sub}</div>' if sub else ""
    size = 190 if len(title) <= 10 else 150
    return f"""
    {dots(num)}
    <div id="§-numwrap" class="§-center" style="top:230px;align-items:center;gap:26px">
      <div id="§-num" class="§-anton §-hot" style="font-size:120px">0{num}</div>
      <div id="§-svc" class="§-label">SERVICE</div>
    </div>
    <div class="§-center" style="top:380px;flex-direction:column;align-items:center">
      <div id="§-title" class="§-anton §-white3d" style="font-size:{size}px;text-align:center">{title}</div>
      {sub_html}
    </div>"""


def service_header_js():
    return """
    tl.fromTo("#§-numwrap", {y: -60, opacity: 0}, {y: 0, opacity: 1, duration: .45, ease: "back.out(2)"}, 0.05);
    tl.fromTo("#§-title", {scale: 1.8, opacity: 0, filter: "blur(16px)"}, {scale: 1, opacity: 1, filter: "blur(0px)", duration: .5, ease: "expo.out"}, 0.12);
    if (document.querySelector("#§-sub")) tl.fromTo("#§-sub", {y: 30, opacity: 0}, {y: 0, opacity: 1, duration: .4, ease: "power3.out"}, 0.45);"""


def chip(ident, text, kind="glass", icon=""):
    ic = svg(icon, "0 0 120 120") if icon else ""
    return f'<div id="§-{ident}" class="§-chip {kind}">{ic}<span>{text}</span></div>'


def pop(sel, t, frm="scale: 0.4, opacity: 0", to="scale: 1, opacity: 1", dur=0.45, ease="back.out(2.2)"):
    return f'tl.fromTo("{sel}", {{{frm}}}, {{{to}, duration: {dur}, ease: "{ease}"}}, {t});'


def slide_in(sel, t, dx=-160):
    return f'tl.fromTo("{sel}", {{x: {dx}, opacity: 0}}, {{x: 0, opacity: 1, duration: .45, ease: "back.out(1.8)"}}, {t});'


# ───────────────────────────── frames ─────────────────────────────

def f01(d):
    css = """
    .§-word { font-size: 300px; }
    """
    html = f"""
    <div class="§-center" style="top:250px;flex-direction:column;align-items:center;gap:0">
      <div id="§-w1" class="§-anton §-white3d §-word" style="font-size:230px">NE</div>
      <div id="§-w2" class="§-anton §-hot §-word" style="font-size:250px">SWIPEZ</div>
      <div id="§-w3" class="§-anton §-white3d §-word">PAS</div>
    </div>
    <div id="§-phone" class="§-abs" style="left:390px;top:1130px;width:300px;height:300px">
      {svg('<rect x="70" y="10" width="160" height="280" rx="30" fill="#0A1A44" stroke="#fff" stroke-width="10"/><path id="§-arrow" d="M150 220 V80 M110 120 L150 80 L190 120" stroke="#F19E14" stroke-width="16" fill="none" stroke-linecap="round" stroke-linejoin="round"/>', "0 0 300 300", style="width:300px;height:300px")}
      {svg('<path d="M40 40 L260 260 M260 40 L40 260" stroke="#FF3B3B" stroke-width="30" stroke-linecap="round"/>', "0 0 300 300", ident="§-x", cls="§-abs", style="left:0;top:0;width:300px;height:300px")}
    </div>
    <div id="§-pill" class="§-center" style="top:1470px">{chip("pillc", "C'EST POUR VOUS", "hot")}</div>
    <div id="§-s1" class="§-streak" style="left:-200px;top:420px;width:520px"></div>
    <div id="§-s2" class="§-streak" style="left:-200px;top:940px;width:640px"></div>
    """
    js = f"""
    tl.fromTo("#§-w1", {{scale: 3, opacity: 0}}, {{scale: 1, opacity: 1, duration: .28, ease: "expo.out"}}, 0.02);
    tl.fromTo("#§-w2", {{scale: 3, opacity: 0, rotation: -6}}, {{scale: 1, opacity: 1, rotation: -3, duration: .3, ease: "expo.out"}}, 0.28);
    tl.fromTo("#§-w3", {{scale: 3, opacity: 0}}, {{scale: 1, opacity: 1, duration: .3, ease: "expo.out"}}, 0.55);
    tl.to("#§-cam", {{keyframes: {{x: [0, -18, 16, -10, 6, 0], y: [0, 10, -8, 6, -3, 0]}}, duration: .35, ease: "none"}}, 0.6);
    tl.fromTo("#§-s1", {{x: 0}}, {{x: 1500, duration: .45, ease: "power2.in"}}, 0.3);
    tl.fromTo("#§-s2", {{x: 0}}, {{x: 1500, duration: .45, ease: "power2.in"}}, 0.6);
    tl.fromTo("#§-phone", {{y: 120, opacity: 0}}, {{y: 0, opacity: 1, duration: .4, ease: "back.out(2)"}}, 0.85);
    tl.fromTo("#§-arrow", {{y: 40}}, {{y: -30, duration: .35, ease: "power2.inOut"}}, 1.1);
    tl.fromTo("#§-x", {{scale: 0, opacity: 0, rotation: -30}}, {{scale: 1, opacity: 1, rotation: 0, duration: .3, ease: "back.out(3)"}}, 1.35);
    tl.fromTo("#§-pill", {{y: 80, opacity: 0}}, {{y: 0, opacity: 1, duration: .45, ease: "back.out(2)"}}, 1.65);
    """
    return css, html, js


def f02(d):
    css = """
    .§-row { position: absolute; left: 90px; display: flex; align-items: center; gap: 30px; padding: 26px 46px 26px 30px; }
    .§-row svg { width: 120px; height: 120px; }
    .§-row span { font-family: "Anton"; font-size: 96px; text-transform: uppercase; }
    .§-bubble { position: absolute; left: 110px; top: 1080px; width: 860px; padding: 48px 56px; background: #fff; color: #0A1A44; border-radius: 48px; box-shadow: 0 30px 80px rgba(0,0,0,.45); }
    .§-bubble:after { content: ""; position: absolute; left: 90px; bottom: -36px; border: 22px solid transparent; border-top: 30px solid #fff; border-left: 30px solid #fff; }
    .§-q { font-weight: 800; font-size: 62px; line-height: 1.1; }
    .§-q b { font-family: "Anton"; font-weight: 400; font-size: 130px; color: #E2741B; display: block; letter-spacing: .01em; }
    .§-typing { display: flex; gap: 16px; }
    .§-typing i { width: 26px; height: 26px; border-radius: 50%; background: #9DB5EE; display: block; }
    """
    html = f"""
    <div id="§-r1" class="§-row §-glass" style="top:230px">{svg(ICON_CAR, "0 0 140 110")}<span>UN TRAJET ?</span></div>
    <div id="§-r2" class="§-row §-glass" style="top:470px">{svg(ICON_BOX, "0 0 120 120")}<span>UNE LIVRAISON ?</span></div>
    <div id="§-r3" class="§-row §-glass" style="top:710px">{svg(ICON_BALLOON, "0 0 120 120")}<span>UN ÉVÉNEMENT ?</span></div>
    <div id="§-bub" class="§-bubble">
      <div id="§-typing" class="§-typing"><i></i><i></i><i></i></div>
      <div id="§-q" class="§-q" style="position:absolute;left:56px;top:40px;right:40px">QUELQU'UN DE <b>FIABLE ?</b></div>
    </div>
    <div id="§-cursor" class="§-abs" style="left:0;top:0;width:90px;height:90px">{svg('<path d="M10 6 L80 44 L46 52 L34 86 Z" fill="#2F7BFF" stroke="#fff" stroke-width="6" stroke-linejoin="round"/>', "0 0 90 90", style="width:90px;height:90px")}</div>
    """
    js = f"""
    {slide_in("#§-r1", 0.05)}
    {slide_in("#§-r2", 0.75, 160)}
    {slide_in("#§-r3", 1.45)}
    tl.fromTo("#§-bub", {{scale: .3, opacity: 0, transformOrigin: "15% 100%"}}, {{scale: 1, opacity: 1, duration: .45, ease: "back.out(2)"}}, 2.55);
    tl.set("#§-q", {{opacity: 0}}, 0);
    tl.fromTo("#§-typing i", {{y: 0}}, {{y: -16, duration: .18, ease: "power1.out", stagger: .09, yoyo: true, repeat: 1}}, 2.7);
    tl.to("#§-typing", {{opacity: 0, duration: .1}}, 3.15);
    tl.fromTo("#§-q", {{opacity: 0, y: 20}}, {{opacity: 1, y: 0, duration: .35, ease: "power3.out"}}, 3.2);
    tl.fromTo("#§-q b", {{scale: .5}}, {{scale: 1, duration: .5, ease: "elastic.out(1,.5)"}}, 3.45);
    tl.fromTo("#§-bub", {{height: 150}}, {{height: 300, duration: .3, ease: "power2.out"}}, 3.15);
    tl.fromTo("#§-cursor", {{x: 1100, y: 1700, opacity: 0}}, {{x: 820, y: 1290, opacity: 1, duration: .7, ease: "power3.out"}}, 3.3);
    tl.to("#§-cursor", {{scale: .8, duration: .1, yoyo: true, repeat: 1}}, 4.05);
    """
    return css, html, js


def f03(d):
    css = """
    .§-plate { position: absolute; left: 140px; top: 470px; width: 800px; height: 800px; border-radius: 64px; background: #fff; overflow: hidden; box-shadow: 0 0 0 10px rgba(241,158,20,.85), 0 0 120px rgba(241,158,20,.55), 0 40px 100px rgba(0,0,0,.5); }
    .§-plate img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .§-ring { position: absolute; left: 90px; top: 420px; width: 900px; height: 900px; border-radius: 50%; border: 6px solid rgba(255,255,255,.35); }
    """
    html = f"""
    <div id="§-kick" class="§-center" style="top:230px"><div class="§-anton §-white3d" style="font-size:140px">DÉCOUVREZ</div></div>
    <div id="§-ring" class="§-ring"></div>
    <div id="§-plate" class="§-plate"><img id="§-logo" src="assets/logo-dtl.jpg" alt="DTL"/></div>
    <div id="§-flash" class="§-streak" style="left:-700px;top:850px;width:700px;height:26px;border-radius:13px"></div>
    <div id="§-tag" class="§-center" style="top:1380px">{chip("tagc", "VOTRE PARTENAIRE DE CONFIANCE", "hot")}</div>
    """
    js = f"""
    tl.fromTo("#§-kick", {{y: -80, opacity: 0}}, {{y: 0, opacity: 1, duration: .45, ease: "back.out(2)"}}, 0.05);
    tl.fromTo("#§-plate", {{scale: 0, rotation: -12, opacity: 0}}, {{scale: 1, rotation: 0, opacity: 1, duration: .7, ease: "back.out(1.6)"}}, 0.35);
    tl.fromTo("#§-logo", {{clipPath: "inset(0 100% 0 0)"}}, {{clipPath: "inset(0 0% 0 0)", duration: .8, ease: "power3.inOut"}}, 0.75);
    tl.fromTo("#§-flash", {{x: 0}}, {{x: 2000, duration: .7, ease: "power2.in"}}, 0.9);
    tl.fromTo("#§-ring", {{scale: .6, opacity: 0}}, {{scale: 1.15, opacity: 1, duration: 1.2, ease: "power2.out"}}, 0.5);
    tl.to("#§-ring", {{scale: 1.35, opacity: 0, duration: 1.4, ease: "power1.out"}}, 1.7);
    tl.fromTo("#§-plate", {{y: 0}}, {{y: -30, duration: {d - 1.2:.2f}, ease: "sine.inOut"}}, 1.1);
    tl.fromTo("#§-tag", {{y: 80, opacity: 0, scale: .7}}, {{y: 0, opacity: 1, scale: 1, duration: .5, ease: "back.out(2)"}}, 3.0);
    """
    return css, html, js


def f04(d):
    css = """
    .§-road { position: absolute; left: 0; right: 0; top: 1050px; height: 90px; background: #0E1D47; border-top: 6px solid rgba(255,255,255,.2); }
    .§-lane { position: absolute; top: 40px; height: 12px; width: 120px; border-radius: 6px; background: #F19E14; }
    .§-city { position: absolute; left: 0; right: 0; top: 700px; height: 360px; }
    """
    lanes = "".join(f'<div class="§-lane" style="left:{x}px"></div>' for x in range(-200, 2400, 240))
    html = f"""
    {service_header(1, "TRANSPORT", "DE PERSONNES & SCOLAIRE")}
    {svg('<g fill="#16306E"><rect x="40" y="140" width="120" height="220"/><rect x="180" y="60" width="140" height="300"/><rect x="340" y="180" width="100" height="180"/><rect x="460" y="100" width="150" height="260"/><rect x="630" y="200" width="110" height="160"/><rect x="760" y="40" width="130" height="320"/><rect x="910" y="160" width="140" height="200"/></g><g fill="#2F7BFF" opacity=".35"><rect x="200" y="90" width="20" height="20"/><rect x="240" y="130" width="20" height="20"/><rect x="480" y="130" width="20" height="20"/><rect x="790" y="80" width="20" height="20"/><rect x="830" y="140" width="20" height="20"/></g>', "0 0 1080 360", cls="§-city")}
    <div class="§-road"><div id="§-lanes" class="§-abs" style="left:0;top:0;width:2600px;height:90px">{lanes}</div></div>
    <div id="§-bus" class="§-abs" style="left:220px;top:760px;width:640px;height:360px">{svg(BUS, "0 0 640 360", style="width:640px;height:360px")}</div>
    <div class="§-center" style="top:1200px;gap:22px">{chip("c1", "ÉCOLES")}{chip("c2", "ENTREPRISES")}</div>
    <div class="§-center" style="top:1330px;gap:22px">{chip("c3", "VOYAGES")}</div>
    <div class="§-center" style="top:1460px">{chip("c4", "EN TOUTE SÉCURITÉ", "hot", ICON_SHIELD)}</div>
    """
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-bus", {{x: -900}}, {{x: 0, duration: 1.1, ease: "power3.out"}}, 0.4);
    tl.fromTo("#§-bus", {{y: 0}}, {{y: -6, duration: .18, ease: "sine.inOut", yoyo: true, repeat: {int((d - 1.5) / .18)}}}, 1.5);
    tl.fromTo("#§-bus .wheel", {{rotation: 0, transformOrigin: "50% 50%"}}, {{rotation: 1080, duration: {d:.2f}, ease: "power1.out"}}, 0.4);
    tl.fromTo("#§-lanes", {{x: 0}}, {{x: -1440, duration: {d:.2f}, ease: "none"}}, 0);
    {pop("#§-c1", 2.4)}
    {pop("#§-c2", 2.8)}
    {pop("#§-c3", 3.2)}
    {pop("#§-c4", 4.2)}
    """
    return css, html, js


def f05(d):
    css = """
    .§-map { position: absolute; left: 120px; top: 640px; width: 840px; height: 560px; overflow: hidden; }
    .§-map svg { position: absolute; left: 0; top: 0; }
    """
    route = "M110 440 C 260 440, 260 260, 420 260 S 600 120, 730 120"
    html = f"""
    {service_header(2, "VTC", "& GESTION DE VÉHICULES")}
    <div id="§-mapc" class="§-map §-glass">
      {svg('<g stroke="rgba(255,255,255,.12)" stroke-width="18" fill="none"><path d="M0 200 H840 M0 380 H840 M200 0 V560 M520 0 V560 M700 0 V560"/></g>', "0 0 840 560", style="width:840px;height:560px")}
      {svg(f'<path id="§-route" d="{route}" stroke="#F19E14" stroke-width="16" fill="none" stroke-linecap="round" stroke-dasharray="1000" stroke-dashoffset="1000"/><circle cx="110" cy="440" r="22" fill="#2F7BFF" stroke="#fff" stroke-width="6"/>', "0 0 840 560", style="width:840px;height:560px")}
      <div id="§-pin" class="§-abs" style="left:680px;top:20px;width:100px;height:110px">{svg('<path d="M50 105 C 20 70 6 52 6 36 a44 44 0 0 1 88 0 c0 16 -14 34 -44 69z" fill="#E2741B" stroke="#fff" stroke-width="6"/><circle cx="50" cy="38" r="15" fill="#fff"/>', "0 0 100 110", style="width:100px;height:110px")}</div>
      <div id="§-car" class="§-abs" style="left:0;top:0;width:150px;height:110px">{svg(ICON_CAR, "0 0 140 110", style="width:150px;height:110px")}</div>
    </div>
    <div class="§-center" style="top:1280px">{chip("c1", "CHAUFFEURS PROS", "glass")}</div>
    <div class="§-center" style="top:1420px">{chip("c2", "FLOTTE ENTRETENUE", "hot")}</div>
    """
    # car follows sampled route points (deterministic keyframes)
    pts = [(110, 440), (190, 438), (250, 400), (290, 320), (350, 270), (420, 260), (500, 240), (560, 180), (640, 130), (730, 120)]
    xs = ", ".join(str(x - 75) for x, _ in pts)
    ys = ", ".join(str(y - 80) for _, y in pts)
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-mapc", {{y: 120, opacity: 0, rotationX: 30}}, {{y: 0, opacity: 1, rotationX: 0, duration: .6, ease: "power3.out"}}, 0.35);
    tl.fromTo("#§-route", {{strokeDashoffset: 1000}}, {{strokeDashoffset: 0, duration: 1.8, ease: "power1.inOut"}}, 0.8);
    tl.fromTo("#§-car", {{x: {pts[0][0] - 75}, y: {pts[0][1] - 80}}}, {{keyframes: {{x: [{xs}], y: [{ys}]}}, duration: 1.8, ease: "power1.inOut"}}, 0.8);
    tl.fromTo("#§-pin", {{y: -80, opacity: 0}}, {{y: 0, opacity: 1, duration: .5, ease: "bounce.out"}}, 0.9);
    tl.fromTo("#§-pin", {{scale: 1}}, {{scale: 1.15, duration: .25, ease: "sine.inOut", yoyo: true, repeat: 5, transformOrigin: "50% 100%"}}, 2.6);
    {pop("#§-c1", 2.0)}
    {pop("#§-c2", 3.1)}
    """
    return css, html, js


def f06(d):
    css = """
    .§-ic { width: 210px; height: 210px; display: flex; align-items: center; justify-content: center; }
    .§-ic svg { width: 150px; height: 150px; }
    .§-stamp { font-family: "Anton"; font-size: 110px; text-transform: uppercase; }
    """
    html = f"""
    {service_header(3, "LIVRAISON")}
    <div class="§-center" style="top:600px;gap:40px">
      <div id="§-i1" class="§-ic §-glass">{svg(ICON_FOOD, "0 0 120 120")}</div>
      <div id="§-i2" class="§-ic §-glass">{svg(ICON_BOX, "0 0 120 120")}</div>
      <div id="§-i3" class="§-ic §-glass">{svg(ICON_PALLET, "0 0 120 120")}</div>
    </div>
    <div class="§-center" style="top:830px;gap:40px">
      <div class="§-label" style="width:210px;text-align:center">REPAS</div><div class="§-label" style="width:210px;text-align:center">COLIS</div><div class="§-label" style="width:210px;text-align:center;letter-spacing:.06em">MARCHANDISES</div>
    </div>
    <div id="§-sl1" class="§-streak" style="left:60px;top:1040px;width:300px"></div>
    <div id="§-sl2" class="§-streak" style="left:20px;top:1100px;width:380px"></div>
    <div id="§-sco" class="§-abs" style="left:330px;top:900px;width:420px;height:300px">{svg(SCOOTER, "0 0 420 300", style="width:420px;height:300px")}</div>
    <div class="§-center" style="top:1260px;gap:30px">
      <div id="§-t1" class="§-stamp §-hot">RAPIDE</div><div id="§-t2" class="§-stamp §-white3d">PARTOUT</div>
    </div>
    <div class="§-center" style="top:1410px"><div id="§-t3" class="§-stamp §-hot">À L'HEURE</div></div>
    """
    js = f"""
    {service_header_js()}
    {pop("#§-i1", 0.55)}
    {pop("#§-i2", 0.95)}
    {pop("#§-i3", 1.35)}
    tl.fromTo("#§-sco", {{x: -800}}, {{x: 0, duration: .8, ease: "power3.out"}}, 1.6);
    tl.fromTo("#§-sco .wheel", {{rotation: 0, transformOrigin: "50% 50%"}}, {{rotation: 900, duration: {d - 1.6:.2f}, ease: "power1.out"}}, 1.6);
    tl.fromTo("#§-sco", {{y: 0}}, {{y: -5, duration: .15, yoyo: true, repeat: {int((d - 2.4) / .15)}, ease: "sine.inOut"}}, 2.4);
    tl.fromTo(["#§-sl1", "#§-sl2"], {{x: 200, opacity: 0}}, {{x: -80, opacity: 1, duration: .5, stagger: .1, ease: "power2.out"}}, 2.0);
    tl.fromTo("#§-t1", {{scale: 2.4, opacity: 0}}, {{scale: 1, opacity: 1, duration: .3, ease: "expo.out"}}, 2.45);
    tl.fromTo("#§-t2", {{scale: 2.4, opacity: 0}}, {{scale: 1, opacity: 1, duration: .3, ease: "expo.out"}}, 2.95);
    tl.fromTo("#§-t3", {{scale: 2.4, opacity: 0}}, {{scale: 1, opacity: 1, duration: .3, ease: "expo.out"}}, 3.45);
    """
    return css, html, js


def lcg(seed):
    s = seed
    while True:
        s = (s * 1103515245 + 12345) & 0x7FFFFFFF
        yield s / 0x7FFFFFFF


def f07(d):
    r = lcg(42)
    conf = []
    cols = ["#F19E14", "#E2741B", "#2F7BFF", "#fff", "#7FB2FF"]
    for i in range(36):
        x = int(next(r) * 1000) + 40
        y = int(next(r) * 300) + 640
        c = cols[i % 5]
        w, h = (18, 34) if i % 2 else (24, 24)
        conf.append(f'<div class="§-cf" style="left:540px;top:900px;width:{w}px;height:{h}px;background:{c};border-radius:{"50%" if i % 3 == 0 else "4px"}" data-x="{x}" data-y="{y}"></div>')
    css = """
    .§-cf { position: absolute; }
    .§-bal { position: absolute; width: 170px; height: 170px; }
    .§-flag { position: absolute; top: 560px; left: 0; right: 0; height: 160px; }
    """
    bunt = "".join(
        f'<path d="M{x} 20 l60 0 l-30 70z" fill="{["#E2741B", "#F19E14", "#2F7BFF", "#fff"][i % 4]}"/>' for i, x in enumerate(range(0, 1080, 70))
    )
    balloons = [(80, "#E2741B"), (260, "#2F7BFF"), (760, "#F19E14"), (900, "#E2741B")]
    bal_html = "".join(
        f'<div id="§-b{i}" class="§-bal" style="left:{x}px;top:1150px">{svg(ICON_BALLOON.replace(ORANGE, c), "0 0 120 120", style="width:170px;height:170px")}</div>'
        for i, (x, c) in enumerate(balloons)
    )
    html = f"""
    {service_header(4, "DÉCORATION")}
    <div id="§-flagw" class="§-flag">{svg('<path d="M0 20 Q540 70 1080 20" stroke="#fff" stroke-width="5" fill="none"/>' + bunt, "0 0 1080 160", style="width:1080px;height:160px")}</div>
    {bal_html}
    <div id="§-gift" class="§-abs" style="left:400px;top:780px;width:280px;height:280px">{svg(ICON_GIFT, "0 0 120 120", style="width:280px;height:280px")}</div>
    {"".join(conf)}
    <div class="§-center" style="top:1180px;gap:20px">{chip("c1", "MARIAGES")}{chip("c2", "ANNIVERSAIRES")}</div>
    <div class="§-center" style="top:1310px">{chip("c3", "CÉRÉMONIES")}</div>
    <div class="§-center" style="top:1450px">{chip("c4", "ÉVÉNEMENTS & ESPACES", "hot")}</div>
    """
    cf_js = []
    r2 = lcg(7)
    for i in range(36):
        cf_js.append(i)
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-flagw", {{y: -200, opacity: 0}}, {{y: 0, opacity: 1, duration: .6, ease: "bounce.out"}}, 0.4);
    tl.fromTo("#§-gift", {{scale: 0, rotation: -20}}, {{scale: 1, rotation: 0, duration: .5, ease: "back.out(2.5)"}}, 0.6);
    document.querySelectorAll(".§-cf").forEach(function (el, i) {{
      var x = +el.dataset.x - 540, y = +el.dataset.y - 900;
      tl.fromTo(el, {{x: 0, y: 0, opacity: 0, rotation: 0}}, {{x: x, y: y, opacity: 1, rotation: (i % 2 ? 1 : -1) * (180 + i * 17), duration: .8, ease: "power3.out"}}, 1.05 + (i % 6) * .02);
      tl.to(el, {{y: y + 260 + (i % 5) * 40, rotation: "+=" + (120 + i * 9), duration: {d - 1.9:.2f}, ease: "power1.in"}}, 1.85);
    }});
    tl.fromTo("#§-gift", {{scale: 1}}, {{scale: 1.15, duration: .12, yoyo: true, repeat: 1}}, 1.12);
    tl.fromTo([{", ".join(f'"#§-b{i}"' for i in range(4))}], {{y: 400, opacity: 0}}, {{y: -380, opacity: 1, duration: 3.2, stagger: .25, ease: "power1.out"}}, 1.2);
    {pop("#§-c1", 1.5)}
    {pop("#§-c2", 2.1)}
    {pop("#§-c3", 2.8)}
    {pop("#§-c4", 3.9)}
    """
    return css, html, js


def f08(d):
    css = """
    .§-globe { position: absolute; left: 190px; top: 600px; width: 700px; height: 700px; }
    """
    globe = f"""
<defs><radialGradient id="§-gg" cx=".35" cy=".3" r=".8"><stop offset="0" stop-color="#4C93FF"/><stop offset=".6" stop-color="#1B3F8F"/><stop offset="1" stop-color="#0A1A44"/></radialGradient>
<clipPath id="§-gc"><circle cx="350" cy="350" r="300"/></clipPath></defs>
<circle cx="350" cy="350" r="330" fill="rgba(47,123,255,.18)"/>
<circle cx="350" cy="350" r="300" fill="url(#§-gg)"/>
<g clip-path="url(#§-gc)">
  <g id="§-land" fill="#F19E14" opacity=".9">
    <path d="M300 200 q60-30 100 10 q30 40 10 90 q-10 60 -50 90 q-30 30 -40 -10 q-10-40 -40-60 q-30-30 -10-70 q10-40 30-50z"/>
    <path d="M120 180 q40-20 70 20 q10 40 -20 60 q-40 10 -60-20 q-10-40 10-60z"/>
    <path d="M480 160 q70-10 120 30 q20 50 -30 70 q-60 10 -80-30 q-20-40 -10-70z"/>
    <path d="M520 420 q40-10 60 30 q0 40 -40 40 q-30-10 -20-70z"/>
    <path d="M900 200 q60-30 100 10 q30 40 10 90 q-10 60 -50 90 q-30 30 -40 -10 q-10-40 -40-60 q-30-30 -10-70 q10-40 30-50z"/>
    <path d="M720 180 q40-20 70 20 q10 40 -20 60 q-40 10 -60-20 q-10-40 10-60z"/>
  </g>
  <g stroke="rgba(255,255,255,.18)" stroke-width="3" fill="none"><ellipse cx="350" cy="350" rx="300" ry="100"/><ellipse cx="350" cy="350" rx="300" ry="200"/><ellipse cx="350" cy="350" rx="100" ry="300"/><ellipse cx="350" cy="350" rx="200" ry="300"/><path d="M50 350 H650 M350 50 V650"/></g>
</g>
<circle cx="350" cy="350" r="300" fill="none" stroke="rgba(255,255,255,.5)" stroke-width="4"/>
<path id="§-a1" d="M330 390 Q 200 120 90 150" stroke="#fff" stroke-width="8" fill="none" stroke-dasharray="500" stroke-dashoffset="500" stroke-linecap="round"/>
<path id="§-a2" d="M330 390 Q 520 140 610 220" stroke="#fff" stroke-width="8" fill="none" stroke-dasharray="500" stroke-dashoffset="500" stroke-linecap="round"/>
<path id="§-a3" d="M330 390 Q 470 600 600 520" stroke="#fff" stroke-width="8" fill="none" stroke-dasharray="500" stroke-dashoffset="500" stroke-linecap="round"/>
<circle id="§-ci" cx="330" cy="390" r="20" fill="#E2741B" stroke="#fff" stroke-width="6"/>
<circle id="§-ciring" cx="330" cy="390" r="20" fill="none" stroke="#F19E14" stroke-width="6"/>
"""
    html = f"""
    {service_header(5, "IMPORT-EXPORT")}
    <div id="§-gw" class="§-globe">{svg(globe, "0 0 700 700", style="width:700px;height:700px")}</div>
    <div id="§-plane" class="§-abs" style="left:150px;top:640px;width:130px;height:130px">{svg(ICON_PLANE, "0 0 120 120", style="width:130px;height:130px")}</div>
    <div id="§-ship" class="§-abs" style="left:760px;top:1100px;width:150px;height:150px">{svg(ICON_SHIP, "0 0 120 120", style="width:150px;height:150px")}</div>
    <div id="§-ciL" class="§-abs §-label" style="left:380px;top:1010px;color:#fff;font-size:30px">CÔTE D'IVOIRE</div>
    <div class="§-center" style="top:1330px">{chip("c1", "CÔTE D'IVOIRE → MONDE", "glass", ICON_GLOBE)}</div>
    <div class="§-center" style="top:1460px">{chip("c2", "MONDE → CHEZ VOUS", "hot", ICON_BOX)}</div>
    """
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-gw", {{scale: .5, opacity: 0}}, {{scale: 1, opacity: 1, duration: .7, ease: "back.out(1.5)"}}, 0.3);
    tl.fromTo("#§-land", {{x: -600}}, {{x: 0, duration: {d:.2f}, ease: "none"}}, 0);
    tl.fromTo("#§-ci", {{scale: 0, transformOrigin: "50% 50%"}}, {{scale: 1, duration: .4, ease: "back.out(3)"}}, 0.8);
    tl.fromTo("#§-ciring", {{scale: 1, opacity: 1, transformOrigin: "50% 50%"}}, {{scale: 3, opacity: 0, duration: .9, ease: "power2.out", repeat: 3}}, 0.9);
    tl.fromTo("#§-ciL", {{opacity: 0, y: 20}}, {{opacity: 1, y: 0, duration: .4}}, 1.0);
    tl.fromTo("#§-a1", {{strokeDashoffset: 500}}, {{strokeDashoffset: 0, duration: .8, ease: "power2.out"}}, 1.4);
    tl.fromTo("#§-a2", {{strokeDashoffset: 500}}, {{strokeDashoffset: 0, duration: .8, ease: "power2.out"}}, 1.6);
    tl.fromTo("#§-a3", {{strokeDashoffset: 500}}, {{strokeDashoffset: 0, duration: .8, ease: "power2.out"}}, 1.8);
    tl.fromTo("#§-plane", {{x: 400, y: 260, opacity: 0, scale: .5}}, {{x: 0, y: 0, opacity: 1, scale: 1, duration: 1.2, ease: "power2.out"}}, 1.4);
    tl.fromTo("#§-ship", {{x: -300, opacity: 0}}, {{x: 0, opacity: 1, duration: 1.6, ease: "power2.out"}}, 1.8);
    {pop("#§-c1", 1.9)}
    {pop("#§-c2", 3.9)}
    """
    return css, html, js


def f09(d):
    css = """
    .§-list { position: absolute; left: 110px; top: 610px; width: 860px; padding: 40px 50px; }
    .§-item { display: flex; align-items: center; gap: 34px; height: 118px; font-weight: 800; font-size: 56px; letter-spacing: .04em; }
    .§-box { width: 76px; height: 76px; border-radius: 20px; border: 5px solid rgba(255,255,255,.6); position: relative; flex: none; }
    .§-chk { position: absolute; left: -6px; top: -14px; width: 92px; height: 92px; }
    .§-big { font-family: "Anton"; font-size: 140px; text-transform: uppercase; }
    """
    items = ["RÉCEPTION", "PRÉPARATION", "EXPÉDITION", "SUIVI"]
    rows = "".join(
        f'<div id="§-it{i}" class="§-item"><div class="§-box">{svg("<path d=\"M14 48 l22 22 l44-50\" stroke=\"#F19E14\" stroke-width=\"14\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-dasharray=\"120\" stroke-dashoffset=\"120\"/>", "0 0 92 92", cls="§-chk", ident=f"§-ck{i}")}</div><span>{t}</span></div>'
        for i, t in enumerate(items)
    )
    html = f"""
    {service_header(6, "LOGISTIQUE", "POUR LES ENTREPRISES")}
    <div id="§-list" class="§-list §-glass">{rows}</div>
    <div class="§-center" style="top:1230px"><div id="§-p1" class="§-big §-white3d">VOUS VENDEZ.</div></div>
    <div class="§-center" style="top:1400px"><div id="§-p2" class="§-big §-hot">ON GÈRE LE RESTE.</div></div>
    """
    times = [1.9, 2.45, 3.0, 3.55]
    ck = "\n    ".join(
        f'tl.fromTo("#§-ck{i} path", {{strokeDashoffset: 120}}, {{strokeDashoffset: 0, duration: .3, ease: "power2.out"}}, {t});\n    tl.fromTo("#§-it{i}", {{x: 0}}, {{x: 14, duration: .1, yoyo: true, repeat: 1}}, {t});'
        for i, t in enumerate(times)
    )
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-list", {{y: 100, opacity: 0}}, {{y: 0, opacity: 1, duration: .55, ease: "power3.out"}}, 0.6);
    tl.fromTo(".§-item", {{x: -60, opacity: 0}}, {{x: 0, opacity: 1, duration: .35, stagger: .12, ease: "power3.out"}}, 0.9);
    {ck}
    tl.fromTo("#§-p1", {{y: 80, opacity: 0}}, {{y: 0, opacity: 1, duration: .4, ease: "back.out(2)"}}, 4.6);
    tl.fromTo("#§-p2", {{scale: 2.2, opacity: 0}}, {{scale: 1, opacity: 1, duration: .35, ease: "expo.out"}}, 5.4);
    """
    return css, html, js


def f10(d):
    css = """
    .§-med { width: 280px; height: 280px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .§-med svg { width: 190px; height: 190px; }
    .§-stampb { position: absolute; left: 250px; top: 1220px; width: 580px; padding: 34px 20px; text-align: center; border-radius: 30px; font-family: "Anton"; font-size: 84px; color: #fff; background: linear-gradient(135deg,#F19E14,#E2741B 60%,#C9520F); border: 6px solid #fff; box-shadow: 0 20px 60px rgba(226,116,27,.5); }
    """
    html = f"""
    {service_header(7, "ÉLEVAGE")}
    <div class="§-center" style="top:640px;gap:30px">
      <div id="§-m1" class="§-med §-glass">{svg(ICON_HEN, "0 0 120 120")}</div>
      <div id="§-m2" class="§-med §-glass">{svg(ICON_RABBIT, "0 0 120 120")}</div>
      <div id="§-m3" class="§-med §-glass">{svg(ICON_QUAIL, "0 0 120 120")}</div>
    </div>
    <div class="§-center" style="top:950px;gap:30px">
      <div class="§-label" style="width:280px;text-align:center;color:#fff">POULETS</div><div class="§-label" style="width:280px;text-align:center;color:#fff">LAPINS</div><div class="§-label" style="width:280px;text-align:center;color:#fff">CAILLES</div>
    </div>
    <div id="§-stamp" class="§-stampb">FRAIS & DE QUALITÉ</div>
    """
    js = f"""
    {service_header_js()}
    tl.fromTo("#§-m1", {{y: 200, scale: .3, opacity: 0}}, {{y: 0, scale: 1, opacity: 1, duration: .5, ease: "back.out(2.2)"}}, 0.9);
    tl.fromTo("#§-m2", {{y: 200, scale: .3, opacity: 0}}, {{y: 0, scale: 1, opacity: 1, duration: .5, ease: "back.out(2.2)"}}, 1.35);
    tl.fromTo("#§-m3", {{y: 200, scale: .3, opacity: 0}}, {{y: 0, scale: 1, opacity: 1, duration: .5, ease: "back.out(2.2)"}}, 1.8);
    tl.fromTo(["#§-m1 svg", "#§-m2 svg", "#§-m3 svg"], {{rotation: -6}}, {{rotation: 6, duration: .4, yoyo: true, repeat: 4, stagger: .15, ease: "sine.inOut"}}, 2.3);
    tl.fromTo("#§-stamp", {{scale: 2.5, rotation: -14, opacity: 0}}, {{scale: 1, rotation: -4, opacity: 1, duration: .35, ease: "expo.out"}}, 2.75);
    tl.to("#§-cam", {{keyframes: {{x: [0, -10, 8, -4, 0]}}, duration: .25, ease: "none"}}, 3.05);
    """
    return css, html, js


def f11(d):
    css = """
    .§-hub { position: absolute; left: 340px; top: 470px; width: 400px; height: 400px; border-radius: 50%; background: #fff; overflow: hidden; box-shadow: 0 0 0 10px rgba(241,158,20,.8), 0 0 140px rgba(241,158,20,.6); }
    .§-hub img { width: 140%; height: 140%; margin: -20% 0 0 -20%; object-fit: cover; }
    .§-node { position: absolute; width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .§-node svg { width: 100px; height: 100px; }
    .§-word { font-family: "Anton"; font-size: 96px; text-transform: uppercase; }
    """
    import math
    icons = [ICON_CAR, ICON_BOX, ICON_BALLOON, ICON_GLOBE, ICON_PALLET, ICON_HEN, ICON_SHIELD]
    vbs = ["0 0 140 110"] + ["0 0 120 120"] * 6
    nodes = ""
    js_nodes = []
    for i, (ic, vb) in enumerate(zip(icons, vbs)):
        a = -math.pi / 2 + i * 2 * math.pi / 7
        x = 540 + 360 * math.cos(a) - 75
        y = 670 + 360 * math.sin(a) - 75
        nodes += f'<div id="§-n{i}" class="§-node §-glass" style="left:{x:.0f}px;top:{y:.0f}px">{svg(ic, vb)}</div>'
        js_nodes.append(f'tl.fromTo("#§-n{i}", {{x: {540 - 75 - x:.0f}, y: {670 - 75 - y:.0f}, scale: 0, opacity: 0}}, {{x: 0, y: 0, scale: 1, opacity: 1, duration: .55, ease: "back.out(1.8)"}}, {0.35 + i * 0.08:.2f});')
    html = f"""
    <div id="§-orbit" class="§-abs" style="left:0;top:0;width:1080px;height:1340px;transform-origin:540px 670px">{nodes}</div>
    <div id="§-hub" class="§-hub"><img src="assets/logo-dtl.jpg" alt="DTL"/></div>
    <div class="§-center" style="top:1120px;flex-direction:column;align-items:center;gap:6px">
      <div id="§-w1" class="§-word §-white3d">FIABILITÉ</div>
      <div id="§-w2" class="§-word §-white3d">RAPIDITÉ</div>
      <div id="§-w3" class="§-word §-white3d" style="font-size:84px">PROFESSIONNALISME</div>
    </div>
    <div id="§-go" class="§-abs §-anton §-hot" style="left:0;right:0;top:300px;text-align:center;font-size:150px">ÇA BOUGE !</div>
    """
    js = f"""
    tl.fromTo("#§-hub", {{scale: 0, opacity: 0}}, {{scale: 1, opacity: 1, duration: .5, ease: "back.out(2)"}}, 0.05);
    {chr(10).join("    " + j for j in js_nodes)}
    tl.fromTo("#§-orbit", {{rotation: 0}}, {{rotation: 40, duration: {d:.2f}, ease: "none"}}, 0);
    {"".join(f'tl.fromTo("#§-n{i} svg", {{rotation: 0}}, {{rotation: -40, duration: {d:.2f}, ease: "none"}}, 0);' for i in range(7))}
    tl.fromTo("#§-w1", {{x: -300, opacity: 0}}, {{x: 0, opacity: 1, duration: .35, ease: "back.out(2)"}}, 0.2);
    tl.fromTo("#§-w2", {{x: 300, opacity: 0}}, {{x: 0, opacity: 1, duration: .35, ease: "back.out(2)"}}, 0.85);
    tl.fromTo("#§-w3", {{x: -300, opacity: 0}}, {{x: 0, opacity: 1, duration: .35, ease: "back.out(2)"}}, 1.5);
    tl.fromTo("#§-go", {{scale: 2.6, opacity: 0, rotation: -8}}, {{scale: 1, opacity: 1, rotation: -3, duration: .35, ease: "expo.out"}}, 3.0);
    tl.to("#§-cam", {{keyframes: {{x: [0, -16, 12, -6, 0], y: [0, 8, -6, 3, 0]}}, duration: .3, ease: "none"}}, 3.3);
    """
    return css, html, js


def f12(d):
    css = """
    .§-phone { position: absolute; left: 190px; top: 400px; width: 700px; height: 1180px; border-radius: 80px; padding: 50px 44px; }
    .§-notch { position: absolute; left: 260px; top: 22px; width: 180px; height: 30px; border-radius: 15px; background: rgba(0,0,0,.45); }
    .§-avatar { width: 200px; height: 200px; border-radius: 50%; background: #fff; overflow: hidden; margin: 40px auto 0; box-shadow: 0 0 0 6px #F19E14; }
    .§-avatar img { width: 150%; height: 150%; margin: -25% 0 0 -25%; object-fit: cover; }
    .§-name { text-align: center; font-family: "Anton"; font-size: 64px; margin-top: 24px; }
    .§-numf { margin: 34px auto 0; width: 600px; height: 120px; border-radius: 30px; background: rgba(255,255,255,.95); color: #0A1A44; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 54px; letter-spacing: .02em; white-space: nowrap; overflow: hidden; }
    .§-btn { position: absolute; left: 44px; right: 44px; height: 150px; border-radius: 75px; display: flex; align-items: center; justify-content: center; gap: 24px; font-weight: 900; font-size: 56px; letter-spacing: .06em; }
    .§-btn svg { width: 84px; height: 84px; }
    .§-call { top: 760px; background: linear-gradient(135deg,#F19E14,#E2741B 60%,#C9520F); box-shadow: 0 20px 50px rgba(226,116,27,.55); }
    .§-wa { top: 950px; background: #25D366; box-shadow: 0 20px 50px rgba(37,211,102,.45); }
    .§-ripple { position: absolute; left: 0; top: 0; width: 160px; height: 160px; border-radius: 50%; border: 8px solid #fff; }
    """
    number = "+225 07 79 84 22 76"
    digits = "".join(f'<span class="§-dg">{c if c != " " else "&nbsp;"}</span>' for c in number)
    html = f"""
    <div id="§-h1" class="§-center" style="top:150px"><div class="§-anton §-white3d" style="font-size:130px">APPELEZ-NOUS</div></div>
    <div id="§-h2" class="§-center" style="top:290px"><div class="§-label" style="color:#F19E14;font-size:42px">OU WHATSAPP · MAINTENANT</div></div>
    <div id="§-ph" class="§-phone §-glass">
      <div class="§-notch"></div>
      <div class="§-avatar"><img src="assets/logo-dtl.jpg" alt="DTL"/></div>
      <div class="§-name">DIBI TRANSPORT</div>
      <div id="§-numf" class="§-numf">{digits}<span id="§-caret" style="display:inline-block;width:5px;height:60px;background:#E2741B;margin-left:6px"></span></div>
      <div id="§-call" class="§-btn §-call">{svg(ICON_PHONE, "0 0 120 120")}<span>APPELER</span></div>
      <div id="§-wa" class="§-btn §-wa">{svg(ICON_WHATSAPP, "0 0 120 120")}<span>WHATSAPP</span></div>
    </div>
    <div id="§-rip" class="§-ripple"></div>
    <div id="§-cursor" class="§-abs" style="left:0;top:0;width:110px;height:110px">{svg('<path d="M10 6 L80 44 L46 52 L34 86 Z" fill="#2F7BFF" stroke="#fff" stroke-width="6" stroke-linejoin="round"/>', "0 0 90 90", style="width:110px;height:110px")}</div>
    """
    n = len(number)
    t0, t1 = 3.3, 8.2
    js = f"""
    tl.fromTo("#§-h1", {{scale: 2, opacity: 0}}, {{scale: 1, opacity: 1, duration: .4, ease: "expo.out"}}, 0.05);
    tl.fromTo("#§-h2", {{y: 30, opacity: 0}}, {{y: 0, opacity: 1, duration: .4, ease: "power3.out"}}, 1.3);
    tl.fromTo("#§-ph", {{y: 400, opacity: 0, rotation: 6}}, {{y: 0, opacity: 1, rotation: 0, duration: .7, ease: "back.out(1.4)"}}, 0.3);
    tl.fromTo("#§-call", {{scale: .5, opacity: 0}}, {{scale: 1, opacity: 1, duration: .45, ease: "back.out(2.2)"}}, 0.9);
    tl.fromTo("#§-wa", {{scale: .5, opacity: 0}}, {{scale: 1, opacity: 1, duration: .45, ease: "back.out(2.2)"}}, 1.6);
    tl.fromTo("#§-wa", {{scale: 1}}, {{scale: 1.06, duration: .3, yoyo: true, repeat: 3, ease: "sine.inOut"}}, 2.1);
    tl.fromTo(".§-dg", {{opacity: 0, y: 20}}, {{opacity: 1, y: 0, duration: .12, stagger: {(t1 - t0) / n:.3f}, ease: "power2.out"}}, {t0});
    tl.fromTo("#§-caret", {{opacity: 1}}, {{opacity: 0, duration: .25, yoyo: true, repeat: {int((d - 0.2) / 0.25)}, ease: "steps(1)"}}, 0.2);
    tl.fromTo("#§-numf", {{scale: 1}}, {{scale: 1.08, duration: .2, yoyo: true, repeat: 1}}, {t1 + 0.05});
    tl.fromTo("#§-cursor", {{x: 1150, y: 1750, opacity: 0}}, {{x: 620, y: 1260, opacity: 1, duration: .8, ease: "power3.out"}}, 7.4);
    tl.to("#§-cursor", {{scale: .82, duration: .1, yoyo: true, repeat: 1}}, 8.3);
    tl.to("#§-call", {{scale: .94, duration: .1, yoyo: true, repeat: 1}}, 8.3);
    tl.fromTo("#§-rip", {{x: 560, y: 1160, scale: .2, opacity: 1}}, {{scale: 2.4, opacity: 0, duration: .7, ease: "power2.out"}}, 8.35);
    """
    return css, html, js


def f13(d):
    css = """
    .§-plate { position: absolute; left: 190px; top: 380px; width: 700px; height: 700px; border-radius: 60px; background: #fff; overflow: hidden; box-shadow: 0 0 0 10px rgba(241,158,20,.85), 0 0 140px rgba(241,158,20,.55), 0 40px 100px rgba(0,0,0,.5); }
    .§-plate img { width: 100%; height: 100%; object-fit: cover; }
    """
    html = f"""
    <div id="§-plate" class="§-plate"><img src="assets/logo-dtl.jpg" alt="DTL"/></div>
    <div class="§-center" style="top:1140px;flex-direction:column;align-items:center;gap:34px">
      <div id="§-s1" class="§-anton §-white3d" style="font-size:118px">ON VOUS TRANSPORTE</div>
      <div id="§-s2" class="§-anton §-hot" style="font-size:150px">PLUS LOIN !</div>
    </div>
    <div id="§-tel" class="§-center" style="top:180px">{chip("telc", "+225 07 79 84 22 76", "hot", ICON_PHONE)}</div>
    <div id="§-ar" class="§-streak" style="left:-900px;top:1500px;width:900px;height:22px;border-radius:11px"></div>
    """
    js = f"""
    tl.fromTo("#§-plate", {{scale: .4, opacity: 0, rotation: 8}}, {{scale: 1, opacity: 1, rotation: 0, duration: .6, ease: "back.out(1.7)"}}, 0.05);
    tl.fromTo("#§-tel", {{y: -80, opacity: 0}}, {{y: 0, opacity: 1, duration: .45, ease: "back.out(2)"}}, 0.6);
    tl.fromTo("#§-s1", {{y: 60, opacity: 0}}, {{y: 0, opacity: 1, duration: .4, ease: "power3.out"}}, 2.2);
    tl.fromTo("#§-s2", {{scale: 2.3, opacity: 0}}, {{scale: 1, opacity: 1, duration: .35, ease: "expo.out"}}, 2.8);
    tl.fromTo("#§-ar", {{x: 0}}, {{x: 2200, duration: .8, ease: "power2.in"}}, 3.0);
    tl.fromTo("#§-plate", {{y: 0}}, {{y: -20, duration: {d - 0.7:.2f}, ease: "sine.inOut"}}, 0.65);
    """
    return css, html, js


def fix_repeated_fromto(doc):
    """Later fromTo calls on an already-tweened target must not render their from-state early."""
    seen, out = set(), []
    for line in doc.split("\n"):
        m = re.search(r'tl\.fromTo\(("[^"]+"|\[[^\]]+\])', line)
        if m and line.count("tl.fromTo(") == 1:
            key = m.group(1)
            if key in seen:
                i = line.index("}, {", m.end())
                line = line[:i] + "}, {immediateRender: false, " + line[i + 4:]
            seen.add(key)
        out.append(line)
    return "\n".join(out)


BUILDERS = {1: f01, 2: f02, 3: f03, 4: f04, 5: f05, 6: f06, 7: f07, 8: f08, 9: f09, 10: f10, 11: f11, 12: f12, 13: f13}


def build():
    durs = durations()
    for n, fn in BUILDERS.items():
        d, src = durs[n]
        fid = os.path.splitext(os.path.basename(src))[0]
        css, html, js = fn(d)
        doc = f"""<template>
  <script src="assets/vendor/gsap.min.js"></script>
  <style>{FONTS}{SHARED_CSS}{css}
  </style>
  <div id="root" data-composition-id="{fid}" data-width="{W}" data-height="{H}" data-duration="{d}">
  {ground(d)}
  <div id="§-content" class="clip §-layer" data-start="0" data-duration="{d}" data-track-index="1">
    <div id="§-cam" class="§-cam">
    {html}
    </div>
  </div>
  </div>
  <script>
  (function () {{
    var tl = gsap.timeline({{ paused: true }});
    {ground_js(d)}
    {js}
    window.__timelines["{fid}"] = tl;
  }})();
  </script>
</template>
"""
        doc = fix_repeated_fromto(doc).replace("§", f"f{n:02d}")
        path = os.path.join(ROOT, src)
        os.makedirs(os.path.dirname(path), exist_ok=True)
        open(path, "w", encoding="utf-8").write(doc)
        print("wrote", src, d)


if __name__ == "__main__":
    build()
