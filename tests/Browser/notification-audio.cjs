// Run with: node tests/Browser/notification-audio.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

(async () => {
    const listeners = {}, frequencies = [], nodes = [], stops = [], peakGains = [];
    let contexts = 0;
    const config = { dataset: { sound: 'bell', customUrl: '', sounds: JSON.stringify({
        chime: { notes: [[880, .55], [659, 1.6]] }, bell: { notes: [[1047, .35]] }, gentle: { notes: [[440, .18], [554, .25]] },
    }) } };
    const document = { addEventListener: (name, callback) => { (listeners[name] ||= []).push(callback); }, querySelector: () => config };
    class AudioContext {
        constructor() { contexts++; this.state = 'running'; this.currentTime = 0; }
        createOscillator() { const node = { frequency: { setValueAtTime: (value) => frequencies.push(value) }, connect() {}, disconnect() {}, start() {}, stop(at) { if (at !== undefined) stops.push(at); } }; nodes.push(node); return node; }
        createGain() { return { gain: { setValueAtTime() {}, linearRampToValueAtTime(value) { peakGains.push(value); }, exponentialRampToValueAtTime() {} }, connect() {}, disconnect() {} }; }
    }
    let customPlays = 0, customPauses = 0;
    class Audio {
        constructor(url) { assert.equal(url, '/settings/notifications/audio'); }
        async play() { customPlays++; }
        pause() { customPauses++; }
        removeAttribute() {}
        load() {}
    }
    const window = { addEventListener() {}, AudioContext, Audio, navigator: { userActivation: { hasBeenActive: true } } };
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../public/assets/starter/js/starter-runtime.js'), 'utf8'), { window, document, setTimeout, clearTimeout });
    const runtime = window.StarterTemplate;
    runtime.bind(); runtime.bind();
    assert.equal(contexts, 0); // Loading the runtime never plays a sound.
    assert.equal(await runtime.playNotificationSound(), true);
    assert.deepEqual(frequencies, [1047]);
    assert.equal(await runtime.playNotificationSound('gentle'), true);
    assert.deepEqual(frequencies.slice(-2), [440, 554]);
    assert.equal(contexts, 1);
    listeners['starter-notification-sound-updated'][0]({ detail: { sound: 'chime' } });
    assert.equal(config.dataset.sound, 'chime');
    assert.equal(listeners['starter-notification-sound-updated'].length, 1);
    listeners['starter-notification-sound-updated'][0]({ detail: { sound: 'https://external.test/audio' } });
    assert.equal(config.dataset.sound, 'chime');
    assert.equal(await runtime.playNotificationSound('unknown'), true);
    assert.deepEqual(frequencies.slice(-2), [880, 659]);
    assert.equal(nodes.at(-1).type, 'triangle');
    assert.ok(peakGains.every((gain) => gain >= .25));
    assert.ok(stops.at(-1) >= 2 && stops.at(-1) <= 3); // Full default chime includes its decay tail.
    runtime.stopNotificationSound();
    assert.equal(runtime.notificationAudioNodes.length, 0);
    window.AudioContext = null;
    runtime.notificationAudioContext = null;
    assert.equal(await runtime.playNotificationSound(), false);
    window.AudioContext = AudioContext;
    window.navigator.userActivation.hasBeenActive = false;
    assert.equal(await runtime.playNotificationSound(), false);
    assert.equal(contexts, 1);
    listeners['starter-notification-sound-updated'][0]({ detail: { sound: 'custom', customUrl: '/settings/notifications/audio' } });
    assert.equal(config.dataset.sound, 'custom');
    assert.equal(await runtime.playNotificationSound(), true);
    assert.equal(customPlays, 1);
    runtime.stopNotificationSound();
    assert.equal(customPauses, 1);
    listeners['starter-notification-sound-updated'][0]({ detail: { sound: 'custom', customUrl: 'https://external.test/audio.mp3' } });
    assert.equal(config.dataset.customUrl, '/settings/notifications/audio');
    const form = runtime.notificationPreferences('gentle');
    let deferred;
    form.$wire = { $set: (...args) => { deferred = args; } };
    form.useDefault();
    assert.equal(form.sound, 'chime');
    assert.deepEqual(deferred, ['sound', 'chime', false]);
    console.log('Per-user audio selection, no autoplay, local tones, context reuse, invalid-key fallback, browser restrictions and deferred defaults passed.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
