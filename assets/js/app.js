(function () {
    const uploadForm = document.getElementById('upload-form');
    const feedback = document.getElementById('upload-feedback');
    const sessionData = window.WEBVIEW_SESSION || null;

    const formatElapsed = (seconds) => {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
        const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
        return hrs > 0 ? `${String(hrs).padStart(2, '0')}:${mins}:${secs}` : `${mins}:${secs}`;
    };

    const setFeedback = (message, state) => {
        if (!feedback) {
            return;
        }
        feedback.className = `feedback${state ? ` ${state}` : ''}`;
        feedback.innerHTML = message;
    };

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            setFeedback('Preparing secure session and uploading file...', '');

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: new FormData(uploadForm),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to create session.');
                }

                setFeedback(`
                    <strong>${data.session.title} is ready.</strong><br>
                    Join code: <code>${data.session.join_code}</code><br>
                    ${data.session.password_required ? '<span class="feedback-tag">Viewer password enabled</span><br>' : ''}
                    <a href="${data.session.presenter_url}">Open presenter suite</a><br>
                    <a href="${data.session.viewer_url}" target="_blank" rel="noopener">Open viewer entry</a>
                `, 'success');
                uploadForm.reset();
            } catch (error) {
                setFeedback(error.message, 'error');
            }
        });
    }

    if (!sessionData) {
        return;
    }

    const bodyRole = document.body.dataset.role;
    const isPresenter = bodyRole === 'presenter';
    const isViewer = bodyRole === 'viewer';
    let currentState = Object.assign({}, sessionData.state);
    let analytics = Object.assign({}, sessionData.analytics || {});
    let timerId = null;

    const stage = document.getElementById('presentation-stage');
    const connectionIndicator = document.getElementById(isPresenter ? 'connection-indicator' : 'viewer-connection-indicator');
    const highlightInput = document.getElementById('highlight-message');
    const viewerHighlight = document.getElementById('viewer-highlight-message');

    const setConnectionState = (online) => {
        if (!connectionIndicator) {
            return;
        }
        connectionIndicator.textContent = online ? (isPresenter ? 'Live' : 'Synced') : 'Retrying';
        connectionIndicator.classList.toggle('online', online);
        connectionIndicator.classList.toggle('offline', !online);
    };

    const applyFocusMode = () => {
        document.body.classList.toggle('focus-mode', Boolean(currentState.focus_mode));
    };

    const updateUi = () => {
        const slideDisplay = document.getElementById(isPresenter ? 'slide-display' : 'viewer-slide-display');
        const statusDisplay = document.getElementById(isPresenter ? 'status-display' : 'viewer-status-display');
        const elapsedDisplay = document.getElementById(isPresenter ? 'elapsed-display' : 'viewer-elapsed-display');
        const viewerCountDisplay = document.getElementById('viewer-count-display');

        if (slideDisplay) slideDisplay.textContent = currentState.current_slide;
        if (statusDisplay) statusDisplay.textContent = currentState.status.charAt(0).toUpperCase() + currentState.status.slice(1);
        if (elapsedDisplay) elapsedDisplay.textContent = formatElapsed(currentState.elapsed_seconds || 0);
        if (viewerCountDisplay) viewerCountDisplay.textContent = analytics.viewer_count || 0;
        if (highlightInput) highlightInput.value = currentState.highlight_message || '';
        if (viewerHighlight) viewerHighlight.textContent = currentState.highlight_message || 'Live presentation';
        applyFocusMode();
    };

    const syncRemoteState = async (method, payload) => {
        const options = { method };
        if (payload) {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(payload);
        }

        const url = method === 'GET'
            ? `api/sync_state.php?session=${encodeURIComponent(sessionData.sessionId)}`
            : 'api/sync_state.php';

        const response = await fetch(url, options);
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'State sync failed.');
        }

        currentState = data.session.state;
        analytics = data.session.analytics || analytics;
        updateUi();
        setConnectionState(true);
        return data.session;
    };

    const sendState = async () => {
        await syncRemoteState('POST', {
            session_id: sessionData.sessionId,
            presenter_token: sessionData.presenterToken,
            state: currentState,
        });
    };

    const startTimer = () => {
        if (timerId) {
            clearInterval(timerId);
        }
        timerId = setInterval(() => {
            if (currentState.status === 'playing') {
                currentState.elapsed_seconds += 1;
                updateUi();
            }
        }, 1000);
    };

    document.getElementById('toggle-fullscreen')?.addEventListener('click', async () => {
        if (!stage) {
            return;
        }

        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await stage.requestFullscreen();
        }
    });

    if (isPresenter) {
        startTimer();
        document.getElementById('next-slide')?.addEventListener('click', async () => {
            currentState.current_slide += 1;
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('prev-slide')?.addEventListener('click', async () => {
            currentState.current_slide = Math.max(1, currentState.current_slide - 1);
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('start-presentation')?.addEventListener('click', async () => {
            currentState.status = 'playing';
            currentState.highlight_message = highlightInput?.value || currentState.highlight_message || 'Presentation live';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('pause-presentation')?.addEventListener('click', async () => {
            currentState.status = 'paused';
            currentState.highlight_message = highlightInput?.value || 'Presentation paused';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('stop-presentation')?.addEventListener('click', async () => {
            currentState.status = 'stopped';
            currentState.elapsed_seconds = 0;
            currentState.current_slide = 1;
            currentState.highlight_message = 'Waiting to begin';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('refresh-state')?.addEventListener('click', async () => {
            try {
                await syncRemoteState('GET');
            } catch (error) {
                setConnectionState(false);
            }
        });

        document.getElementById('toggle-focus')?.addEventListener('click', async () => {
            currentState.focus_mode = !currentState.focus_mode;
            currentState.timestamp = Math.floor(Date.now() / 1000);
            currentState.highlight_message = highlightInput?.value || currentState.highlight_message;
            await sendState();
        });

        highlightInput?.addEventListener('change', async () => {
            currentState.highlight_message = highlightInput.value || 'Live update';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            await sendState();
        });

        document.getElementById('copy-viewer-link')?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(sessionData.viewerUrl);
                setConnectionState(true);
            } catch (error) {
                console.error(error);
            }
        });
    }

    if (isViewer) {
        updateUi();
        setInterval(async () => {
            try {
                await syncRemoteState('GET');
            } catch (error) {
                console.error(error);
                setConnectionState(false);
            }
        }, sessionData.pollInterval || 1500);
    }

    updateUi();
})();
