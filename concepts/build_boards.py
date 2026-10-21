"""Builds the creative-direction presentation boards (HTML) from the data
below. Render them to PNG with render.sh. Stdlib only."""
import pathlib

OUT = pathlib.Path(__file__).parent / "src"

DIRS = {
  "a": dict(
    name="Open Membership", tag="Recommended",
    short="A members' club where everyone is a member. Light, quiet and confident, with one red accent reserved for booking.",
    font="Instrument Sans", gf="Instrument+Sans:wdth,wght@75..100,400..700",
    line="The clubhouse, minus the rules.",
    concept=("Every private club has a rulebook. Ours has one rule: have a good time. Open Membership "
             "borrows the calm confidence of a members' club (generous space, quiet type, real photography) "
             "and drops the gatekeeping. No membership, no dress code, no handicap required. The restraint "
             "is the attitude: nothing on the page is shouting, so the one red button does."),
    voice=["The clubhouse, minus the rules.", "No membership. No dress code. No handicap required.",
           "Four bays. Two bars. Stay a while.", "Take over the upstairs."],
    bg="#F3EFE7", fg="#22231F", mute="#6E6A62",
    palette=[("Linen", "#F3EFE7", "Page base · 70%"), ("Chalk", "#FBF9F5", "Surfaces, review card"),
             ("Charcoal", "#22231F", "Type, footer · 15%"), ("Pine", "#33493C", "Private events only"),
             ("Stone", "#B9B2A5", "Hairlines, quiet text"), ("Flag red", "#C4462B", "Booking actions only · <3%")],
    why=("Every golf venue defaults to green on green. Here green is a supporting note (the private events "
         "room) and the only saturated colour is the red of a pin flag, the one thing every golfer aims at. "
         "It makes Book a bay unmissable without neon, and keeps the page light and breathable."),
    alts=[("Flag red", "#C4462B", "Recommended. Warmest, most decisive call to action."),
          ("Clubhouse ochre", "#A97C2F", "Softer, more hospitality, less urgency."),
          ("Range blue", "#2F5D7C", "Cooler, more apparel-brand, calmer.")],
    type_notes=("One variable family at three weights (400 / 500 / 600). Headlines are medium weight, sentence case, "
                "with tight tracking. No uppercase except 11px photo labels. It is free for commercial use (OFL) "
                "and self-hosts on WordPress. If you'd rather license a premium face, Neue Montreal or Suisse "
                "Int'l drop into the same system without changing the layout."),
    spec=[("Display / H1", "84px desktop · 46px mobile · 500 · −4.5%", "The clubhouse, minus the rules.", "font-size:76px;font-weight:500;letter-spacing:-.045em;line-height:1;font-stretch:92%"),
          ("H2", "56 / 36px · 500 · −4%", "Take over the upstairs.", "font-size:52px;font-weight:500;letter-spacing:-.04em;line-height:1.05;font-stretch:92%"),
          ("Navigation", "15px · 500", "Golf&nbsp;&nbsp;&nbsp; Karaoke&nbsp;&nbsp;&nbsp; Private events&nbsp;&nbsp;&nbsp; Leagues&nbsp;&nbsp;&nbsp; Lessons&nbsp;&nbsp;&nbsp; Shop", "font-size:15px;font-weight:500"),
          ("Body", "18 / 17px · 400 · 1.55", "Four Golfzon simulators, two full bars, karaoke and private events. Bring friends, coworkers, or the one person who has never held a club.", "font-size:18px;line-height:1.55;max-width:56ch;color:#3A3B36"),
          ("Label", "13–14px · 500 · muted", "Indoor golf &amp; bar · Newmarket, Ontario", "font-size:14px;font-weight:500;color:#6E6A62")],
    photo=("Warm, natural and unposed. Shot at eye level on a 35–50mm lens, with the venue's own lights, shallow depth "
           "and people mid-moment rather than posing. No flash, no wide-angle distortion, no heavy filters. "
           "Graded slightly warm with true blacks. Photography carries the story, so no graphic decoration "
           "competes with it."),
    desktop="a-desktop-full.png", mobile="a-mobile.png",
    pros=["Light and breathable, as briefed", "One typeface, one accent, booking unmissable",
          "Lets the heavy condensed logo be the only loud type on the page", "Most app-like on mobile"],
    cons=["Depends on strong photography (as all three do)"],
  ),
  "b": dict(
    name="After Hours", tag="Alternative",
    short="The club at 10 pm. Cinematic, low-light and amber, with golf as the excuse and the night as the product.",
    font="Inter Tight", gf="Inter+Tight:wght@300..600",
    line="Golf until last call.",
    concept=("The Bogey Club at 10 pm. A cinematic, low-light brand where golf is the excuse and the night "
             "is the product: amber tungsten light, full-bleed scenes, very light type at very large sizes. "
             "Closest to a nightlife or boutique bar brand."),
    voice=["Golf until last call.", "The bar's loudest corner.", "The whole upstairs, bar included."],
    bg="#121411", fg="#ECE6DA", mute="#8D8A83",
    palette=[("Ink", "#121411", "Page base"), ("Smoke", "#1C1F1B", "Surfaces"), ("Bone", "#ECE6DA", "Type"),
             ("Ash", "#8D8A83", "Secondary text"), ("Pine night", "#1E2B24", "Events section"), ("Amber", "#E3A857", "Booking, highlights")],
    why=("Amber is the colour of a draft beer under tungsten light. On near-black it reads as warm and "
         "expensive rather than sporty. It is the most atmospheric option."),
    alts=[], type_notes=("Inter Tight at 300 / 400 / 500. Very light display weight, close to Helvetica Now Display. "
                         "Free (OFL)."),
    spec=[("Display / H1", "136px desktop · 60px mobile · 300 · −5.5%", "Golf until last call.", "font-size:84px;font-weight:300;letter-spacing:-.055em;line-height:.95"),
          ("Navigation", "15px · 400", "Golf&nbsp;&nbsp;&nbsp; Karaoke&nbsp;&nbsp;&nbsp; Private events&nbsp;&nbsp;&nbsp; Menu", "font-size:15px;color:#CFC9BD"),
          ("Body", "17px · 300", "Four Golfzon simulators, two full bars, karaoke after dark and a private room upstairs.", "font-size:18px;font-weight:300;color:#CFC9BD;max-width:56ch")],
    photo="Night only: tungsten, slow shutter, visible grain, deep shadows, glowing screens and taps.",
    desktop="b-desktop-hero.png", mobile="b-mobile.png",
    pros=["Most desirable for bar, karaoke and events", "Very strong first impression"],
    cons=["Dark everywhere, which is the opposite of light and breathable", "Needs exceptional night photography to work at all",
          "Undersells daytime golf, lessons and leagues", "Dark interfaces are harder to read for some guests"],
  ),
  "c": dict(
    name="The Clubhouse Journal", tag="Alternative",
    short="The club as a printed journal: one serif family, captions and a contents list, closer to an apparel lookbook.",
    font="Newsreader", gf="Newsreader:ital,opsz,wght@0,6..72,300..600;1,6..72,300..500",
    line="Golf for the rest of us.",
    concept=("The club as a beautifully printed journal: one serif family, figure captions, a numbered contents "
             "list, generous margins, closer to a golf apparel lookbook than a venue website."),
    voice=["Golf for the rest of us.", "Fig. 1: The clubhouse bar, a Thursday, 9:40 pm."],
    bg="#F6F2EA", fg="#1C1B18", mute="#77736A",
    palette=[("Paper", "#F6F2EA", "Page base"), ("Ink", "#1C1B18", "Type, primary button"), ("Moss", "#4F5B3A", "Labels"),
             ("Sky", "#9DBACB", "Secondary booking"), ("Grey", "#77736A", "Captions")],
    why=("Paper, ink and a pale Sunday-sky blue: closer to golf apparel than to a sports venue. It is distinctive but "
         "quieter, and the least nightlife-like of the three."),
    alts=[], type_notes="Newsreader (optical sizes 6–72), one serif family with small-caps labels. Free (OFL).",
    spec=[("Display / H1", "124px desktop · 58px mobile · 300", "Golf for the rest of us.", "font-size:80px;font-weight:300;letter-spacing:-.035em;line-height:.98"),
          ("Navigation", "17px · 400", "Golf&nbsp;&nbsp;&nbsp; Karaoke&nbsp;&nbsp;&nbsp; Private events&nbsp;&nbsp;&nbsp; Leagues", "font-size:17px"),
          ("Body", "19px · 400", "Four Golfzon simulators, two full bars, karaoke and a private room upstairs.", "font-size:19px;max-width:56ch")],
    photo="Portraits of regulars and staff in natural window light, with captions that tell small true stories.",
    desktop="c-desktop-hero.png", mobile="c-mobile.png",
    pros=["Most literary and unusual for the category", "Very calm and premium"],
    cons=["Reads more heritage than slightly rebellious", "A serif system competes with your heavy sans logo",
          "Booking feels less immediate on mobile"],
  ),
}

