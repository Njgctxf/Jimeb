# Hand-drawn flat-3D SVG illustrations for the DTL promo (navy / blue / orange palette).
# Each value is an <svg> inner markup string sized for the viewBox noted in its key comment.

NAVY = "#112557"
NAVY2 = "#1B3F8F"
BLUE = "#2F7BFF"
ORANGE = "#E2741B"
AMBER = "#F19E14"
WHITE = "#FFFFFF"

# viewBox 0 0 640 360 — school bus, facing right
BUS = f"""
<defs>
  <linearGradient id="busBody" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#FFD54A"/><stop offset="1" stop-color="{AMBER}"/>
  </linearGradient>
</defs>
<ellipse cx="320" cy="330" rx="290" ry="18" fill="rgba(0,0,0,.35)"/>
<rect x="40" y="70" width="520" height="220" rx="34" fill="url(#busBody)"/>
<path d="M560 140 h40 a20 20 0 0 1 20 20 v110 a20 20 0 0 1 -20 20 h-40z" fill="{AMBER}"/>
<rect x="40" y="200" width="580" height="22" fill="{NAVY}"/>
<rect x="40" y="240" width="580" height="10" fill="{NAVY}" opacity=".6"/>
<g fill="#CFE3FF" stroke="{NAVY}" stroke-width="6">
  <rect x="70" y="100" width="80" height="80" rx="12"/>
  <rect x="170" y="100" width="80" height="80" rx="12"/>
  <rect x="270" y="100" width="80" height="80" rx="12"/>
  <rect x="370" y="100" width="80" height="80" rx="12"/>
  <rect x="470" y="100" width="70" height="120" rx="12"/>
</g>
<path d="M75 105 l30 0 l-25 70 l-10 0z" fill="#fff" opacity=".55"/>
<path d="M175 105 l30 0 l-25 70 l-10 0z" fill="#fff" opacity=".55"/>
<path d="M275 105 l30 0 l-25 70 l-10 0z" fill="#fff" opacity=".55"/>
<rect x="590" y="235" width="26" height="18" rx="6" fill="#FFF6D0"/>
<rect x="44" y="236" width="18" height="16" rx="5" fill="#FF5A3C"/>
<text x="300" y="275" font-family="Montserrat" font-weight="900" font-size="30" fill="{NAVY}" text-anchor="middle" letter-spacing="4">ÉCOLE</text>
<g class="wheel"><circle cx="150" cy="292" r="42" fill="{NAVY}"/><circle cx="150" cy="292" r="18" fill="#C9D3EA"/><rect x="146" y="256" width="8" height="72" fill="#C9D3EA" opacity=".6"/></g>
<g class="wheel"><circle cx="480" cy="292" r="42" fill="{NAVY}"/><circle cx="480" cy="292" r="18" fill="#C9D3EA"/><rect x="476" y="256" width="8" height="72" fill="#C9D3EA" opacity=".6"/></g>
"""

# viewBox 0 0 400 200 — car (VTC), facing right
CAR = f"""
<ellipse cx="200" cy="186" rx="170" ry="10" fill="rgba(0,0,0,.35)"/>
<path d="M30 140 q0-40 40-46 l50-8 q40-50 100-50 q60 0 100 48 l40 8 q30 6 30 40 v20 h-360z" fill="{WHITE}"/>
<path d="M135 88 q30-38 85-38 q45 0 75 38z" fill="{NAVY2}"/>
<path d="M150 86 q25-28 60-30 l-8 30z" fill="#CFE3FF" opacity=".7"/>
<rect x="30" y="128" width="360" height="16" fill="{ORANGE}"/>
<text x="210" y="122" font-family="Anton" font-size="26" fill="{NAVY}" text-anchor="middle" letter-spacing="3">VTC</text>
<rect x="372" y="112" width="20" height="12" rx="4" fill="#FFF6D0"/>
<g class="wheel"><circle cx="105" cy="160" r="28" fill="{NAVY}"/><circle cx="105" cy="160" r="11" fill="#C9D3EA"/></g>
<g class="wheel"><circle cx="305" cy="160" r="28" fill="{NAVY}"/><circle cx="305" cy="160" r="11" fill="#C9D3EA"/></g>
"""

# viewBox 0 0 420 300 — delivery scooter with box, facing right
SCOOTER = f"""
<ellipse cx="210" cy="286" rx="180" ry="10" fill="rgba(0,0,0,.35)"/>
<rect x="40" y="70" width="130" height="110" rx="14" fill="{ORANGE}"/>
<rect x="40" y="70" width="130" height="26" rx="10" fill="{AMBER}"/>
<path d="M95 112 h20 v30 h-20z" fill="{WHITE}" opacity=".9"/>
<text x="105" y="166" font-family="Anton" font-size="24" fill="{WHITE}" text-anchor="middle">DTL</text>
<path d="M60 200 h200 l40-90 h40 l-30 110 h-260z" fill="{NAVY2}"/>
<path d="M300 110 l20-40 h30" stroke="{NAVY}" stroke-width="12" fill="none" stroke-linecap="round"/>
<circle cx="245" cy="120" r="26" fill="{NAVY}"/>
<path d="M225 112 a22 22 0 0 1 40 0z" fill="{BLUE}"/>
<path d="M220 150 q30-20 50 10 l-10 50 h-40z" fill="{AMBER}"/>
<g class="wheel"><circle cx="95" cy="240" r="38" fill="{NAVY}"/><circle cx="95" cy="240" r="15" fill="#C9D3EA"/></g>
<g class="wheel"><circle cx="330" cy="240" r="38" fill="{NAVY}"/><circle cx="330" cy="240" r="15" fill="#C9D3EA"/></g>
"""

