// sse.js
export function initPropertyStatusSSE() {
    console.log('[SSE] Initializing property status listener...');
    console.log('[SSE] No Error...');

    const evtSource = new EventSource('/odl_mini_projects/zambezi-mini-project/sse/status-stream.php');

    evtSource.addEventListener('statusUpdate', e => {
        console.log('[SSE] Received update:', e.data);

        const { id, status } = JSON.parse(e.data);
        const el = document.querySelector(`.status[data-property-id="${id}"]`);

        if (!el) {
            console.warn(`[SSE] Property card with ID ${id} not found.`);
            return;
        }

        // Skip if status hasn’t changed
        if (el.dataset.status === status) {
            console.log(`[SSE] Status unchanged for property ID ${id}: ${status}`);
            return;
        }

        console.log(`[SSE] Updating status for property ID ${id} to "${status}"`);

        // Update text
        el.querySelector('strong').textContent = status;
        el.dataset.status = status;

        // Update color classes
        el.classList.remove('text-success', 'text-warning', 'text-danger');
        if (status === 'available') el.classList.add('text-success');
        else if (status === 'pending') el.classList.add('text-warning');
        else el.classList.add('text-danger');
    });

    evtSource.onerror = () => console.error('[SSE] Connection error');
}
