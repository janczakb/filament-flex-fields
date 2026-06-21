export function isMobileCameraDevice() {
    if (typeof navigator === 'undefined') {
        return false
    }

    const userAgent = navigator.userAgent ?? ''

    return /Android|iPhone|iPad|iPod/i.test(userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
}

export function isIosDevice() {
    if (typeof navigator === 'undefined') {
        return false
    }

    const userAgent = navigator.userAgent ?? ''

    return /iPhone|iPad|iPod/i.test(userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
}

function buildVideoConstraints(cameraFacing, deviceId = null) {
    if (deviceId) {
        return [
            {
                video: {
                    deviceId: { exact: deviceId },
                    width: { ideal: isIosDevice() ? 640 : 1280 },
                    height: { ideal: isIosDevice() ? 480 : 720 },
                },
                audio: false,
            },
            {
                video: {
                    deviceId: { ideal: deviceId },
                    width: { ideal: isIosDevice() ? 640 : 1280 },
                    height: { ideal: isIosDevice() ? 480 : 720 },
                },
                audio: false,
            },
            {
                video: {
                    deviceId: { ideal: deviceId },
                },
                audio: false,
            },
        ]
    }

    const mobileConstraints = isIosDevice()
        ? [
            {
                video: {
                    facingMode: { exact: cameraFacing },
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                },
                audio: false,
            },
            {
                video: {
                    facingMode: { ideal: cameraFacing },
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                },
                audio: false,
            },
            {
                video: {
                    facingMode: cameraFacing,
                },
                audio: false,
            },
        ]
        : []

    return [
        ...mobileConstraints,
        {
            video: {
                facingMode: { ideal: cameraFacing },
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        },
        {
            video: {
                facingMode: cameraFacing,
            },
            audio: false,
        },
        {
            video: true,
            audio: false,
        },
    ]
}

export function prepareVideoElement(videoElement) {
    if (! videoElement) {
        return
    }

    videoElement.setAttribute('playsinline', 'true')
    videoElement.setAttribute('webkit-playsinline', 'true')
    videoElement.setAttribute('autoplay', 'true')
    videoElement.playsInline = true
    videoElement.muted = true
    videoElement.autoplay = true
}

export function stopMediaStreamTracks(stream) {
    if (stream && typeof stream.getTracks === 'function') {
        stream.getTracks().forEach((track) => track.stop())
    }
}

async function ensureVideoPlayback(videoElement) {
    prepareVideoElement(videoElement)

    if (videoElement.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && ! videoElement.paused) {
        return
    }

    try {
        await videoElement.play()
    } catch {
        await new Promise((resolve) => {
            const finish = () => {
                videoElement.removeEventListener('loadedmetadata', finish)
                videoElement.removeEventListener('canplay', finish)
                videoElement.removeEventListener('playing', finish)
                resolve()
            }

            videoElement.addEventListener('loadedmetadata', finish, { once: true })
            videoElement.addEventListener('canplay', finish, { once: true })
            videoElement.addEventListener('playing', finish, { once: true })
            window.setTimeout(finish, 1500)
        })

        await videoElement.play()
    }
}

export async function waitForVideoFrames(videoElement, maxAttempts = 60) {
    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        if (videoElement.videoWidth > 0 && videoElement.videoHeight > 0) {
            return true
        }

        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })
    }

    return false
}

export async function reviveVideoPlayback(videoElement) {
    if (! videoElement) {
        return false
    }

    prepareVideoElement(videoElement)

    const stream = videoElement.srcObject

    if (isIosDevice() && stream) {
        videoElement.srcObject = null
        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })
        videoElement.srcObject = stream
    }

    await ensureVideoPlayback(videoElement)

    return waitForVideoFrames(videoElement)
}

export async function acquireCameraStream(cameraFacing = 'environment', deviceId = null) {
    if (typeof navigator?.mediaDevices?.getUserMedia !== 'function') {
        throw new Error('Camera unavailable')
    }

    const constraintAttempts = buildVideoConstraints(cameraFacing, deviceId)
    let lastError = null

    for (const constraints of constraintAttempts) {
        try {
            return await navigator.mediaDevices.getUserMedia(constraints)
        } catch (error) {
            lastError = error
        }
    }

    throw lastError ?? new Error('Camera unavailable')
}

export async function attachStreamToVideoElement(videoElement, stream) {
    prepareVideoElement(videoElement)
    videoElement.srcObject = stream
    await ensureVideoPlayback(videoElement)
    await waitForVideoFrames(videoElement)
    await reviveVideoPlayback(videoElement)

    return stream
}

export async function attachCameraStream(videoElement, cameraFacing, deviceId = null, preAcquiredStream = null) {
    if (preAcquiredStream) {
        return attachStreamToVideoElement(videoElement, preAcquiredStream)
    }

    const stream = await acquireCameraStream(cameraFacing, deviceId)

    return attachStreamToVideoElement(videoElement, stream)
}