# viewBox 0 0 120 120 icons
ICON_FOOD = f"""
<path d="M20 60 h80 a40 40 0 0 1 -80 0z" fill="{AMBER}"/>
<path d="M20 60 h80" stroke="{NAVY}" stroke-width="6" stroke-linecap="round"/>
<path d="M45 45 q5-12 0-22 M60 45 q5-12 0-22 M75 45 q5-12 0-22" stroke="{WHITE}" stroke-width="5" fill="none" stroke-linecap="round" opacity=".85"/>
<rect x="30" y="98" width="60" height="8" rx="4" fill="{NAVY}"/>
"""
ICON_BOX = f"""
<path d="M60 14 l44 22 v50 l-44 22 l-44-22 v-50z" fill="{ORANGE}"/>
<path d="M60 14 l44 22 l-44 22 l-44-22z" fill="{AMBER}"/>
<path d="M60 58 v50" stroke="#B5530F" stroke-width="4"/>
<path d="M38 25 l44 22 v18" stroke="{WHITE}" stroke-width="7" fill="none" opacity=".9"/>
"""
ICON_PALLET = f"""
<rect x="16" y="20" width="40" height="40" rx="6" fill="{ORANGE}"/>
<rect x="64" y="20" width="40" height="40" rx="6" fill="{AMBER}"/>
<rect x="40" y="58" width="40" height="34" rx="6" fill="{BLUE}"/>
<rect x="10" y="94" width="100" height="10" rx="3" fill="{NAVY2}"/>
<rect x="18" y="104" width="12" height="8" fill="{NAVY2}"/><rect x="54" y="104" width="12" height="8" fill="{NAVY2}"/><rect x="90" y="104" width="12" height="8" fill="{NAVY2}"/>
"""
ICON_CAR = f"""
<path d="M14 74 q0-16 16-18 l14-2 q14-22 34-22 q22 0 32 22 l8 2 q10 2 10 18 v10 h-114z" fill="{WHITE}"/>
<path d="M50 54 q10-16 28-16 q16 0 24 16z" fill="{BLUE}"/>
<rect x="14" y="70" width="114" height="6" fill="{ORANGE}"/>
<circle cx="38" cy="86" r="12" fill="{NAVY}"/><circle cx="102" cy="86" r="12" fill="{NAVY}"/>
"""
ICON_BALLOON = f"""
<ellipse cx="60" cy="48" rx="32" ry="38" fill="{ORANGE}"/>
<ellipse cx="48" cy="34" rx="8" ry="12" fill="#fff" opacity=".5"/>
<path d="M54 84 l6 8 l6-8z" fill="{ORANGE}"/>
<path d="M60 92 q-8 12 0 24" stroke="{WHITE}" stroke-width="3" fill="none"/>
"""
ICON_GLOBE = f"""
<circle cx="60" cy="60" r="46" fill="{BLUE}"/>
<path d="M30 40 q14-8 20 6 q-4 14 10 18 q6 14-8 22 q-14-6-18-22z M70 26 q16 2 26 18 q-10 4-16-2 q-12 0-10-16z M76 70 q12-4 18 6 q-6 14-16 12z" fill="{AMBER}"/>
<circle cx="60" cy="60" r="46" fill="none" stroke="{WHITE}" stroke-width="5"/>
"""
ICON_HEN = f"""
<ellipse cx="62" cy="70" rx="38" ry="32" fill="{WHITE}"/>
<circle cx="84" cy="40" r="20" fill="{WHITE}"/>
<path d="M78 18 q4-10 10 0 q6-8 10 4 q-10 4-20-4z" fill="#E8392B"/>
<path d="M102 40 l14 5 l-14 5z" fill="{AMBER}"/>
<path d="M92 50 q4 10-4 12 q-4-6 4-12z" fill="#E8392B"/>
<circle cx="88" cy="36" r="3.5" fill="{NAVY}"/>
<path d="M24 60 q-14-14 -4-30 q8 16 18 18z" fill="{WHITE}"/>
<path d="M40 70 q20 18 40 0" stroke="#D6DDEE" stroke-width="5" fill="none"/>
<path d="M54 100 v14 m-6 0 h12 M72 100 v14 m-6 0 h12" stroke="{AMBER}" stroke-width="5" stroke-linecap="round"/>
"""
ICON_RABBIT = f"""
<ellipse cx="44" cy="20" rx="9" ry="22" fill="#F3EEE8" transform="rotate(-12 44 20)"/>
<ellipse cx="66" cy="18" rx="9" ry="22" fill="#F3EEE8" transform="rotate(10 66 18)"/>
<ellipse cx="44" cy="22" rx="4" ry="15" fill="#F7B8B0" transform="rotate(-12 44 22)"/>
<ellipse cx="66" cy="20" rx="4" ry="15" fill="#F7B8B0" transform="rotate(10 66 20)"/>
<ellipse cx="62" cy="84" rx="40" ry="28" fill="#F3EEE8"/>
<circle cx="56" cy="54" r="24" fill="#F3EEE8"/>
<circle cx="47" cy="50" r="3.5" fill="{NAVY}"/><circle cx="64" cy="50" r="3.5" fill="{NAVY}"/>
<path d="M53 60 l3 3 l3-3z" fill="#F08A80"/>
<circle cx="100" cy="82" r="8" fill="#fff"/>
"""
ICON_QUAIL = f"""
<ellipse cx="58" cy="72" rx="40" ry="30" fill="#B07A45"/>
<circle cx="84" cy="46" r="18" fill="#9A6535"/>
<path d="M84 28 q2-14 10-16" stroke="{NAVY}" stroke-width="4" fill="none" stroke-linecap="round"/>
<circle cx="96" cy="12" r="5" fill="{NAVY}"/>
<path d="M100 48 l10 3 l-10 4z" fill="{NAVY}"/>
<circle cx="88" cy="43" r="3" fill="#fff"/><circle cx="88" cy="43" r="1.6" fill="{NAVY}"/>
<g fill="#E9D7BF" opacity=".9"><circle cx="40" cy="66" r="4"/><circle cx="54" cy="78" r="4"/><circle cx="68" cy="68" r="4"/><circle cx="46" cy="88" r="4"/><circle cx="62" cy="92" r="4"/></g>
<path d="M50 100 v12 M68 100 v12" stroke="{AMBER}" stroke-width="5" stroke-linecap="round"/>
"""
ICON_SHIELD = f"""
<path d="M60 8 l44 16 v30 q0 40 -44 58 q-44-18 -44-58 v-30z" fill="{BLUE}"/>
<path d="M60 8 l44 16 v30 q0 40 -44 58z" fill="{NAVY2}" opacity=".5"/>
<path d="M38 58 l16 16 l30-32" stroke="{WHITE}" stroke-width="11" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
"""
ICON_PLANE = f"""
<path d="M10 64 l40-6 l30-40 h12 l-16 38 l30-4 l10-14 h8 l-6 22 l6 22 h-8 l-10-14 l-30-4 l16 38 h-12 l-30-40 l-40-6z" fill="{WHITE}" transform="rotate(-8 60 60)"/>
"""
ICON_SHIP = f"""
<path d="M10 70 h100 l-14 28 h-72z" fill="{NAVY2}"/>
<rect x="24" y="44" width="22" height="24" fill="{ORANGE}"/><rect x="48" y="44" width="22" height="24" fill="{AMBER}"/><rect x="72" y="44" width="22" height="24" fill="{BLUE}"/>
<rect x="36" y="22" width="22" height="22" fill="{AMBER}"/><rect x="60" y="22" width="22" height="22" fill="{ORANGE}"/>
<path d="M4 104 q14 8 28 0 q14 8 28 0 q14 8 28 0 q14 8 28 0" stroke="#7FB2FF" stroke-width="5" fill="none"/>
"""
ICON_PHONE = f"""
<path d="M34 16 q-14 4 -14 22 q4 40 44 62 q18 6 26-8 l-14-16 q-8 6-16 2 q-14-8 -20-24 q-2-8 6-12 l-12-26z" fill="{WHITE}"/>
"""
ICON_WHATSAPP = f"""
<circle cx="60" cy="58" r="44" fill="none" stroke="{WHITE}" stroke-width="9"/>
<path d="M24 100 l8-26 l18 18z" fill="{WHITE}"/>
<path d="M46 40 q-8 4-4 14 q8 18 26 24 q10 2 12-6 l-8-6 q-4 4-8 2 q-8-4-12-12 q-2-4 2-6z" fill="{WHITE}"/>
"""
ICON_TRUCK = f"""
<rect x="8" y="34" width="66" height="48" rx="6" fill="{ORANGE}"/>
<path d="M74 48 h22 l14 16 v18 h-36z" fill="{NAVY2}"/>
<path d="M80 52 h14 l8 10 h-22z" fill="#CFE3FF"/>
<circle cx="30" cy="86" r="11" fill="{NAVY}"/><circle cx="90" cy="86" r="11" fill="{NAVY}"/>
"""
ICON_GIFT = f"""
<rect x="18" y="46" width="84" height="60" rx="6" fill="{ORANGE}"/>
<rect x="12" y="34" width="96" height="18" rx="5" fill="{AMBER}"/>
<rect x="54" y="34" width="12" height="72" fill="{WHITE}"/>
<path d="M60 34 q-28-26 -30 -6 q4 10 30 6 q26 4 30-6 q-2-20 -30 6z" fill="{WHITE}"/>
"""
ICON_CHICKEN_SMALL = ICON_HEN
