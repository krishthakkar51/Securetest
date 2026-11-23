import './bootstrap';

import Alpine from 'alpinejs';

import {
    FaceDetector,
     FaceLandmarker,
    FilesetResolver
} from '@mediapipe/tasks-vision';

window.Alpine = Alpine;

Alpine.start();

window.FaceDetector = FaceDetector;
window.FilesetResolver = FilesetResolver;

import { EyeGazeTracker } from '../../public/js/proctoring_tracker.js';
window.EyeGazeTracker = EyeGazeTracker;


// ========================================
// MediaPipe Face Detector Initialization
// ========================================

let faceDetector = null;
let faceLandmarker = null;

async function initializeFaceDetector() {

    try {

        console.log('Initializing MediaPipe Face Detector...');

        const vision = await FilesetResolver.forVisionTasks(
            'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.0.1/wasm'
        );

        faceDetector = await FaceDetector.createFromOptions(
            vision,
            {
                baseOptions: {
                    modelAssetPath: '/models/blaze_face_short_range.tflite',
                    delegate: 'CPU'
                },

                runningMode: 'VIDEO',

                minDetectionConfidence: 0.5,

                minSuppressionThreshold: 0.3
            }
        );

        window.faceDetector = faceDetector;

        console.log(
            'MediaPipe Face Detector initialized successfully.'
        );

    } catch (error) {

        console.error(
            'MediaPipe Face Detector initialization failed:',
            error
        );

    }
}

initializeFaceDetector();

// ========================================
// MediaPipe Face Landmarker Initialization
// ========================================

async function initializeFaceLandmarker() {

    try {

        console.log(
            'Initializing MediaPipe Face Landmarker...'
        );

        const vision =
            await FilesetResolver.forVisionTasks(
                'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.0.1/wasm'
            );

        faceLandmarker =
            await FaceLandmarker.createFromOptions(
                vision,
                {

                    baseOptions: {
                        modelAssetPath:
                            '/models/face_landmarker.task',

                        delegate: 'CPU'
                    },

                    runningMode: 'VIDEO',

                    numFaces: 1,

                    minFaceDetectionConfidence: 0.5,

                    minFacePresenceConfidence: 0.5,

                    minTrackingConfidence: 0.5,

                    outputFaceBlendshapes: false,

                    outputFacialTransformationMatrixes: true

                }
            );

        window.faceLandmarker =
            faceLandmarker;

        console.log(
            'MediaPipe Face Landmarker initialized successfully.'
        );

    } catch (error) {

        console.error(
            'MediaPipe Face Landmarker initialization failed:',
            error
        );

    }

}

initializeFaceLandmarker();

// ========================================
// Session Heartbeat (Authenticated Users Only)
// ========================================

const authCheckMeta = document.querySelector('meta[name="auth-check"]');
const isAuthenticated = authCheckMeta ? authCheckMeta.getAttribute('content') === '1' : false;

// Do not ping heartbeat on guest auth pages (register, login, password reset, etc.)
const isGuestAuthPage = ['/login', '/register', '/forgot-password', '/reset-password'].some(
    path => window.location.pathname.startsWith(path)
);

if (isAuthenticated && !isGuestAuthPage) {
    setInterval(async () => {
        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            if (!csrfToken) {
                console.warn('CSRF token not found.');
                return;
            }

            const response = await fetch('/heartbeat', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401 && !window.location.pathname.startsWith('/login')) {
                window.location.href = '/login';
            }

        } catch (error) {
            console.warn('Heartbeat failed:', error);
        }
    }, 10000);
}