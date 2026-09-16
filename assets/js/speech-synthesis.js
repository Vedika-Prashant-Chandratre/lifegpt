/**
 * LifeGPT - Browser Speech Synthesis Module
 * Wrapper around Web Speech API window.speechSynthesis
 */

class LifeGPTSpeechSynthesis {
    constructor(options = {}) {
        this.synth = window.speechSynthesis;
        this.utterance = null;
        this.isPlaying = false;
        
        // Voice Config Defaults
        this.defaultRate = options.rate || 1.0;
        this.defaultPitch = options.pitch || 1.0;
        this.defaultLang = options.lang || 'en-US';
        
        // Callbacks
        this.onStart = options.onStart || null;
        this.onEnd = options.onEnd || null;
        this.onError = options.onError || null;
        
        this.init();
    }
    
    init() {
        if (!this.synth) {
            console.warn('Speech Synthesis is not supported by this browser.');
            return;
        }
        try {
            this.synth.cancel();
        } catch (e) {}
    }
    
    /**
     * Check browser support
     */
    isSupported() {
        return this.synth !== undefined;
    }
    
    /**
     * Speak text out loud
     */
    speak(text, customConfig = {}) {
        if (!this.isSupported()) return;
        
        // Stop any current speech
        this.cancel();
        
        this.utterance = new SpeechSynthesisUtterance(text);
        
        // Set speech speed, pitch, and language
        this.utterance.rate = customConfig.rate || this.defaultRate;
        this.utterance.pitch = customConfig.pitch || this.defaultPitch;
        this.utterance.lang = customConfig.lang || this.defaultLang;
        
        // Attempt to pick a high quality natural English voice
        if (this.synth.getVoices) {
            const voices = this.synth.getVoices();
            // Look for Google US English or Microsoft David or generic English voices
            let voice = voices.find(v => v.lang === this.utterance.lang && (v.name.includes('Google') || v.name.includes('Natural')));
            if (!voice) {
                voice = voices.find(v => v.lang.startsWith('en'));
            }
            if (voice) {
                this.utterance.voice = voice;
            }
        }
        
        // Setup events
        this.utterance.onstart = () => {
            this.isPlaying = true;
            if (this.onStart) this.onStart();
        };
        
        this.utterance.onend = () => {
            this.isPlaying = false;
            if (this.onEnd) this.onEnd();
        };
        
        this.utterance.onerror = (event) => {
            console.error('Speech Synthesis Error:', event);
            this.isPlaying = false;
            if (this.onError) this.onError(event);
        };
        
        this.synth.speak(this.utterance);
    }
    
    /**
     * Stop all voice playback
     */
    cancel() {
        if (!this.isSupported()) return;
        try {
            this.synth.cancel();
        } catch (e) {}
        this.isPlaying = false;
        if (this.onEnd) this.onEnd();
    }
    
    /**
     * Pause speaking
     */
    pause() {
        if (!this.isSupported()) return;
        this.synth.pause();
    }
    
    /**
     * Resume speaking
     */
    resume() {
        if (!this.isSupported()) return;
        this.synth.resume();
    }
}
window.LifeGPTSpeechSynthesis = LifeGPTSpeechSynthesis;