STRUCTURE = [
  ("01", "Hero", "One photograph. One headline. One sentence. Book a bay, plus Book karaoke and Plan an event as quiet secondary links."),
  ("02", "What's on", "Golf, Karaoke and Private events as a typographic index. On desktop the photo follows the line you point at; on mobile each is a full-width photo you tap."),
  ("03", "The venue", "Two bars, four bays and cold beer on tap told in two photographs and three facts. No cards."),
  ("04", "Private events", "The only colour-blocked section: one large image, three sentences and Start an event inquiry, which opens the existing form."),
  ("05", "Good to know", "A compact list: price, how to book, hours (golf and bar labelled separately), address and one verified review."),
  ("06", "Footer", "Newsletter, plus every secondary link: lessons, leagues, Classic, shop, about, FAQs, contact, legal, social."),
]

BASE_CSS = """
*{box-sizing:border-box;margin:0;padding:0}
body{width:1600px;background:#FFFFFF;color:#1d1d1b;font-family:'Instrument Sans',system-ui,sans-serif;-webkit-font-smoothing:antialiased}
.pad{padding:72px 80px}
.k{font-size:13px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#7a766d}
h1.t{font-size:64px;font-weight:500;letter-spacing:-.04em;line-height:1}
h2.t{font-size:34px;font-weight:500;letter-spacing:-.03em;margin-bottom:20px}
p.c{font-size:19px;line-height:1.55;color:#3b3a36;max-width:70ch}
.rule{border-top:1px solid #e3e0d9}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:64px}
.sw{display:grid;gap:10px}
.sw div{display:grid;grid-template-columns:72px 1fr;gap:16px;align-items:center}
.sw i{display:block;height:56px;border-radius:4px;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08)}
.sw b{font-weight:600;font-size:16px}.sw small{display:block;color:#7a766d;font-size:14px}
.shot{box-shadow:0 1px 0 rgba(0,0,0,.06),0 20px 60px rgba(0,0,0,.12);display:block}
.phone{width:390px;border-radius:44px;overflow:hidden;box-shadow:0 0 0 10px #1d1d1b,0 30px 80px rgba(0,0,0,.25)}
.phone img{width:390px;display:block}
.pill{display:inline-block;font-size:13px;font-weight:600;padding:6px 12px;border-radius:99px;background:#1d1d1b;color:#fff;margin-left:14px;vertical-align:middle;letter-spacing:0}
ul.l{list-style:none;display:grid;gap:8px;font-size:17px;color:#3b3a36}
ul.l li::before{content:'— ';color:#a9a49a}
"""

