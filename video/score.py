# Generates score.wav: ambient pad + music-box motifs synced to the film (34 s).
import numpy as np, wave
SR = 48000; D = 34.0; N = int(SR * D); t = np.arange(N) / SR
rng = np.random.default_rng(3)
def hz(m): return 440 * 2 ** ((m - 69) / 12)
L = np.zeros(N); R = np.zeros(N)

# Pad: chords D maj9 -> B m7(add11) -> G maj9 -> A sus -> D maj9
chords = [(0, [50, 57, 62, 66, 69]), (4.2, [50, 57, 62, 66, 69, 76]), (11, [47, 54, 59, 62, 66, 73]), (15.4, [43, 50, 59, 62, 66, 69]),
          (19.8, [40, 52, 55, 59, 62, 67]), (24.2, [45, 52, 57, 61, 64, 71]), (28.6, [38, 50, 57, 62, 66, 69, 76])]
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
for a, m, v in [(1.0, 74, .7), (1.6, 78, .6), (2.4, 81, .6), (4.2, 86, .55), (4.25, 74, .5), (7.4, 85, .45), (11.0, 83, .55), (11.05, 71, .45),
                (13.2, 81, .45), (15.4, 79, .55), (15.45, 67, .45), (17.6, 78, .45), (19.8, 76, .55), (19.85, 64, .45), (22.0, 79, .45),
                (24.2, 81, .55), (24.25, 69, .45), (26.4, 85, .45), (28.6, 86, .6), (28.65, 74, .5), (29.0, 81, .45), (30.6, 78, .5), (31.2, 74, .45), (32.4, 69, .4)]:
    bell(a, m, v, .35 + .3 * rng.random())

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
