---
version: 1
name: DTL Motion — frame (9:16 TikTok)
description: >
  Bespoke spec for DIBI TRANSPORT & LOGISTIQUE, built from the user's two TikTok references
  (glossy saturated gradients, glassmorphism cards, chunky kinetic type, UI chat bubbles + cursor)
  and the DTL logo palette (navy + orange). Replaces the creative-mode preset, whose "flat, no
  gradient" law contradicted the user's references.
unit: the frame — 1080×1920 portrait
principle: brand colors are sacred · one hero per frame · motion carries speed

colors:
  canvas: "#0A1A44"        # deep navy ground (base of every gradient)
  navy: "#112557"          # logo navy
  navy-2: "#1B3F8F"        # gradient mid / glass tint
  blue-glow: "#2F7BFF"     # electric highlight in gradients
  orange: "#E2741B"        # logo orange (primary accent)
  amber: "#F19E14"         # logo amber (gradient partner of orange)
  white: "#FFFFFF"
  ink: "#0A1A44"
  muted: "#B9C6E8"
  whatsapp: "#25D366"
  glass-fill: "rgba(255,255,255,0.10)"
  glass-border: "rgba(255,255,255,0.28)"

gradients:
  ground: "radial-gradient(120% 80% at 50% 20%, #1B3F8F 0%, #0A1A44 60%, #060F2B 100%)"
  ground-hot: "radial-gradient(100% 70% at 50% 30%, #2F7BFF 0%, #112557 55%, #060F2B 100%)"
  accent: "linear-gradient(135deg, #F19E14 0%, #E2741B 60%, #C9520F 100%)"
  type-3d: "linear-gradient(180deg, #FFD08A 0%, #F19E14 45%, #E2741B 100%)"

typography:
  display:  { fontFamily: "Anton", weight: 400, upper: true, lineHeight: 0.92, sizes: "180–300px hero, 110–150px headline" }
  heading:  { fontFamily: "Montserrat", weight: 900, upper: true, lineHeight: 1.0, sizes: "64–96px" }
  label:    { fontFamily: "Montserrat", weight: 800, upper: true, tracking: "0.12em", sizes: "30–40px" }
  body:     { fontFamily: "Montserrat", weight: 600, sizes: "38–48px" }
  fonts: "assets/fonts/Anton-400.woff2, assets/fonts/Montserrat-var.woff2 (variable 100–900)"

components:
  glass-card: "background glass-fill; 2px glass-border; radius 40px; backdrop-filter blur(18px); inner top highlight; soft drop shadow 0 30px 80px rgba(0,0,0,.35)"
  service-chip: "pill radius 999px; accent gradient fill, white Montserrat 800 uppercase label 34px; or glass variant"
  number-tag: "Anton 120px orange→amber gradient text '01'..'07' + label 'SERVICE' in Montserrat 800 tracking .2em"
  chat-bubble: "white bubble radius 36px with tail, ink text Montserrat 800, small blue cursor arrow beside it"
  cursor: "blue (#2F7BFF) triangular pointer with white 4px stroke, 70px"
  speed-lines: "orange/amber horizontal streaks, like the logo's arrow tail"
  logo-plate: "white rounded plate (radius 48px) holding assets/logo-dtl.jpg, glow ring orange"
  icon-style: "flat-3D illustrated SVG icons: navy/blue bodies, orange accents, white highlights, soft gradient shading"

layout:
  safe: "content between y=140 and y=1600 (bottom 17% reserved for captions); side gutters 70px"
  hero: "anchor hero around y=480–800"
---

# DTL Motion

TikTok-native, fast and glossy. Every frame sits on a deep-navy radial gradient with drifting
soft light blobs (blue glow + orange) and occasional star/sparkle shapes, like the reference.
One hero per frame: a giant Anton word, an illustrated icon scene, or a glass card. Orange is
the voltage — speed streaks, numbers, CTA button. White is for type on navy.

Motion: snappy (back.out / expo.out), 0.3–0.6s entrances, overshoot pops, constant gentle camera
drift (scale 1→1.06) so no frame is ever static. Text never repeats narration sentences — short
keywords only (captions carry the voice).
