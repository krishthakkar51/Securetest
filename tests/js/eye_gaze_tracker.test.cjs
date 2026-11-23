// tests/js/eye_gaze_tracker.test.cjs
// CommonJS automated tests for EyeGazeTracker proctoring rules

const assert = require('assert');

async function runAllTests() {
    console.log('Running EyeGazeTracker unit tests (CJS)...');
    const { EyeGazeTracker } = await import('../../public/js/proctoring_tracker.js');
    let mockTime = 10000;
    const events = [];
    const tracker = new EyeGazeTracker({
        now: () => mockTime,
        onEvent: (type, desc) => events.push({ type, desc, time: mockTime })
    });

    // Test 1: Normal center gaze produces Centered status and 0 events
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Centered');
    assert.strictEqual(events.length, 0);

    // Test 2: Gaze away Left at 1900ms -> no event; reaches 2000ms -> triggers 1st screenshot
    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Gaze: Left');
    assert.strictEqual(events.length, 0);

    mockTime += 1900; // Total 1900ms looking left
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 0, 'No event before 2.0s');

    mockTime += 100; // Total 2000ms looking left
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 1);
    assert.strictEqual(events[0].type, 'gaze_away_left');

    // Test 3: Same continuous incident continuing past safeguard threshold (6.0s continuous)
    // Allows at most a 2nd safeguard screenshot, but never a 3rd
    mockTime += 3000; // Total 5000ms
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 1, 'Still 1 screenshot before safeguard threshold');

    mockTime += 1000; // Total 6000ms continuous looking left
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 2, '2nd safeguard screenshot allowed for prolonged incident');
    assert.strictEqual(events[1].type, 'gaze_away_left');

    // Even if it persists for another 20 seconds, no 3rd screenshot is EVER allowed for the same continuous incident
    mockTime += 20000;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.15, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 2, 'Never exceed 2 screenshots for the same continuous incident');

    // Test 4: Gaze returns to center -> resets active timer IMMEDIATELY
    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.gazeTimerStart, null, 'Active gaze timer reset immediately');
    assert.strictEqual(tracker.currentStatus, 'Centered');

    // Test 5: New independent incident can use remaining attempt quota after 1.5s normal stability
    mockTime += 1600; // 1.6s normal stability
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.gazeIncidentScreenshotCount, 0, 'Incident count reset after 1.5s normal stability');

    // Start 2nd independent incident: Looking Right for 2.0s
    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.85, avgGazeY: 0.50, handOverEyesDetected: false });
    mockTime += 2000;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.85, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(events.length, 3, 'New independent incident captures screenshot');
    assert.strictEqual(events[2].type, 'gaze_away_right');

    // Return to center
    mockTime += 1600;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });

    // Test 6: Normal Blink under 500ms does NOT trigger any alert
    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: true, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Blinking');
    mockTime += 300; // 300ms blink
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: true, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Blinking');
    assert.strictEqual(events.length, 3, 'No event triggered during normal blink');

    // Eyes open after blink
    mockTime += 50;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Centered');

    // Test 7: Eyes undetected for > 3.0s (3100ms) without hand -> triggers eyes_not_detected
    mockTime += 1600; // Stabilize
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });

    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: false });
    assert.strictEqual(tracker.currentStatus, 'Eyes Undetected');

    mockTime += 2800; // Total 2800ms
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: false });
    assert.strictEqual(events.length, 3, 'No event before 3.0s');

    mockTime += 300; // Total 3100ms (> 3000ms)
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: false });
    assert.strictEqual(events.length, 4);
    assert.strictEqual(events[3].type, 'eyes_not_detected');

    // Safeguard 2nd screenshot for continuous undetected eyes at 7000ms
    mockTime += 4000; // Total 7100ms
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: false });
    assert.strictEqual(events.length, 5);
    assert.strictEqual(events[4].type, 'eyes_not_detected');

    // Further persistence capped at 2 screenshots
    mockTime += 5000;
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: false });
    assert.strictEqual(events.length, 5, 'Max 2 screenshots for undetected eyes incident');

    // Return to visible eyes
    mockTime += 1600;
    tracker.processFrame({ faceDetected: true, landmarksPresent: true, isEyeClosed: false, avgGazeX: 0.50, avgGazeY: 0.50, handOverEyesDetected: false });

    // Test 8: Both eyes covered by hand for > 3.0s -> triggers eyes_covered_object
    mockTime += 100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: true });
    assert.strictEqual(tracker.currentStatus, 'Eyes Covered');

    mockTime += 3100;
    tracker.processFrame({ faceDetected: true, landmarksPresent: false, isEyeClosed: false, avgGazeX: null, avgGazeY: null, handOverEyesDetected: true });
    assert.strictEqual(events.length, 6);
    assert.strictEqual(events[5].type, 'eyes_covered_object');

    // Test 9: Diagonal priority normalization
    assert.strictEqual(tracker.classifyGazeDirection(0.10, 0.18), 'left');
    assert.strictEqual(tracker.classifyGazeDirection(0.28, 0.95), 'down');
    assert.strictEqual(tracker.classifyGazeDirection(0.90, 0.10), 'right');
    assert.strictEqual(tracker.classifyGazeDirection(0.75, 0.05), 'up');

    console.log('ALL EYE GAZE TRACKER TESTS PASSED SUCCESSFULLY!');
}

runAllTests();
