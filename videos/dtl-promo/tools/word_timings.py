#!/usr/bin/env python3
"""Estimate per-word timings for each narration clip (no transcriber available offline).

Speech regions come from the clip's energy envelope; words are spread across them in
proportion to their length, with pauses aligned to punctuation. Display text swaps the
phonetic spellings used for TTS back to how they should read on screen (DTL, digits).
"""
import json, re, numpy as np, soundfile as sf

meta = json.load(open("audio_meta.json"))
sb = open("STORYBOARD.md", encoding="utf-8").read()
vo = {int(n): t for n, t in re.findall(r'## Frame (\d+) —.*?- voiceover: "(.*?)"', sb, re.S)}

NUMS = {"plus deux cent vingt-cinq": "+225", "zéro sept": "07", "soixante-dix-neuf": "79",
        "quatre-vingt-quatre": "84", "vingt-deux": "22", "soixante-seize": "76"}

def display_tokens(text):
    """[(display_word, weight, pause_after)] — weight ~ spoken length."""
    for k, v in NUMS.items():
        text = text.replace(k, "⟦%s|%d⟧" % (v, len(k)))
    text = text.replace("Dé Té El", "⟦DTL|8⟧")
    out = []
    for tok in text.split():
        m = re.match(r"⟦(.+?)\|(\d+)⟧(.*)", tok)
        if m:
            word, w, tail = m.group(1) + m.group(3).rstrip(","), int(m.group(2)), m.group(3)
        else:
            word, w, tail = tok, len(re.sub(r"\W", "", tok)) or 1, tok
        pause = bool(re.search(r"[,:;.!?…]$", tail))
        if re.fullmatch(r"[:!?…,;.]+", word) and out:  # orphan punctuation
            out[-1] = (out[-1][0] + " " + word, out[-1][1], True); continue
        out.append((word, w + 2, pause))
    return out

def speech_span(path):
    x, sr = sf.read(path)
    if x.ndim > 1: x = x.mean(1)
    hop = int(sr * 0.01)
    rms = np.array([np.sqrt(np.mean(x[i:i + hop] ** 2)) for i in range(0, len(x) - hop, hop)])
    thr = max(rms.max() * 0.06, 1e-4)
    v = np.where(rms > thr)[0]
    return (v[0] * 0.01, (v[-1] + 1) * 0.01) if len(v) else (0, len(x) / sr)

for v in meta["voices"]:
    toks = display_tokens(vo[v["frame"]])
    a, b = speech_span(v["path"])
    pause_w = 3  # a punctuation pause costs ~3 chars of time
    total = sum(w for _, w, _ in toks) + pause_w * sum(1 for *_, p in toks[:-1] if p)
    t, words = a, []
    unit = (b - a) / total
    for i, (word, w, p) in enumerate(toks):
        s, e = t, t + w * unit
        words.append({"text": word, "start": round(s, 3), "end": round(e - unit * 0.6, 3)})
        t = e + (pause_w * unit if p and i < len(toks) - 1 else 0)
    v["words"] = words
json.dump(meta, open("audio_meta.json", "w"), indent=2, ensure_ascii=False)
print(sum(len(v["words"]) for v in meta["voices"]), "words")
print([w["text"] for w in meta["voices"][11]["words"]])
