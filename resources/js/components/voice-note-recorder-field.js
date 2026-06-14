import { formatAudioTime } from '../core/format-time.js'
import { createWaveformBarsMixin } from '../core/waveform-bars.js'
import {
    AUDIO_WAVEFORM_SAMPLE_COUNT,
    extractWaveformFromUrl,
    generateWaveformFromFingerprint,
    extractPeaksFromBuffer,
} from '../core/audio-waveform.js'

export function formatTime(seconds) {
    return formatAudioTime(seconds)
}

export default function voiceNoteRecorderFieldFormComponent({
    state,
    statePath,
    schemaComponentKey = null,
    initialAudioUrl = null,
    maxDuration = 120,
    uploadImmediately = false,
    readOnly = false,
    labels = {},
}) {
    const waveformBars = createWaveformBarsMixin()

    return {
        state,
        statePath,
        schemaComponentKey,
        initialAudioUrl,
        maxDuration,
        uploadImmediately,
        readOnly,
        labels,

        mode: 'idle', // idle, recording, uploading, playback
        recordingDuration: 0,
        playbackDuration: 0,
        recordingStartedAt: null,
        uploadProgress: 0,
        pendingUpload: false,
        isUploadingForSubmit: false,
        isBackgroundUploading: false,
        isProtectingRecording: false,
        hasLocalRecording: false,
        uploadedFileKey: null,
        playing: false,
        currentTime: 0,
        duration: 0,
        canPlay: false,
        waveformReady: false,
        seeking: false,

        localAudioUrl: null,
        recordedBlob: null,
        recordedMimeType: null,
        mediaRecorder: null,
        audioContext: null,
        analyser: null,
        microphoneStream: null,
        animationFrameId: null,
        timerIntervalId: null,
        chunks: [],

        sourceWaveform: [],
        waveformAnalysisToken: 0,

        ...waveformBars,

        init() {
            // Set up all audio event listeners once synchronously on the audio element
            const audio = this.$refs.audio;
            if (audio) {
                audio.addEventListener('loadedmetadata', () => {
                    this.applyResolvedDuration(audio.duration);
                    this.canPlay = true;
                });

                audio.addEventListener('timeupdate', () => {
                    if (! this.seeking) {
                        this.currentTime = audio.currentTime || 0;
                    }
                });

                audio.addEventListener('play', () => {
                    this.playing = true;
                });

                audio.addEventListener('pause', () => {
                    this.playing = false;
                });

                audio.addEventListener('ended', () => {
                    this.playing = false;
                    this.currentTime = 0;
                });

                // Force trigger if already loaded/ready
                if (audio.readyState >= 1) {
                    this.applyResolvedDuration(audio.duration);
                    this.canPlay = true;
                }
            }

            // Determine initial state
            if (this.initialAudioUrl) {
                this.mode = 'playback';
                this.loadExistingAudio(this.initialAudioUrl);
            } else if (this.state && typeof this.state === 'string' && ! this.state.startsWith('livewire-file:')) {
                this.mode = 'playback';
                this.loadExistingAudio(this.state);
            }

            // Watch state changes (e.g. cleared externally)
            this.$watch('state', (value) => {
                if (this.shouldIgnoreStateReset()) {
                    return;
                }

                if (this.isEmptyState(value)) {
                    this.resetToIdle();
                }
            });

            // Watch mode changes to load audio when transitioning to playback
            this.$watch('mode', (newMode) => {
                if (newMode === 'playback') {
                    this.$nextTick(() => {
                        this.preparePlayback();
                    });
                }
            });

            // Watch audioSrc changes and load the audio element
            this.$watch('audioSrc', (src) => {
                this.$nextTick(() => {
                    const audio = this.$refs.audio;
                    if (! audio || ! src) {
                        return;
                    }

                    this.playing = false;
                    this.currentTime = 0;

                    if (this.localAudioUrl && src === this.localAudioUrl) {
                        this.preparePlayback();

                        return;
                    }

                    this.duration = 0;
                    this.canPlay = false;

                    if (this.mode === 'playback') {
                        audio.load();
                    }
                });
            });

            this.$nextTick(() => {
                this.setupWaveformObserver();
            });

            if (! this.uploadImmediately) {
                this.bindDeferredUpload();
            }
        },

        destroy() {
            this.cleanupAudioContext();
            this.cleanupRecorder();
            this.disconnectWaveformObserver();
            if (this.localAudioUrl) {
                URL.revokeObjectURL(this.localAudioUrl);
            }
        },

        get audioSrc() {
            return this.localAudioUrl || this.initialAudioUrl || '';
        },

        async loadExistingAudio(src) {
            this.waveformReady = false;
            this.sourceWaveform = generateWaveformFromFingerprint(src, AUDIO_WAVEFORM_SAMPLE_COUNT);
            this.updateWaveformBars();

            const token = ++this.waveformAnalysisToken;
            const peaks = await extractWaveformFromUrl(src, AUDIO_WAVEFORM_SAMPLE_COUNT);

            if (token === this.waveformAnalysisToken && peaks?.length) {
                this.sourceWaveform = peaks;
                this.updateWaveformBars();
            }

            this.waveformReady = true;

            this.$nextTick(() => {
                this.preparePlayback();
            });
        },

        isEmptyState(value) {
            if (value === null || value === undefined || value === '') {
                return true;
            }

            if (typeof value === 'object' && ! Array.isArray(value)) {
                return Object.keys(value).length === 0;
            }

            return false;
        },

        shouldIgnoreStateReset() {
            if (this.hasLocalRecording) {
                return true;
            }

            if (this.isProtectingRecording) {
                return true;
            }

            if (this.mode === 'playback') {
                return true;
            }

            if (this.mode === 'uploading' || this.isUploadingForSubmit || this.isBackgroundUploading) {
                return true;
            }

            if (this.pendingUpload) {
                return true;
            }

            if (this.uploadedFileKey) {
                return true;
            }

            return false;
        },

        applyResolvedDuration(audioDuration = null) {
            const resolved = this.resolvePlaybackDuration(audioDuration);

            if (resolved > 0) {
                this.duration = resolved;
            }
        },

        syncPlaybackFromRecording() {
            const resolved = this.resolvePlaybackDuration();

            this.playbackDuration = resolved;
            this.duration = resolved;
            this.canPlay = Boolean(this.audioSrc || this.recordedBlob);
        },

        resolvePlaybackDuration(audioDuration = null) {
            if (Number.isFinite(audioDuration) && audioDuration > 0) {
                return audioDuration;
            }

            if (this.playbackDuration > 0) {
                return this.playbackDuration;
            }

            if (this.recordingDuration > 0) {
                return this.recordingDuration;
            }

            if (this.localAudioUrl || this.recordedBlob) {
                return 1;
            }

            return 0;
        },

        preparePlayback() {
            if (! this.localAudioUrl && ! this.initialAudioUrl && ! this.recordedBlob) {
                return;
            }

            this.syncPlaybackFromRecording();

            this.$nextTick(() => {
                const audio = this.$refs.audio;
                if (! audio) {
                    return;
                }

                const applyDurationFromAudio = () => {
                    this.applyResolvedDuration(audio.duration);
                    this.canPlay = true;
                };

                audio.addEventListener('loadeddata', applyDurationFromAudio, { once: true });
                audio.addEventListener('canplay', applyDurationFromAudio, { once: true });
                audio.addEventListener('durationchange', applyDurationFromAudio, { once: true });
                audio.addEventListener('loadedmetadata', applyDurationFromAudio, { once: true });

                const src = this.audioSrc;

                if (src && audio.getAttribute('src') !== src) {
                    audio.src = src;
                }

                if (src) {
                    audio.load();
                }

                applyDurationFromAudio();
            });
        },

        bindDeferredUpload() {
            const form = this.$el.closest('form');

            if (! form || this._deferredUploadBound) {
                return;
            }

            this._deferredUploadBound = true;

            form.addEventListener('submit', async (event) => {
                if (! this.pendingUpload || ! this.recordedBlob) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                if (this.isUploadingForSubmit) {
                    return;
                }

                this.isUploadingForSubmit = true;

                try {
                    await this.uploadRecording();
                    this.isUploadingForSubmit = false;
                    form.requestSubmit();
                } catch (error) {
                    this.isUploadingForSubmit = false;
                    this.mode = 'playback';
                    console.error('Upload failed:', error);
                    alert('Wystąpił błąd podczas wysyłania nagrania.');
                }
            }, true);
        },

        resolveRecordingDuration() {
            if (this.recordingStartedAt) {
                const elapsed = (performance.now() - this.recordingStartedAt) / 1000;

                return Math.max(this.recordingDuration, elapsed);
            }

            return this.recordingDuration;
        },

        async finalizeRecording() {
            this.recordingDuration = this.resolveRecordingDuration();
            this.playbackDuration = this.recordingDuration;
            this.waveformReady = false;
            this.isProtectingRecording = true;
            this.pendingUpload = true;

            try {
                const arrayBuffer = await this.recordedBlob.arrayBuffer();
                const tempCtx = new (window.AudioContext || window.webkitAudioContext)();
                const decodedBuffer = await tempCtx.decodeAudioData(arrayBuffer);
                this.sourceWaveform = extractPeaksFromBuffer(decodedBuffer, AUDIO_WAVEFORM_SAMPLE_COUNT);

                if (decodedBuffer.duration > 0) {
                    this.recordingDuration = decodedBuffer.duration;
                    this.playbackDuration = decodedBuffer.duration;
                }

                await tempCtx.close();
            } catch (error) {
                console.warn('Could not extract peaks locally:', error);
                this.sourceWaveform = generateWaveformFromFingerprint(this.localAudioUrl, AUDIO_WAVEFORM_SAMPLE_COUNT);
            }

            this.waveformReady = true;
            this.mode = 'playback';
            this.syncPlaybackFromRecording();

            this.$nextTick(() => {
                this.updateWaveformBars();
                this.preparePlayback();
            });

            if (this.uploadImmediately) {
                try {
                    await this.uploadRecording({ keepPlaybackVisible: true });
                } catch (error) {
                    console.error('Upload failed:', error);
                    alert('Wystąpił błąd podczas wysyłania nagrania.');
                    this.resetToIdle();
                }

                return;
            }

            this.isProtectingRecording = false;
        },

        async startRecording() {
            if (this.readOnly) return;

            try {
                this.chunks = [];
                this.recordingDuration = 0;
                this.playbackDuration = 0;
                this.recordingStartedAt = performance.now();
                this.pendingUpload = false;
                this.hasLocalRecording = false;

                // Request mic permission
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.microphoneStream = stream;

                // MediaRecorder setup
                // Try preferred mime types (prefer audio/mp4 for Safari and macOS/iOS playback support)
                let options = { mimeType: 'audio/mp4' };
                if (! MediaRecorder.isTypeSupported(options.mimeType)) {
                    options = { mimeType: 'audio/webm' };
                }
                if (! MediaRecorder.isTypeSupported(options.mimeType)) {
                    options = { mimeType: 'audio/ogg' };
                }
                if (! MediaRecorder.isTypeSupported(options.mimeType)) {
                    options = {}; // browser default
                }

                this.mediaRecorder = new MediaRecorder(stream, options);

                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data && event.data.size > 0) {
                        this.chunks.push(event.data);
                    }
                };

                this.mediaRecorder.onstop = async () => {
                    if (this.chunks.length > 0) {
                        const recordedMimeType = this.mediaRecorder.mimeType || 'audio/wav';
                        this.recordedMimeType = recordedMimeType;
                        this.recordedBlob = new Blob(this.chunks, { type: recordedMimeType });

                        if (this.localAudioUrl) {
                            URL.revokeObjectURL(this.localAudioUrl);
                        }
                        this.localAudioUrl = URL.createObjectURL(this.recordedBlob);
                        this.hasLocalRecording = true;
                        this.isProtectingRecording = true;
                        this.pendingUpload = true;
                        this.recordingDuration = this.resolveRecordingDuration();
                        this.playbackDuration = this.recordingDuration;
                        this.syncPlaybackFromRecording();

                        if (this.mode === 'recording') {
                            await this.finalizeRecording();
                        }
                    }
                    this.cleanupRecorder();
                };

                // Visualizer Analyser Setup
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                this.audioContext = new AudioContextClass();
                const source = this.audioContext.createMediaStreamSource(stream);
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 256;
                source.connect(this.analyser);

                if (this.audioContext.state === 'suspended') {
                    await this.audioContext.resume();
                }

                this.mode = 'recording';

                // Start visualizer after the recording panel is visible and sized
                this.$nextTick(() => {
                    this.drawVisualizer();
                });

                // Start MediaRecorder
                this.mediaRecorder.start(250); // collect data chunks every 250ms

                // Start Timer
                this.timerIntervalId = setInterval(() => {
                    this.recordingDuration++;
                    if (this.recordingDuration >= this.maxDuration) {
                        this.stopRecording();
                    }
                }, 1000);

            } catch (err) {
                console.error('Failed to start recording:', err);
                alert('Mikrofon jest niedostępny lub zablokowany.');
                this.resetToIdle();
            }
        },

        drawVisualizer() {
            const canvas = this.$refs.canvas;

            if (! canvas || ! this.analyser) {
                return;
            }

            const bufferLength = this.analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            const dpr = window.devicePixelRatio || 1;

            const draw = () => {
                if (this.mode !== 'recording') {
                    return;
                }

                this.animationFrameId = requestAnimationFrame(draw);

                const width = canvas.clientWidth;
                const height = canvas.clientHeight;

                if (! width || ! height) {
                    return;
                }

                const ctx = canvas.getContext('2d');
                const pixelWidth = Math.floor(width * dpr);
                const pixelHeight = Math.floor(height * dpr);

                if (canvas.width !== pixelWidth || canvas.height !== pixelHeight) {
                    canvas.width = pixelWidth;
                    canvas.height = pixelHeight;
                }

                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                this.analyser.getByteFrequencyData(dataArray);
                ctx.clearRect(0, 0, width, height);

                const barWidth = 3;
                const barGap = 2;
                const totalBarWidth = barWidth + barGap;
                const maxBarsCount = Math.floor(width / totalBarWidth);

                ctx.fillStyle = this.isDark() ? '#f472b6' : '#ec4899';

                const centerY = height / 2;

                for (let i = 0; i < maxBarsCount; i++) {
                    const dataIndex = Math.floor((i / maxBarsCount) * bufferLength);
                    const value = dataArray[dataIndex] || 0;
                    const percent = value / 255;
                    const barHeight = Math.max(4, percent * (height - 8));

                    const x = i * totalBarWidth + (width - maxBarsCount * totalBarWidth) / 2;
                    const y = centerY - barHeight / 2;

                    ctx.beginPath();
                    ctx.roundRect(x, y, barWidth, barHeight, 2);
                    ctx.fill();
                }
            };

            draw();
        },

        isDark() {
            return document.documentElement.classList.contains('dark');
        },

        stopRecording() {
            this.recordingDuration = this.resolveRecordingDuration();

            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            this.cleanupAudioContext();
        },

        cancelRecording() {
            this.cleanupAudioContext();
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.onstop = null; // discard recording callbacks
                this.mediaRecorder.stop();
            }
            this.cleanupRecorder();
            this.resetToIdle();
        },

        cleanupAudioContext() {
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
            if (this.audioContext) {
                if (this.audioContext.state !== 'closed') {
                    this.audioContext.close();
                }
                this.audioContext = null;
            }
            this.analyser = null;
        },

        cleanupRecorder() {
            if (this.timerIntervalId) {
                clearInterval(this.timerIntervalId);
                this.timerIntervalId = null;
            }
            if (this.microphoneStream) {
                this.microphoneStream.getTracks().forEach(track => track.stop());
                this.microphoneStream = null;
            }
            this.mediaRecorder = null;
        },

        resetToIdle() {
            this.mode = 'idle';
            this.recordingDuration = 0;
            this.playbackDuration = 0;
            this.recordingStartedAt = null;
            this.uploadProgress = 0;
            this.pendingUpload = false;
            this.isUploadingForSubmit = false;
            this.isBackgroundUploading = false;
            this.isProtectingRecording = false;
            this.uploadedFileKey = null;
            this.hasLocalRecording = false;
            this.playing = false;
            this.currentTime = 0;
            this.duration = 0;
            this.canPlay = false;
            this.waveformReady = false;
            this.recordedBlob = null;
            this.recordedMimeType = null;
            this.displayWaveform = [];
            this.sourceWaveform = [];

            if (this.localAudioUrl) {
                URL.revokeObjectURL(this.localAudioUrl);
                this.localAudioUrl = null;
            }
            this.initialAudioUrl = null;
        },

        async deleteRecording() {
            if (this.readOnly) {
                return;
            }

            if (! confirm('Czy na pewno chcesz usunąć to nagranie?')) {
                return;
            }

            const fileKey = this.resolveUploadedFileKey();
            const fileValue = fileKey ? this.resolveUploadedFileValue(fileKey) : null;
            const hasServerFile = Boolean(fileKey && fileValue);

            if (! hasServerFile && this.pendingUpload) {
                this.state = null;

                return;
            }

            if (hasServerFile && this.schemaComponentKey) {
                try {
                    const method = this.isTemporaryUpload(fileValue)
                        ? 'removeUploadedFile'
                        : 'deleteUploadedFile';

                    await this.$wire.callSchemaComponentMethod(
                        this.schemaComponentKey,
                        method,
                        { fileKey },
                    );
                } catch (error) {
                    console.error('Delete failed:', error);
                    alert('Wystąpił błąd podczas usuwania nagrania.');

                    return;
                }
            } else {
                this.state = null;
            }

            this.resetToIdle();
        },

        resolveUploadedFileKey() {
            if (this.uploadedFileKey) {
                return this.uploadedFileKey;
            }

            const rawState = this.$wire.get(this.statePath);

            if (! rawState || typeof rawState !== 'object' || Array.isArray(rawState)) {
                return null;
            }

            const keys = Object.keys(rawState);

            return keys.length ? keys[0] : null;
        },

        resolveUploadedFileValue(fileKey) {
            const rawState = this.$wire.get(this.statePath);

            if (! rawState) {
                return null;
            }

            if (typeof rawState === 'object' && ! Array.isArray(rawState)) {
                return rawState[fileKey] ?? null;
            }

            return rawState;
        },

        isTemporaryUpload(file) {
            return typeof file === 'string' && file.startsWith('livewire-file:');
        },

        uploadRecording({ keepPlaybackVisible = false } = {}) {
            if (! this.recordedBlob) {
                return Promise.resolve();
            }

            if (keepPlaybackVisible) {
                this.isBackgroundUploading = true;
            } else {
                this.mode = 'uploading';
            }

            this.uploadProgress = 0;

            const mimeType = this.recordedMimeType || this.recordedBlob.type || 'audio/wav';
            const extension = mimeType.split(';')[0]?.split('/')[1] || 'wav';
            const file = new File([this.recordedBlob], `voice-note.${extension}`, {
                type: this.recordedBlob.type,
            });

            const fileKey = crypto.randomUUID();
            this.uploadedFileKey = fileKey;

            return new Promise((resolve, reject) => {
                this.$wire.upload(
                    `${this.statePath}.${fileKey}`,
                    file,
                    () => {
                        this.pendingUpload = false;
                        this.isBackgroundUploading = false;

                        if (! keepPlaybackVisible) {
                            this.mode = 'playback';
                        }

                        this.syncPlaybackFromRecording();

                        this.$nextTick(() => {
                            this.updateWaveformBars();
                            this.preparePlayback();
                        });

                        resolve();
                    },
                    (error) => {
                        this.isBackgroundUploading = false;
                        this.isProtectingRecording = false;
                        reject(error);
                    },
                    (progressEvent) => {
                        this.uploadProgress = progressEvent.detail.progress;
                    },
                );
            });
        },

        getStatePath() {
            // Traverse state configuration to find path
            return this.$el.closest('[x-data]').__x?.$data?.statePath || '';
        },

        // Helper to format remaining recording limit / elapsed time
        get formattedDuration() {
            return `${formatTime(this.recordingDuration)} / ${formatTime(this.maxDuration)}`;
        },

        get progressRatio() {
            const resolved = this.resolvePlaybackDuration(this.duration);

            if (! resolved) {
                return 0;
            }

            return Math.max(0, Math.min(1, this.currentTime / resolved));
        },

        get timeLabel() {
            if (this.currentTime > 0) {
                return formatTime(this.currentTime);
            }

            if (this.playbackDuration > 0) {
                return formatTime(this.playbackDuration);
            }

            if (this.duration > 0) {
                return formatTime(this.duration);
            }

            if (this.recordingDuration > 0) {
                return formatTime(this.recordingDuration);
            }

            return '0:00';
        },

        togglePlay() {
            const audio = this.$refs.audio;
            if (! audio) {
                return;
            }

            if (! this.audioSrc && this.recordedBlob) {
                if (this.localAudioUrl) {
                    URL.revokeObjectURL(this.localAudioUrl);
                }

                this.localAudioUrl = URL.createObjectURL(this.recordedBlob);
                this.hasLocalRecording = true;
            }

            const src = this.audioSrc;

            if (! src) {
                return;
            }

            if (audio.readyState === 0 || audio.getAttribute('src') !== src) {
                audio.src = src;
                audio.load();
            }

            this.syncPlaybackFromRecording();

            if (audio.paused) {
                audio.play().then(() => {
                    this.playing = true;
                }).catch((e) => {
                    console.error('Audio play failed:', e);
                    this.playing = false;
                });
            } else {
                audio.pause();
                this.playing = false;
            }
        },

        seekTo(ratio) {
            const audio = this.$refs.audio;
            const resolved = this.resolvePlaybackDuration(this.duration);

            if (! audio || ! resolved) return;

            const next = Math.max(0, Math.min(resolved, ratio * resolved));
            audio.currentTime = next;
            this.currentTime = next;
        },

        onWaveformPointerDown(event) {
            const audio = this.$refs.audio;
            if (! audio) return;

            // Resolve duration and play state if not yet set
            if (! this.canPlay && audio.readyState >= 1) {
                this.applyResolvedDuration(audio.duration);
                this.canPlay = true;
            }

            const resolved = this.resolvePlaybackDuration(this.duration);

            if (! resolved) return;

            event.preventDefault();
            this.seeking = true;
            this.seekFromPointerEvent(event);

            const onMove = (moveEvent) => {
                this.seekFromPointerEvent(moveEvent);
            };

            const onUp = () => {
                this.seeking = false;
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onUp);
                window.removeEventListener('pointercancel', onUp);
            };

            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp);
            window.addEventListener('pointercancel', onUp);
        },

        seekFromPointerEvent(event) {
            const waveform = this.$refs.waveform;
            if (! waveform) return;

            const rect = waveform.getBoundingClientRect();
            if (! rect.width) return;

            const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            this.seekTo(ratio);
        },
    }
}