def head(title, gf):
    return f"""<!doctype html><html lang="en"><head><meta charset="utf-8"><title>{title}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wdth,wght@75..100,400..700&family={gf}&display=swap">
<style>{BASE_CSS}</style></head><body>"""

def board(key):
    d = DIRS[key]
    sw = "".join(f'<div><i style="background:{h}"></i><span><b>{n}</b> <small>{h} · {u}</small></span></div>' for n, h, u in d["palette"])
    spec = "".join(f'<div style="display:grid;grid-template-columns:220px 1fr;gap:32px;padding:26px 0;border-top:1px solid rgba(128,128,128,.25)">'
                   f'<div><div class="k" style="color:{d["mute"]}">{lab}</div><div style="font-size:13px;color:{d["mute"]};margin-top:6px;font-family:Instrument Sans">{meta}</div></div>'
                   f'<div style="font-family:\'{d["font"]}\';color:{d["fg"]};{css}">{txt}</div></div>' for lab, meta, txt, css in d["spec"])
    alts = ""
    if d["alts"]:
        alts = '<h2 class="t" style="margin-top:56px">Accent alternatives</h2><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">' + "".join(
            f'<div style="background:#F3EFE7;padding:28px;border-radius:6px"><div style="font-size:30px;font-weight:500;letter-spacing:-.04em;line-height:1.05">Four bays.<br>Two bars.</div>'
            f'<span style="display:inline-block;margin-top:20px;background:{h};color:#fff;border-radius:99px;padding:12px 22px;font-weight:600;font-size:15px">Book a bay</span>'
            f'<p style="margin-top:16px;font-size:15px;color:#6E6A62"><b style="color:#22231F">{n}</b> {h}<br>{t}</p></div>' for n, h, t in d["alts"]) + "</div>"
    # palette in context: a mini page strip using the real proportions
    pal = d["palette"]
    context = (f'<div style="background:{d["bg"]};color:{d["fg"]};padding:40px;border-radius:6px;font-family:\'{d["font"]}\'">'
               f'<div style="font-size:14px;color:{d["mute"]}">Indoor golf &amp; bar · Newmarket</div>'
               f'<div style="font-size:44px;font-weight:{300 if key != "a" else 500};letter-spacing:-.04em;line-height:1;margin-top:12px">{d["line"]}</div>'
               f'<div style="display:flex;gap:14px;margin-top:26px;align-items:center"><span style="background:{pal[-1][1]};color:{d["bg"] if key=="b" else "#fff"};padding:13px 22px;border-radius:{99 if key=="a" else 2}px;font-weight:600;font-size:15px">Book a bay</span>'
               f'<span style="border-bottom:1px solid;font-weight:600;font-size:15px">Book karaoke</span></div></div>')
    voice = "".join(f'<li>{v}</li>' for v in d["voice"])
    pros = "".join(f"<li>{p}</li>" for p in d["pros"]); cons = "".join(f"<li>{c}</li>" for c in d["cons"])
    is_full = key == "a"
    desk_w = 1440
    extra = " + what's on" if is_full else ""
    html = head(f"Direction {key.upper()} — {d['name']}", d["gf"]) + f"""
<section class="pad">
  <div class="k">Direction {key.upper()}</div>
  <h1 class="t" style="margin-top:14px">{d['name']}<span class="pill" style="{'' if key=='a' else 'background:#e3e0d9;color:#1d1d1b'}">{d['tag']}</span></h1>
  <div class="grid2" style="margin-top:36px"><p class="c">{d['concept']}</p><div><div class="k" style="margin-bottom:14px">Voice</div><ul class="l">{voice}</ul></div></div>
</section>
<section class="pad rule"><div class="k" style="margin-bottom:24px">Desktop hero{extra} · 1440px · photo areas are briefs, not finished images</div>
  <img class="shot" src="../renders/{d['desktop']}" style="width:{desk_w}px"></section>
<section class="pad rule" style="display:grid;grid-template-columns:430px 1fr;gap:80px;align-items:start">
  <div><div class="k" style="margin-bottom:24px">Mobile homepage · 390px</div><div class="phone"><img src="../renders/{d['mobile']}"></div></div>
  <div>
    <h2 class="t">Typography: {d['font']}</h2><p class="c" style="font-size:17px;margin-bottom:24px">{d['type_notes']}</p>
    <div style="background:{d['bg']};padding:8px 36px 12px;border-radius:6px">{spec}</div>
    <h2 class="t" style="margin-top:56px">Colour</h2>
    <div class="grid2" style="gap:40px"><div class="sw">{sw}</div><div>{context}<p class="c" style="font-size:16px;margin-top:20px">{d['why']}</p></div></div>
    {alts}
    <h2 class="t" style="margin-top:56px">Photography</h2><p class="c" style="font-size:17px">{d['photo']}</p>
    <div class="grid2" style="margin-top:48px;gap:40px"><div><div class="k" style="margin-bottom:12px">Strengths</div><ul class="l">{pros}</ul></div><div><div class="k" style="margin-bottom:12px">Trade-offs</div><ul class="l">{cons}</ul></div></div>
  </div>
</section>
</body></html>"""
    (OUT / f"board-{key}.html").write_text(html)

