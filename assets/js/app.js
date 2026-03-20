(function () {
    const uploadForm = document.getElementById('upload-form');
    const feedback = document.getElementById('upload-feedback');
    const sessionData = window.WEBVIEW_SESSION || null;

    const formatElapsed = (seconds) => {
        const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
        const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
        return `${minutes}:${secs}`;
    };

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            feedback.textContent = 'Uploading file and creating session...';
            feedback.className = 'feedback';

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: new FormData(uploadForm),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to create session.');
                }

                feedback.className = 'feedback success';
                feedback.innerHTML = `
                    <strong>Session ready.</strong><br>
                    Join code: <code>${data.session.join_code}</code><br>
                    <a href="${data.session.presenter_url}">Open presenter view</a><br>
                    <a href="${data.session.viewer_url}" target="_blank" rel="noopener">Open viewer link</a>
                `;
                uploadForm.reset();
            } catch (error) {
                feedback.className = 'feedback error';
                feedback.textContent = error.message;
            }
        });
    }

    if (!sessionData) {
        return;
    }

    const isPresenter = document.body.dataset.role === 'presenter';
    const isViewer = document.body.dataset.role === 'viewer';
    let currentState = Object.assign({}, sessionData.state);
    let timerId = null;

    const updateUi = () => {
        const slideDisplay = document.getElementById(isPresenter ? 'slide-display' : 'viewer-slide-display');
        const statusDisplay = document.getElementById(isPresenter ? 'status-display' : 'viewer-status-display');
        const elapsedDisplay = document.getElementById(isPresenter ? 'elapsed-display' : 'viewer-elapsed-display');

        if (slideDisplay) slideDisplay.textContent = currentState.current_slide;
        if (statusDisplay) statusDisplay.textContent = currentState.status.charAt(0).toUpperCase() + currentState.status.slice(1);
        if (elapsedDisplay) elapsedDisplay.textContent = formatElapsed(currentState.elapsed_seconds || 0);
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
        updateUi();
        return data.session;
    };

    const sendState = async () => {
        await syncRemoteState('POST', {
            session_id: sessionData.sessionId,
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

    if (isPresenter) {
        startTimer();
        document.getElementById('next-slide')?.addEventListener('click', async () => {
            currentState.current_slide += 1;
            currentState.timestamp = Math.floor(Date.now() / 1000);
            updateUi();
            await sendState();
        });

        document.getElementById('prev-slide')?.addEventListener('click', async () => {
            currentState.current_slide = Math.max(1, currentState.current_slide - 1);
            currentState.timestamp = Math.floor(Date.now() / 1000);
            updateUi();
            await sendState();
        });

        document.getElementById('start-presentation')?.addEventListener('click', async () => {
            currentState.status = 'playing';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            updateUi();
            await sendState();
        });

        document.getElementById('pause-presentation')?.addEventListener('click', async () => {
            currentState.status = 'paused';
            currentState.timestamp = Math.floor(Date.now() / 1000);
            updateUi();
            await sendState();
        });

        document.getElementById('stop-presentation')?.addEventListener('click', async () => {
            currentState.status = 'stopped';
            currentState.elapsed_seconds = 0;
            currentState.current_slide = 1;
            currentState.timestamp = Math.floor(Date.now() / 1000);
            updateUi();
            await sendState();
        });

        document.getElementById('refresh-state')?.addEventListener('click', async () => {
            await syncRemoteState('GET');
        });
    }

    if (isViewer) {
        updateUi();
        setInterval(async () => {
            try {
                await syncRemoteState('GET');
            } catch (error) {
                console.error(error);
            }
        }, sessionData.pollInterval || 1500);
    }

    updateUi();
})();
