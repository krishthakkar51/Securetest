// public/js/proctoring_tracker.js
// Shared Proctoring & Eye Gaze Tracker Module (SecureTest)

class EyeGazeTracker {
    constructor(options = {}) {
        this.onEvent = options.onEvent || (() => {});
        this.onStatusChange = options.onStatusChange || (() => {});
        this.now = options.now || (() => Date.now());

        // Threshold configurations
        this.GAZE_DURATION_THRESHOLD = 2000;          // 2.0s continuous
        this.GAZE_SAFEGUARD_THRESHOLD = 6000;         // 6.0s continuous for optional 2nd safeguard screenshot
        this.EYES_UNDETECTED_THRESHOLD = 3000;        // > 3.0s continuous
        this.EYES_UNDETECTED_SAFEGUARD = 7000;        // 7.0s continuous for optional 2nd safeguard screenshot
        this.EYES_COVERED_THRESHOLD = 3000;           // > 3.0s continuous
        this.EYES_COVERED_SAFEGUARD = 7000;           // 7.0s continuous for optional 2nd safeguard screenshot
        this.NORMAL_STABILITY_REQUIRED = 1500;        // 1.5s normal stability to confirm new incident eligibility
        this.NORMAL_BLINK_MAX_DURATION = 500;         // < 500ms normal blink window
        this.MAX_SCREENSHOTS_PER_INCIDENT = 2;        // Never exceed 2 per continuous incident

        // Gaze boundary definitions
        this.CENTER_X_MIN = 0.30;
        this.CENTER_X_MAX = 0.70;
        this.CENTER_Y_MIN = 0.22;
        this.CENTER_Y_MAX = 0.80;

        // Active detection timers (reset IMMEDIATELY when abnormal condition ends)
        this.gazeTimerStart = null;
        this.gazeLockedDirection = null;

        this.eyesUndetectedTimerStart = null;
        this.eyesCoveredTimerStart = null;

        this.blinkTimerStart = null;

        // Incident screenshot counters (max 2 per continuous incident)
        this.gazeIncidentScreenshotCount = 0;
        this.eyesUndetectedScreenshotCount = 0;
        this.eyesCoveredScreenshotCount = 0;

        // Normal-stability tracking (for confirming eligibility for a genuinely new incident)
        this.gazeNormalStartTime = null;
        this.eyesNormalStartTime = null;

        // Status tracking
        this.currentStatus = 'Centered';
    }

    classifyGazeDirection(avgGazeX, avgGazeY) {
        let hDev = 0;
        let hDir = null;
        if (avgGazeX < this.CENTER_X_MIN) {
            hDev = (this.CENTER_X_MIN - avgGazeX) / this.CENTER_X_MIN;
            hDir = 'left';
        } else if (avgGazeX > this.CENTER_X_MAX) {
            hDev = (avgGazeX - this.CENTER_X_MAX) / 0.30;
            hDir = 'right';
        }

        let vDev = 0;
        let vDir = null;
        if (avgGazeY < this.CENTER_Y_MIN) {
            vDev = (this.CENTER_Y_MIN - avgGazeY) / this.CENTER_Y_MIN;
            vDir = 'up';
        } else if (avgGazeY > this.CENTER_Y_MAX) {
            vDev = (avgGazeY - this.CENTER_Y_MAX) / 0.20;
            vDir = 'down';
        }

        if (hDev === 0 && vDev === 0) {
            return null;
        }

        if (hDev >= vDev) {
            return hDir;
        } else {
            return vDir;
        }
    }

