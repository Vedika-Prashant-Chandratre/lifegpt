/**
 * LifeGPT - Browser Speech Recognition Module
 * Wrapper around Web Speech API SpeechRecognition
 */

class LifeGPTSpeechRecognition {
    constructor(options = {}) {
        this.recognition = null;
        this.isRecording = false;
        
        // Callbacks
        this.onResult = options.onResult || null;
        this.onStart = options.onStart || null;
        this.onEnd = options.onEnd || null;
        this.onError = options.onError || null;
        
        this.init();
    }
    
    /**
     * Check support and initialize Web Speech API
     */
    init() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            console.warn('Speech Recognition is not supported by this browser.');
            return;
        }
        
        this.recognition = new SpeechRecognition();
        this.recognition.continuous = true;
        this.recognition.interimResults = true;
        this.recognition.lang = 'en-US';
        
        // Bind events
        this.recognition.onstart = () => {
            this.isRecording = true;
            if (this.onStart) this.onStart();
        };
        
        this.recognition.onend = () => {
            this.isRecording = false;
            if (this.onEnd) this.onEnd();
        };
        
        this.recognition.onerror = (event) => {
            console.error('Speech Recognition Error:', event.error);
            this.isRecording = false;
            if (this.onError) this.onError(event.error);
        };
        
        this.recognition.onresult = (event) => {
            let interimTranscript = '';
            let finalTranscript = '';
            
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }
            
            if (this.onResult) {
                this.onResult(finalTranscript, interimTranscript);
            }
        };
    }
    
    /**
     * Is speech recognition supported in the client browser?
     */
    isSupported() {
        return this.recognition !== null;
    }
    
    /**
     * Start recording audio
     */
    start() {
        if (!this.isSupported()) return;
        if (this.isRecording) return;
        
        try {
            this.recognition.start();
        } catch (e) {
            console.error('Failed to start recognition:', e);
        }
    }
    
    /**
     * Stop recording audio
     */
    stop() {
        if (!this.isSupported()) return;
        if (!this.isRecording) return;
        
        try {
            this.recognition.stop();
        } catch (e) {
            console.error('Failed to stop recognition:', e);
        }
    }
    
    /**
     * Toggle recording state
     */
    toggle() {
        if (this.isRecording) {
            this.stop();
        } else {
            this.start();
        }
    }
}
window.LifeGPTSpeechRecognition = LifeGPTSpeechRecognition;