def overview():
    cols = ""
    for k, d in DIRS.items():
        cols += (f'<div><div class="k">Direction {k.upper()}{" · Recommended" if k=="a" else ""}</div>'
                 f'<div style="font-size:30px;font-weight:500;letter-spacing:-.03em;margin:8px 0 18px">{d["name"]}</div>'
                 f'<img class="shot" src="../renders/{d["desktop"].replace("-full","-hero")}" style="width:100%;">'
                 f'<p class="c" style="font-size:16px;margin-top:20px">{d["short"]}</p>'
                 f'<div style="display:flex;gap:6px;margin-top:18px">' + "".join(f'<i style="width:36px;height:36px;border-radius:50%;background:{h};box-shadow:inset 0 0 0 1px rgba(0,0,0,.1)"></i>' for _, h, _ in d["palette"]) +
                 f'</div><p style="margin-top:12px;font-size:15px;color:#7a766d">{d["font"]} · “{d["line"]}”</p></div>')
    steps = "".join(f'<div style="display:grid;grid-template-columns:60px 200px 1fr;gap:20px;padding:22px 0;border-top:1px solid #e3e0d9;font-size:18px">'
                    f'<span style="color:#C4462B;font-weight:600">{n}</span><b style="font-weight:600">{t}</b><span style="color:#3b3a36">{x}</span></div>' for n, t, x in STRUCTURE)
    html = head("The Bogey Club — Creative directions", "Inter+Tight:wght@300..600") + f"""
<section class="pad"><div class="k">The Bogey Club · Homepage creative reset · Round 2</div>
<h1 class="t" style="margin-top:14px;max-width:18ch">Three directions. One recommendation.</h1>
<p class="c" style="margin-top:24px">Each direction has its own idea, typeface, palette, photography and layout. None of them reuse anything from round one. Photo areas are shot briefs: no venue photography is invented.</p></section>
<section class="pad rule" style="display:grid;grid-template-columns:repeat(3,1fr);gap:40px">{cols}</section>
<section class="pad rule grid2">
  <div><div class="k">Recommendation</div><h2 class="t" style="margin-top:12px;font-size:44px">Direction A, Open Membership.</h2>
  <p class="c">It is the only direction that is light and breathable, distinctive, and fastest to book on a phone. Your logo already
  carries the loud, heavy condensed type. A quiet grotesk with one red accent lets the logo be the only loud thing on the page,
  instead of fighting it the way round one did. The idea (a members' club where everyone is a member) gives every section the
  same attitude without a single golf pun or graphic gimmick.</p>
  <p class="c" style="margin-top:16px">B is the strongest mood but the wrong default for a venue that also sells daytime golf, lessons and leagues.
  Its night photography could become A's events and karaoke imagery. C is beautiful but more heritage than rebellious.</p></div>
  <div><div class="k">Simplified homepage (Direction A): six sections</div><div style="margin-top:12px">{steps}</div>
  <p class="c" style="font-size:16px;margin-top:18px">Mobile: Book a bay is the first button. Once you scroll, it lives in a floating dock with Karaoke, so both bookings are always one tap away. Plan an event sits in the hero and in its own section.</p></div>
</section></body></html>"""
    (OUT / "board-0-overview.html").write_text(html)

if __name__ == "__main__":
    overview()
    for k in DIRS: board(k)
    print("boards written")