    processFrame(input) {
        const currentTime = this.now();

        if (!input.faceDetected) {
            this.resetAllTimers();
            return;
        }

        // 1. BLINK DURATION TRACKING (Rule 4)
        if (input.landmarksPresent && input.isEyeClosed) {
            if (this.blinkTimerStart === null) {
                this.blinkTimerStart = currentTime;
            }
            const blinkDuration = currentTime - this.blinkTimerStart;

            if (blinkDuration < this.NORMAL_BLINK_MAX_DURATION) {
                // NORMAL BLINK: under 500ms - must never trigger screenshots or gaze/occlusion timers
                this.gazeTimerStart = null;
                this.gazeLockedDirection = null;
                this.eyesUndetectedTimerStart = null;
                this.eyesCoveredTimerStart = null;

                this.setStatus('Blinking');
                return;
            }
            // If blinkDuration >= 500ms, transitions out of normal blink to eye closure
        } else {
            this.blinkTimerStart = null;
        }

        // 2. EYE VISIBILITY & OCCLUSION (Rules 2 & 3)
        const eyesAreVisibleAndOpen = input.landmarksPresent && !input.isEyeClosed && input.avgGazeX !== null && input.avgGazeY !== null;

        if (!eyesAreVisibleAndOpen) {
            this.gazeTimerStart = null;
            this.gazeLockedDirection = null;

            if (input.handOverEyesDetected) {
                // RULE 3: Verified Hand / Object Occlusion
                this.eyesUndetectedTimerStart = null;

                if (this.eyesCoveredTimerStart === null) {
                    this.eyesCoveredTimerStart = currentTime;
                }

                const coveredDuration = currentTime - this.eyesCoveredTimerStart;

                if (coveredDuration > this.EYES_COVERED_THRESHOLD) {
                    if (this.eyesCoveredScreenshotCount === 0) {
                        this.eyesCoveredScreenshotCount = 1;
                        this.onEvent('eyes_covered_object', 'Both eyes covered by object for more than 3 seconds.');
                    } else if (this.eyesCoveredScreenshotCount === 1 && coveredDuration >= this.EYES_COVERED_SAFEGUARD) {
                        this.eyesCoveredScreenshotCount = 2;
                        this.onEvent('eyes_covered_object', 'Both eyes covered by object (safeguard confirmation).');
                    }
                }
                this.setStatus('Eyes Covered');
            } else {
                // RULE 2: Face visible, but eyes undetected (closure or landmark failure)
                this.eyesCoveredTimerStart = null;

                if (this.eyesUndetectedTimerStart === null) {
                    this.eyesUndetectedTimerStart = currentTime;
                }

                const undetectedDuration = currentTime - this.eyesUndetectedTimerStart;

                if (undetectedDuration > this.EYES_UNDETECTED_THRESHOLD) {
                    if (this.eyesUndetectedScreenshotCount === 0) {
                        this.eyesUndetectedScreenshotCount = 1;
                        this.onEvent('eyes_not_detected', 'Face visible but both eyes undetected for more than 3 seconds.');
                    } else if (this.eyesUndetectedScreenshotCount === 1 && undetectedDuration >= this.EYES_UNDETECTED_SAFEGUARD) {
                        this.eyesUndetectedScreenshotCount = 2;
                        this.onEvent('eyes_not_detected', 'Face visible but both eyes undetected (safeguard confirmation).');
                    }
                }
                this.setStatus('Eyes Undetected');
            }

            this.eyesNormalStartTime = null;
            return;
        }

        // Eyes are detected and open: reset undetected & covered timers IMMEDIATELY
        this.eyesUndetectedTimerStart = null;
        this.eyesCoveredTimerStart = null;

        if (this.eyesNormalStartTime === null) {
            this.eyesNormalStartTime = currentTime;
        } else if (currentTime - this.eyesNormalStartTime >= this.NORMAL_STABILITY_REQUIRED) {
            this.eyesUndetectedScreenshotCount = 0;
            this.eyesCoveredScreenshotCount = 0;
        }

        // 3. SUSTAINED DIRECTIONAL GAZE (Rule 1)
        const direction = this.classifyGazeDirection(input.avgGazeX, input.avgGazeY);

        if (direction !== null) {
            this.gazeNormalStartTime = null;

            if (this.gazeTimerStart === null) {
                this.gazeTimerStart = currentTime;
                this.gazeLockedDirection = direction;
            }

            const gazeDuration = currentTime - this.gazeTimerStart;

            if (gazeDuration >= this.GAZE_DURATION_THRESHOLD) {
                const eventType = `gaze_away_${this.gazeLockedDirection}`;
                const dirName = this.gazeLockedDirection.toUpperCase();

                if (this.gazeIncidentScreenshotCount === 0) {
                    this.gazeIncidentScreenshotCount = 1;
                    this.onEvent(eventType, `Sustained eye gaze away from screen (${dirName}) for 2+ seconds.`);
                } else if (this.gazeIncidentScreenshotCount === 1 && gazeDuration >= this.GAZE_SAFEGUARD_THRESHOLD) {
                    this.gazeIncidentScreenshotCount = 2;
                    this.onEvent(eventType, `Sustained eye gaze away from screen (${dirName}) (safeguard confirmation).`);
                }
            }

            this.setStatus(`Gaze: ${this.gazeLockedDirection.charAt(0).toUpperCase() + this.gazeLockedDirection.slice(1)}`);
        } else {
            // Centered
            this.gazeTimerStart = null;
            this.gazeLockedDirection = null;

            if (this.gazeNormalStartTime === null) {
                this.gazeNormalStartTime = currentTime;
            } else if (currentTime - this.gazeNormalStartTime >= this.NORMAL_STABILITY_REQUIRED) {
                this.gazeIncidentScreenshotCount = 0;
            }

            this.setStatus('Centered');
        }
    }

    resetAllTimers() {
        this.gazeTimerStart = null;
        this.gazeLockedDirection = null;
        this.eyesUndetectedTimerStart = null;
        this.eyesCoveredTimerStart = null;
        this.blinkTimerStart = null;
        this.gazeNormalStartTime = null;
        this.eyesNormalStartTime = null;
    }

    setStatus(status) {
        if (this.currentStatus !== status) {
            this.currentStatus = status;
            this.onStatusChange(status);
        }
    }
}

if (typeof window !== 'undefined') {
    window.EyeGazeTracker = EyeGazeTracker;
}
if (typeof globalThis !== 'undefined') {
    globalThis.EyeGazeTracker = EyeGazeTracker;
}

export { EyeGazeTracker };
export default EyeGazeTracker;
