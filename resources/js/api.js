// Fetch wrapper for the /api endpoints: session cookie, CSRF token, JSON in and out, readable errors.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export class ApiError extends Error {
    constructor(message, status, body) {
        super(message);
        this.status = status;
        this.body = body;
    }
}

function errorMessage(status, body) {
    if (body?.errors) return Object.values(body.errors).flat()[0];
    if (body?.message && status !== 500) return body.message;
    if (status === 413) return 'The request is too large for the server.';
    if (status === 419) return 'Your session expired. Reload the page and try again.';
    if (status === 403) return 'You are not allowed to do this.';

    return `The server could not complete the request (HTTP ${status}).`;
}

export async function apiFetch(url, { method = 'GET', json } = {}) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(json !== undefined ? { 'Content-Type': 'application/json' } : {}),
        },
        body: json !== undefined ? JSON.stringify(json) : undefined,
    });
    const body = await response.json().catch(() => null);
    if (!response.ok) throw new ApiError(errorMessage(response.status, body), response.status, body);

    return body;
}

/** POST multipart form data with upload progress (fetch has no upload progress events). */
export function uploadWithProgress(url, formData, onProgress) {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        request.open('POST', url);
        request.withCredentials = true;
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) onProgress(event.loaded / event.total);
        });
        request.addEventListener('load', () => {
            let body = null;
            try {
                body = JSON.parse(request.responseText);
            } catch {
                body = null;
            }
            if (request.status >= 200 && request.status < 300) resolve(body);
            else reject(new ApiError(errorMessage(request.status, body), request.status, body));
        });
        request.addEventListener('error', () => reject(new ApiError('The connection was interrupted.', 0, null)));
        request.send(formData);
    });
}
