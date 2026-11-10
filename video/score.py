# Generates score.wav: ambient pad + music-box motifs synced to the film (34 s).
import numpy as np, wave
SR = 48000; D = 34.0; N = int(SR * D); t = np.arange(N) / SR
rng = np.random.default_rng(3)
def hz(m): return 440 * 2 ** ((m - 69) / 12)
L = np.zeros(N); R = np.zeros(N)

# Pad: chords D maj9 -> B m7(add11) -> G maj9 -> A sus -> D maj9
chords = [(0, [50, 57, 62, 66, 69, 76]), (6.5, [47, 54, 59, 62, 66, 73]), (13, [43, 50, 59, 62, 66, 69]),
          (21, [45, 52, 57, 62, 64, 71]), (25.6, [38, 50, 57, 62, 66, 69, 76])]
for i, (st, notes) in enumerate(chords):
    en = chords[i + 1][0] + 1.5 if i + 1 < len(chords) else D
    seg = (t >= st) & (t < en); tt = t[seg] - st; dur = en - st
    env = np.minimum(1, tt / 2.2) * np.minimum(1, (dur - tt) / 2.0).clip(0)
    for k, m in enumerate(notes):
        f = hz(m)
        for det, pan in ((-0.07, .2), (0.07, .8)):
            ph = 2 * np.pi * f * (1 + det / 100) * tt + k
            tone = np.sin(ph) + .25 * np.sin(2 * ph) + .08 * np.sin(3 * ph)
            amp = .035 * (1 + .15 * np.sin(2 * np.pi * .13 * tt + k)) * (.6 if m > 70 else 1)
            L[seg] += tone * env * amp * (1 - pan); R[seg] += tone * env * amp * pan

# Music box / celesta notes
def bell(at, m, vel=1., pan=.5):
    f = hz(m); n = int(SR * 4); tt = np.arange(n) / SR
    env = np.exp(-tt * 2.2) * np.minimum(1, tt / .004)
    x = (np.sin(2*np.pi*f*tt) + .45*np.sin(2*np.pi*f*2.0*tt)*np.exp(-tt*4) + .22*np.sin(2*np.pi*f*3.01*tt)*np.exp(-tt*7)
         + .1*np.sin(2*np.pi*f*5.4*tt)*np.exp(-tt*12)) * env * .09 * vel
    s = int(at * SR); e = min(N, s + n)
    L[s:e] += x[:e-s] * (1 - pan); R[s:e] += x[:e-s] * pan
motif = [(1.6, 74), (2.1, 78), (2.6, 81), (3.4, 86), (4.4, 85)]
for a, m in motif: bell(a, m, .9, .35 + .3 * rng.random())
for a, m in [(8.4, 81), (8.75, 78), (9.1, 76), (9.8, 74), (11.2, 73)]: bell(a, m, .8, .4 + .2 * rng.random())
for a, m in [(15.4, 71), (16.5, 74), (17.6, 78), (18.6, 76)]: bell(a, m, .8, .3 + .4 * rng.random())
for a, m in [(21.2, 81), (23.35, 79), (24.0, 76)]: bell(a, m, .75, .5)
for a, m in [(27.0, 86), (27.15, 90), (27.3, 93), (27.45, 98), (29.4, 81), (30.2, 78), (30.6, 74), (31.4, 69)]: bell(a, m, .7, .35 + .3 * rng.random())

# Shimmer swell into the logo: filtered noise riser + soft low impact
seg = (t > 25.2) & (t < 30); tt = t[seg] - 25.2
noise = rng.standard_normal(seg.sum()); noise = np.convolve(noise, np.ones(6) / 6, 'same')
sw = np.clip(tt / 1.6, 0, 1) ** 2 * np.exp(-np.clip(tt - 1.6, 0, None) * 1.4) * .05
L[seg] += noise * sw; R[seg] += np.roll(noise, 37) * sw
s = int(27.0 * SR); n = int(SR * 3); tt = np.arange(n) / SR
boom = np.sin(2 * np.pi * 55 * tt + 4 * np.exp(-tt * 8)) * np.exp(-tt * 1.6) * .22 * np.minimum(1, tt / .01)
L[s:s+n] += boom; R[s:s+n] += boom

# Reverb: FFT-convolve with a decaying stereo noise tail
def reverb(x, seed):
    n = int(SR * 3.2); ir = np.random.default_rng(seed).standard_normal(n) * np.exp(-np.arange(n) / SR * 2.3)
    ir[:int(SR*.02)] = 0; size = 1 << int(np.ceil(np.log2(len(x) + n)))
    y = np.fft.irfft(np.fft.rfft(x, size) * np.fft.rfft(ir, size), size)[:len(x)]
    return y / np.abs(ir).sum() * 40
wl, wr = reverb(L, 1), reverb(R, 2)
L, R = L * .75 + wl * .45, R * .75 + wr * .45
fade = np.minimum(1, t / 1.5) * np.minimum(1, (D - t) / 2.2).clip(0)
out = np.stack([L, R], 1) * fade[:, None]; out = np.tanh(out / np.abs(out).max() * 1.1) * .84
with wave.open('score.wav', 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes((out * 32767).astype('<i2').tobytes())
print('ok')
